<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Consent;
use App\Models\MedicalContact;
use Illuminate\Http\Request;
use PhpOffice\PhpWord\TemplateProcessor;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\IOFactory;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log as FacadesLog;
use PhpOffice\PhpWord\Settings;
use setasign\Fpdi\Fpdi;
use Intervention\Image\ImageManager;
use Illuminate\Support\Facades\Log;



// Set PDF Renderer
Settings::setPdfRendererName(Settings::PDF_RENDERER_MPDF);
Settings::setPdfRendererPath(base_path('vendor/mpdf/mpdf'));


class ConsentController extends Controller
{
    // Display all clients and their consents
    public function index()
    {
        $clients = Client::with('consents')->get();
        return view('consents.index', compact('clients'));
    }

    public function create(Request $request)
    {
        $clientId = $request->query('client_id');
        if ($clientId) {
            $client = Client::findOrFail($clientId);
            return view('consents.create', compact('client'));
        } else {
            $clients = Client::all();  // Fetch all clients for dropdown
            return view('consents.create', compact('clients'));
        }
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_address' => 'nullable|string|max:255',
            'emergency_contact_cell' => 'nullable|string|max:20',
            'emergency_contact_home' => 'nullable|string|max:20',
            'emergency_contact_relationship' => 'nullable|string|max:100',
            'purpose_of_release_medical_info' => 'nullable|string|max:100',
            'purpose_of_release_client_location' => 'nullable|string|max:100',
            'interpreter' => 'nullable|in:yes,but,no',
            'interpreter_language' => 'nullable|string|max:100',
            'consent_to_electronic_communication' => 'boolean',
            'appt_reminder_phone' => 'boolean',
            'appt_reminder_email' => 'boolean',
            'appt_reminder_text' => 'boolean',
            'health_plan' => 'nullable|string|max:100',
        ]);
        // dd($request->has('consent_to_electronic_communication') && $request->has('appt_reminder_phone'));
        $validatedData['consent_to_electronic_communication'] = $request->has('consent_to_electronic_communication');
        $validatedData['appt_reminder_phone'] = $request->has('consent_to_electronic_communication') && $request->has('appt_reminder_phone');
        $validatedData['appt_reminder_email'] = $request->has('consent_to_electronic_communication') && $request->has('appt_reminder_email');
        $validatedData['appt_reminder_text'] = $request->has('consent_to_electronic_communication') && $request->has('appt_reminder_text');
        // dd($validatedData);
        $consent = Consent::create([
            'client_id' => $validatedData['client_id'],
            'status' => 'complete',
            'emergency_contact_name' => $validatedData['emergency_contact_name'],
            'emergency_contact_address' => $validatedData['emergency_contact_address'],
            'emergency_contact_cell' => $validatedData['emergency_contact_cell'],
            'emergency_contact_home' => $validatedData['emergency_contact_home'],
            'emergency_contact_relationship' => $validatedData['emergency_contact_relationship'],
            'purpose_of_release_medical_info' => $validatedData['purpose_of_release_medical_info'],
            'purpose_of_release_client_location' => $validatedData['purpose_of_release_client_location'],
            'interpreter' => $validatedData['interpreter'],
            'interpreter_language' => $validatedData['interpreter_language'],
            'consent_to_electronic_communication' => $validatedData['consent_to_electronic_communication'],
            'appt_reminder_phone' => $validatedData['appt_reminder_phone'],
            'appt_reminder_email' => $validatedData['appt_reminder_email'],
            'appt_reminder_text' => $validatedData['appt_reminder_text'],
            'health_plan' => $validatedData['health_plan'],
        ]);
        // dd($consent);
        $this->generatePDF($consent->id);

        return redirect()->route('consents.index', $request->client_id)
                        ->with('success', 'Consent completed successfully.');
    }

    public function edit($id)
    {
        $consent = Consent::findOrFail($id);
        $client = $consent->client;
        return view('consents.edit', compact('consent', 'client'));
    }

    public function update(Request $request, $id)
    {
        $consent = Consent::findOrFail($id);

        $validatedData = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_address' => 'nullable|string|max:255',
            'emergency_contact_cell' => 'nullable|string|max:20',
            'emergency_contact_home' => 'nullable|string|max:20',
            'emergency_contact_relationship' => 'nullable|string|max:100',
            'purpose_of_release_medical_info' => 'nullable|string|max:100',
            'purpose_of_release_client_location' => 'nullable|string|max:100',
            'interpreter' => 'nullable|in:yes,but,no',
            'interpreter_language' => 'nullable|string|max:100',
            'consent_to_electronic_communication' => 'boolean',
            'appt_reminder_phone' => 'boolean',
            'appt_reminder_email' => 'boolean',
            'appt_reminder_text' => 'boolean',
            'health_plan' => 'nullable|string|max:100',
        ]);
        // dd($request->has('consent_to_electronic_communication') && $request->has('appt_reminder_phone'));
        $validatedData['consent_to_electronic_communication'] = $request->has('consent_to_electronic_communication');
        $validatedData['appt_reminder_phone'] = $request->has('consent_to_electronic_communication') && $request->has('appt_reminder_phone');
        $validatedData['appt_reminder_email'] = $request->has('consent_to_electronic_communication') && $request->has('appt_reminder_email');
        $validatedData['appt_reminder_text'] = $request->has('consent_to_electronic_communication') && $request->has('appt_reminder_text');
        $validatedData['status'] = 'complete';
        $validatedData['signed_date'] = NULL;
        $consent->update($validatedData);
        $this->generatePDF($id);
        return redirect()->route('consents.index')->with('success', 'Consent updated successfully.');
    }

    public function regenerate(Request $request, $id)
    {
        $consent = Consent::findOrFail($id);

        $this->generatePDF($id);
        if($consent->signature_path)
            $this->insertSignatureIntoPDF($id,
                                        storage_path('app/' . $consent->signature_path),
                                        storage_path('app/' . $consent->initial_path));

        return redirect()->route('consents.index')->with('success', 'Consent updated and regenerated successfully.');
    }

    public function updateAndRegenerate(Request $request, $id)
    {
        $consent = Consent::findOrFail($id);

        $this->update($request, $id);
        $this->generatePDF($id);
        if($consent->signature_path)
            $this->insertSignatureIntoPDF($id,
                                        storage_path('app/' . $consent->signature_path),
                                        storage_path('app/' . $consent->initial_path));

        return redirect()->route('consents.index')->with('success', 'Consent updated and regenerated successfully.');
    }


    // Show form to sign consent for a client
    public function showSignForm($consentId)
    {
        $consent = Consent::findOrFail($consentId);
        $client = $consent->client;
        $medicalContacts = MedicalContact::where('status', 'active')->get();
        return view('consents.sign', compact('client', 'consent', 'medicalContacts'));
    }

    // Handle signing and generate document
    public function generatePDF($consentId)
    {
        // dd($consentId);
        $consent = Consent::findOrFail($consentId);
        $client = $consent->client;

        // Load the existing PDF
        $pdf = new Fpdi();
        $totalPages = $pdf->setSourceFile(storage_path('app/private/templates/consent-forms-template.pdf'));

        // Pages where information should be written
        $pagesToModify = [1, 3, 5, 6, 10, 12, 14, 17, 19, 20, 22, 26, 28, 32, 34, 38, 39, 48, 50, 53, 54];

        // Loop through each page to import and modify specific pages
        for ($pageNo = 1; $pageNo <= $totalPages; $pageNo++) {
            $pdf->AddPage('P', 'Letter');  // Keep original Letter size (216mm x 279mm)
            $templateId = $pdf->importPage($pageNo);
            $pdf->useTemplate($templateId, 0, 0);

            // Set font: Times New Roman, size 12
            if ($pageNo < 56)
                $pdf->SetFont('Times', '', 12);
            else
                $pdf->SetFont('Arial', '', 10);

            $pdf->SetTextColor(0, 0, 0);  // Black color

            // Write info only on specified pages
            if (in_array($pageNo, $pagesToModify))
            {

                // Positioning based on page number
                switch ($pageNo)
                {
                    case 1:
                        $pdf->SetXY(50, 30);
                        $pdf->Write(0, $client->last_name . ', ' . $client->first_name);

                        $pdf->SetXY(50, 37);
                        $pdf->Write(0, $client->mrn);

                        $pdf->SetXY(50, 44.5);
                        $pdf->Write(0, Carbon::parse($client->date_of_birth)->format('m/d/Y'));
                        break;

                    case 3:
                        $pdf->SetXY(50, 55);
                        $pdf->Write(0, $client->last_name . ', ' . $client->first_name);

                        $pdf->SetXY(50, 62.5);
                        $pdf->Write(0, $client->mrn);

                        $pdf->SetXY(50, 70);
                        $pdf->Write(0, Carbon::parse($client->date_of_birth)->format('m/d/Y'));
                        break;

                    case 54:
                        $pdf->SetXY(50, 49.5);
                        $pdf->Write(0, $client->last_name . ', ' . $client->first_name);

                        $pdf->SetXY(50, 57);
                        $pdf->Write(0, $client->mrn);

                        $pdf->SetXY(50, 64.5);
                        $pdf->Write(0, Carbon::parse($client->date_of_birth)->format('m/d/Y'));
                        break;

                    default:
                        $pdf->SetXY(50, 28);
                        $pdf->Write(0, $client->last_name . ', ' . $client->first_name);

                        $pdf->SetXY(50, 35.5);
                        $pdf->Write(0, $client->mrn);

                        $pdf->SetXY(50, 43);
                        $pdf->Write(0, Carbon::parse($client->date_of_birth)->format('m/d/Y'));
                        break;
                }


            }

            switch ($pageNo)
            {
                case 4:
                    $pdf->SetXY(68, 39);
                    $pdf->Write(0, $consent->emergency_contact_name);

                    $pdf->SetXY(68, 46);
                    $pdf->Write(0, $consent->emergency_contact_address);

                    $pdf->SetXY(68, 53);
                    $pdf->Write(0, $consent->emergency_contact_cell);

                    $pdf->SetXY(68, 59);
                    $pdf->Write(0, $consent->emergency_contact_home);

                    $pdf->SetXY(68, 66);
                    $pdf->Write(0, $consent->emergency_contact_relationship);

                    $pdf->SetXY(71, 96.5);
                    $pdf->Write(0, $consent->purpose_of_release_medical_info);

                    $pdf->SetXY(71, 104);
                    $pdf->Write(0, $consent->purpose_of_release_client_location);
                    break;

                case 5:
                    if ($consent->interpreter == 'yes')
                    {
                        $this->drawTickMark($pdf, 30.5, 76);
                        $pdf->SetXY(140, 84.5);
                        $pdf->Write(0, $consent->interpreter_language);
                    }
                    elseif ($consent->interpreter == 'but')
                        $this->drawTickMark($pdf, 30.5, 90.5);
                    else
                        $this->drawTickMark($pdf, 30.5, 105);

                    break;

                case 9:
                    if ($consent->consent_to_electronic_communication)
                    {
                        $this->drawTickMark($pdf, 102, 112);
                        if ($consent->appt_reminder_phone)
                            $this->drawTickMark($pdf, 38.5, 128);
                        if ($consent->appt_reminder_email)
                            $this->drawTickMark($pdf, 102, 128);
                        if ($consent->appt_reminder_text)
                            $this->drawTickMark($pdf, 127.5, 128);
                    }
                    else
                        $this->drawTickMark($pdf, 127.5, 112);
                    break;

                case 38:
                    $pdf->SetXY(25.5, 140.5);
                    $pdf->Write(0, $consent->client->levelOfCareHistory->first()->levelOfCare?->display_name ?? '');
                    break;

                case 56:
                    $pdf->SetXY(16, 101);
                    $pdf->Write(0, $consent->client->first_name . ' ' . $consent->client->last_name);

                    $pdf->SetXY(110.5, 112);
                    $pdf->Write(0, $consent->client->carelon_id);

                    $pdf->SetXY(175, 112);
                    $pdf->Write(0, Carbon::parse($client->date_of_birth)->format('m/d/Y'));

                    $pdf->SetXY(38.5, 119.5);
                    $pdf->Write(0, $consent->client->phone);

                    $pdf->SetXY(123.5, 119.5);
                    $pdf->Write(0, $consent->health_plan);

                    break;

                case 57:
                    $pdf->SetXY(13, 222);
                    $pdf->Write(0, $consent->client->first_name . ' ' . $consent->client->last_name);
            }

        }

        // Save the output file
        // $outputPath = storage_path('app/private/consents/consent_form_' . $client->id . '.pdf');
        // $pdf->Output('F', $outputPath);
        $documentPath = 'private/consents/consent_form_' . $client->id . '_' . \Carbon\Carbon::now()->format('Ymd_His') . '.pdf';
        $outputPath = storage_path('app/' . $documentPath);
        $pdf->Output('F', $outputPath);


        // Update consent record with PDF path
        $consent->document_path = $documentPath;
        $consent->save();
        // dd($outputPath);
        // Return the PDF for download
        // return response()->download($outputPath);
    }

    private function drawTickMark($pdf, $x, $y, $size = 3)
    {
        // Set the line width relative to the tick size
        $pdf->SetLineWidth(0.5);

        // Calculate coordinates for the tick mark
        $x1 = $x;
        $y1 = $y + ($size / 2);  // Bottom left start point
        $x2 = $x + ($size / 3);  // Midpoint of the tick
        $y2 = $y + $size;        // Bottom right
        $x3 = $x + $size;        // Top right end point
        $y3 = $y;                // Top of the tick

        // Draw the first line (the downward stroke)
        $pdf->Line($x1, $y1, $x2, $y2);

        // Draw the second line (the upward stroke)
        $pdf->Line($x2, $y2, $x3, $y3);
    }


    public function signConsent(Request $request, $consentId)
    {

        $test = $request->validate([
            'signed_date' => 'required|date',
            'signature' => 'required|string',  // This will receive the base64-encoded signature
            'generate_medical_contacts' => 'array', // Ensure it is an array
            'generate_medical_contacts.*' => 'exists:medical_contacts,id', // Each value must exist in the medical_contacts table
            'email_medical_contacts' => 'array', // Ensure it is an array
            'email_medical_contacts.*' => 'exists:medical_contacts,id', // Each value must exist in the medical_contacts table
        ]);

        // Update Consent Status and Signed Date
        $consent = Consent::findOrFail($consentId);
        $consent->status = 'signed';
        $consent->signed_date = $request->signed_date;
        $consent->save();

        // Process Signature Image
        $signatureBase64 = $request->signature;
        $signatureImage = $this->processSignature($signatureBase64, $consent->id);

        $initialsBase64 = $request->initials;
        $initialsImage = $this->processInitials($initialsBase64, $consent->id);

        // Insert Signature into Existing PDF
        $this->insertSignatureIntoPDF($consent->id, $signatureImage, $initialsImage);

        $generateContacts = $request->input('generate_medical_contacts', []);
        $emailContacts = $request->input('email_medical_contacts', []);

        foreach ($generateContacts as $contactId)
        {
            $medicalContact = MedicalContact::find($contactId);
            if ($medicalContact)
                app(AuthToReleaseInfoController::class)->sign($request, $consent->client, $medicalContact);
        }

        // foreach ($emailContacts as $contactId)
        // {
        //     app(AuthToReleaseInfoController::class)->email2($consent->client->id, $contactId);
        // }

        return redirect()->route('consents.index', $consent->client_id)->with('success', 'Consent signed successfully.');
    }

    private function processSignature($base64Signature, $consentId)
    {
        // Remove the data URL scheme prefix if it exists
        $base64String = preg_replace('#^data:image/\w+;base64,#i', '', $base64Signature);

        // Decode the base64 string
        $imageData = base64_decode($base64String);

        $path = 'private/signatures/signature_' . $consentId . '_' . \Carbon\Carbon::now()->format('Ymd_His') . '.png';
        // Define the storage path
        $signaturePath = storage_path('app/' . $path);
        $consent = Consent::findOrFail($consentId);
        $consent->signature_path = $path;
        $consent->save();

        // Save the decoded image data to a PNG file with error handling
        file_put_contents($signaturePath, $imageData);

        $this->cropBelowSignature($signaturePath);
        return $signaturePath;
    }

    private function processInitials($base64Initials, $consentId)
    {
        // Remove the data URL scheme prefix if it exists
        $base64String = preg_replace('#^data:image/\w+;base64,#i', '', $base64Initials);

        // Decode the base64 string
        $imageData = base64_decode($base64String);

        $path = 'private/initials/initial_' . $consentId . '_' . \Carbon\Carbon::now()->format('Ymd_His') . '.png';
        // Define the storage path
        $initialPath = storage_path('app/' . $path);
        $consent = Consent::findOrFail($consentId);
        $consent->initial_path = $path;
        $consent->save();

        // Save the decoded image data to a PNG file with error handling
        file_put_contents($initialPath, $imageData);

        $this->cropBelowSignature($initialPath);
        return $initialPath;
    }

    private function insertSignatureIntoPDF($consentId, $signaturePath, $initialsPath)
    {
        $consent = Consent::findOrFail($consentId);
        $pdfPath = storage_path('app/' . $consent->document_path);
        $tempPdfPath = storage_path('app/private/consents/temp_consent_form_' . $consentId . '.pdf');

        $pdf = new Fpdi();
        $pageCount = $pdf->setSourceFile($pdfPath);
        // dd($signaturePath);
        // Get original signature dimensions
        list($originalSignatureWidth, $originalSignatureHeight) = getimagesize($signaturePath);

        // Convert pixels to mm
        $signatureWidthMM = $originalSignatureWidth / 3.78;
        $signatureHeightMM = $originalSignatureHeight / 3.78;

        // Define signature X coordinates
        $signatureLineStartX = 25;
        $signatureLineEndX = 108;
        $dateX = 151.5; // Date position

        // Calculate centered X-coordinate for signature
        $targetX = ($signatureLineEndX - $signatureLineStartX) / 2 + $signatureLineStartX - $signatureWidthMM / 2;

        // Define positions for signature and initials
        $signaturePositions = [
            2 => 163,
            4 => 159,
            5 => 178,
            9 => 209,
            11 => 95,
            13 => 102,
            16 => 122,
            18 => 134.5,
            19 => 211.5,
            21 => 234.5,
            25 => 167,
            27 => 125.5,
            31 => 158.5,
            33 => 102.5,
            37 => 176.5,
            38 => 217.5,
            47 => 159,
            49 => 145.5,
            52 => 172.5,
            53 => 143,
            55 => 187,
            57 => 205,
        ];

        $datePositions = [
            2 => 162,
            4 => 158,
            5 => 177,
            9 => 208,
            11 => 94,
            13 => 101,
            16 => 121,
            18 => 133.5,
            19 => 210.5,
            21 => 233.5,
            25 => 166,
            27 => 124.5,
            31 => 157.5,
            33 => 101.5,
            37 => 175.5,
            38 => 216.5,
            47 => 158,
            49 => 144.5,
            52 => 171.5,
            53 => 142,
            55 => 186,
            57 => 204,
        ];

        // Define initials positions (Bottom-Left corner)
        $initialsPositions = [
            57 => [
                ['x' => 12.7, 'y' => 47.5],  // First initials
                ['x' => 12.7, 'y' => 55.5], // Second initials
                ['x' => 12.7, 'y' => 75],  // Third initials
                ['x' => 12.7, 'y' => 83],  // Fourth initials
            ]
        ];

        // Process initials image
        list($initialsWidth, $initialsHeight) = getimagesize($initialsPath);
        $initialsWidthMM = $initialsWidth / 3.78;
        $initialsHeightMM = $initialsHeight / 3.78;

        // Scale initials if they exceed max width/height
        $maxInitialsWidth = 8.6;
        $maxInitialsHeight = 6.6;

        if ($initialsWidthMM > $maxInitialsWidth || $initialsHeightMM > $maxInitialsHeight) {
            $scaleFactor = min($maxInitialsWidth / $initialsWidthMM, $maxInitialsHeight / $initialsHeightMM);
            $initialsWidthMM *= $scaleFactor;
            $initialsHeightMM *= $scaleFactor;
        }

        // Copy all pages, adding signatures, initials, and dates
        foreach (range(1, $pageCount) as $pageNo) {
            $templateId = $pdf->importPage($pageNo);
            $pdf->addPage('P', 'Letter');
            $pdf->useTemplate($templateId);

            // Set size for last page (0.75 scale)
            $size = ($pageNo == 57) ? 0.75 : 1;

            // Adjusted size for signature
            $adjustedSignatureWidth = $signatureWidthMM * $size;
            $adjustedSignatureHeight = $signatureHeightMM * $size;

            // Insert signature
            if (isset($signaturePositions[$pageNo])) {
                $adjustedY = $signaturePositions[$pageNo] - $adjustedSignatureHeight;
                $pdf->Image($signaturePath, $targetX, $adjustedY, $adjustedSignatureWidth, $adjustedSignatureHeight);
            }

            // Insert signed date
            if (isset($datePositions[$pageNo])) {
                $pdf->SetFont('Times', '', 12);
                $pdf->SetTextColor(0, 0, 0);
                $pdf->SetXY($dateX, $datePositions[$pageNo]);
                $pdf->Write(0, Carbon::parse($consent->signed_date)->format('m/d/Y'));
            }

            // Insert initials (Using bottom-left alignment)
            if (isset($initialsPositions[$pageNo])) {
                foreach ($initialsPositions[$pageNo] as $coords) {
                    $adjustedInitialsY = $coords['y'] - $initialsHeightMM; // Adjust to align bottom-left
                    $pdf->Image($initialsPath, $coords['x'], $adjustedInitialsY, $initialsWidthMM, $initialsHeightMM);
                }
            }
        }

        // Save the modified PDF
        $pdf->Output('F', $tempPdfPath);
        rename($tempPdfPath, $pdfPath);
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



    // Download consent form
    public function downloadConsent($consentId)
    {
        Log::error($consentId);
        $consent = Consent::findOrFail($consentId);

        $filePath = storage_path('app/' . $consent->document_path);
        // dd($filePath);
        if (file_exists($filePath)) {
            return response()->download($filePath);
        } else {
            return redirect()->back()->with('error', 'File not found.');
        }
    }

}
