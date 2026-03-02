<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\CertificateType;
use App\Models\Client;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;
use ZipArchive;

class CertificateController extends Controller
{
    public function index()
    {
        $certificates = Certificate::with(['client', 'issuer', 'certificateType'])
            ->get();

        return view('certificates.index', compact('certificates'));
    }

    public function create()
    {
        $clients = Client::orderBy('last_name')->get();
        $types = CertificateType::orderBy('name')->get();

        return view('certificates.create', compact('clients', 'types'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'certificates' => 'required|array|min:1',
            'certificates.*.client_id' => 'required|exists:clients,id',
            'certificates.*.certificate_type_id' => 'required|exists:certificate_types,id',
            'certificates.*.graduation_date' => 'required|date',
        ]);

        $created = [];

        foreach ($request->input('certificates', []) as $payload) {
            $certificate = Certificate::create([
                'client_id' => $payload['client_id'],
                'certificate_type_id' => $payload['certificate_type_id'],
                'graduation_date' => $payload['graduation_date'],
                'issued_by' => $request->user()->id,
                'issued_at' => now(),
                'file_path' => '',
            ]);

            $filePath = $this->generateCertificatePdf($certificate);
            $certificate->update(['file_path' => $filePath]);

            $created[] = $certificate;
        }

        return redirect()
            ->route('certificates.index')
            ->with('success', count($created) . ' certificate(s) generated.');
    }

    public function edit(Certificate $certificate)
    {
        $clients = Client::orderBy('last_name')->get();
        $types = CertificateType::orderBy('name')->get();

        return view('certificates.edit', compact('certificate', 'clients', 'types'));
    }

    public function update(Request $request, Certificate $certificate)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'certificate_type_id' => 'required|exists:certificate_types,id',
            'graduation_date' => 'required|date',
        ]);

        $certificate->update([
            'client_id' => $request->input('client_id'),
            'certificate_type_id' => $request->input('certificate_type_id'),
            'graduation_date' => $request->input('graduation_date'),
        ]);

        $this->deleteExistingFile($certificate);
        $filePath = $this->generateCertificatePdf($certificate);
        $certificate->update(['file_path' => $filePath]);

        return redirect()
            ->route('certificates.index')
            ->with('success', 'Certificate updated.');
    }

    public function regenerate(Request $request, Certificate $certificate)
    {
        $this->deleteExistingFile($certificate);
        $filePath = $this->generateCertificatePdf($certificate);

        $certificate->update([
            'file_path' => $filePath,
            'issued_by' => $request->user()->id,
            'issued_at' => now(),
        ]);

        return redirect()
            ->route('certificates.index')
            ->with('success', 'Certificate regenerated.');
    }

    public function destroy(Certificate $certificate)
    {
        $this->deleteExistingFile($certificate);
        $certificate->delete();

        return redirect()
            ->route('certificates.index')
            ->with('success', 'Certificate deleted.');
    }

    public function download(Certificate $certificate)
    {
        if (! Storage::disk('local')->exists($certificate->file_path)) {
            abort(404);
        }

        return response()->download(
            Storage::disk('local')->path($certificate->file_path),
            $this->buildDownloadFilename($certificate, 'pdf')
        );
    }

    public function bulkDownload(Request $request)
    {
        $request->validate([
            'certificate_ids' => 'required|array|min:1',
            'certificate_ids.*' => 'exists:certificates,id',
        ]);

        $certificates = Certificate::with(['client', 'certificateType'])
            ->whereIn('id', $request->input('certificate_ids', []))
            ->get();

        if ($certificates->isEmpty()) {
            return redirect()
                ->route('certificates.index')
                ->with('error', 'No certificates selected.');
        }

        Storage::disk('local')->makeDirectory('private/certificates');
        $zipName = 'certificates_' . now()->format('Ymd_His') . '.zip';
        $zipPath = Storage::disk('local')->path('private/certificates/' . $zipName);

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            abort(500, 'Unable to create ZIP archive.');
        }

        $usedNames = [];

        foreach ($certificates as $certificate) {
            if (! $certificate->file_path || ! Storage::disk('local')->exists($certificate->file_path)) {
                continue;
            }

            $entryName = $this->buildDownloadFilename($certificate, 'pdf');
            $entryName = $this->ensureUniqueFilename($entryName, $usedNames);

            $zip->addFile(Storage::disk('local')->path($certificate->file_path), $entryName);
        }

        $zip->close();

        if (empty($usedNames)) {
            Storage::disk('local')->delete('private/certificates/' . $zipName);

            return redirect()
                ->route('certificates.index')
                ->with('error', 'No certificate files were available to download.');
        }

        return response()
            ->download($zipPath, $zipName)
            ->deleteFileAfterSend(true);
    }

    private function generateCertificatePdf(Certificate $certificate): string
    {
        $certificate->load(['client', 'certificateType']);
        $certificateType = $certificate->certificateType;

        if (! $certificateType) {
            abort(404);
        }

        $clientName = trim($certificate->client->first_name . ' ' . $certificate->client->last_name);
        $graduationDate = Carbon::parse($certificate->graduation_date)->format('m/d/Y');

        $pdf = $this->buildCertificatePdf($certificateType, $clientName, $graduationDate);

        Storage::disk('local')->makeDirectory('private/certificates');

        $fileName = 'certificate_' . $certificate->id . '_' . now()->format('Ymd_His') . '.pdf';
        $relativePath = 'private/certificates/' . $fileName;

        $pdf->Output(Storage::disk('local')->path($relativePath), Destination::FILE);

        return $relativePath;
    }

    private function buildCertificatePdf(CertificateType $certificateType, string $clientName, string $graduationDate): Mpdf
    {
        $templatePath = Storage::disk('local')->path($certificateType->template_path);

        $config = (new ConfigVariables())->getDefaults();
        $fontConfig = (new FontVariables())->getDefaults();
        $fontData = CertificateType::fontData();

        $pdf = new Mpdf([
            'format' => 'Letter',
            'orientation' => 'L',
            'margin_left' => 0,
            'margin_right' => 0,
            'margin_top' => 0,
            'margin_bottom' => 0,
            'fontDir' => array_merge($config['fontDir'], [resource_path('fonts')]),
            'fontdata' => $fontConfig['fontdata'] + $fontData,
            'default_font' => 'ebcorpmedium',
        ]);

        if (is_file($templatePath)) {
            $pdf->setSourceFile($templatePath);
            $templateId = $pdf->importPage(1);
            $pdf->useTemplate($templateId);
        }

        $nameFont = $certificateType->name_font ?: 'brittanysignature';
        if (! array_key_exists($nameFont, $fontData)) {
            $nameFont = 'brittanysignature';
        }

        $pdf->SetTextColor(141, 58, 234);
        $pdf->SetFont($nameFont, '', 39.5);
        $pdf->SetXY($certificateType->name_x, $certificateType->name_y);
        $pdf->Cell($pdf->w, 18, $clientName, 0, 1, 'C');

        $pdf->SetTextColor(63, 113, 206);
        $pdf->SetFont('ebcorpmedium', '', 18);
        $pdf->SetXY($certificateType->date_x, $certificateType->date_y);
        $pdf->Cell($pdf->w, 8, $graduationDate, 0, 1, 'C');

        return $pdf;
    }

    private function buildDownloadFilename(Certificate $certificate, string $extension): string
    {
        $certificate->loadMissing(['client', 'certificateType']);
        $clientName = trim($certificate->client->first_name . ' ' . $certificate->client->last_name);
        $typeName = $certificate->type_label;
        $graduationDate = $certificate->graduation_date
            ? $certificate->graduation_date->format('m-d-Y')
            : 'unknown-date';

        $baseName = trim($clientName . ' - ' . $typeName . ' - ' . $graduationDate);
        $baseName = $baseName !== '' ? $baseName : 'certificate';
        $sanitized = preg_replace('/[^\pL\pN\s\-_\.]+/u', '', $baseName);
        $sanitized = preg_replace('/\s+/', ' ', $sanitized);
        $sanitized = Str::limit(trim($sanitized), 200, '');

        return $sanitized . '.' . $extension;
    }

    private function ensureUniqueFilename(string $filename, array &$usedNames): string
    {
        if (! array_key_exists($filename, $usedNames)) {
            $usedNames[$filename] = 1;

            return $filename;
        }

        $usedNames[$filename]++;
        $pathInfo = pathinfo($filename);
        $suffix = ' (' . $usedNames[$filename] . ')';
        $name = $pathInfo['filename'] ?? $filename;
        $extension = isset($pathInfo['extension']) ? '.' . $pathInfo['extension'] : '';

        return $name . $suffix . $extension;
    }

    private function deleteExistingFile(Certificate $certificate): void
    {
        if ($certificate->file_path && Storage::disk('local')->exists($certificate->file_path)) {
            Storage::disk('local')->delete($certificate->file_path);
        }
    }
}
