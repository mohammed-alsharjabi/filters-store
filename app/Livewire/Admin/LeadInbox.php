<?php

namespace App\Livewire\Admin;

use App\Models\Lead;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;

class LeadInbox extends Component
{
    use AuthorizesRequests, WithPagination;

    public string $status = '';

    public string $search = '';

    public function updateStatus(int $id, string $status): void
    {
        $this->authorize('view-leads');
        abort_unless(in_array($status, Lead::STATUSES, true), 422);
        Lead::query()->findOrFail($id)->update(['status' => $status]);
    }

    public function render()
    {
        $this->authorize('view-leads');
        $leads = Lead::with(['service', 'images'])->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->when($this->search, fn ($q) => $q->where(fn ($sub) => $sub->where('name', 'like', '%'.$this->search.'%')->orWhere('phone', 'like', '%'.$this->search.'%')))
            ->latest()->paginate(15);

        return view('livewire.admin.lead-inbox', ['leads' => $leads, 'statusLabels' => Lead::STATUS_LABELS])
            ->layout('components.layouts.admin', ['title' => 'طلبات العملاء']);
    }
}
