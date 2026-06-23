<?php

declare(strict_types=1);

namespace App\Actions\Reports;

use App\Enums\DealStage;
use App\Models\Deal;
use App\Models\Tenant;
use App\Models\User;

/**
 * Computes pipeline analytics for a tenant (Improvement 5.2).
 *
 * Returns a plain array consumed by both the HTML analytics page and the PDF
 * report so the two never drift. All figures are tenant-scoped.
 */
final readonly class PipelineAnalytics
{
    /**
     * @return array{
     *     funnel: list<array{stage: string, label: string, count: int, amount: float}>,
     *     per_owner: list<array{owner: string, count: int, amount: float}>,
     *     win_loss: array{won: int, lost: int, reasons: list<array{reason: string, count: int}>},
     *     averages: array{cycle_days: ?float, deal_size: float},
     *     totals: array{deals: int, open_amount: float, won_amount: float}
     * }
     */
    public function handle(Tenant $tenant): array
    {
        $base = fn () => Deal::query()->where('tenant_id', $tenant->id);

        $stageRows = $base()
            ->selectRaw('stage, COUNT(*) as deals_count, COALESCE(SUM(amount), 0) as amount_sum')
            ->groupBy('stage')
            ->get()
            ->keyBy(fn (Deal $row) => $row->stage->value);

        $funnel = [];
        foreach (DealStage::cases() as $stage) {
            $row = $stageRows->get($stage->value);
            $funnel[] = [
                'stage' => $stage->value,
                'label' => $stage->label(),
                'count' => (int) ($row->deals_count ?? 0),
                'amount' => (float) ($row->amount_sum ?? 0),
            ];
        }

        $ownerRows = $base()
            ->selectRaw('owner_id, COUNT(*) as deals_count, COALESCE(SUM(amount), 0) as amount_sum')
            ->groupBy('owner_id')
            ->get();

        $ownerNames = User::query()
            ->whereIn('id', $ownerRows->pluck('owner_id')->filter()->all())
            ->pluck('name', 'id');

        $perOwner = $ownerRows
            ->map(fn (Deal $row) => [
                'owner' => $row->owner_id ? ($ownerNames[$row->owner_id] ?? 'Unknown') : 'Unassigned',
                'count' => (int) $row->deals_count,
                'amount' => (float) $row->amount_sum,
            ])
            ->sortByDesc('amount')
            ->values()
            ->all();

        $wonCount = (int) ($stageRows->get(DealStage::Won->value)->deals_count ?? 0);
        $lostCount = (int) ($stageRows->get(DealStage::Lost->value)->deals_count ?? 0);

        $reasons = $base()
            ->where('stage', DealStage::Lost->value)
            ->selectRaw("COALESCE(NULLIF(lost_reason, ''), 'Unspecified') as reason, COUNT(*) as count")
            ->groupBy('reason')
            ->orderByDesc('count')
            ->get()
            ->map(fn (Deal $row) => [
                'reason' => (string) $row->getAttributes()['reason'],
                'count' => (int) $row->count,
            ])
            ->all();

        $wonDeals = $base()
            ->where('stage', DealStage::Won->value)
            ->whereNotNull('closed_at')
            ->get(['created_at', 'closed_at']);

        $cycleDays = $wonDeals->isEmpty()
            ? null
            : round($wonDeals->avg(fn (Deal $deal) => $deal->created_at->diffInDays($deal->closed_at)), 1);

        $totalDeals = (int) array_sum(array_column($funnel, 'count'));
        $wonAmount = (float) ($stageRows->get(DealStage::Won->value)->amount_sum ?? 0);
        $openAmount = collect($funnel)
            ->whereNotIn('stage', [DealStage::Won->value, DealStage::Lost->value])
            ->sum('amount');

        $avgDealSize = $totalDeals > 0
            ? round((float) array_sum(array_column($funnel, 'amount')) / $totalDeals, 2)
            : 0.0;

        return [
            'funnel' => $funnel,
            'per_owner' => $perOwner,
            'win_loss' => ['won' => $wonCount, 'lost' => $lostCount, 'reasons' => $reasons],
            'averages' => ['cycle_days' => $cycleDays, 'deal_size' => $avgDealSize],
            'totals' => ['deals' => $totalDeals, 'open_amount' => (float) $openAmount, 'won_amount' => $wonAmount],
        ];
    }
}
