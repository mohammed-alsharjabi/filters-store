<?php

namespace App\Jobs;

use App\Models\ServiceImage;
use App\Support\ServiceImageProcessor;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessServiceImage implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 180;

    public function __construct(public int $serviceImageId) {}

    public function handle(ServiceImageProcessor $processor): void
    {
        $image = ServiceImage::withTrashed()->findOrFail($this->serviceImageId);
        if ($image->trashed()) {
            return;
        }
        $processor->removeDerivatives($image);
        $processor->process($image);
    }
}
