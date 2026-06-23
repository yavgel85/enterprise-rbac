<?php

declare(strict_types=1);

namespace App\Http\Controllers\Crm;

use App\Actions\Audit\LogAuditEvent;
use App\Actions\Reports\PipelineAnalytics;
use App\Enums\AuditAction;
use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ReportsController extends Controller
{
    public function analytics(Tenant $tenant, PipelineAnalytics $analytics): View
    {
        return view('crm.reports.analytics', [
            'tenant' => $tenant,
            'report' => $analytics->handle($tenant),
        ]);
    }

    public function dealsPdf(Tenant $tenant, PipelineAnalytics $analytics, LogAuditEvent $audit): Response
    {
        $report = $analytics->handle($tenant);

        $audit->handle(AuditAction::ReportExported, metadata: [
            'report' => 'pipeline_analytics',
            'format' => 'pdf',
        ]);

        $pdf = Pdf::loadView('crm.reports.deals-pdf', [
            'tenant' => $tenant,
            'report' => $report,
            'generatedAt' => now(),
        ])->setPaper('a4');

        return $pdf->download("pipeline-{$tenant->slug}-".now()->format('Ymd-His').'.pdf');
    }
}
