<?php

namespace App\Livewire\Admin;

use App\Models\ServiceCategory;
use App\Support\AdminContent;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;
use Livewire\WithPagination;

class ContentIndex extends Component
{
    use AuthorizesRequests, WithPagination;

    public string $type;

    public string $search = '';

    public function mount(string $type): void
    {
        $this->type = $type;
        AdminContent::get($type);
        $this->authorize('manage-content');
    }

    public function delete(int $id): void
    {
        $this->authorize('manage-content');
        $definition = AdminContent::get($this->type);
        $record = $definition['model']::query()->findOrFail($id);
        $record->delete();
        if (in_array($this->type, ['services', 'service-categories'], true)) {
            Cache::forget('navigation.service-categories');
        }
        if ($this->type === 'projects') {
            Cache::forget('navigation.has-published-projects');
        }
        session()->flash('success', 'حُذف السجل.');
    }

    public function render()
    {
        $definition = AdminContent::get($this->type);
        $title = $definition['title'];
        $query = $definition['model']::query()->when($this->search, fn ($q) => $q->where($title, 'like', '%'.$this->search.'%'));
        if ($this->type === 'services') {
            $records = $query
                ->orderBy(ServiceCategory::query()->select('sort_order')->whereColumn('service_categories.id', 'services.service_category_id'))
                ->orderBy('sort_order')
                ->orderBy('id')
                ->paginate(20);
        } elseif ($this->type === 'service-categories') {
            $records = $query->orderBy('sort_order')->orderBy('id')->paginate(20);
        } else {
            $records = $query->latest()->paginate(20);
        }

        return view('livewire.admin.content-index', compact('definition', 'records'))->layout('components.layouts.admin', ['title' => $definition['label']]);
    }
}
