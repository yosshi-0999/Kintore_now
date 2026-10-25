<?php

namespace Tests\Feature;

use App\Models\Muscle;
use App\Models\User;
use App\Models\Workout;
use App\Models\WorkoutPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class WorkoutPlanTest extends TestCase
{
    use RefreshDatabase;

    public function test_plan_can_be_created(): void
    {
        $user = User::factory()->create();
        $chest = Muscle::factory()->create();

        $this->actingAs($user);

        Volt::test('plans.edit')
            ->set('scheduled_at', now()->addDays(3)->format('Y-m-d\TH:i'))
            ->set('place', '駅前ジム')
            ->set('menu_name', '胸の日')
            ->set('muscle_ids', [(string) $chest->id])
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('plans.index'));

        $plan = WorkoutPlan::sole();
        $this->assertSame('駅前ジム', $plan->place);
        $this->assertTrue($plan->is_enabled);
        $this->assertTrue($plan->muscles->contains($chest));
    }

    public function test_plan_with_muscle_resting_at_scheduled_time_cannot_be_created(): void
    {
        $user = User::factory()->create();
        $chest = Muscle::factory()->create(['name' => '大胸筋']);
        $workout = Workout::factory()->for($user)->create(['performed_at' => now(), 'rest_days' => 3]);
        $workout->muscles()->attach($chest);

        $this->actingAs($user);

        Volt::test('plans.edit')
            ->set('scheduled_at', now()->addDay()->format('Y-m-d\TH:i'))
            ->set('place', '自宅')
            ->set('menu_name', '胸の日')
            ->set('muscle_ids', [(string) $chest->id])
            ->call('save')
            ->assertHasErrors('muscle_ids')
            ->assertSee('大胸筋');

        $this->assertSame(0, WorkoutPlan::count());
    }

    public function test_plan_in_the_past_cannot_be_created(): void
    {
        $this->actingAs(User::factory()->create());

        Volt::test('plans.edit')
            ->set('scheduled_at', now()->subHour()->format('Y-m-d\TH:i'))
            ->set('place', '自宅')
            ->set('menu_name', '胸の日')
            ->set('muscle_ids', [(string) Muscle::factory()->create()->id])
            ->call('save')
            ->assertHasErrors(['scheduled_at' => 'after']);
    }

    public function test_changing_scheduled_time_resets_sent_at(): void
    {
        $user = User::factory()->create();
        $chest = Muscle::factory()->create();
        $plan = WorkoutPlan::factory()->for($user)->create(['sent_at' => now()]);
        $plan->muscles()->attach($chest);

        $this->actingAs($user);

        Volt::test('plans.edit', ['plan' => $plan])
            ->set('scheduled_at', now()->addDays(5)->format('Y-m-d\TH:i'))
            ->call('save')
            ->assertHasNoErrors();

        $this->assertNull($plan->fresh()->sent_at);
    }

    public function test_other_users_plan_cannot_be_edited_toggled_or_deleted(): void
    {
        $plan = WorkoutPlan::factory()->create();

        $this->actingAs(User::factory()->create());

        $this->get(route('plans.edit', $plan))->assertForbidden();
        Volt::test('plans.index')->call('toggle', $plan->id)->assertForbidden();
        Volt::test('plans.index')->call('delete', $plan->id)->assertForbidden();

        $this->assertTrue($plan->fresh()->is_enabled);
    }

    public function test_plan_can_be_toggled_and_deleted(): void
    {
        $user = User::factory()->create();
        $plan = WorkoutPlan::factory()->for($user)->create();

        $this->actingAs($user);

        Volt::test('plans.index')->call('toggle', $plan->id);
        $this->assertFalse($plan->fresh()->is_enabled);

        Volt::test('plans.index')->call('delete', $plan->id);
        $this->assertNull($plan->fresh());
    }
}
