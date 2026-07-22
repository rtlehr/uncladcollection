<?php

namespace App\Analytics;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use InvalidArgumentException;

final readonly class AnalyticsPeriod
{
    public function __construct(public CarbonImmutable $start, public CarbonImmutable $end)
    {
        if ($start->greaterThan($end)) {
            throw new InvalidArgumentException('Analytics period start must be before its end.');
        }
    }

    public static function fromRequest(Request $request): self
    {
        $preset = (string) $request->input('period', '30_days');
        $today = CarbonImmutable::today();

        return match ($preset) {
            '7_days' => new self($today->subDays(6)->startOfDay(), $today->endOfDay()),
            '90_days' => new self($today->subDays(89)->startOfDay(), $today->endOfDay()),
            'year_to_date' => new self($today->startOfYear(), $today->endOfDay()),
            'custom' => new self(
                CarbonImmutable::parse($request->string('start_date'))->startOfDay(),
                CarbonImmutable::parse($request->string('end_date'))->endOfDay(),
            ),
            default => new self($today->subDays(29)->startOfDay(), $today->endOfDay()),
        };
    }

    public function previous(): self
    {
        $days = $this->start->diffInDays($this->end) + 1;
        $previousEnd = $this->start->subDay()->endOfDay();

        return new self($previousEnd->subDays($days - 1)->startOfDay(), $previousEnd);
    }

    public function toArray(): array
    {
        return ['start_date' => $this->start->toDateString(), 'end_date' => $this->end->toDateString()];
    }
}
