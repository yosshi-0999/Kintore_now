<?php

namespace Tests\Feature;

use App\Models\Muscle;
use App\Models\User;
use App\Models\Workout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class UserPageTest extends TestCase
{
    use RefreshDatabase;

    private function ownerWithWorkout(): User
    {
        $owner = User::factory()->create(['name' => 'Owner']);
        $workout = Workout::factory()->for($owner)->create(['menu_name' => 'ひみつのメニュー']);
        $workout->muscles()->attach(Muscle::factory()->create());

        return $owner;
    }

    public function test_mypage_redirects_to_own_page_with_form(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('mypage'))->assertRedirect(route('users.show', $user));
        $this->actingAs($user)->get(route('users.show', $user))
            ->assertOk()
            ->assertSee('筋トレ内容を記録')
            ->assertSee('休ませる筋肉');
    }

    public function test_other_user_without_permission_sees_only_name(): void
    {
        $owner = $this->ownerWithWorkout();

        $this->actingAs(User::factory()->create())
            ->get(route('users.show', $owner))
            ->assertOk()
            ->assertSee('Owner')
            ->assertDontSee('ひみつのメニュー')
            ->assertSee('許可されたユーザーのみ閲覧できます');
    }

    public function test_permitted_user_sees_records_but_not_form(): void
    {
        $owner = $this->ownerWithWorkout();
        $viewer = User::factory()->create();
        $owner->viewers()->attach($viewer);

        $this->actingAs($viewer)
            ->get(route('users.show', $owner))
            ->assertOk()
            ->assertSee('ひみつのメニュー')
            ->assertDontSee('筋トレ内容を記録');
    }

    public function test_permission_can_be_granted_and_revoked(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();

        $this->actingAs($owner);

        Volt::test('permissions.index')
            ->set('email', $viewer->email)
            ->call('grant')
            ->assertHasNoErrors();
        $this->assertTrue($owner->viewers()->whereKey($viewer->id)->exists());

        Volt::test('permissions.index')->call('revoke', $viewer->id);
        $this->assertFalse($owner->viewers()->whereKey($viewer->id)->exists());
    }

    public function test_cannot_grant_permission_to_self(): void
    {
        $owner = User::factory()->create();

        $this->actingAs($owner);

        Volt::test('permissions.index')
            ->set('email', $owner->email)
            ->call('grant')
            ->assertHasErrors(['email' => 'not_in']);
    }

    public function test_guest_cannot_view_user_pages(): void
    {
        $this->get(route('users.show', User::factory()->create()))->assertRedirect(route('login'));
    }
}
