<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Agent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * An agent writing their own profile.
 *
 * An agency could already write its own description. An agent could not,
 * so a bio reached a public page only if an administrator typed it there,
 * and almost every agent's page carried an empty card.
 */
class AgentProfileTest extends TestCase
{
    use RefreshDatabase;

    private function agent(array $overrides = []): Agent
    {
        $role = Role::findOrCreate('agent', 'web');
        $user = User::factory()->create(['name' => 'Sami']);
        $user->assignRole($role);

        return Agent::create(array_merge([
            'user_id' => $user->id,
            'slug' => 'sami-'.str()->random(5),
            'title' => 'Sales Agent',
            'status' => 'approved',
        ], $overrides));
    }

    public function test_an_agent_reads_their_own_profile(): void
    {
        $agent = $this->agent(['bio' => 'Responsible person.']);

        $this->actingAs($agent->user)
            ->getJson('/api/agent/profile')
            ->assertOk()
            ->assertJsonPath('data.slug', $agent->slug)
            ->assertJsonPath('data.bio', 'Responsible person.');
    }

    public function test_an_agent_writes_their_own_bio(): void
    {
        $agent = $this->agent();

        $this->actingAs($agent->user)
            ->postJson('/api/agent/profile', [
                'bio' => 'Fifteen years around Gulberg.',
                'yearsExperience' => 15,
                'dealsClosed' => 60,
                'officeAddress' => 'Main Boulevard, Lahore',
            ])
            ->assertOk()
            ->assertJsonPath('data.bio', 'Fifteen years around Gulberg.')
            ->assertJsonPath('data.yearsExperience', 15);

        $agent->refresh();

        $this->assertSame('Fifteen years around Gulberg.', $agent->bio);
        $this->assertSame(60, $agent->deals_closed);
        $this->assertSame('Main Boulevard, Lahore', $agent->office_address);
    }

    /** The name and phone live on the user record, not the agent one. */
    public function test_an_agent_changes_their_name_and_phone(): void
    {
        $agent = $this->agent();

        $this->actingAs($agent->user)
            ->postJson('/api/agent/profile', [
                'name' => 'Sami Ullah',
                'phone' => '03001234567',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Sami Ullah');

        $this->assertSame('Sami Ullah', $agent->user->fresh()->name);
        $this->assertSame('03001234567', $agent->user->fresh()->phone);
    }

    public function test_an_agent_cannot_verify_themselves(): void
    {
        $agent = $this->agent();

        /* Not in the rules, so it is never read — the tick stays earned. */
        $this->actingAs($agent->user)
            ->postJson('/api/agent/profile', ['verified' => true, 'bio' => 'x'])
            ->assertOk();

        $this->assertFalse((bool) $agent->fresh()->verified);
    }

    public function test_an_agent_cannot_move_themselves_to_another_agency(): void
    {
        $agent = $this->agent();
        $owner = User::factory()->create();
        $other = Agency::create([
            'user_id' => $owner->id,
            'name' => 'Someone Else',
            'slug' => 'someone-else',
            'status' => 'approved',
        ]);

        $this->actingAs($agent->user)
            ->postJson('/api/agent/profile', ['agencyId' => $other->id, 'bio' => 'x'])
            ->assertOk();

        $this->assertNull($agent->fresh()->agency_id);
    }

    public function test_someone_who_is_not_an_agent_is_refused(): void
    {
        $this->actingAs(User::factory()->create())
            ->getJson('/api/agent/profile')
            ->assertForbidden();
    }

    public function test_a_signed_out_visitor_is_refused(): void
    {
        $this->getJson('/api/agent/profile')->assertUnauthorized();
    }

    public function test_a_bio_longer_than_the_column_is_refused(): void
    {
        $agent = $this->agent();

        $this->actingAs($agent->user)
            ->postJson('/api/agent/profile', ['bio' => str_repeat('x', 5001)])
            ->assertStatus(422)
            ->assertJsonValidationErrors('bio');
    }
}
