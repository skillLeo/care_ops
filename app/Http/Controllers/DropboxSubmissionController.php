<?php

namespace App\Http\Controllers;

use App\Mail\DropboxRoiMail;
use App\Mail\DropboxSubmissionMail;
use App\Models\Dropbox;
use App\Services\EmailTemplateService;
use App\Services\TaskWorkflowService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use setasign\Fpdi\Fpdi;

class DropboxSubmissionController extends Controller
{
    private const ROI_SIGNATURE_EXPORT_WIDTH = 480;
    private const ROI_SIGNATURE_EXPORT_HEIGHT = 120;
    private const ROI_SIGNATURE_PADDING = 6;
    private const ROI_SIGNATURE_BORDER = 12;

    public function showIntake(Request $request)
    {
        $type = $request->query('type');
        $defaultType = in_array($type, ['individual', 'agency'], true) ? $type : null;

        return view('dropboxes.public.intake', [
            'defaultType' => $defaultType,
        ]);
    }

    public function submitIndividual(Request $request): RedirectResponse
    {
        $this->storeSubmission($request, 'individual');

        return redirect()->route('dropbox.thank-you')->with('success', 'Thank you! Your information has been received.');
    }

    public function submitAgency(Request $request): RedirectResponse
    {
        $this->storeSubmission($request, 'agency');

        return redirect()->route('dropbox.thank-you')->with('success', 'Thank you! Your information has been received.');
    }

    public function thankYou()
    {
        return view('dropboxes.public.thank-you');
    }

    public function previewRoi(Request $request)
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'date_of_birth' => ['required', 'date'],
            'social_security_number' => ['required', 'string', 'max:255'],
            'program_name' => ['required', 'string', 'max:255'],
            'program_level_of_care' => ['required', 'string', 'max:255'],
            'program_contact_details' => ['required', 'string', 'max:500'],
        ]);

        $baseName = $this->buildBaseFileName($data['first_name'], $data['last_name'], Carbon::now()->format('m_d_Y'));
        $pdfContent = $this->generateRoiDocument($data, $baseName, null, false);

        abort_if(! $pdfContent, 404, 'Authorization document template is unavailable.');

        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="authorization_to_release_information.pdf"',
        ]);
    }

    protected function storeSubmission(Request $request, string $type): Dropbox
    {

        if ($type === 'individual') {
            $deliveryMethod = $request->input('currently_in_program') === 'yes' ? 'email' : 'none';
            $request->merge(['document_delivery_method' => $deliveryMethod]);
        }

        $validated = $this->validateRequest($request, $type);

        $firstName = $validated['first_name'];
        $lastName = $validated['last_name'];
        $formattedDate = Carbon::now()->format('m_d_Y');
        $baseName = $this->buildBaseFileName($firstName, $lastName, $formattedDate);

        $biopsychosocialPath = null;
        $urinePaths = [];
        $dischargeSummaryPath = null;
        $roiPath = null;

        $documentMethod = $validated['document_delivery_method'] ?? 'email';

        if ($documentMethod === 'upload') {
            if ($request->hasFile('biopsychosocial')) {
                $biopsychosocialPath = $this->storeFile($request->file('biopsychosocial'), $baseName . '_Bio');
            }

            if ($request->hasFile('urine_history')) {
                foreach ($request->file('urine_history') as $index => $file) {
                    if (! $file) {
                        continue;
                    }

                    $urinePaths[] = $this->storeFile($file, $baseName . '_Urine_' . ($index + 1));
                }
            }

            if ($request->hasFile('discharge_summary')) {
                $dischargeSummaryPath = $this->storeFile($request->file('discharge_summary'), $baseName . '_Discharge');
            }

            if ($type === 'agency' && $request->hasFile('roi_authorization')) {
                $roiPath = $this->storeFile($request->file('roi_authorization'), $baseName . '_ROI');
            }
        }

        if ($type === 'individual' && ($validated['currently_in_program'] === 'yes')) {
            $signatureData = $request->input('roi_signature');
            $roiPath = $this->generateRoiDocument($validated, $baseName, $signatureData, true);
        }

        $dropbox = Dropbox::create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'date_of_birth' => $validated['date_of_birth'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'gender' => $validated['gender'] ?? null,
            'heard_about_us' => $validated['heard_about_us'],
            'pickup_instructions' => $validated['pickup_instructions'] ?? null,
            'social_security_number' => $validated['social_security_number'],
            'medicaid_number' => $validated['medicaid_number'],
            'notes' => $validated['notes'] ?? null,
            'drug_of_choice' => $validated['drug_of_choice'],
            'last_use_date' => $validated['last_use_date'],
            'type' => $type,
            'returning_client' => $validated['returning_client'] === 'yes',
            'currently_in_program' => $validated['currently_in_program'] === 'yes',
            'program_name' => ($validated['currently_in_program'] === 'yes') ? ($validated['program_name'] ?? null) : null,
            'program_contact_details' => ($validated['currently_in_program'] === 'yes') ? ($validated['program_contact_details'] ?? null) : null,
            'program_level_of_care' => ($validated['currently_in_program'] === 'yes') ? ($validated['program_level_of_care'] ?? null) : null,
            'biopsychosocial_path' => $biopsychosocialPath,
            'urine_history_paths' => $urinePaths ?: null,
            'discharge_summary_path' => $dischargeSummaryPath,
            'document_delivery_method' => $documentMethod,
            'roi_document_path' => $roiPath,
            'status' => 'pending',
        ]);

        app(TaskWorkflowService::class)->triggerFirstTaskForDropbox('Intake', $dropbox);

        $this->notifySubmission($dropbox);

        if ($dropbox->type === 'individual' && $dropbox->currently_in_program && $dropbox->roi_document_path) {
            $this->sendRoiToClient($dropbox);
        }

        return $dropbox;
    }

    protected function validateRequest(Request $request, string $type): array
    {
        $rules = [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'date_of_birth' => ['required', 'date'],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string', 'max:25'],
            'gender' => ['nullable', Rule::in(['male', 'female'])],
            'heard_about_us' => ['required', 'string', 'max:255'],
            'pickup_instructions' => ['nullable', 'string'],
            'social_security_number' => ['required', 'string', 'max:255'],
            'medicaid_number' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'drug_of_choice' => ['required', 'string', 'max:255'],
            'last_use_date' => ['required', 'date'],
            'returning_client' => ['required', Rule::in(['yes', 'no'])],
            'currently_in_program' => ['required', Rule::in(['yes', 'no'])],
            'program_name' => ['nullable', 'string', 'max:255'],
            'program_contact_details' => ['nullable', 'string', 'max:500'],
            'program_level_of_care' => ['nullable', 'string', 'max:255'],
            'type' => ['required', Rule::in([$type])],
            // 'document_delivery_method' => ['required', Rule::in($type === 'individual' ? ['email', 'none'] : ['upload', 'email', 'fax'])],
            'roi_signature' => ['nullable', 'string'],
            'roi_acknowledgement' => ['nullable'],
            'g-recaptcha-response' => ['required', 'string'],
        ];

        $fileRules = [
            'biopsychosocial' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'urine_history' => ['nullable', 'array', 'max:10'],
            'urine_history.*' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'discharge_summary' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ];

        if ($type === 'agency') {
            $fileRules['roi_authorization'] = ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'];
        }

        $rules = array_merge($rules, $fileRules);

        $validated = $request->validate($rules);

        $this->validateCaptchaResponse($request);

        if (($validated['currently_in_program'] ?? 'no') === 'yes') {
            $request->validate([
                'email' => ['required', 'email'],
                'program_name' => ['required', 'string', 'max:255'],
                'program_contact_details' => ['required', 'string', 'max:500'],
                'program_level_of_care' => ['required', 'string', 'max:255'],
            ]);

            if ($type === 'individual') {
                $request->validate([
                    'roi_acknowledgement' => ['accepted'],
                    'roi_signature' => ['required', 'string'],
                ]);
            }
        }

        return $validated;
    }

    protected function validateCaptchaResponse(Request $request): void
    {
        $secretKey = config('services.recaptcha.secret_key');
        $captchaResponse = $request->input('g-recaptcha-response');

        if (! $secretKey) {
            throw ValidationException::withMessages([
                'g-recaptcha-response' => 'Captcha verification is not configured. Please contact support.',
            ]);
        }

        $verificationResponse = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => $secretKey,
            'response' => $captchaResponse,
            'remoteip' => $request->ip(),
        ]);

        if (! $verificationResponse->ok()) {
            throw ValidationException::withMessages([
                'g-recaptcha-response' => 'Captcha verification failed. Please try again.',
            ]);
        }

        $payload = $verificationResponse->json();

        if (! ($payload['success'] ?? false)) {
            throw ValidationException::withMessages([
                'g-recaptcha-response' => 'Captcha verification failed. Please try again.',
            ]);
        }
    }

    protected function buildBaseFileName(string $firstName, string $lastName, string $date): string
    {
        $name = $lastName . ', ' . $firstName;

        return Str::of($name)->replaceMatches('/[^A-Za-z0-9,_-]+/', '_')->trim('_')->value() . '_' . $date;
    }

    protected function storeFile($file, string $baseName): string
    {
        $extension = $file->getClientOriginalExtension();
        $fileName = $baseName . '.' . $extension;
        $directory = 'private/dropbox';

        Storage::makeDirectory($directory);

        return $file->storeAs($directory, $fileName);
    }

    protected function generateRoiDocument(array $data, string $baseName, ?string $signatureData, bool $store): ?string
    {
        $templatePath = storage_path('app/private/templates/roi.pdf');

        if (! file_exists($templatePath)) {
            return null;
        }

        $pdf = new Fpdi();
        $pageCount = $pdf->setSourceFile($templatePath);

        $signaturePath = null;

        if ($signatureData) {
            $signaturePath = $this->storeSignatureImage($signatureData, $baseName);
        }

        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $pdf->AddPage('P', 'Letter');
            $templateId = $pdf->importPage($pageNo);
            $pdf->useTemplate($templateId);

            $pdf->SetFont('Times', '', 10);
            $pdf->SetTextColor(0, 0, 0);

            if ($pageNo === 1) {
                $clientName = trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? ''));
                $programName = $data['program_name'] ?? '';
                $contactLines = $this->prepareProgramContactLines($data['program_contact_details'] ?? '', $data['program_level_of_care'] ?? '');

                $this->writeCenteredText($pdf, $clientName, 71.2, 76.0);
                $this->writeCenteredText($pdf, $programName, 108.7, 103.8);
                $this->writeCenteredText($pdf, $contactLines[0] ?? '', 108.7, 107.8);
                $this->writeCenteredText($pdf, $contactLines[1] ?? '', 108.7, 111.8);
                $this->writeCenteredText($pdf, $contactLines[2] ?? '', 108.7, 115.8);
                $this->writeCenteredText($pdf, $contactLines[3] ?? '', 108.7, 119.8);
                $this->writeCenteredText($pdf, $clientName, 80.5, 199.5);
                $this->writeCenteredText($pdf, $data['social_security_number'] ?? '', 135.0, 199.5);
            }

            if ($pageNo === 2) {
                $clientName = trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? ''));
                $signedDate = Carbon::now()->format('m/d/Y');

                $this->writeCenteredText($pdf, $clientName, 154.0, 81.0);
                $this->writeCenteredText($pdf, $signedDate, 154.0, 100.5);

                if ($signaturePath) {
                    $imagePath = Storage::path($signaturePath);   // instead of storage_path('app/'.$signaturePath)
                    if (file_exists($imagePath)) {
                        [$w, $h] = getimagesize($imagePath);
                        $targetX = 70; $targetY = 101;
                        $pdf->Image($imagePath, $targetX - ($w / 8), $targetY - ($h / 4), $w / 4, $h / 4);
                    }
                }
            }
        }

        if ($store) {
            $directory = 'private/dropbox-roi';
            Storage::makeDirectory($directory); // ensures dir exists on the default disk

            $fileSuffix = Carbon::now()->format('Ymd_His');
            $outputPath = $directory . '/' . $baseName . '_ROI_' . $fileSuffix . '.pdf';

            // Safest: get the PDF bytes and let Laravel write it
            $pdfBytes = $pdf->Output('S');
            Storage::put($outputPath, $pdfBytes);

            return $outputPath;
        }

        return $pdf->Output('S');
    }

    protected function storeSignatureImage(string $signatureData, string $baseName): ?string
    {
        $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $signatureData));

        if (! $imageData) {
            return null;
        }

        $directory = 'private/dropbox-roi/signatures';
        Storage::makeDirectory($directory);

        $fileName = $baseName . '_signature_' . Carbon::now()->format('Ymd_His') . '.png';
        $relativePath = $directory . '/' . $fileName;

        $image = imagecreatefromstring($imageData);

        if (! $image) {
            return null;
        }

        $width = imagesx($image);
        $height = imagesy($image);

        if (! imageistruecolor($image)) {
            $trueColor = imagecreatetruecolor($width, $height);
            imagecopy($trueColor, $image, 0, 0, 0, 0, $width, $height);
            imagedestroy($image);
            $image = $trueColor;
        }

        $absolutePath = Storage::path($relativePath);

        $processed = $this->normalizeSignatureImage($image);

        imagedestroy($image);

        if (! $processed) {
            return null;
        }

        imagepng($processed, $absolutePath);
        imagedestroy($processed);

        return $relativePath;
    }

    protected function normalizeSignatureImage(\GdImage $image): ?\GdImage
    {
        $width  = imagesx($image);
        $height = imagesy($image);

        imagesavealpha($image, true);

        $minX = $width; $minY = $height; $maxX = 0; $maxY = 0;




        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $rgba  = imagecolorat($image, $x, $y);
                $alpha = ($rgba & 0x7F000000) >> 24;

                $red   = ($rgba >> 16) & 0xFF;
                $green = ($rgba >> 8) & 0xFF;
                $blue  = $rgba & 0xFF;

                $isInkPixel = ($alpha < 120) || ($red < 245 || $green < 245 || $blue < 245);

                if ($isInkPixel) {
                    $minX = min($minX, $x); $maxX = max($maxX, $x);
                    $minY = min($minY, $y); $maxY = max($maxY, $y);


                }
            }
        }

        $targetWidth = self::ROI_SIGNATURE_EXPORT_WIDTH;
        $targetHeight = self::ROI_SIGNATURE_EXPORT_HEIGHT;

        $canvas = imagecreatetruecolor($targetWidth, $targetHeight);
        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefill($canvas, 0, 0, $white);

        if ($minX >= $maxX || $minY >= $maxY) {
            return $canvas;
        }

        $minX = max(0, $minX - self::ROI_SIGNATURE_PADDING);
        $minY = max(0, $minY - self::ROI_SIGNATURE_PADDING);
        $maxX = min($width - 1, $maxX + self::ROI_SIGNATURE_PADDING);
        $maxY = min($height - 1, $maxY + self::ROI_SIGNATURE_PADDING);

        $croppedWidth = max(1, $maxX - $minX + 1);
        $croppedHeight = max(1, $maxY - $minY + 1);

        $availableWidth = max(1, $targetWidth - (self::ROI_SIGNATURE_BORDER * 2));
        $availableHeight = max(1, $targetHeight - (self::ROI_SIGNATURE_BORDER * 2));

        $scale = min($availableWidth / $croppedWidth, $availableHeight / $croppedHeight);
        $scale = min($scale, 1.0);

        $scaledWidth = max(1, (int) round($croppedWidth * $scale));
        $scaledHeight = max(1, (int) round($croppedHeight * $scale));

        $offsetX = (int) round(($targetWidth - $scaledWidth) / 2);
        $offsetY = (int) round(($targetHeight - $scaledHeight) / 2);

        imagecopyresampled(
            $canvas,
            $image,
            $offsetX,
            $offsetY,
            $minX,
            $minY,
            $scaledWidth,
            $scaledHeight,
            $croppedWidth,
            $croppedHeight
        );

        return $canvas;
    }

    protected function writeCenteredText(Fpdi $pdf, string $text, float $targetX, float $targetY): void
    {
        if ($text === '') {
            return;
        }

        $textWidth = $pdf->GetStringWidth($text);
        $centeredX = $targetX - ($textWidth / 2);
        $pdf->SetXY($centeredX, $targetY);
        $pdf->Write(0, $text);
    }

    protected function prepareProgramContactLines(?string $details, ?string $levelOfCare): array
    {
        $details = $details ?? '';
        $lines = array_filter(array_map('trim', preg_split('/\r?\n/', $details)));

        if (empty($lines) && str_contains($details, ',')) {
            $lines = array_filter(array_map('trim', explode(',', $details)));
        }

        $lines = array_values($lines);

        if ($levelOfCare) {
            $lines[] = 'Level of Care: ' . $levelOfCare;
        }

        return array_slice($lines, 0, 4);
    }

    protected function notifySubmission(Dropbox $dropbox): void
    {
        $recipients = $this->parseEmails(env('DROPBOX_SUBMISSION_MAIL'));

        $templateService = app(EmailTemplateService::class);
        $message = $templateService->buildDropboxMessage('dropbox_submission', $dropbox);

        if ($message) {
            $to = $message['to'] ?: $recipients;
            if (empty($to)) {
                return;
            }

            Mail::raw($message['body'], function ($mail) use ($message, $to) {
                $mail->to($to)
                    ->subject($message['subject']);

                if (! empty($message['cc'])) {
                    $mail->cc($message['cc']);
                }

                if (! empty($message['bcc'])) {
                    $mail->bcc($message['bcc']);
                }
            });
            return;
        }

        if (empty($recipients)) {
            return;
        }

        Mail::to($recipients)->send(new DropboxSubmissionMail($dropbox));
    }

    protected function sendRoiToClient(Dropbox $dropbox): void
    {
        if ($dropbox->type !== 'individual' || ! $dropbox->email) {
            return;
        }

        if (! Storage::disk('local')->exists($dropbox->roi_document_path)) {
            return;
        }

        $templateService = app(EmailTemplateService::class);
        $message = $templateService->buildDropboxMessage('dropbox_roi', $dropbox);

        if ($message) {
            $to = $message['to'] ?: [$dropbox->email];

            Mail::raw($message['body'], function ($mail) use ($message, $to, $dropbox) {
                $mail->to($to)
                    ->subject($message['subject'])
                    ->attachFromStorageDisk('local', $dropbox->roi_document_path);

                if (! empty($message['cc'])) {
                    $mail->cc($message['cc']);
                }

                if (! empty($message['bcc'])) {
                    $mail->bcc($message['bcc']);
                }
            });
            return;
        }

        Mail::to($dropbox->email)
            ->cc('snbllc.org@gmail.com')
            ->send(new DropboxRoiMail($dropbox, $dropbox->roi_document_path));
    }

    protected function parseEmails(?string $raw): array
    {
        if (! $raw) {
            return [];
        }

        return collect(preg_split('/[;,\n]/', $raw))
            ->map(fn ($email) => trim($email))
            ->filter()
            ->all();
    }
}
