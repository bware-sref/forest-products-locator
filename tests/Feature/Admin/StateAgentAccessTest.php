<?php

use App\Enums\UserRoles;
use App\Models\MillType;
use App\Models\State;
use App\Models\StateAssistanceCategory;
use App\Models\StateAssistanceLink;
use App\Models\StateContact;
use App\Models\StatePage;

/**
 * SetsUpForStateAgents: State Agents only see and change records for their own state.
 *
 * One admin request per test: Backpack's CRUD panel holds per-request state, so a second request in the same test
 * can see the first one's setup.
 */

beforeEach(function () {
    $this->home = State::forceCreate(['name' => 'Alabama', 'abbreviation' => 'AL']);
    $this->other = State::forceCreate(['name' => 'Georgia', 'abbreviation' => 'GA']);
});

function contactIn(State $state, string $name): StateContact
{
    return StateContact::create(['state_id' => $state->id, 'name' => $name]);
}

describe('records with a state_id (StateContact)', function () {
    beforeEach(function () {
        $this->mine = contactIn($this->home, 'Home Contact');
        $this->theirs = contactIn($this->other, 'Other Contact');
    });

    test('the list only includes the agent\'s state', function () {
        loginStateAgent($this->home->id, ['state_contacts.edit']);

        $rows = listRows($this, 'state-contact');

        expect($rows['recordsTotal'])->toBe(1)
            ->and(json_encode($rows['data']))->toContain('Home Contact')->not->toContain('Other Contact');
    });

    test('another state\'s record 404s on show', function () {
        loginStateAgent($this->home->id, ['state_contacts.edit']);

        $this->get("/admin/state-contact/{$this->theirs->id}/show")->assertNotFound();
    });

    test('another state\'s record 404s on edit', function () {
        loginStateAgent($this->home->id, ['state_contacts.edit']);

        $this->get("/admin/state-contact/{$this->theirs->id}/edit")->assertNotFound();
    });

    test('another state\'s record can\'t be updated', function () {
        loginStateAgent($this->home->id, ['state_contacts.edit']);

        $this->put("/admin/state-contact/{$this->theirs->id}", [
            'id' => $this->theirs->id,
            'state_id' => $this->other->id,
            'name' => 'Hijacked',
        ])->assertNotFound();

        expect($this->theirs->fresh()->name)->toBe('Other Contact');
    });

    test('another state\'s record can\'t be deleted', function () {
        loginStateAgent($this->home->id, ['state_contacts.edit']);

        $this->delete("/admin/state-contact/{$this->theirs->id}")->assertNotFound();

        expect(StateContact::find($this->theirs->id))->not->toBeNull();
    });

    test('the agent\'s own record can be deleted', function () {
        loginStateAgent($this->home->id, ['state_contacts.edit']);

        $this->delete("/admin/state-contact/{$this->mine->id}")->assertOk();

        expect(StateContact::find($this->mine->id))->toBeNull();
    });

    test('creating with another state_id saves it to the agent\'s state', function () {
        loginStateAgent($this->home->id, ['state_contacts.edit']);

        $this->post('/admin/state-contact', [
            'state_id' => $this->other->id,
            'name' => 'Sneaky New Contact',
        ])->assertRedirect();

        expect(StateContact::where('name', 'Sneaky New Contact')->value('state_id'))->toBe($this->home->id);
    });

    test('updating the agent\'s own record can\'t move it to another state', function () {
        loginStateAgent($this->home->id, ['state_contacts.edit']);

        $this->put("/admin/state-contact/{$this->mine->id}", [
            'id' => $this->mine->id,
            'state_id' => $this->other->id,
            'name' => 'Renamed',
        ])->assertRedirect();

        expect($this->mine->fresh())
            ->name->toBe('Renamed')
            ->state_id->toBe($this->home->id);
    });

    test('the create form only offers the agent\'s state', function () {
        loginStateAgent($this->home->id, ['state_contacts.edit']);

        $this->get('/admin/state-contact/create')
            ->assertOk()
            ->assertSee('Alabama')
            ->assertDontSee('Georgia');
    });

    test('non-agents still see every state', function () {
        loginAdminUser(['state_contacts.edit'], UserRoles::ADMIN);

        $rows = listRows($this, 'state-contact');

        expect($rows['recordsTotal'])->toBe(2);
    });

    test('an agent role without a state_id gets no state scoping', function () {
        /**
         * isStateAgent() requires both the role and a state_id, so this user is treated as a regular user.
         * Pinned here so a change to that rule is a deliberate one.
         */
        loginAdminUser(['state_contacts.edit'], UserRoles::AGENT);

        $rows = listRows($this, 'state-contact');

        expect($rows['recordsTotal'])->toBe(2);
    });
});

describe('one-per-state records (StatePage)', function () {
    test('agents can\'t open the create form', function () {
        loginStateAgent($this->home->id, ['state_pages.edit']);

        $this->get('/admin/state-page/create')->assertForbidden();
    });

    test('agents can\'t create', function () {
        loginStateAgent($this->home->id, ['state_pages.edit']);

        $this->post('/admin/state-page', ['state_id' => $this->home->id])->assertForbidden();

        expect(StatePage::count())->toBe(0);
    });

    test('agents can\'t delete their own state\'s page', function () {
        $page = StatePage::create(['state_id' => $this->home->id]);
        loginStateAgent($this->home->id, ['state_pages.edit']);

        $this->delete("/admin/state-page/{$page->id}")->assertForbidden();

        expect(StatePage::find($page->id))->not->toBeNull();
    });

    test('agents can update their own state\'s page', function () {
        $page = StatePage::create(['state_id' => $this->home->id]);
        loginStateAgent($this->home->id, ['state_pages.edit']);

        $this->put("/admin/state-page/{$page->id}", [
            'id' => $page->id,
            'state_id' => $this->home->id,
            'hero_headline' => 'New Headline',
        ])->assertRedirect();

        expect($page->fresh()->hero_headline)->toBe('New Headline');
    });

    test('non-agents can open the create form', function () {
        loginAdminUser(['state_pages.edit'], UserRoles::ADMIN);

        $this->get('/admin/state-page/create')->assertOk();
    });
});

describe('shared records with no state (MillType)', function () {
    beforeEach(function () {
        $this->millType = MillType::create(['name' => 'Sawmill']);
    });

    test('agents can create', function () {
        loginStateAgent($this->home->id, ['mill_types.edit']);

        $this->post('/admin/mill-type', ['name' => 'Pellet Mill'])->assertRedirect();

        expect(MillType::where('name', 'Pellet Mill')->exists())->toBeTrue();
    });

    test('agents can\'t open the edit form', function () {
        loginStateAgent($this->home->id, ['mill_types.edit']);

        $this->get("/admin/mill-type/{$this->millType->id}/edit")->assertForbidden();
    });

    test('agents can\'t update', function () {
        loginStateAgent($this->home->id, ['mill_types.edit']);

        $this->put("/admin/mill-type/{$this->millType->id}", [
            'id' => $this->millType->id,
            'name' => 'Renamed Mill',
        ])->assertForbidden();

        expect($this->millType->fresh()->name)->toBe('Sawmill');
    });

    test('agents can\'t delete', function () {
        loginStateAgent($this->home->id, ['mill_types.edit']);

        $this->delete("/admin/mill-type/{$this->millType->id}")->assertForbidden();

        expect(MillType::find($this->millType->id))->not->toBeNull();
    });
});

describe('records related to a state through a parent (StateAssistanceLink)', function () {
    beforeEach(function () {
        $this->myCategory = StateAssistanceCategory::create(['state_id' => $this->home->id, 'title' => 'Mine']);
        $this->theirCategory = StateAssistanceCategory::create(['state_id' => $this->other->id, 'title' => 'Theirs']);
        $this->theirLink = StateAssistanceLink::create([
            'state_assistance_category_id' => $this->theirCategory->id,
            'label' => 'Their Link',
            'url' => 'https://example.com/theirs',
        ]);
    });

    test('another state\'s link 404s on edit', function () {
        loginStateAgent($this->home->id, ['state_assistance_links.edit']);

        $this->get("/admin/state-assistance-link/{$this->theirLink->id}/edit")->assertNotFound();
    });

    test('creating a link in another state\'s category is refused', function () {
        loginStateAgent($this->home->id, ['state_assistance_links.edit']);

        $this->post('/admin/state-assistance-link', [
            'state_assistance_category_id' => $this->theirCategory->id,
            'label' => 'Sneaky Link',
            'url' => 'https://example.com/sneaky',
        ])->assertForbidden();

        expect(StateAssistanceLink::where('label', 'Sneaky Link')->exists())->toBeFalse();
    });

    test('creating a link in the agent\'s own category works', function () {
        loginStateAgent($this->home->id, ['state_assistance_links.edit']);

        $this->post('/admin/state-assistance-link', [
            'state_assistance_category_id' => $this->myCategory->id,
            'label' => 'My Link',
            'url' => 'https://example.com/mine',
        ])->assertRedirect();

        expect(StateAssistanceLink::where('label', 'My Link')->value('state_assistance_category_id'))
            ->toBe($this->myCategory->id);
    });
});
