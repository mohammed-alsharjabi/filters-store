<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLeadRequest;
use App\Jobs\ProcessNewLead;
use App\Models\Lead;
use App\Support\LeadWhatsappMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class LeadController extends Controller
{
    public function store(StoreLeadRequest $request): RedirectResponse
    {
        $data = $request->safe()->except(['website', 'site_images', 'submission_channel']);
        $leadDirectory = null;

        try {
            $lead = DB::transaction(function () use ($data, $request, &$leadDirectory): Lead {
                $lead = Lead::create($data + [
                    'source_url' => mb_substr((string) $request->headers->get('referer'), 0, 2048) ?: null,
                    'ip_hash' => hash_hmac('sha256', (string) $request->ip(), (string) config('app.key')),
                    'metadata' => ['user_agent' => mb_substr((string) $request->userAgent(), 0, 500)],
                ]);

                $leadDirectory = 'leads/'.$lead->id;

                foreach ($request->file('site_images', []) as $image) {
                    $path = $image->store($leadDirectory, 'local');
                    abort_unless($path, 500, 'تعذر حفظ صورة الموقع.');
                    $lead->images()->create([
                        'path' => $path,
                        'original_name' => mb_substr($image->getClientOriginalName(), 0, 255),
                        'mime_type' => $image->getMimeType(),
                        'size' => $image->getSize(),
                    ]);
                }

                $lead->load('service')->loadCount('images');
                $lead->update(['whatsapp_message' => LeadWhatsappMessage::make($lead)]);

                return $lead;
            });
        } catch (Throwable $exception) {
            if ($leadDirectory) {
                Storage::disk('local')->deleteDirectory($leadDirectory);
            }
            Log::error('تعذر حفظ طلب خدمة من الموقع.', [
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
                'request_id' => $request->header('X-Request-ID'),
            ]);

            throw $exception;
        }

        ProcessNewLead::dispatch($lead->id);

        if ($request->validated('submission_channel') === 'whatsapp') {
            return redirect()->away(LeadWhatsappMessage::url((string) $lead->whatsapp_message));
        }

        return back()
            ->with('success', 'وصل طلبك بنجاح. سنتواصل معك عبر وسيلة التواصل التي اخترتها.')
            ->with('lead_whatsapp_url', LeadWhatsappMessage::url((string) $lead->whatsapp_message));
    }
}
