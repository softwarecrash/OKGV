<?php

namespace App\Services;

use App\Models\DunningNotice;
use Dompdf\Dompdf;
use Dompdf\Options;

final class DunningNoticePdfGenerator
{
    public function __construct(
        private readonly AssociationDocumentProfile $profile,
    ) {}

    public function render(DunningNotice $notice): string
    {
        $options = new Options;
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');

        $pdf = new Dompdf($options);
        $association = $this->profile->resolve($notice->association_snapshot);
        $pdf->loadHtml(view('dunning-notices.pdf', compact('notice', 'association'))->render(), 'UTF-8');
        $pdf->setPaper('A4');
        $pdf->render();

        return $pdf->output();
    }
}
