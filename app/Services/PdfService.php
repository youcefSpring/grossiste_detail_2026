<?php

namespace App\Services;

use Illuminate\Support\Facades\View;
use Mpdf\Mpdf;
use Symfony\Component\HttpFoundation\Response;

/**
 * Renders a Blade view to PDF.
 *
 * mPDF rather than dompdf: it shapes and joins Arabic letters correctly and
 * honours dir="rtl". dompdf renders Arabic as disconnected, reversed glyphs.
 */
class PdfService
{
    public function download(string $view, array $data, string $filename, string $orientation = 'P'): Response
    {
        return $this->respond($view, $data, $filename, $orientation, 'attachment');
    }

    /** Opens in the browser's viewer instead of saving. */
    public function stream(string $view, array $data, string $filename, string $orientation = 'P'): Response
    {
        return $this->respond($view, $data, $filename, $orientation, 'inline');
    }

    private function respond(string $view, array $data, string $filename, string $orientation, string $disposition): Response
    {
        $pdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => $orientation === 'L' ? 'A4-L' : 'A4',
            'orientation' => $orientation,
            'margin_top' => 12,
            'margin_bottom' => 14,
            'margin_left' => 10,
            'margin_right' => 10,
            'default_font' => 'dejavusans',   // ships with mPDF and covers Arabic
            'tempDir' => storage_path('app/mpdf'),
        ]);

        if (app()->getLocale() === 'ar') {
            $pdf->SetDirectionality('rtl');
        }

        $pdf->SetTitle($filename);
        $pdf->SetAuthor((string) settings('shop.name'));

        // Page numbers, in the reader's language.
        $pdf->SetHTMLFooter(
            '<div style="text-align:center;font-size:8pt;color:#94a3b8;">{PAGENO} / {nbpg}</div>'
        );

        $pdf->WriteHTML(View::make($view, $data + ['forPdf' => true])->render());

        // 'S' returns the document so Laravel owns the response, rather than
        // letting mPDF echo it and send headers behind the framework's back.
        return response($pdf->Output($filename, 'S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('%s; filename="%s"', $disposition, $filename),
        ]);
    }
}
