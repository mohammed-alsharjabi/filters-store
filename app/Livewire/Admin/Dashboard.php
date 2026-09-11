<?php

namespace App\Livewire\Admin;

use App\Models\Article;
use App\Models\Lead;
use App\Models\Project;
use App\Models\Service;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class Dashboard extends Component
{
    use AuthorizesRequests;

    public function render()
    {
        $this->authorize('manage-content');

        return view('livewire.admin.dashboard', [
            'counts' => ['الطلبات الجديدة' => Lead::where('status', 'new')->count(), 'الخدمات المنشورة' => Service::published()->count(), 'المشاريع المنشورة' => Project::published()->count(), 'المقالات المنشورة' => Article::published()->count()],
            'recentLeads' => Lead::with('service')->latest()->limit(8)->get(),
        ])->layout('components.layouts.admin', ['title' => 'لوحة التحكم']);
    }
}
