<?php

namespace App\Jobs;

use App\Models\Lead;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessNewLead implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $leadId) {}

    public function handle(): void
    {
        if (Lead::query()->whereKey($this->leadId)->exists()) {
            Log::info('New website lead is ready for follow-up.', ['lead_id' => $this->leadId]);
        }
    }
}
