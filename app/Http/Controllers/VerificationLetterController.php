<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use setasign\Fpdi\Fpdi;

class VerificationLetterController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $clients = Client::all();
        return view('verification_letters.index', compact('clients'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function download(string $clientId)
    {
        $pdf = $this->generatePDF($clientId);
        return $this->downloadPDF($pdf);
    }

    public function generatePDF(string $clientId)
    {
        $client = Client::findOrFail($clientId);
        $pdf = new Fpdi();

        // Load the template
        $templatePath = storage_path('app/private/templates/verification-letters-template.pdf');
        $totalPages = $pdf->setSourceFile($templatePath);

        // Use first page as template
        $pdf->AddPage('P', 'Letter');
        $templateId = $pdf->importPage(1);
        $pdf->useTemplate($templateId, 0, 0);

        // Set font and text color
        $pdf->SetFont('Times', '', 12);
        $pdf->SetTextColor(0, 0, 0);  // Black

        // Set starting position in the top-right corner
        $pageWidth = 216; // Letter width in mm
        $x = 24.5; // Adjust as needed
        $y = 40;  // Starting Y position

        // Line spacing
        $lineSpacing = 5.75; // Slightly above 1.15 line spacing

        $lastName = $client->last_name;
        $prefix = ($client->gender === 'female') ? 'Ms.' : 'Mr.';
        $startingDate = \Carbon\Carbon::parse($client->starting_date)->format('m/d/Y');
        $currentDate = \Carbon\Carbon::now()->format('m/d/Y');
        $dob = \Carbon\Carbon::parse($client->date_of_birth)->format('m/d/Y');

        // Write client details in separate lines
        $pdf->SetXY($x, $y);
        $pdf->Write(0, "Date: " . $currentDate);

        $pdf->SetXY($x, $y + 2 * $lineSpacing);
        $pdf->Write(0, "Name: " . $prefix . ' ' . $client->first_name . ' ' . $client->last_name);

        $pdf->SetXY($x, $y + 3 * $lineSpacing);
        $pdf->Write(0, "DOB: " . $dob);

        $pdf->SetXY($x, $y + 4 * $lineSpacing);
        $pdf->Write(0, "SSN: " . ($client->ssn ?? "Not Reported"));

        $pdf->SetXY($x, $y + 5 * $lineSpacing);
        $pdf->Write(0, "Medicaid ID: " . ($client->medicaid_id ?? "Not Reported"));

        $pdf->SetXY($x, $y + 6 * $lineSpacing);
        $pdf->Write(0, "Carelon ID: " . ($client->carelon_id ?? "Not Reported"));

        $pdf->SetXY($x, $y + 11 * $lineSpacing);
        $pdf->Write(0, "To Whom It May Concern:");

        $pdf->SetXY($x, $y + 12 * $lineSpacing);


        $text = "       {$prefix} {$lastName} is currently enrolled in our Residential Treatment Program located at S&B Behavioral Health Care, 7200 Belair Rd., Baltimore, Maryland 21206 #4. {$prefix} {$lastName} was enrolled in our program on/about {$startingDate}, and has been complying since enrollment. S&B Behavioral Health Care is a state-certified treatment program for adults. It is staffed by mental health and substance abuse treatment professionals. This letter is provided as confirmation for enrollment and residential verification purposes.";
        $pdf->MultiCell($pageWidth - $x*2, 5.75, $text, 0, 'J');

        $text2 = "If you have any questions or need further information, please feel free to contact us at 410-444-4357.";
        $pdf->SetXY($x, $y + 19 * $lineSpacing);
        $pdf->MultiCell($pageWidth - $x*2, 5.75, $text2, 0, 'J');

        if ($client->profile_photo) {
            $photoPath = storage_path('app/private/' . $client->profile_photo);

            $imageY = $y - 10; // Y position for the top-right corner
            $imageWidth = 40; // Fixed width to maintain aspect ratio
            $imageX = $pageWidth - $imageWidth - $x - 2; // X position for the top-right corner
            // $pdf->Image($signaturePath, $targetX, $adjustedY, $adjustedSignatureWidth, $adjustedSignatureHeight);
            $pdf->Image($photoPath, $imageX, $imageY, $imageWidth);
        }
        // Save to storage
        $outputPath = storage_path('app/private/verification_letters/verification_letter_' . $clientId . '_' . \Carbon\Carbon::now()->format('Ymd_His') . '.pdf');
        $pdf->Output('F', $outputPath);

        return $outputPath;
    }

    public function downloadPDF(string $pdfPath)
    {
        return response()->download($pdfPath)->deleteFileAfterSend(true);
    }

}
