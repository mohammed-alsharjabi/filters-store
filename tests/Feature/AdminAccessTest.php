<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_routes_require_an_admin_account(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));
        $regular = User::factory()->create(['is_admin' => false]);
        $this->actingAs($regular)->get(route('admin.dashboard'))->assertForbidden();
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()->assertSee('نظرة عامة');
    }
}
