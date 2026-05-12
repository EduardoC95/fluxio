<?php

namespace Tests\Feature;

use App\Models\CalendarEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PlanningModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_calendar_requires_authentication(): void
    {
        $this->get(route('calendar.index'))->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_list_calendar(): void
    {
        $user = User::factory()->create();
        $this->givePermission($user, 'calendario.read');

        $this->actingAs($user)
            ->get(route('calendar.index'))
            ->assertOk();
    }

    public function test_calendar_event_can_be_created(): void
    {
        $user = User::factory()->create();
        $this->givePermission($user, 'calendario.create');

        $this->actingAs($user)
            ->from(route('calendar.index'))
            ->post(route('calendar.store'), [
                'user_id' => $user->id,
                'scheduled_for' => now()->addDay()->format('Y-m-d H:i:s'),
                'duration_minutes' => 30,
                'description' => 'Follow-up',
                'status' => 'scheduled',
                'shared' => true,
                'knowledge' => false,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('calendar.index'));

        $this->assertSame('Follow-up', CalendarEvent::query()->first()?->description);
    }

    public function test_calendar_event_requires_valid_duration(): void
    {
        $user = User::factory()->create();
        $this->givePermission($user, 'calendario.create');

        $this->actingAs($user)
            ->from(route('calendar.index'))
            ->post(route('calendar.store'), [
                'user_id' => $user->id,
                'scheduled_for' => now()->addDay()->format('Y-m-d H:i:s'),
                'duration_minutes' => 1,
                'status' => 'scheduled',
            ])
            ->assertSessionHasErrors('duration_minutes');
    }

    private function givePermission(User $user, string $permission): void
    {
        Permission::findOrCreate($permission, 'web');
        $user->givePermissionTo($permission);
    }
}
