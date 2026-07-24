<?php

namespace App\Services\Support;

use App\Enums\SupportTicketStatus;
use App\Models\SupportTicket;
use App\Models\SupportTicketCategory;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class SupportReportingService
{
    public function report(CarbonInterface $from, CarbonInterface $to): array
    {
        $query = SupportTicket::query()->whereBetween('created_at', [$from, $to]);

        $created = (clone $query)->count();
        $resolved = (clone $query)->whereNotNull('resolved_at')->count();
        $closed = (clone $query)->whereNotNull('closed_at')->count();

        $firstResponseMinutes = $this->averageMinutes(
            (clone $query)->whereNotNull('first_response_at'),
            'created_at',
            'first_response_at',
        );

        $resolutionMinutes = $this->averageMinutes(
            (clone $query)->whereNotNull('resolved_at'),
            'created_at',
            'resolved_at',
        );

        return [
            'period' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'summary' => [
                'created' => $created,
                'resolved' => $resolved,
                'closed' => $closed,
                'resolution_rate_percent' => $created > 0 ? round(($resolved / $created) * 100, 1) : 0.0,
                'average_first_response_minutes' => $firstResponseMinutes,
                'average_resolution_minutes' => $resolutionMinutes,
                'backlog' => SupportTicket::query()
                    ->whereNotIn('status', [SupportTicketStatus::Resolved, SupportTicketStatus::Closed, SupportTicketStatus::Cancelled])
                    ->count(),
            ],
            'by_status' => $this->groupCounts((clone $query), 'status'),
            'by_priority' => $this->groupCounts((clone $query), 'priority'),
            'by_category' => SupportTicketCategory::query()
                ->leftJoin('support_tickets', function ($join) use ($from, $to): void {
                    $join->on('support_ticket_categories.id', '=', 'support_tickets.category_id')
                        ->whereBetween('support_tickets.created_at', [$from, $to]);
                })
                ->groupBy('support_ticket_categories.id', 'support_ticket_categories.name')
                ->orderByDesc('ticket_count')
                ->get([
                    'support_ticket_categories.id',
                    'support_ticket_categories.name',
                    \DB::raw('COUNT(support_tickets.id) as ticket_count'),
                ])
                ->map(fn ($row) => ['id' => $row->id, 'name' => $row->name, 'count' => (int) $row->ticket_count])
                ->values(),
            'by_assignee' => User::query()
                ->leftJoin('support_tickets', function ($join) use ($from, $to): void {
                    $join->on('users.id', '=', 'support_tickets.assigned_user_id')
                        ->whereBetween('support_tickets.created_at', [$from, $to]);
                })
                ->where(function ($query): void {
                    $query->whereHas('permissions', fn ($q) => $q->where('name', 'view_support_tickets'))
                        ->orWhereHas('roles.permissions', fn ($q) => $q->where('name', 'view_support_tickets'));
                })
                ->groupBy('users.id', 'users.name')
                ->orderByDesc('ticket_count')
                ->get(['users.id', 'users.name', \DB::raw('COUNT(support_tickets.id) as ticket_count')])
                ->map(fn ($row) => ['id' => $row->id, 'name' => $row->name, 'count' => (int) $row->ticket_count])
                ->values(),
        ];
    }

    private function groupCounts(Builder $query, string $column): Collection
    {
        return $query->selectRaw("{$column} as label, COUNT(*) as aggregate")
            ->groupBy($column)
            ->orderByDesc('aggregate')
            ->get()
            ->map(fn ($row) => ['label' => (string) $row->label, 'count' => (int) $row->aggregate])
            ->values();
    }

    private function averageMinutes(Builder $query, string $fromColumn, string $toColumn): ?float
    {
        $seconds = $query->selectRaw("AVG(TIMESTAMPDIFF(SECOND, {$fromColumn}, {$toColumn})) as average_seconds")
            ->value('average_seconds');

        return $seconds === null ? null : round(((float) $seconds) / 60, 1);
    }
}
