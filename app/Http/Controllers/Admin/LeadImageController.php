<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LeadImage;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LeadImageController extends Controller
{
    public function __invoke(LeadImage $leadImage): StreamedResponse
    {
        Gate::authorize('view-leads');
        abort_unless(Storage::disk('local')->exists($leadImage->path), 404);

        return Storage::disk('local')->download(
            $leadImage->path,
            $leadImage->original_name,
            ['Content-Type' => $leadImage->mime_type, 'X-Content-Type-Options' => 'nosniff']
        );
    }
}
