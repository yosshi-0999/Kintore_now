<?php

namespace Tests\Feature;

use App\Models\Exercise;
use App\Models\Muscle;
use App\Models\User;
use App\Models\Workout;
use App\Models\WorkoutPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class WorkoutFormTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Muscle $chest;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->chest = Muscle::factory()->create(['name' => '大胸筋']);
    }

    private function makeChestResting(): void
    {
        $workout = Workout::factory()->for($this->user)->create([
            'performed_at' => now()->subHour(),
            'rest_days' => 2,
        ]);
        $workout->muscles()->attach($this->chest);
    }

    private function fillForm()
    {
        $this->actingAs($this->user);

        return Volt::test('workouts.form')
            ->set('performed_at', now()->format('Y-m-d\TH:i'))
            ->set('menu_name', 'ベンチプレス')
            ->set('muscle_ids', [(string) $this->chest->id])
            ->set('rest_days', 2);
    }

    public function test_confirmation_is_shown_when_resting_muscle_is_selected(): void
    {
        $this->makeChestResting();

        $this->fillForm()
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee('休息中の筋肉が含まれています')
            ->assertSee('大胸筋')
            ->assertSee(now()->subHour()->addDays(2)->format('Y/m/d H:i'));

        $this->assertSame(1, Workout::count());
    }

    public function test_workout_is_saved_after_continuing_the_confirmation(): void
    {
        $this->makeChestResting();

        $this->fillForm()
            ->call('save')
            ->call('continueSave')
            ->assertSet('restWarnings', []);

        $this->assertSame(2, Workout::count());
        $this->assertTrue(Workout::latest('id')->first()->muscles->contains($this->chest));
    }

    public function test_workout_is_not_saved_when_going_back(): void
    {
        $this->makeChestResting();

        $this->fillForm()
            ->call('save')
            ->call('cancelSave')
            ->assertSet('restWarnings', []);

        $this->assertSame(1, Workout::count());
    }

    public function test_continue_without_confirmation_does_not_save(): void
    {
        $this->makeChestResting();

        $this->fillForm()->call('continueSave');

        $this->assertSame(1, Workout::count());
    }

    public function test_workout_is_saved_without_confirmation_when_no_muscle_is_resting(): void
    {
        $this->fillForm()
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('restWarnings', [])
            ->assertDontSee('休息中の筋肉が含まれています');

        $workout = Workout::sole();
        $this->assertSame('ベンチプレス', $workout->menu_name);
        $this->assertTrue($workout->user->is($this->user));
    }

    public function test_required_fields_are_validated(): void
    {
        $this->actingAs($this->user);

        Volt::test('workouts.form')
            ->set('menu_name', '')
            ->set('muscle_ids', [])
            ->call('save')
            ->assertHasErrors(['menu_name' => 'required', 'muscle_ids' => 'required']);

        $this->assertSame(0, Workout::count());
    }

    public function test_plan_link_prefills_the_form(): void
    {
        $plan = WorkoutPlan::factory()->for($this->user)->create(['menu_name' => '胸の日']);
        $plan->muscles()->attach($this->chest);

        $this->actingAs($this->user)
            ->get(route('workouts.create', ['plan' => $plan->id]))
            ->assertOk()
            ->assertSee('通知プランの内容を入力済みです');

        Volt::test('workouts.form', ['planId' => $plan->id])
            ->assertSet('menu_name', '胸の日')
            ->assertSet('muscle_ids', [(string) $this->chest->id]);
    }

    public function test_other_users_plan_cannot_be_opened(): void
    {
        $plan = WorkoutPlan::factory()->create();

        $this->actingAs($this->user)
            ->get(route('workouts.create', ['plan' => $plan->id]))
            ->assertForbidden();
    }

    public function test_guest_is_redirected_to_login_and_back(): void
    {
        $plan = WorkoutPlan::factory()->for($this->user)->create();
        $url = route('workouts.create', ['plan' => $plan->id]);

        $this->get($url)->assertRedirect(route('login'));
        $this->assertSame($url, session('url.intended'));
    }

    public function test_muscle_link_prefills_the_form_with_its_first_exercise(): void
    {
        Exercise::factory()->for($this->chest)->create(['name' => 'ベンチプレス', 'default_rest_days' => 3]);
        Exercise::factory()->for($this->chest)->create(['name' => '腕立て伏せ']);

        $this->actingAs($this->user)
            ->get(route('workouts.create', ['muscle' => $this->chest->id]))
            ->assertOk()
            ->assertSee('選んだ筋肉の種目を入力済みです');

        Volt::test('workouts.form', ['muscleId' => $this->chest->id])
            ->assertSet('menu_name', 'ベンチプレス')
            ->assertSet('muscle_ids', [(string) $this->chest->id])
            ->assertSet('rest_days', 3)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(1, Workout::count());
    }

    public function test_muscle_link_without_exercise_uses_muscle_name_as_menu(): void
    {
        $this->actingAs($this->user);

        Volt::test('workouts.form', ['muscleId' => $this->chest->id])
            ->assertSet('menu_name', '大胸筋')
            ->assertSet('muscle_ids', [(string) $this->chest->id]);
    }

    public function test_muscle_link_still_shows_confirmation_when_resting(): void
    {
        Exercise::factory()->for($this->chest)->create();
        $this->makeChestResting();
        $this->actingAs($this->user);

        Volt::test('workouts.form', ['muscleId' => $this->chest->id])
            ->call('save')
            ->assertSee('休息中の筋肉が含まれています');

        $this->assertSame(1, Workout::count());
    }

    public function test_unknown_muscle_link_returns_not_found(): void
    {
        $this->actingAs($this->user)
            ->get(route('workouts.create', ['muscle' => 999999]))
            ->assertNotFound();
    }
}
