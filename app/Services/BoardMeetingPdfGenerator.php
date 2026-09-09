<?php

namespace App\Services;

use App\Models\BoardMeeting;
use Dompdf\Dompdf;
use Dompdf\Options;

final class BoardMeetingPdfGenerator
{
    public function __construct(private readonly AssociationDocumentProfile $profile) {}

    public function render(BoardMeeting $meeting): string
    {
        $meeting->load(['agendaItems', 'resolutions.agendaItem']);
        $options = new Options;
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');
        $pdf = new Dompdf($options);
        $association = $this->profile->resolve($meeting->association_snapshot);
        $pdf->loadHtml(view('board-meetings.pdf', compact('meeting', 'association'))->render(), 'UTF-8');
        $pdf->setPaper('A4');
        $pdf->render();

        return $pdf->output();
    }
}
