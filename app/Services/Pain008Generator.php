<?php

namespace App\Services;

use App\Models\PaymentBatch;
use Illuminate\Support\Collection;
use XMLWriter;

final class Pain008Generator
{
    public function generate(PaymentBatch $batch): string
    {
        $batch->loadMissing('items');
        $writer = new XMLWriter;
        $writer->openMemory();
        $writer->startDocument('1.0', 'UTF-8');
        $writer->startElement('Document');
        $writer->writeAttribute(
            'xmlns',
            'urn:iso:std:iso:20022:tech:xsd:'.$batch->message_version,
        );
        $writer->startElement('CstmrDrctDbtInitn');

        $writer->startElement('GrpHdr');
        $writer->writeElement('MsgId', $batch->message_id);
        $writer->writeElement('CreDtTm', $batch->created_at->format('Y-m-d\TH:i:s'));
        $writer->writeElement('NbOfTxs', (string) $batch->item_count);
        $writer->writeElement('CtrlSum', $batch->control_sum);
        $writer->startElement('InitgPty');
        $writer->writeElement('Nm', $batch->creditor_name);
        $writer->endElement();
        $writer->endElement();

        foreach ($batch->items->groupBy('sequence_type') as $sequenceType => $items) {
            $this->writePaymentInformation(
                $writer,
                $batch,
                $sequenceType,
                $items,
            );
        }

        $writer->endElement();
        $writer->endElement();
        $writer->endDocument();

        return $writer->outputMemory();
    }

    private function writePaymentInformation(
        XMLWriter $writer,
        PaymentBatch $batch,
        string $sequenceType,
        Collection $items,
    ): void {
        $writer->startElement('PmtInf');
        $writer->writeElement('PmtInfId', mb_substr($batch->message_id.'-'.$sequenceType, 0, 35));
        $writer->writeElement('PmtMtd', 'DD');
        $writer->writeElement('BtchBookg', $batch->batch_booking ? 'true' : 'false');
        $writer->writeElement('NbOfTxs', (string) $items->count());
        $writer->writeElement('CtrlSum', $items->reduce(
            fn (string $sum, $item): string => bcadd($sum, $item->amount, 2),
            '0.00',
        ));
        $writer->startElement('PmtTpInf');
        $writer->startElement('SvcLvl');
        $writer->writeElement('Cd', 'SEPA');
        $writer->endElement();
        $writer->startElement('LclInstrm');
        $writer->writeElement('Cd', 'CORE');
        $writer->endElement();
        $writer->writeElement('SeqTp', $sequenceType);
        $writer->endElement();
        $writer->writeElement('ReqdColltnDt', $batch->requested_collection_date->format('Y-m-d'));
        $writer->startElement('Cdtr');
        $writer->writeElement('Nm', $batch->creditor_name);
        $writer->endElement();
        $writer->startElement('CdtrAcct');
        $writer->startElement('Id');
        $writer->writeElement('IBAN', $batch->creditor_iban);
        $writer->endElement();
        $writer->endElement();
        $this->writeAgent($writer, 'CdtrAgt', $batch->creditor_bic);
        $writer->writeElement('ChrgBr', 'SLEV');
        $writer->startElement('CdtrSchmeId');
        $writer->startElement('Id');
        $writer->startElement('PrvtId');
        $writer->startElement('Othr');
        $writer->writeElement('Id', $batch->creditor_identifier);
        $writer->startElement('SchmeNm');
        $writer->writeElement('Prtry', 'SEPA');
        $writer->endElement();
        $writer->endElement();
        $writer->endElement();
        $writer->endElement();
        $writer->endElement();

        foreach ($items as $item) {
            $writer->startElement('DrctDbtTxInf');
            $writer->startElement('PmtId');
            $writer->writeElement('EndToEndId', $item->end_to_end_id);
            $writer->endElement();
            $writer->startElement('InstdAmt');
            $writer->writeAttribute('Ccy', 'EUR');
            $writer->text($item->amount);
            $writer->endElement();
            $writer->startElement('DrctDbtTx');
            $writer->startElement('MndtRltdInf');
            $writer->writeElement('MndtId', $item->mandate_reference);
            $writer->writeElement('DtOfSgntr', $item->mandate_signed_at->format('Y-m-d'));
            $writer->endElement();
            $writer->endElement();
            $this->writeAgent($writer, 'DbtrAgt', $item->debtor_bic);
            $writer->startElement('Dbtr');
            $writer->writeElement('Nm', $item->debtor_name);
            $writer->endElement();
            $writer->startElement('DbtrAcct');
            $writer->startElement('Id');
            $writer->writeElement('IBAN', $item->debtor_iban);
            $writer->endElement();
            $writer->endElement();
            $writer->startElement('RmtInf');
            $writer->writeElement('Ustrd', $item->remittance_information);
            $writer->endElement();
            $writer->endElement();
        }

        $writer->endElement();
    }

    private function writeAgent(XMLWriter $writer, string $element, ?string $bic): void
    {
        $writer->startElement($element);
        $writer->startElement('FinInstnId');
        if ($bic) {
            $writer->writeElement('BICFI', $bic);
        } else {
            $writer->startElement('Othr');
            $writer->writeElement('Id', 'NOTPROVIDED');
            $writer->endElement();
        }
        $writer->endElement();
        $writer->endElement();
    }
}
