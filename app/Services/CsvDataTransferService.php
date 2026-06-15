<?php

namespace App\Services;

use App\Enums\DataTransferType;
use App\Enums\MemberStatus;
use App\Enums\MeterReadingSource;
use App\Enums\MeterStatus;
use App\Enums\MeterType;
use App\Enums\ParcelStatus;
use App\Models\Invoice;
use App\Models\Member;
use App\Models\Meter;
use App\Models\Parcel;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class CsvDataTransferService
{
    public function __construct(
        private readonly MeterReadingManager $meterReadingManager,
    ) {}

    /**
     * @return array{created: int, updated: int}
     */
    public function import(DataTransferType $type, UploadedFile $file, User $actor): array
    {
        abort_unless($type->importable(), 404);

        $rows = $this->read($file, $type);

        $result = DB::transaction(function () use ($type, $rows): array {
            return match ($type) {
                DataTransferType::Members => $this->importMembers($rows),
                DataTransferType::Parcels => $this->importParcels($rows),
                DataTransferType::Meters => $this->importMeters($rows),
                DataTransferType::MeterReadings => $this->importMeterReadings($rows),
                DataTransferType::Invoices => throw new RuntimeException('Invoices cannot be imported.'),
            };
        });

        AuditLogger::log('csv.imported', $actor, metadata: [
            'type' => $type->value,
            ...$result,
        ]);

        return $result;
    }

    public function export(DataTransferType $type, User $actor): StreamedResponse
    {
        AuditLogger::log('csv.exported', $actor, metadata: ['type' => $type->value]);

        $filename = sprintf(
            'okgv-%s-%s.csv',
            str_replace('_', '-', $type->value),
            now()->format('Ymd-His'),
        );

        return response()->streamDownload(function () use ($type): void {
            $output = fopen('php://output', 'wb');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, $type->headers(), ';', '"', '');

            foreach ($this->exportRows($type) as $row) {
                fputcsv($output, array_map($this->safeCell(...), $row), ';', '"', '');
            }

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function template(DataTransferType $type): StreamedResponse
    {
        abort_unless($type->importable(), 404);

        return response()->streamDownload(function () use ($type): void {
            $output = fopen('php://output', 'wb');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, $type->headers(), ';', '"', '');
            fputcsv($output, $type->example(), ';', '"', '');
            fclose($output);
        }, "okgv-importvorlage-{$type->value}.csv", [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * @return list<array<string, string|null>>
     */
    private function read(UploadedFile $file, DataTransferType $type): array
    {
        $path = $file->getRealPath();
        $contents = file_get_contents($path);

        if ($contents === false || ! mb_check_encoding($contents, 'UTF-8')) {
            throw ValidationException::withMessages([
                'file' => 'Die CSV-Datei muss UTF-8-kodiert sein.',
            ]);
        }

        $firstLine = strtok($contents, "\r\n") ?: '';
        $delimiter = collect([';', ',', "\t"])
            ->sortByDesc(fn (string $candidate): int => substr_count($firstLine, $candidate))
            ->first();
        $handle = fopen($path, 'rb');
        $headers = fgetcsv($handle, separator: $delimiter, escape: '');

        if (! is_array($headers)) {
            fclose($handle);
            throw ValidationException::withMessages(['file' => 'Die CSV-Datei ist leer.']);
        }

        $headers = array_map(
            fn (string $header): string => trim(str_replace("\xEF\xBB\xBF", '', $header)),
            $headers,
        );

        if ($headers !== $type->headers()) {
            fclose($handle);
            throw ValidationException::withMessages([
                'file' => 'Die Spalten entsprechen nicht der Importvorlage. Lade die passende Vorlage herunter und übernimm deren Kopfzeile unverändert.',
            ]);
        }

        $rows = [];
        $line = 1;

        while (($values = fgetcsv($handle, separator: $delimiter, escape: '')) !== false) {
            $line++;

            if ($values === [null] || collect($values)->every(fn ($value): bool => trim((string) $value) === '')) {
                continue;
            }

            if (count($values) !== count($headers)) {
                fclose($handle);
                throw ValidationException::withMessages([
                    'file' => "Zeile {$line} enthält nicht die erwartete Anzahl an Spalten.",
                ]);
            }

            $row = array_combine($headers, array_map(
                fn ($value): ?string => trim((string) $value) === '' ? null : trim((string) $value),
                $values,
            ));
            $row['_line'] = (string) $line;
            $rows[] = $row;
        }

        fclose($handle);

        if ($rows === []) {
            throw ValidationException::withMessages([
                'file' => 'Die CSV-Datei enthält keine Datenzeilen.',
            ]);
        }

        return $rows;
    }

    /**
     * @param  list<array<string, string|null>>  $rows
     * @return array{created: int, updated: int}
     */
    private function importMembers(array $rows): array
    {
        $result = ['created' => 0, 'updated' => 0];

        foreach ($rows as $row) {
            $data = $this->validateRow($row, [
                'member_number' => ['required', 'string', 'max:50'],
                'first_name' => ['required', 'string', 'max:255'],
                'last_name' => ['required', 'string', 'max:255'],
                'street' => ['required', 'string', 'max:255'],
                'zip' => ['required', 'string', 'max:10'],
                'city' => ['required', 'string', 'max:255'],
                'phone' => ['nullable', 'string', 'max:50'],
                'mobile' => ['nullable', 'string', 'max:50'],
                'email' => ['nullable', 'email', 'max:255'],
                'joined_at' => ['required', 'date_format:Y-m-d'],
                'left_at' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:joined_at'],
                'status' => ['required', Rule::enum(MemberStatus::class)],
                'notes' => ['nullable', 'string', 'max:10000'],
            ]);
            $member = Member::query()->where('member_number', $data['member_number'])->first();
            $attributes = [
                ...$data,
                'archived_at' => $data['status'] === MemberStatus::Archived->value
                    ? ($member?->archived_at ?? now())
                    : null,
            ];

            if ($member) {
                $member->update($attributes);
                $result['updated']++;
            } else {
                Member::create($attributes);
                $result['created']++;
            }
        }

        return $result;
    }

    /**
     * @param  list<array<string, string|null>>  $rows
     * @return array{created: int, updated: int}
     */
    private function importParcels(array $rows): array
    {
        $result = ['created' => 0, 'updated' => 0];

        foreach ($rows as $row) {
            $data = $this->validateRow($row, [
                'parcel_number' => ['required', 'string', 'max:50'],
                'area_sqm' => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'max:99999999.99'],
                'status' => ['required', Rule::enum(ParcelStatus::class)],
                'location_description' => ['nullable', 'string', 'max:255'],
                'notes' => ['nullable', 'string', 'max:10000'],
            ]);
            $parcel = Parcel::query()->where('parcel_number', $data['parcel_number'])->first();

            if ($parcel) {
                $parcel->update($data);
                $result['updated']++;
            } else {
                Parcel::create($data);
                $result['created']++;
            }
        }

        return $result;
    }

    /**
     * @param  list<array<string, string|null>>  $rows
     * @return array{created: int, updated: int}
     */
    private function importMeters(array $rows): array
    {
        $created = 0;

        foreach ($rows as $row) {
            $data = $this->validateRow($row, [
                'parcel_number' => ['required', 'string', 'exists:parcels,parcel_number'],
                'type' => ['required', Rule::enum(MeterType::class)],
                'meter_number' => ['required', 'string', 'max:100', 'unique:meters,meter_number'],
                'installed_at' => ['required', 'date_format:Y-m-d'],
                'removed_at' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:installed_at'],
                'start_reading' => ['required', 'numeric', 'decimal:0,4', 'min:0'],
                'end_reading' => ['nullable', 'numeric', 'decimal:0,4', 'gte:start_reading'],
                'status' => ['required', Rule::enum(MeterStatus::class)],
                'notes' => ['nullable', 'string', 'max:10000'],
            ]);
            $status = MeterStatus::from($data['status']);

            if ($status === MeterStatus::Active && ($data['removed_at'] || $data['end_reading'])) {
                $this->rowError($row, 'Aktive Zähler dürfen kein Ausbau-Datum und keinen Endstand enthalten.');
            }

            if (in_array($status, [MeterStatus::Replaced, MeterStatus::Removed], true)
                && (! $data['removed_at'] || $data['end_reading'] === null)) {
                $this->rowError($row, 'Nicht aktive Zähler benötigen Ausbau-Datum und Endstand.');
            }

            if (($data['removed_at'] === null) !== ($data['end_reading'] === null)) {
                $this->rowError($row, 'Ausbau-Datum und Endstand müssen gemeinsam angegeben werden.');
            }

            $parcel = Parcel::query()->where('parcel_number', $data['parcel_number'])->firstOrFail();

            if ($status === MeterStatus::Active && Meter::query()
                ->where('parcel_id', $parcel->id)
                ->where('type', $data['type'])
                ->where('status', MeterStatus::Active)
                ->exists()) {
                $this->rowError($row, 'Für diese Parzelle existiert bereits ein aktiver Zähler dieses Typs.');
            }

            unset($data['parcel_number']);
            Meter::create([...$data, 'parcel_id' => $parcel->id]);
            $created++;
        }

        return ['created' => $created, 'updated' => 0];
    }

    /**
     * @param  list<array<string, string|null>>  $rows
     * @return array{created: int, updated: int}
     */
    private function importMeterReadings(array $rows): array
    {
        usort($rows, fn (array $left, array $right): int => [
            $left['meter_number'], $left['reading_date'],
        ] <=> [
            $right['meter_number'], $right['reading_date'],
        ]);
        $created = 0;

        foreach ($rows as $row) {
            $data = $this->validateRow($row, [
                'meter_number' => ['required', 'string', 'exists:meters,meter_number'],
                'reading_value' => ['required', 'numeric', 'decimal:0,4', 'min:0'],
                'reading_date' => ['required', 'date_format:Y-m-d'],
                'source' => ['required', Rule::in([MeterReadingSource::Import->value])],
                'notes' => ['nullable', 'string', 'max:10000'],
            ]);
            $meter = Meter::query()->where('meter_number', $data['meter_number'])->firstOrFail();
            unset($data['meter_number']);

            try {
                $this->meterReadingManager->create([
                    ...$data,
                    'meter_id' => $meter->id,
                    'photo_path' => null,
                ]);
            } catch (ValidationException $exception) {
                $this->rowError($row, collect($exception->errors())->flatten()->first());
            }
            $created++;
        }

        return ['created' => $created, 'updated' => 0];
    }

    /**
     * @param  array<string, string|null>  $row
     * @param  array<string, list<mixed>>  $rules
     * @return array<string, mixed>
     */
    private function validateRow(array $row, array $rules): array
    {
        $line = $row['_line'];
        unset($row['_line']);
        $validator = Validator::make($row, $rules);

        if ($validator->fails()) {
            throw ValidationException::withMessages([
                'file' => "Zeile {$line}: ".$validator->errors()->first(),
            ]);
        }

        return $validator->validated();
    }

    /**
     * @param  array<string, string|null>  $row
     */
    private function rowError(array $row, string $message): never
    {
        throw ValidationException::withMessages([
            'file' => "Zeile {$row['_line']}: {$message}",
        ]);
    }

    /**
     * @return iterable<list<string|null>>
     */
    private function exportRows(DataTransferType $type): iterable
    {
        return match ($type) {
            DataTransferType::Members => $this->memberRows(),
            DataTransferType::Parcels => $this->parcelRows(),
            DataTransferType::Meters => $this->meterRows(),
            DataTransferType::MeterReadings => $this->meterReadingRows(),
            DataTransferType::Invoices => $this->invoiceRows(),
        };
    }

    private function memberRows(): iterable
    {
        foreach (Member::query()->orderBy('member_number')->cursor() as $member) {
            yield [
                $member->member_number, $member->first_name, $member->last_name,
                $member->street, $member->zip, $member->city, $member->phone,
                $member->mobile, $member->email, $member->joined_at->toDateString(),
                $member->left_at?->toDateString(), $member->status->value, $member->notes,
            ];
        }
    }

    private function parcelRows(): iterable
    {
        foreach (Parcel::query()->orderBy('parcel_number')->cursor() as $parcel) {
            yield [
                $parcel->parcel_number, $parcel->area_sqm, $parcel->status->value,
                $parcel->location_description, $parcel->notes,
            ];
        }
    }

    private function meterRows(): iterable
    {
        foreach (Meter::query()->with('parcel')->orderBy('meter_number')->cursor() as $meter) {
            yield [
                $meter->parcel->parcel_number, $meter->type->value,
                $meter->meter_number, $meter->installed_at->toDateString(),
                $meter->removed_at?->toDateString(), $meter->start_reading,
                $meter->end_reading, $meter->status->value, $meter->notes,
            ];
        }
    }

    private function meterReadingRows(): iterable
    {
        foreach (Meter::query()->with([
            'readings' => fn ($query) => $query->with('corrections')->orderBy('reading_date'),
        ])->orderBy('meter_number')->cursor() as $meter) {
            foreach ($meter->readings as $reading) {
                yield [
                    $meter->meter_number, $reading->effective_reading_value,
                    $reading->reading_date->toDateString(), $reading->source->value,
                    $reading->notes,
                ];
            }
        }
    }

    private function invoiceRows(): iterable
    {
        foreach (Invoice::query()->with([
            'billingPeriod', 'recipients', 'items.parcel',
        ])->orderBy('invoice_number')->cursor() as $invoice) {
            $recipients = $invoice->recipients
                ->map(fn ($recipient): string => "{$recipient->member_number}: {$recipient->full_name}")
                ->implode(' | ');

            if ($invoice->items->isEmpty()) {
                yield $this->invoiceRow($invoice, $recipients);

                continue;
            }

            foreach ($invoice->items as $item) {
                yield $this->invoiceRow($invoice, $recipients, $item);
            }
        }
    }

    /**
     * @return list<string|null>
     */
    private function invoiceRow(Invoice $invoice, string $recipients, mixed $item = null): array
    {
        return [
            $invoice->invoice_number, $invoice->billingPeriod->name,
            $invoice->status->value, $invoice->payment_status->value,
            $invoice->issued_at->toDateString(), $invoice->due_at->toDateString(),
            $invoice->paid_at?->toDateString(), $invoice->total_amount, $recipients,
            $item?->code, $item?->description, $item?->parcel?->parcel_number,
            $item?->quantity, $item?->unit_price, $item?->total_amount,
        ];
    }

    private function safeCell(mixed $value): mixed
    {
        if (is_string($value) && preg_match('/\A[=+\-@]/', $value) === 1) {
            return "'{$value}";
        }

        return $value;
    }
}
