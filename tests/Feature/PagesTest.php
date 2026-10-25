<?php

namespace Tests\Feature;

use App\Models\Muscle;
use App\Models\User;
use App\Models\WorkoutPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_prototype_pages_are_displayed(): void
    {
        $user = User::factory()->create();
        $plan = WorkoutPlan::factory()->for($user)->create();
        $plan->muscles()->attach(Muscle::factory()->create());

        $this->actingAs($user);

        $this->get(route('workouts.create'))->assertOk()->assertSee('筋トレ内容を記録');
        $this->get(route('plans.index'))->assertOk()->assertSee($plan->menu_name);
        $this->get(route('plans.create'))->assertOk()->assertSee('通知プランを作成');
        $this->get(route('plans.edit', $plan))->assertOk()->assertSee('通知プランを編集');
        $this->get(route('users.index'))->assertOk()->assertSee('ユーザー一覧');
        $this->get(route('permissions.index'))->assertOk()->assertSee('閲覧許可');
        $this->get(route('dashboard'))->assertOk()->assertSee('マイページ');
    }
}
