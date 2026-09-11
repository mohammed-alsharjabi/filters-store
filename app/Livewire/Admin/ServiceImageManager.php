<?php

namespace App\Livewire\Admin;

use App\Jobs\ProcessServiceImage;
use App\Models\Service;
use App\Models\ServiceImage;
use App\Support\ServiceImageImportService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithFileUploads;

class ServiceImageManager extends Component
{
    use AuthorizesRequests, WithFileUploads;

    public int $serviceId;

    public array $uploads = [];

    public array $metadata = [];

    public string $previewMode = 'desktop';

    public function mount(Service $service): void
    {
        $this->authorize('manage-content');
        $this->serviceId = $service->id;
        $this->refreshMetadata();
    }

    public function uploadImages(ServiceImageImportService $importer): void
    {
        $this->authorize('manage-content');
        $this->validate([
            'uploads' => ['required', 'array', 'min:1', 'max:30'],
            'uploads.*' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,avif', 'max:15360', 'dimensions:min_width=300,min_height=250,max_width=10000,max_height=10000'],
        ], [
            'uploads.required' => 'اختر صورة واحدة على الأقل.',
            'uploads.max' => 'يمكن رفع 30 صورة في الدفعة الواحدة.',
            'uploads.*.mimes' => 'الصورة يجب أن تكون JPG أو PNG أو WebP أو AVIF.',
            'uploads.*.max' => 'حجم الصورة يجب ألا يتجاوز 15MB.',
            'uploads.*.dimensions' => 'أبعاد الصورة غير مناسبة؛ الحد الأدنى 300×250.',
        ]);

        $service = Service::findOrFail($this->serviceId);
        $queued = 0;
        $duplicates = 0;
        foreach ($this->uploads as $upload) {
            $result = $importer->ingest($service, $upload->getRealPath(), $upload->getClientOriginalName(), 'رفع من لوحة التحكم', null, null, true);
            $result['status'] === 'duplicate' ? $duplicates++ : $queued++;
        }
        $this->uploads = [];
        $this->refreshMetadata();
        session()->flash('gallery_success', sprintf('أُضيفت %d صورة إلى قائمة المعالجة.%s', $queued, $duplicates ? ' وتُجاهلت '.$duplicates.' صورة مكررة.' : ''));
    }

    public function saveMetadata(int $imageId): void
    {
        $this->authorize('manage-content');
        $image = $this->imageQuery()->findOrFail($imageId);
        $validated = $this->validate([
            'metadata.'.$imageId.'.title' => ['required', 'string', 'max:255'],
            'metadata.'.$imageId.'.alt_text' => ['required', 'string', 'max:255'],
            'metadata.'.$imageId.'.caption' => ['nullable', 'string', 'max:1000'],
        ], [
            'metadata.*.title.required' => 'اكتب عنوانًا واضحًا للصورة.',
            'metadata.*.alt_text.required' => 'النص البديل مطلوب لتسهيل الوصول.',
        ]);
        $image->update($validated['metadata'][$imageId]);
        session()->flash('gallery_success', 'حُفظ وصف الصورة.');
    }

    public function updateOrder(string $item, int $position): void
    {
        $this->authorize('manage-content');
        $imageId = (int) $item;
        $ids = $this->imageQuery()->whereNull('deleted_at')->orderBy('sort_order')->orderBy('id')->pluck('id')->all();
        $current = array_search($imageId, $ids, true);
        abort_if($current === false, 404);
        array_splice($ids, $current, 1);
        array_splice($ids, max(0, min($position, count($ids))), 0, [$imageId]);
        DB::transaction(function () use ($ids): void {
            foreach ($ids as $index => $id) {
                ServiceImage::where('service_id', $this->serviceId)->whereKey($id)->update(['sort_order' => $index + 1]);
            }
        });
    }

    public function setCover(int $imageId): void
    {
        $this->authorize('manage-content');
        $image = $this->imageQuery()->whereNull('deleted_at')->findOrFail($imageId);
        abort_unless($image->processing_status === 'processed', 422, 'لا يمكن اختيار صورة غلاف قبل اكتمال المعالجة.');
        DB::transaction(function () use ($image): void {
            ServiceImage::where('service_id', $this->serviceId)->update(['is_cover' => false]);
            $image->update(['is_cover' => true]);
        });
    }

    public function reprocess(int $imageId): void
    {
        $this->authorize('manage-content');
        $image = $this->imageQuery()->whereNull('deleted_at')->findOrFail($imageId);
        $image->update(['processing_status' => 'queued', 'processing_notes' => 'أُرسلت الصورة لإعادة المعالجة من لوحة التحكم.']);
        ProcessServiceImage::dispatch($image->id);
        session()->flash('gallery_success', 'أُرسلت الصورة لإعادة المعالجة.');
    }

    public function deleteImage(int $imageId): void
    {
        $this->authorize('manage-content');
        $image = $this->imageQuery()->whereNull('deleted_at')->findOrFail($imageId);
        $wasCover = $image->is_cover;
        $image->delete();
        if ($wasCover) {
            $next = $this->imageQuery()->whereNull('deleted_at')->where('processing_status', 'processed')->orderBy('sort_order')->first();
            $next?->update(['is_cover' => true]);
        }
        session()->flash('gallery_success', 'نُقلت الصورة إلى المحذوفات ويمكن استعادتها.');
    }

    public function restoreImage(int $imageId): void
    {
        $this->authorize('manage-content');
        $image = $this->imageQuery()->whereNotNull('deleted_at')->findOrFail($imageId);
        $image->restore();
        $this->refreshMetadata();
        session()->flash('gallery_success', 'استُعيدت الصورة.');
    }

    public function render()
    {
        $service = Service::findOrFail($this->serviceId);
        $images = $this->imageQuery()->whereNull('deleted_at')->orderBy('sort_order')->orderBy('id')->get();
        $deletedImages = $this->imageQuery()->whereNotNull('deleted_at')->latest('deleted_at')->get();

        return view('livewire.admin.service-image-manager', compact('service', 'images', 'deletedImages'));
    }

    private function imageQuery()
    {
        return ServiceImage::withTrashed()->where('service_id', $this->serviceId);
    }

    private function refreshMetadata(): void
    {
        $this->metadata = $this->imageQuery()->whereNull('deleted_at')->get()->mapWithKeys(fn (ServiceImage $image): array => [
            $image->id => ['title' => $image->title, 'alt_text' => $image->alt_text, 'caption' => $image->caption],
        ])->all();
    }
}
