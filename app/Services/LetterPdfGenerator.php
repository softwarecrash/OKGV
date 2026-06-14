<?php

namespace App\Services;

use App\Models\Letter;
use Dompdf\Dompdf;
use Dompdf\Options;

final class LetterPdfGenerator
{
    public function render(Letter $letter): string
    {
        $options = new Options;
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');

        $pdf = new Dompdf($options);
        $pdf->loadHtml(view('letters.pdf', compact('letter'))->render(), 'UTF-8');
        $pdf->setPaper('A4');
        $pdf->render();

        return $pdf->output();
    }
}
