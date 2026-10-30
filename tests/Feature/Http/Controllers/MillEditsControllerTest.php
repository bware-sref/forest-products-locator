<?php

use App\Enums\PublicationStatus;
use App\Jobs\ProcessMill;
use App\Models\Mill;
use App\Models\MillEdit;
use App\Models\MillType;
use App\Models\State;
use App\Models\WoodSpecies;
use Illuminate\Support\Facades\Bus;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * The review links in notification emails: /mill-edits/{review_hash}, plus approve/reject POSTs keyed by their own
 * hashes. Anyone holding a link can act on it; there's no login.
 */

beforeEach(function () {
    Bus::fake([ProcessMill::class]);

    $this->state = State::factory()->create();
    $this->mill = Mill::factory()->for($this->state)->create([
        'mill_name' => 'Old Name Lumber',
        'physical_address' => '1 Old Rd',
    ]);
});

describe('review page', function () {
    test('an edit shows the original and the proposed changes', function () {
        $edit = MillEdit::factory()->for($this->mill)->withChanges(['mill_name' => 'New Name Lumber'])->create();

        $this->get("/mill-edits/{$edit->review_hash}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('mill-edit-show')
                ->where('original.mill_name', 'Old Name Lumber')
                ->where('submitted.mill_name', 'New Name Lumber')
                ->where('millEdit.id', $edit->id)
            );
    });

    test('a new business submission shows the submitted details', function () {
        $edit = MillEdit::factory()->newBusiness(['mill_name' => 'Brand New Mill', 'state_id' => $this->state->id])->create();

        $this->get("/mill-edits/{$edit->review_hash}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('review-new-mill')
                ->where('submitted.mill_name', 'Brand New Mill')
                ->where('submitted.state', $this->state->name)
            );
    });

    test('an edit that was already reviewed 404s', function (string $status) {
        $edit = MillEdit::factory()->for($this->mill)->{$status}()->create();

        $this->get("/mill-edits/{$edit->review_hash}")->assertNotFound();
    })->with(['approved', 'rejected']);

    test('the approve and reject hashes don\'t open the review page', function () {
        $edit = MillEdit::factory()->for($this->mill)->create();

        $this->get("/mill-edits/{$edit->approve_hash}")->assertNotFound();
        $this->get("/mill-edits/{$edit->reject_hash}")->assertNotFound();
    });
});

describe('approving', function () {
    test('an edit is applied to the mill', function () {
        $sawmill = MillType::factory()->create();
        $pine = WoodSpecies::factory()->create();
        $edit = MillEdit::factory()->for($this->mill)->withChanges([
            'mill_name' => 'New Name Lumber',
            'mill_types' => [$sawmill->id],
            'wood_species' => [$pine->id],
        ])->create();

        $this->post("/mill-edits/{$edit->approve_hash}/approve")
            ->assertRedirect(route('mills.show', $this->mill->fresh()));

        $mill = $this->mill->fresh();
        expect($mill->mill_name)->toBe('New Name Lumber')
            ->and($mill->millTypes->pluck('id')->all())->toBe([$sawmill->id])
            ->and($mill->woodSpecies->pluck('id')->all())->toBe([$pine->id])
            ->and($edit->fresh())
                ->status->toBe(PublicationStatus::Approved)
                ->reviewed_at->not->toBeNull();
    });

    test('approving runs the processing chain for the mill', function () {
        $edit = MillEdit::factory()->for($this->mill)->create();

        $this->post("/mill-edits/{$edit->approve_hash}/approve");

        Bus::assertDispatched(ProcessMill::class, fn (ProcessMill $job) => $job->mill->is($this->mill));
    });

    test('an approved address change flags the mill for geocoding', function () {
        $edit = MillEdit::factory()->for($this->mill)->withChanges(['physical_address' => '9 New St'])->create();

        $this->post("/mill-edits/{$edit->approve_hash}/approve");

        expect(freshMill($this->mill))
            ->physical_address->toBe('9 New St')
            ->needs_geocode->toBeTrue();
    });

    test('an approved edit that leaves the address alone doesn\'t flag the mill', function () {
        $edit = MillEdit::factory()->for($this->mill)->withChanges(['telephone' => '706-555-0199'])->create();

        $this->post("/mill-edits/{$edit->approve_hash}/approve");

        expect(freshMill($this->mill)->needs_geocode)->toBeFalse();
    });

    test('a new business becomes a published mill', function () {
        $sawmill = MillType::factory()->create();
        $edit = MillEdit::factory()->newBusiness([
            'mill_name' => 'Brand New Mill',
            'state_id' => $this->state->id,
            'mill_types' => [$sawmill->id],
        ])->create();

        $this->post("/mill-edits/{$edit->approve_hash}/approve")->assertRedirect();

        $mill = Mill::where('mill_name', 'Brand New Mill')->first(); // visible through ApprovedScope
        expect($mill)->not->toBeNull()
            ->and($mill->state_id)->toBe($this->state->id)
            ->and($mill->millTypes->pluck('id')->all())->toBe([$sawmill->id])
            ->and($edit->fresh()->mill_id)->toBe($mill->id);
        Bus::assertDispatched(ProcessMill::class, fn (ProcessMill $job) => $job->mill->is($mill));
    });

    test('an edit can only be approved once', function () {
        $edit = MillEdit::factory()->for($this->mill)->approved()->create();

        $this->post("/mill-edits/{$edit->approve_hash}/approve")->assertNotFound();

        Bus::assertNotDispatched(ProcessMill::class);
    });

    test('the review and reject hashes can\'t approve', function () {
        $edit = MillEdit::factory()->for($this->mill)->create();

        $this->post("/mill-edits/{$edit->review_hash}/approve")->assertNotFound();
        $this->post("/mill-edits/{$edit->reject_hash}/approve")->assertNotFound();

        expect($edit->fresh()->status)->toBe(PublicationStatus::Pending);
    });
});

describe('rejecting', function () {
    test('an edit is rejected and the mill left alone', function () {
        $edit = MillEdit::factory()->for($this->mill)->withChanges(['mill_name' => 'Rejected Name'])->create();

        $this->post("/mill-edits/{$edit->reject_hash}/reject")
            ->assertRedirect(route('mills.show', $this->mill));

        expect($this->mill->fresh()->mill_name)->toBe('Old Name Lumber')
            ->and($edit->fresh())
                ->status->toBe(PublicationStatus::Rejected)
                ->reviewed_at->not->toBeNull();
        Bus::assertNotDispatched(ProcessMill::class);
    });

    test('a rejected new business creates no mill and sends the reviewer home', function () {
        $edit = MillEdit::factory()->newBusiness(['mill_name' => 'Spam Mill', 'state_id' => $this->state->id])->create();

        $this->post("/mill-edits/{$edit->reject_hash}/reject")->assertRedirect(route('home'));

        expect(Mill::withoutGlobalScopes()->where('mill_name', 'Spam Mill')->exists())->toBeFalse();
    });

    test('an edit can only be rejected once', function () {
        $edit = MillEdit::factory()->for($this->mill)->rejected()->create();

        $this->post("/mill-edits/{$edit->reject_hash}/reject")->assertNotFound();
    });

    test('the approve hash can\'t reject', function () {
        $edit = MillEdit::factory()->for($this->mill)->create();

        $this->post("/mill-edits/{$edit->approve_hash}/reject")->assertNotFound();

        expect($edit->fresh()->status)->toBe(PublicationStatus::Pending);
    });
});
