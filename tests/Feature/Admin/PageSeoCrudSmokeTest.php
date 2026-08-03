<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

test('page-seo crud list and create screens render', function () {
    Permission::create(['name' => 'page_seos.edit', 'guard_name' => 'web']);

    $user = User::factory()->create();
    $user->givePermissionTo('page_seos.edit');
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    // Auth::guard('backpack')->login() authenticates on the backpack guard
    // without touching the app's default guard, unlike actingAs($user,
    // 'backpack') -- which also calls shouldUse('backpack'), changing what
    // guard Spatie's permission check resolves against mid-request.
    Auth::guard('backpack')->login($user);

    $response = $this->get('/admin/page-seo');
    $response->assertOk();

    $response = $this->get('/admin/page-seo/create');
    $response->assertOk();
});
