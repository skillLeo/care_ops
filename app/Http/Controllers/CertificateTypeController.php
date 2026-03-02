<?php

namespace App\Http\Controllers;

use App\Models\CertificateType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;

class CertificateTypeController extends Controller
{
    public function index()
    {
        $certificateTypes = CertificateType::orderBy('name')->get();

        return view('certificate-types.index', compact('certificateTypes'));
    }

    public function create()
    {
        $fontOptions = CertificateType::fontOptions();

        return view('certificate-types.create', compact('fontOptions'));
    }

    public function store(Request $request)
    {
        $fontOptions = CertificateType::fontOptions();

        $request->validate([
            'name' => 'required|string|max:255|unique:certificate_types,name',
            'template' => 'required|file|mimes:pdf',
            'name_x' => 'required|numeric',
            'name_y' => 'required|numeric',
            'date_x' => 'required|numeric',
            'date_y' => 'required|numeric',
            'name_font' => 'nullable|string|in:' . implode(',', array_keys($fontOptions)),
        ]);

        $templatePath = $this->storeTemplate($request);

        CertificateType::create([
            'name' => $request->input('name'),
            'template_path' => $templatePath,
            'name_x' => $request->input('name_x'),
            'name_y' => $request->input('name_y'),
            'date_x' => $request->input('date_x'),
            'date_y' => $request->input('date_y'),
            'name_font' => $request->input('name_font') ?: 'brittanysignature',
        ]);

        return redirect()
            ->route('certificate-types.index')
            ->with('success', 'Certificate type created.');
    }

    public function edit(CertificateType $certificateType)
    {
        $fontOptions = CertificateType::fontOptions();

        return view('certificate-types.edit', compact('certificateType', 'fontOptions'));
    }

    public function update(Request $request, CertificateType $certificateType)
    {
        $fontOptions = CertificateType::fontOptions();

        $request->validate([
            'name' => 'required|string|max:255|unique:certificate_types,name,' . $certificateType->id,
            'template' => 'nullable|file|mimes:pdf',
            'name_x' => 'required|numeric',
            'name_y' => 'required|numeric',
            'date_x' => 'required|numeric',
            'date_y' => 'required|numeric',
            'name_font' => 'nullable|string|in:' . implode(',', array_keys($fontOptions)),
        ]);

        $update = [
            'name' => $request->input('name'),
            'name_x' => $request->input('name_x'),
            'name_y' => $request->input('name_y'),
            'date_x' => $request->input('date_x'),
            'date_y' => $request->input('date_y'),
            'name_font' => $request->input('name_font') ?: 'brittanysignature',
        ];

        if ($request->hasFile('template')) {
            $this->deleteTemplate($certificateType);
            $update['template_path'] = $this->storeTemplate($request);
        }

        $certificateType->update($update);

        return redirect()
            ->route('certificate-types.index')
            ->with('success', 'Certificate type updated.');
    }

    public function destroy(CertificateType $certificateType)
    {
        if ($certificateType->certificates()->exists()) {
            return redirect()
                ->route('certificate-types.index')
                ->with('error', 'Cannot delete a certificate type that is in use.');
        }

        $this->deleteTemplate($certificateType);
        $certificateType->delete();

        return redirect()
            ->route('certificate-types.index')
            ->with('success', 'Certificate type deleted.');
    }

    public function preview(CertificateType $certificateType)
    {
        return view('certificate-types.preview', compact('certificateType'));
    }

    public function previewPdf(CertificateType $certificateType)
    {
        $pdf = $this->buildCertificatePdf($certificateType, 'Client Name', 'mm/dd/yyyy');

        return response($pdf->Output('', Destination::STRING_RETURN), 200, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    private function storeTemplate(Request $request): string
    {
        $file = $request->file('template');
        $filename = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))
            . '-' . now()->format('YmdHis') . '.' . $file->getClientOriginalExtension();

        return $file->storeAs('private/certificate-types', $filename, 'local');
    }

    private function deleteTemplate(CertificateType $certificateType): void
    {
        if ($certificateType->template_path && Storage::disk('local')->exists($certificateType->template_path)) {
            Storage::disk('local')->delete($certificateType->template_path);
        }
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
}
