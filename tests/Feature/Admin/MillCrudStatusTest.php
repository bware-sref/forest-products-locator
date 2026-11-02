<?php

use App\Enums\PublicationStatus;
use App\Jobs\GeocodeMill;
use App\Models\Mill;
use App\Models\Scopes\ApprovedScope;
use App\Models\User;
use Illuminate\Support\Facades\Bus;
use Spatie\Permission\Models\Permission;

/**
 * The public site shows only approved mills; the admin panel must show every status,
 * or a mill set to Pending can't be found again to approve it.
 */

beforeEach(function () {
    Bus::fake([GeocodeMill::class]);

    $user = User::factory()->create();
    $user->givePermissionTo(Permission::findOrCreate('mills.see', 'web'));
    $user->givePermissionTo(Permission::findOrCreate('mills.edit', 'web'));
    backpack_auth()->setUser($user);

    $this->pending = makePendingMill(['mill_name' => 'Hidden Pending Mill']);
});

test('the admin mill list includes pending mills', function () {
    $response = $this->postJson('/admin/mill/search', [
        'draw' => 1,
        'start' => 0,
        'length' => 50,
        'search' => ['value' => 'Hidden Pending'],
    ]);

    $response->assertOk();
    expect($response->json('recordsFiltered'))->toBe(1);
});

test('a pending mill can be opened for editing in the admin panel', function () {
    $this->get("/admin/mill/{$this->pending->id}/edit")->assertOk();
});

test('a pending mill can be approved again from the admin panel', function () {
    $this->put("/admin/mill/{$this->pending->id}", [
        'id' => $this->pending->id,
        'mill_name' => $this->pending->mill_name,
        'status' => PublicationStatus::Approved->value,
    ])->assertRedirect();

    expect(Mill::find($this->pending->id))->not->toBeNull();
});

test('the public site still hides pending mills', function () {
    expect(Mill::find($this->pending->id))->toBeNull()
        ->and(Mill::withoutGlobalScope(ApprovedScope::class)->find($this->pending->id))->not->toBeNull();
});
