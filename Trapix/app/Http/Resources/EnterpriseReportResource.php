<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * EnterpriseReportResource
 *
 * Transforms AnalysisJob data into a professional report DTO
 * ready for HTML, PDF, and JSON rendering.
 */
class EnterpriseReportResource extends JsonResource
{
    /**
     * Transform the resource into an array
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'status'            => $this->status,
            'report_data'       => app(\App\Services\Report\ReportBuilder::class)->build($this->resource),
            'can_download'      => $this->canDownload(),
            'created_at'        => $this->created_at?->toIso8601String(),
            'completed_at'      => $this->completed_at?->toIso8601String(),
        ];
    }

    private function canDownload(): bool
    {
        return $this->report !== null;
    }
}
