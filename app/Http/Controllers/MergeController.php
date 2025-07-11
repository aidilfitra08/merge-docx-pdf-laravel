<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use setasign\Fpdi\Fpdi;

class MergeController extends Controller
{
    public function merge(Request $request)
    {
        $request->validate([
            'documents' => 'required',
            'documents.*' => 'file|mimes:doc,docx',
        ]);

        $outputDir = storage_path('app/converted');
        $uploadDir = storage_path('app/uploads');

        if (!file_exists($outputDir)) mkdir($outputDir, 0777, true);
        if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);

        $pdfFiles = [];

        foreach ($request->file('documents') as $file) {
            $filename = $file->getClientOriginalName();
            $path = $file->storeAs('uploads', $filename);
            $docPath = storage_path('app/' . $path);

            // Convert DOCX to PDF via LibreOffice
            $cmd = 'soffice --headless -env:UserInstallation=file:///tmp/LibreOfficeProfile --convert-to pdf ' . escapeshellarg($docPath) . ' --outdir ' . escapeshellarg($outputDir);

            // $cmd = 'soffice --headless --convert-to pdf ' . escapeshellarg($docPath) . ' --outdir ' . escapeshellarg($outputDir);
            exec($cmd, $output, $returnCode);

            if ($returnCode !== 0) {
                return back()->with('error', 'Failed to convert: ' . $filename);
            }

            $pdfPath = $outputDir . '/' . pathinfo($filename, PATHINFO_FILENAME) . '.pdf';
            $pdfFiles[] = $pdfPath;
        }

        // $newData = storage_path('app/basic_data') . '/hayo1.pdf';
        // array_splice($pdfFiles, 1, 0, $newData);
        // $newData2 = storage_path('app/basic_data') . '/hayo2.pdf';
        // $pdfFiles[] = $newData2;

        // ✅ Merge PDFs using FPDI
        $mergedFile = $outputDir . '/merged-' . time() . '.pdf';
        $pdf = new Fpdi();

        foreach ($pdfFiles as $file) {
            $pageCount = $pdf->setSourceFile($file);
            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                $tplId = $pdf->importPage($pageNo);
                $size = $pdf->getTemplateSize($tplId);
                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $pdf->useTemplate($tplId);
            }
        }

        $pdf->Output($mergedFile, 'F');

        return response()->download($mergedFile);
    }

    public function convert(Request $request)
    {
        $request->validate([
            'document' => 'required|file|mimes:doc,docx',
        ]);

        $file = $request->file('document');
        $filename = $file->getClientOriginalName();
        $path = $file->storeAs('uploads', $filename);

        $docPath = storage_path('app/' . $path);
        $outputDir = storage_path('app/converted');

        // Make sure output directory exists
        if (!file_exists($outputDir)) {
            mkdir($outputDir, 0777, true);
        }

        // LibreOffice Conversion
        $cmd = 'soffice --headless --convert-to pdf ' . escapeshellarg($docPath) . ' --outdir ' . escapeshellarg($outputDir);
        exec($cmd, $output, $returnCode);

        if ($returnCode !== 0) {
            return back()->with('error', 'Failed to convert document.');
        }

        $convertedFile = $outputDir . '/' . pathinfo($filename, PATHINFO_FILENAME) . '.pdf';

        return response()->download($convertedFile);
    }
}
