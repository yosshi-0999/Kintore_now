<?php

namespace Tests\Feature;

use App\Models\Muscle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');
    }

    public function test_authenticated_users_can_visit_the_dashboard(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get('/dashboard');
        $response->assertStatus(200);
    }

    public function test_dashboard_links_each_muscle_to_the_workout_form(): void
    {
        $chest = Muscle::factory()->create(['name' => '大胸筋']);

        $this->actingAs(User::factory()->create())
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('muscle-map.jpg')
            ->assertSee(route('workouts.create', ['muscle' => $chest->id]), false)
            ->assertSee('大胸筋を記録する');
    }
}
