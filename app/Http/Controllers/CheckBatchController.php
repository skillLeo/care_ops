<?php

namespace App\Http\Controllers;

use App\Models\Check;
use App\Models\Claim;
use App\Models\ClaimLineOfService;
use App\Models\Client;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class CheckBatchController extends Controller
{
    public function show(Check $check)
    {
        if (! $check->open) {
            abort(403, 'Check is closed.');
        }

        return view('checks.batch', compact('check'));
    }

    public function scan(Request $request, Check $check)
    {
        if (! $check->open) {
            abort(403, 'Check is closed.');
        }

        $request->validate([
            'statement' => 'nullable|file|mimes:pdf|max:10240',
            'attachment' => 'nullable|string',
        ]);

        Log::info('Batch scan requested.', [
            'check_id' => $check->id,
            'check_number' => $check->check_number,
            'attachment' => $request->input('attachment'),
            'has_upload' => $request->hasFile('statement'),
        ]);

        $disk = Storage::disk('local');
        $path = null;
        $fullPath = null;
        $scriptPath = base_path('scripts/check_scan.py');

        $attachmentName = $request->input('attachment');
        if ($attachmentName) {
            $attachments = $check->attachments ?? [];
            if (! in_array($attachmentName, $attachments, true)) {
                Log::warning('Batch scan attachment not found on check.', [
                    'check_id' => $check->id,
                    'attachment' => $attachmentName,
                ]);
                return response()->json([
                    'message' => 'Attachment not found on this check.',
                ], 422);
            }

            $attachmentPath = 'attachments/checks/' . $attachmentName;
            if (! $disk->exists($attachmentPath)) {
                Log::warning('Batch scan attachment missing on disk.', [
                    'check_id' => $check->id,
                    'attachment' => $attachmentName,
                    'path' => $attachmentPath,
                ]);
                return response()->json([
                    'message' => 'Unable to locate the attachment on disk.',
                ], 422);
            }

            $fullPath = $disk->path($attachmentPath);
        } else {
            if (! $request->hasFile('statement')) {
                Log::warning('Batch scan missing upload and attachment selection.', [
                    'check_id' => $check->id,
                ]);
                return response()->json([
                    'message' => 'Please select a PDF file or attachment to scan.',
                ], 422);
            }

            $path = $request->file('statement')->store('tmp', 'local');
            $fullPath = $disk->path($path);

            if (! $disk->exists($path)) {
                Log::warning('Batch scan uploaded statement missing on disk.', [
                    'check_id' => $check->id,
                    'path' => $path,
                ]);
                return response()->json([
                    'message' => 'Unable to locate the uploaded statement on disk.',
                ], 422);
            }
        }

        if (! file_exists($scriptPath)) {
            Log::error('Batch scan script missing.', [
                'check_id' => $check->id,
                'script_path' => $scriptPath,
            ]);
            if ($path) {
                $disk->delete($path);
            }
            return response()->json([
                'message' => 'Scanner script is missing.',
            ], 422);
        }

        $process = $this->runScanner($scriptPath, $fullPath);

        if ($path) {
            $disk->delete($path);
        }

        if (! $process->isSuccessful()) {
            Log::error('Batch scan failed.', [
                'check_id' => $check->id,
                'script_path' => $scriptPath,
                'scan_path' => $fullPath,
                'stderr' => trim($process->getErrorOutput()),
                'stdout' => trim($process->getOutput()),
            ]);
            return response()->json([
                'message' => 'Unable to read the PDF statement.',
                'details' => trim($process->getErrorOutput()) ?: trim($process->getOutput()),
            ], 422);
        }

        $rows = json_decode(trim($process->getOutput()), true);
        if (! is_array($rows)) {
            Log::error('Batch scan returned invalid JSON.', [
                'check_id' => $check->id,
                'output' => trim($process->getOutput()),
            ]);
            return response()->json([
                'message' => 'Invalid response from the scanner.',
            ], 422);
        }

        return response()->json([
            'rows' => $rows,
            'raw_rows' => $rows,
        ]);
    }

    public function claimsForClient(Request $request, Check $check)
    {
        if (! $check->open) {
            abort(403, 'Check is closed.');
        }

        $request->validate([
            'client_id' => 'required|integer|exists:clients,id',
        ]);

        $client = Client::findOrFail($request->input('client_id'));

        return response()->json([
            'claims' => $this->buildClaimOptions($client),
        ]);
    }

    public function resolveClientClaims(Request $request, Check $check)
    {
        if (! $check->open) {
            abort(403, 'Check is closed.');
        }

        $request->validate([
            'name' => 'nullable|string',
            'mrn' => 'nullable|string',
        ]);

        $name = trim((string) $request->input('name', ''));
        $mrn = trim((string) $request->input('mrn', ''));

        if ($name === '' && $mrn === '') {
            return response()->json([
                'client' => null,
                'claims' => [],
            ]);
        }

        $client = $this->resolveClient([
            [
                'name' => $name,
                'mrn' => $mrn,
            ],
        ]);

        return response()->json([
            'client' => $client ? [
                'id' => $client->id,
                'name' => $client->last_name . ', ' . $client->first_name,
                'mrn' => $client->mrn,
            ] : null,
            'claims' => $client ? $this->buildClaimOptions($client) : [],
        ]);
    }

    public function commit(Request $request, Check $check)
    {
        if (! $check->open) {
            abort(403, 'Check is closed.');
        }

        $request->validate([
            'groups' => 'required|array',
            'groups.*.claim_id' => 'nullable|integer|exists:claims,id',
            'groups.*.lines' => 'required|array',
        ]);

        foreach ($request->input('groups') as $group) {
            $claimId = $group['claim_id'] ?? null;
            $lines = $group['lines'] ?? [];

            foreach ($lines as $line) {
                $lineId = $line['line_id'] ?? null;

                if (! $lineId) {
                    continue;
                }

                $claimLine = ClaimLineOfService::find($lineId);
                if (! $claimLine) {
                    continue;
                }

                if ($claimId && $claimLine->claim_id !== (int) $claimId) {
                    continue;
                }

                $paid = $this->toFloat($line['paid'] ?? null);
                $billed = $this->toFloat($line['billed'] ?? null);
                $denied = max(0, $billed - $paid);

                $claimLine->update([
                    'processed_amount' => $paid,
                    'denied_amount' => $denied,
                    'check_id' => $check->id,
                ]);
            }
        }

        return response()->json([
            'message' => 'Check updates applied.',
        ]);
    }

    private function buildGroups(array $rows): array
    {
        $grouped = [];

        foreach ($rows as $row) {
            $claimNumber = trim((string) ($row['claim_number'] ?? ''));
            $mrn = trim((string) ($row['mrn'] ?? ''));
            $name = trim((string) ($row['name'] ?? ''));
            $key = strtoupper($claimNumber) . '|' . strtoupper($mrn) . '|' . strtoupper($name);
            if ($key === '||') {
                $key = 'unknown';
            }
            $grouped[$key][] = $row;
        }

        $groups = [];

        foreach ($grouped as $groupKey => $lines) {
            $claimNumber = '';
            foreach ($lines as $line) {
                $lineClaim = trim((string) ($line['claim_number'] ?? ''));
                if ($lineClaim !== '') {
                    $claimNumber = $lineClaim;
                    break;
                }
            }
            $client = $this->resolveClient($lines);
            $claims = $client ? $this->buildClaimOptions($client) : [];
            $match = $this->pickMatchingClaim($lines, $claims, $claimNumber);

            $groups[] = [
                'claim_number' => $claimNumber !== '' ? $claimNumber : $groupKey,
                'name' => $lines[0]['name'] ?? '',
                'mrn' => $lines[0]['mrn'] ?? '',
                'pdf_lines' => array_values($lines),
                'client' => $client ? [
                    'id' => $client->id,
                    'name' => $client->last_name . ', ' . $client->first_name,
                    'mrn' => $client->mrn,
                ] : null,
                'claims' => $claims,
                'suggested_claim_id' => $match['claim_id'],
                'line_matches' => $match['line_matches'],
                'match_status' => $match['match_status'],
                'pdf_totals' => [
                    'billed' => $this->sumRows($lines, 'billed'),
                    'paid' => $this->sumRows($lines, 'paid'),
                ],
            ];
        }

        return $groups;
    }

    private function buildTotals(array $rows): array
    {
        return [
            'billed' => $this->sumRows($rows, 'billed'),
            'paid' => $this->sumRows($rows, 'paid'),
            'lines' => count($rows),
        ];
    }

    private function sumRows(array $rows, string $key): float
    {
        $sum = 0.0;

        foreach ($rows as $row) {
            $sum += $this->toFloat($row[$key] ?? null);
        }

        return $sum;
    }

    private function resolveClient(array $lines): ?Client
    {
        $mrn = $lines[0]['mrn'] ?? null;
        if ($mrn) {
            $client = Client::where('mrn', $mrn)->first();
            if ($client) {
                return $client;
            }
        }

        $name = $lines[0]['name'] ?? null;
        if (! $name) {
            return null;
        }

        $parts = preg_split('/\s+/', trim($name));
        if (count($parts) < 2) {
            return null;
        }

        $lastName = strtoupper($parts[0]);
        $firstName = strtoupper($parts[1]);

        return Client::whereRaw('UPPER(last_name) = ?', [$lastName])
            ->whereRaw('UPPER(first_name) = ?', [$firstName])
            ->first();
    }

    private function buildClaimOptions(Client $client): array
    {
        return Claim::with('lines')
            ->where('client_id', $client->id)
            ->get()
            ->filter(function (Claim $claim) {
                $status = $claim->status();
                return in_array($status, ['Processed', 'In-Process'], true);
            })
            ->map(function (Claim $claim) {
                return [
                    'id' => $claim->id,
                    'claim_number' => $claim->claim_number,
                    'carelon_claim_number' => $claim->carelon_claim_number,
                    'status' => $claim->status(),
                    'submission_date' => $claim->submission_date
                        ? Carbon::parse($claim->submission_date)->format('m/d/Y')
                        : null,
                    'service_dates' => $claim->serviceDates(),
                    'total_billed' => $claim->totalBilledAmount(),
                    'total_processed' => $claim->totalProcessedAmount(),
                    'total_denied' => $claim->totalDeniedAmount(),
                    'lines' => $claim->lines->map(function (ClaimLineOfService $line) {
                        return [
                            'id' => $line->id,
                            'service_date' => $line->service_date
                                ? Carbon::parse($line->service_date)->format('m/d/Y')
                                : null,
                            'service_code' => $line->service_code,
                            'units' => $line->units,
                            'billed_amount' => $line->billed_amount,
                            'processed_amount' => $line->processed_amount,
                            'denied_amount' => $line->denied_amount,
                        ];
                    })->values(),
                ];
            })
            ->values()
            ->all();
    }

    private function pickMatchingClaim(array $pdfLines, array $claims, string $claimNumber): array
    {
        $best = [
            'claim_id' => null,
            'line_matches' => [],
            'match_status' => 'none',
            'matched_count' => 0,
            'score' => 0,
        ];

        $normalizedClaimNumber = strtoupper(str_replace(' ', '', $claimNumber));

        foreach ($claims as $claim) {
            $claimLines = $claim['lines'] ?? [];
            $matched = $this->matchLines($pdfLines, $claimLines);
            $claimNumberMatch = false;
            foreach (['claim_number', 'carelon_claim_number'] as $key) {
                $value = strtoupper(str_replace(' ', '', (string) ($claim[$key] ?? '')));
                if ($value && $value === $normalizedClaimNumber) {
                    $claimNumberMatch = true;
                    break;
                }
            }
            $score = $matched['matched_count'] + ($claimNumberMatch ? 1000 : 0);

            if ($score > $best['score']) {
                $best = [
                    'claim_id' => $claim['id'],
                    'line_matches' => $matched['line_matches'],
                    'match_status' => $matched['match_status'],
                    'matched_count' => $matched['matched_count'],
                    'score' => $score,
                ];
            }
        }

        return [
            'claim_id' => $best['claim_id'],
            'line_matches' => $best['line_matches'],
            'match_status' => $best['match_status'],
        ];
    }

    private function matchLines(array $pdfLines, array $claimLines): array
    {
        $matches = [];
        $used = [];

        foreach ($pdfLines as $index => $pdfLine) {
            $matchedId = null;

            foreach ($claimLines as $claimLine) {
                $lineId = $claimLine['id'];
                if (isset($used[$lineId])) {
                    continue;
                }

                if (! $this->lineMatches($pdfLine, $claimLine)) {
                    continue;
                }

                $matchedId = $lineId;
                $used[$lineId] = true;
                break;
            }

            if ($matchedId) {
                $matches[$index] = $matchedId;
            }
        }

        $matchedCount = count($matches);
        $status = $matchedCount === count($pdfLines) && $matchedCount > 0 ? 'full' : 'partial';
        if ($matchedCount === 0) {
            $status = 'none';
        }

        return [
            'line_matches' => $matches,
            'match_status' => $status,
            'matched_count' => $matchedCount,
        ];
    }

    private function lineMatches(array $pdfLine, array $claimLine): bool
    {
        $pdfDate = $this->normalizeDate($pdfLine['service_date'] ?? null);
        $claimDate = $this->normalizeDate($claimLine['service_date'] ?? null);

        if ($pdfDate && $claimDate && $pdfDate !== $claimDate) {
            return false;
        }

        $pdfCode = $this->formatServiceCode($pdfLine['service_code'] ?? null, $pdfLine['modifier'] ?? null);
        $claimCode = $this->formatServiceCode($claimLine['service_code'] ?? null, null);
        if ($pdfCode && $claimCode && $pdfCode !== $claimCode) {
            return false;
        }

        $pdfUnits = $this->toFloat($pdfLine['units'] ?? null);
        $claimUnits = $this->toFloat($claimLine['units'] ?? null);
        if ($pdfUnits > 0 && abs($pdfUnits - $claimUnits) > 0.01) {
            return false;
        }

        $pdfBilled = $this->toFloat($pdfLine['billed'] ?? null);
        $claimBilled = $this->toFloat($claimLine['billed_amount'] ?? null);
        if ($pdfBilled > 0 && abs($pdfBilled - $claimBilled) > 0.01) {
            return false;
        }

        return true;
    }

    private function normalizeDate(?string $date): ?string
    {
        if (! $date) {
            return null;
        }

        try {
            return Carbon::parse($date)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    private function toFloat($value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        return (float) str_replace(',', '', $value);
    }

    private function formatServiceCode(?string $code, ?string $modifier): string
    {
        $normalized = strtoupper(trim($code ?? ''));
        $normalizedModifier = strtoupper(trim($modifier ?? ''));

        if ($normalizedModifier && $normalized && ! str_contains($normalized, '-')) {
            return $normalized . '-' . $normalizedModifier;
        }

        return $normalized;
    }

    private function runScanner(string $scriptPath, string $fullPath): Process
    {
        $pythonBinaries = [
            config('services.check_scanner.python', 'python3'),
            'python3.11',
            'python3.10',
            'python3.9',
            'python3.8',
            'python3.7',
            'python3.6',
            'python3',
        ];
        $pythonBinaries = array_values(array_unique(array_filter($pythonBinaries)));

        foreach ($pythonBinaries as $binary) {
            $process = new Process([$binary, $scriptPath, $fullPath]);
            $process->run();

            if ($process->isSuccessful()) {
                return $process;
            }
        }

        return $process;
    }
}
