<?php

namespace App\Services;

use App\Models\GardenInspection;
use Dompdf\Dompdf;
use Dompdf\Options;

final class GardenInspectionPdfGenerator
{
    public function render(GardenInspection $inspection): string
    {
        $inspection->loadMissing(['findings.parcel', 'findings.responsibleMember', 'findings.task']);
        $options = new Options;
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');
        $pdf = new Dompdf($options);
        $pdf->loadHtml(view('garden-inspections.pdf', compact('inspection'))->render(), 'UTF-8');
        $pdf->setPaper('A4');
        $pdf->render();

        return $pdf->output();
    }
}
