<?php

namespace App\Http\Controllers;

use App\Models\AuthToReleaseInfo;
use App\Models\Client;
use App\Models\MedicalContact;
use App\Services\EmailTemplateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Fpdi;

class AuthToReleaseInfoController extends Controller
{
    public function index()
    {
        $clients = Client::all();
        return view('auth_to_release_info.index', compact('clients'));
    }

    public function show(Client $client)
    {
        $medicalContacts = MedicalContact::all();
        // $auth = AuthToReleaseInfo::where('client_id', $client->id)->first();
        return view('auth_to_release_info.show', compact('client', 'medicalContacts'));
    }

    public function signForm(Client $client, MedicalContact $medicalContact)
    {
        return view('auth_to_release_info.sign', compact('client', 'medicalContact'));
    }

    public function generateFromConsent(Client $client, MedicalContact $medicalContact)
    {
        $consent = $client->consents()
            ->whereNotNull('signed_date')
            ->whereNotNull('signature_path')
            ->orderByDesc('signed_date')
            ->orderByDesc('updated_at')
            ->first();

        if (!$consent || !$this->signatureExists($consent->signature_path)) {
            return back()->with('error', 'No signed consent signature found to generate the authorization.');
        }

        $documentPath = $this->buildAuthDocumentPath($client->id, $medicalContact->id);

        $auth = AuthToReleaseInfo::create([
            'client_id' => $client->id,
            'medical_contact_id' => $medicalContact->id,
            'signed_date' => $consent->signed_date,
            'document_path' => $this->insertSignatureIntoPDF($consent->signed_date, $client, $medicalContact, $consent->signature_path, $documentPath),
            'signature_path' => $consent->signature_path,
        ]);

        return back()->with('success', 'Authorization generated successfully.');
    }

    public function sign(Request $request, Client $client, MedicalContact $medicalContact)
    {
        $request->validate([
            'signature' => 'required',
            'signed_date' => 'required|date',
        ]);

        // Ensure the directory exists before writing the file
        $signatureDirectory = storage_path('app/private/signatures');
        if (!file_exists($signatureDirectory)) {
            mkdir($signatureDirectory, 0755, true); // Create directory if it doesn't exist
        }

        // Convert base64 image to file
        $signatureData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $request->signature));
        $signatureFilename = 'signature_' . \Carbon\Carbon::now()->format('Ymd_His') . '.png';
        $signaturePath = 'private/signatures/' . $signatureFilename;
        $finalPath = storage_path('app/' . $signaturePath);
        file_put_contents($finalPath, $signatureData);

        $this->cropBelowSignature($finalPath);

        // Save Authorization Entry
        $documentPath = $this->buildAuthDocumentPath($client->id, $medicalContact->id);

        $auth = AuthToReleaseInfo::create([
            'client_id' => $client->id,
            'medical_contact_id' => $medicalContact->id,
            'signed_date' => $request->signed_date,
            'document_path' => $this->insertSignatureIntoPDF($request->signed_date, $client, $medicalContact, $signaturePath, $documentPath),
            'signature_path' => $signaturePath,
        ]);

        return redirect()->route('auth-to-release-info.show', $client->id)
            ->with('success', 'Authorization signed successfully.');
    }

    public function regenerate($authId)
    {
        $auth = AuthToReleaseInfo::with(['client', 'medicalContact'])->findOrFail($authId);

        $medicalContact = MedicalContact::find($auth->medical_contact_id);
        if (!$medicalContact) {
            return back()->with('error', 'Medical contact not found for this authorization.');
        }

        if (!$auth->signed_date) {
            return back()->with('error', 'Signed date not found for this authorization.');
        }

        $signaturePath = $this->resolveSignaturePath($auth);

        if (!$signaturePath) {
            return back()->with('error', 'No signature available to regenerate the authorization.');
        }

        $newDocumentPath = $this->buildAuthDocumentPath($auth->client_id, $medicalContact->id);

        $auth->document_path = $this->insertSignatureIntoPDF(
            $auth->signed_date,
            $auth->client,
            $medicalContact,
            $signaturePath,
            $newDocumentPath
        );

        if ($signaturePath !== $auth->signature_path) {
            $auth->signature_path = $signaturePath;
        }

        $auth->save();
        $auth->refresh();

        return back()->with('success', 'Authorization regenerated successfully.');
    }


    private function sendEmail($authId)
    {
        // $auth = AuthToReleaseInfo::findOrFail($authId);

        // $filePath = storage_path('app/' . $auth->document_path);

        // // Logic for sending email with attached document
    }

    private function sendEmail2($clientId, $medicalContactId)
    {
        // $auth = AuthToReleaseInfo::findOrFail($authId);

        // $filePath = storage_path('app/' . $auth->document_path);

        // // Logic for sending email with attached document
    }


    private function insertSignatureIntoPDF($signedDate, $client, $medicalContact, $signaturePath, $outputPath = null)
    {
        $templatePath = storage_path('app/private/templates/auth-to-release-info.pdf');
        $outputDirectory = storage_path('app/private/auth_to_release_info');
        $outputPath = $outputPath ?? $this->buildAuthDocumentPath($client->id, $medicalContact->id);
        $pdfOutputPath = storage_path('app/' . $outputPath);

        // Ensure directory exists
        if (!file_exists($outputDirectory)) {
            mkdir($outputDirectory, 0755, true);
        }

        $pdf = new Fpdi();
        $pageCount = $pdf->setSourceFile($templatePath);

        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $pdf->AddPage('P', 'Letter');
            $templateId = $pdf->importPage($pageNo);
            $pdf->useTemplate($templateId);

            $pdf->SetFont('Times', '', 10);
            $pdf->SetTextColor(0, 0, 0);

            if ($pageNo == 1) {
                // Placeholder for adding text on Page 1
                $text = $client->first_name . ' ' . $client->last_name;
                $targetX = 71.2;  // The X coordinate of the midpoint where we want the text centered
                $targetY = 76;   // Y coordinate where we want the text
                $textWidth = $pdf->GetStringWidth($text);

                // Adjust X to center the text
                $centeredX = $targetX - ($textWidth / 2);
                $pdf->SetXY($centeredX, $targetY);
                $pdf->Write(0, $text);



                $text = $medicalContact->name;
                $targetX = 108.7;  // The X coordinate of the midpoint where we want the text centered
                $targetY = 103.8;   // Y coordinate where we want the text
                $textWidth = $pdf->GetStringWidth($text);

                // Adjust X to center the text
                $centeredX = $targetX - ($textWidth / 2);
                $pdf->SetXY($centeredX, $targetY);
                $pdf->Write(0, $text);

                $text = $medicalContact->address1;
                $targetX = 108.7;  // The X coordinate of the midpoint where we want the text centered
                $targetY = 107.8;   // Y coordinate where we want the text
                $textWidth = $pdf->GetStringWidth($text);

                // Adjust X to center the text
                $centeredX = $targetX - ($textWidth / 2);
                $pdf->SetXY($centeredX, $targetY);
                $pdf->Write(0, $text);



                $text = $medicalContact->address2;
                $targetX = 108.7;  // The X coordinate of the midpoint where we want the text centered
                $targetY = 111.8;   // Y coordinate where we want the text
                $textWidth = $pdf->GetStringWidth($text);

                // Adjust X to center the text
                $centeredX = $targetX - ($textWidth / 2);
                $pdf->SetXY($centeredX, $targetY);
                $pdf->Write(0, $text);


                $text = $medicalContact->email;
                $targetX = 108.7;  // The X coordinate of the midpoint where we want the text centered
                $targetY = 115.8;   // Y coordinate where we want the text
                $textWidth = $pdf->GetStringWidth($text);

                // Adjust X to center the text
                $centeredX = $targetX - ($textWidth / 2);
                $pdf->SetXY($centeredX, $targetY);
                $pdf->Write(0, $text);


                $text = $medicalContact->contact;
                $targetX = 108.7;  // The X coordinate of the midpoint where we want the text centered
                $targetY = 119.8;   // Y coordinate where we want the text
                $textWidth = $pdf->GetStringWidth($text);

                // Adjust X to center the text
                $centeredX = $targetX - ($textWidth / 2);
                $pdf->SetXY($centeredX, $targetY);
                $pdf->Write(0, $text);


                $text = $client->first_name . ' ' . $client->last_name;
                $targetX = 80.5;  // The X coordinate of the midpoint where we want the text centered
                $targetY = 199.5;   // Y coordinate where we want the text
                $textWidth = $pdf->GetStringWidth($text);

                // Adjust X to center the text
                $centeredX = $targetX - ($textWidth / 2);
                $pdf->SetXY($centeredX, $targetY);
                $pdf->Write(0, $text);


                $text = $client->ssn;
                $targetX = 135;  // The X coordinate of the midpoint where we want the text centered
                $targetY = 199.5;   // Y coordinate where we want the text
                $textWidth = $pdf->GetStringWidth($text);

                // Adjust X to center the text
                $centeredX = $targetX - ($textWidth / 2);
                $pdf->SetXY($centeredX, $targetY);
                $pdf->Write(0, $text);


                $text = \Carbon\Carbon::parse($client->date_of_birth)->format('m/d/Y');
                $targetX = 175;  // The X coordinate of the midpoint where we want the text centered
                $targetY = 199.5;   // Y coordinate where we want the text
                $textWidth = $pdf->GetStringWidth($text);

                // Adjust X to center the text
                $centeredX = $targetX - ($textWidth / 2);
                $pdf->SetXY($centeredX, $targetY);
                $pdf->Write(0, $text);

            }

            if ($pageNo == 2) {
                // Placeholder for adding text on Page 2
                $text = $client->first_name . ' ' . $client->last_name;
                $targetX = 154;  // The X coordinate of the midpoint where we want the text centered
                $targetY = 81;   // Y coordinate where we want the text
                $textWidth = $pdf->GetStringWidth($text);

                // Adjust X to center the text
                $centeredX = $targetX - ($textWidth / 2);
                $pdf->SetXY($centeredX, $targetY);
                $pdf->Write(0, $text);



                $text = \Carbon\Carbon::parse($signedDate)->format('m/d/Y');
                $targetX = 154;  // The X coordinate of the midpoint where we want the text centered
                $targetY = 100.5;   // Y coordinate where we want the text
                $textWidth = $pdf->GetStringWidth($text);

                // Adjust X to center the text
                $centeredX = $targetX - ($textWidth / 2);
                $pdf->SetXY($centeredX, $targetY);
                $pdf->Write(0, $text);


                // Insert Signature on Page 2
                // Define the desired bottom-center coordinate
                $targetX = 70; // X coordinate for center alignment
                $targetY = 101; // Y coordinate where the bottom of the image should be

                // Get the dimensions of the image
                list($width, $height) = getimagesize(storage_path('app/' . $signaturePath));

                // Calculate adjusted coordinates
                $adjustedX = $targetX - ($width / 8); // Center horizontally
                $adjustedY = $targetY - ($height / 4); // Align bottom

                // Place the signature image at the calculated position
                $pdf->Image(storage_path('app/' . $signaturePath), $adjustedX, $adjustedY, $width / 4, $height / 4);

            }
        }


        $pdf->Output('F', $pdfOutputPath);
        return $outputPath;
    }

    private function cropBelowSignature($filePath)
    {
        $image = imagecreatefrompng($filePath);
        imagesavealpha($image, true);

        $width = imagesx($image);
        $height = imagesy($image);

        $minX = $width;
        $minY = $height;
        $maxX = 0;
        $maxY = 0;

        // Loop through the image to find non-transparent pixels
        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $rgba = imagecolorat($image, $x, $y);
                $alpha = ($rgba & 0x7F000000) >> 24;

                // Check if pixel is not fully transparent
                if ($alpha < 127) {
                    if ($x < $minX) $minX = $x;
                    if ($x > $maxX) $maxX = $x;
                    if ($y < $minY) $minY = $y;
                    if ($y > $maxY) $maxY = $y;
                }
            }
        }

        // Define crop boundaries to trim below the bottom of the signature
        $croppedWidth = $maxX - $minX + 5;
        $croppedHeight = $maxY - $minY + 5;

        // Adjust to crop a few pixels above the bottom of the signature
        $adjustedHeight = $croppedHeight - 5;  // Adjust this value as needed

        // Create a new cropped image
        $croppedImage = imagecreatetruecolor($croppedWidth, $adjustedHeight);
        imagesavealpha($croppedImage, true);
        $transparent = imagecolorallocatealpha($croppedImage, 0, 0, 0, 127);
        imagefill($croppedImage, 0, 0, $transparent);

        imagecopy($croppedImage, $image, 0, 0, $minX, $minY, $croppedWidth, $adjustedHeight);

        imagepng($croppedImage, $filePath);

        imagedestroy($image);
        imagedestroy($croppedImage);
    }


    public function email($authId)
    {

        $auth = AuthToReleaseInfo::findOrFail($authId);
        $medicalContact = MedicalContact::findOrFail($auth->medical_contact_id);

        // Ensure the medical contact has an email
        if (!$medicalContact->email) {
            return back()->with('error', 'Medical contact does not have an email.');
        }

        // Get the document path
        $filePath = storage_path("app/{$auth->document_path}");

        // Check if the file exists
        if (!file_exists($filePath)) {
            return back()->with('error', 'The document file does not exist.');
        }

        $templateService = app(EmailTemplateService::class);
        $message = $templateService->buildMessage('auth_to_release_info', $auth->client, [
            'medical_contact_name' => $medicalContact->name ?? '',
            'medical_contact_email' => $medicalContact->email ?? '',
        ]);

        $recipients = $message['to'] ?? [];
        if (empty($recipients)) {
            $recipients = [$medicalContact->email];
        }

        $subject = $message['subject'] ?? 'Authorization Release Document of '
            . $auth->client->first_name . ' ' . $auth->client->last_name;
        $body = $message['body'] ?? "Dear {$medicalContact->name},\n\n"
            . "Please find the attached authorization release document.\n\n"
            . "Best regards,\nSnB Behavioral Health Care";

        $cc = $message['cc'] ?? [];
        $bcc = $message['bcc'] ?? [];

        Mail::send([], [], function ($mail) use ($recipients, $cc, $bcc, $subject, $body, $filePath) {
            $mail->to($recipients)
                ->subject($subject)
                ->attach($filePath)
                ->setBody(nl2br(e($body)), 'text/html');

            if (! empty($cc)) {
                $mail->cc($cc);
            }

            if (! empty($bcc)) {
                $mail->bcc($bcc);
            }
        });

        // Update the emailed_date in database
        $auth->emailed_date = now();
        $auth->save();

        return back()->with('success', 'Authorization emailed successfully.');
    }


    public function email2($clientId, $contactId)
    {
        $auth = AuthToReleaseInfo::where('client_id', $clientId)->where('medical_contact_id', $contactId)->latest('id')->first();
        $this->email($auth->id);
    }

    public function download($authId)
    {

        $auth = AuthToReleaseInfo::findOrFail($authId);
        $this->ensureUniqueDocumentPath($auth);
        $auth->refresh();

        $filePath = storage_path('app/' . $auth->document_path);
        $downloadHeaders = [
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ];
        $downloadName = basename($filePath);

        // dd($filePath);
        if (file_exists($filePath)) {

            return response()->download($filePath, $downloadName, $downloadHeaders);
        } else {
            $latestAuth = AuthToReleaseInfo::where('client_id', $auth->client_id)
                ->where('medical_contact_id', $auth->medical_contact_id)
                ->orderByDesc('updated_at')
                ->first();

            if ($latestAuth && $latestAuth->id !== $auth->id) {
                $latestFilePath = storage_path('app/' . $latestAuth->document_path);
                if (file_exists($latestFilePath)) {
                    return response()->download($latestFilePath, basename($latestFilePath), $downloadHeaders);
                }
            }

            return redirect()->back()->with('error', 'File not found.');
        }
    }

    private function ensureUniqueDocumentPath(AuthToReleaseInfo $auth): void
    {
        if (! $this->shouldRegenerateDocument($auth)) {
            return;
        }

        $medicalContact = $auth->medicalContact ?? MedicalContact::find($auth->medical_contact_id);

        if (! $medicalContact || ! $auth->signed_date) {
            return;
        }

        $signaturePath = $this->resolveSignaturePath($auth);

        if (! $signaturePath) {
            return;
        }

        $newDocumentPath = $this->buildAuthDocumentPath($auth->client_id, $medicalContact->id);

        $auth->document_path = $this->insertSignatureIntoPDF(
            $auth->signed_date,
            $auth->client,
            $medicalContact,
            $signaturePath,
            $newDocumentPath
        );

        if ($signaturePath !== $auth->signature_path) {
            $auth->signature_path = $signaturePath;
        }

        $auth->save();
    }

    private function shouldRegenerateDocument(AuthToReleaseInfo $auth): bool
    {
        if (empty($auth->document_path)) {
            return true;
        }

        $expectedContactToken = '_' . $auth->medical_contact_id . '_';

        if (! str_contains($auth->document_path, $expectedContactToken)) {
            return true;
        }

        return AuthToReleaseInfo::where('document_path', $auth->document_path)
            ->where('id', '!=', $auth->id)
            ->exists();
    }

    private function resolveSignaturePath(AuthToReleaseInfo $auth): ?string
    {
        if ($this->signatureExists($auth->signature_path)) {
            return $auth->signature_path;
        }

        $consentSignaturePath = $auth->client->consents()
            ->whereNotNull('signature_path')
            ->orderByDesc('signed_date')
            ->orderByDesc('updated_at')
            ->first()?->signature_path;

        if ($this->signatureExists($consentSignaturePath)) {
            return $consentSignaturePath;
        }

        return null;
    }

    private function signatureExists(?string $path): bool
    {
        return !empty($path) && file_exists(storage_path('app/' . $path));
    }

    private function buildAuthDocumentPath(int $clientId, int $medicalContactId): string
    {
        return 'private/auth_to_release_info/auth_release_'
            . $clientId . '_'
            . $medicalContactId . '_'
            . \Carbon\Carbon::now()->format('Ymd_His_u') . '.pdf';
    }
}
