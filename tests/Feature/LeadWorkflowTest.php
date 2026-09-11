<?php

namespace Tests\Feature;

use App\Jobs\ProcessNewLead;
use App\Models\Lead;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LeadWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_lead_images_are_validated_stored_privately_and_downloadable_only_by_admin(): void
    {
        $this->seed();
        Storage::fake('local');
        Queue::fake();
        $service = Service::query()->firstOrFail();
        $service->update(['status' => 'published', 'is_active' => true, 'published_at' => now()]);

        $response = $this->from(route('quote'))->post(route('leads.store'), [
            'type' => 'quote',
            'name' => 'عميل اختبار',
            'phone' => '0562066426',
            'service_id' => $service->id,
            'area' => 'حي المونسية',
            'area_size' => 2,
            'message' => 'أرغب في ترتيب معاينة للموقع.',
            'preferred_contact' => 'whatsapp',
            'site_images' => [UploadedFile::fake()->image('الموقع.jpg', 1200, 800)],
        ]);

        $response->assertRedirect(route('quote'))->assertSessionHas('success')->assertSessionHas('lead_whatsapp_url');
        $lead = Lead::with('images')->firstOrFail();
        $this->assertSame('2.00', $lead->area_size);
        $this->assertStringContainsString($service->name, $lead->whatsapp_message);
        $this->assertStringContainsString('حي المونسية', $lead->whatsapp_message);
        $this->assertStringContainsString('2 جهاز تقريبًا', $lead->whatsapp_message);
        $this->assertStringContainsString(route('quote'), $lead->whatsapp_message);
        $this->assertCount(1, $lead->images);
        Storage::disk('local')->assertExists($lead->images->first()->path);
        Queue::assertPushed(ProcessNewLead::class, fn ($job) => $job->leadId === $lead->id);

        $imageRoute = route('admin.leads.images.download', $lead->images->first());
        $this->get($imageRoute)->assertRedirect(route('admin.login'));
        $this->actingAs(User::factory()->create(['is_admin' => false]))->get($imageRoute)->assertForbidden();
        $this->actingAs(User::factory()->create(['is_admin' => true]))->get($imageRoute)->assertOk();
    }

    public function test_invalid_or_excessive_uploads_and_unpublished_services_are_rejected(): void
    {
        $this->seed();
        Storage::fake('local');
        $draftService = Service::query()->firstOrFail();
        $draftService->update(['status' => 'draft', 'published_at' => null]);

        $payload = [
            'type' => 'quote', 'name' => 'عميل اختبار', 'phone' => '0562066426',
            'service_id' => $draftService->id, 'preferred_contact' => 'phone',
            'site_images' => array_fill(0, 6, UploadedFile::fake()->image('site.jpg', 800, 600)),
        ];

        $this->post(route('leads.store'), $payload)->assertSessionHasErrors(['service_id', 'site_images']);
        $this->assertDatabaseCount('leads', 0);
    }

    public function test_lead_statuses_match_the_required_business_workflow(): void
    {
        $this->assertSame(
            ['جديد', 'تم التواصل', 'معاينة', 'عرض سعر', 'تم الاتفاق', 'مغلق'],
            array_values(Lead::STATUS_LABELS)
        );
    }

    public function test_contact_request_accepts_only_phone_and_redirects_to_an_ordered_whatsapp_message(): void
    {
        $this->seed();
        Queue::fake();

        $response = $this->from(route('contact'))->post(route('leads.store'), [
            'type' => 'contact',
            'submission_channel' => 'whatsapp',
            'phone' => '0562066426',
            'preferred_contact' => 'whatsapp',
        ]);

        $lead = Lead::firstOrFail();

        $response->assertRedirectContains('https://wa.me/');
        $this->assertNull($lead->name);
        $this->assertSame('0562066426', $lead->phone);
        $this->assertStringContainsString('*طلب جديد من موقع فلاتر وتحلية المياه بالرياض*', $lead->whatsapp_message);
        $this->assertStringContainsString('رقم الطلب: #'.$lead->id, $lead->whatsapp_message);
        $this->assertStringContainsString('رقم الجوال: 0562066426', $lead->whatsapp_message);
        $this->assertStringContainsString('التواصل المفضل: واتساب', $lead->whatsapp_message);
        $this->assertStringContainsString(route('contact'), $lead->whatsapp_message);
        $this->assertStringNotContainsString('غير محددة', $lead->whatsapp_message);
        Queue::assertPushed(ProcessNewLead::class, fn ($job) => $job->leadId === $lead->id);
    }
}
