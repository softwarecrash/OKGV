<?php

namespace App\Services;

use App\Enums\NumberSequenceType;
use App\Models\NumberSequence;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class NumberSequenceManager
{
    public function next(
        NumberSequenceType $type,
        CarbonInterface|\DateTimeInterface|string|null $date = null,
    ): string {
        $year = (int) CarbonImmutable::parse($date ?? now())->format('Y');

        return DB::transaction(function () use ($type, $year): string {
            $sequence = NumberSequence::query()
                ->where('type', $type)
                ->lockForUpdate()
                ->firstOrFail();

            if ($sequence->reset_yearly && $sequence->last_year !== $year) {
                $sequence->next_value = 1;
                $sequence->last_year = $year;
            }

            for ($attempt = 0; $attempt < 10000; $attempt++) {
                $value = $sequence->next_value;
                $number = $this->format($sequence, $value, $year);
                $sequence->next_value = $value + 1;

                if (! DB::table($type->table())
                    ->where($type->column(), $number)
                    ->exists()) {
                    $sequence->save();

                    return $number;
                }
            }

            throw new RuntimeException(
                "No free number found for sequence {$type->value}.",
            );
        }, 5);
    }

    public function preview(NumberSequence $sequence, ?int $year = null): string
    {
        return $this->format(
            $sequence,
            $sequence->next_value,
            $year ?? (int) now()->format('Y'),
        );
    }

    private function format(NumberSequence $sequence, int $value, int $year): string
    {
        $number = str_pad(
            (string) $value,
            $sequence->padding,
            '0',
            STR_PAD_LEFT,
        );
        $result = str_replace(
            ['{JAHR}', '{NUMMER}'],
            [(string) $year, $number],
            $sequence->format,
        );

        if (mb_strlen($result) > $sequence->type->maxLength()) {
            throw new RuntimeException(
                "Generated {$sequence->type->label()} exceeds its maximum length.",
            );
        }

        return $result;
    }
}
