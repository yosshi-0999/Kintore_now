<?php

namespace Tests\Feature;

use App\Models\Exercise;
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
        $workout = Workout::factory()->for($owner)->create(['menu_name' => 'テスト用メニュー']);
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
            ->assertDontSee('テスト用メニュー')
            ->assertDontSee('60kg × 10回 × 3セット')
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
            ->assertSee('テスト用メニュー')
            ->assertSee('60kg × 10回 × 3セット')
            ->assertDontSee('筋トレ内容を記録');
    }

    public function test_workout_recorded_before_volume_columns_is_shown_without_volume(): void
    {
        $user = User::factory()->create();
        Workout::factory()->for($user)->create([
            'menu_name' => '以前の記録',
            'weight' => null,
            'reps' => null,
            'sets' => null,
        ]);

        $this->actingAs($user)
            ->get(route('users.show', $user))
            ->assertOk()
            ->assertSee('以前の記録')
            ->assertDontSee('回 ×');
    }

    public function test_personal_bests_are_shown_to_owner_and_permitted_user_only(): void
    {
        $owner = User::factory()->create();
        $bench = Exercise::factory()->create(['name' => 'ベンチプレス']);
        Workout::factory()->for($owner)->create([
            'exercise_id' => $bench->id,
            'weight' => 82.5,
            'reps' => 5,
            'sets' => 3,
            'performed_at' => '2026-10-16 10:00:00',
        ]);
        $viewer = User::factory()->create();
        $owner->viewers()->attach($viewer);

        foreach ([$owner, $viewer] as $user) {
            $this->actingAs($user)
                ->get(route('users.show', $owner))
                ->assertOk()
                ->assertSeeInOrder(['自己ベスト', 'ベンチプレス', '82.5kg', '× 5回 × 3セット', '2026/10/16 達成', '休ませる筋肉']);
        }

        $this->actingAs(User::factory()->create())
            ->get(route('users.show', $owner))
            ->assertDontSee('自己ベスト')
            ->assertDontSee('82.5kg');
    }

    /**
     * 開いた状態で表示される欄の見出し
     *
     * @return list<string>
     */
    private function openSectionTitles(string $html): array
    {
        preg_match_all('/<details\b([^>]*)>(.*?)<\/summary>/su', $html, $matches, PREG_SET_ORDER);

        return collect($matches)
            ->filter(fn (array $m) => preg_match('/\sopen\s/', $m[1].' '))
            ->map(fn (array $m) => trim(strip_tags($m[2])))
            ->values()
            ->all();
    }

    public function test_only_personal_best_section_is_open_initially(): void
    {
        $user = User::factory()->create();

        $html = $this->actingAs($user)->get(route('users.show', $user))
            ->assertOk()
            ->assertSee('<details', false)
            ->getContent();

        $this->assertSame(['自己ベスト'], $this->openSectionTitles($html));
        $this->assertSame(7, substr_count($html, '<details'));
    }

    public function test_records_section_is_open_when_searching_by_url(): void
    {
        $user = User::factory()->create();

        $html = $this->actingAs($user)->get(route('users.show', ['user' => $user, 'keyword' => 'ベンチ']))
            ->assertOk()
            ->getContent();

        $this->assertSame(['自己ベスト', '記録'], $this->openSectionTitles($html));
    }

    public function test_weight_chart_is_shown_to_owner_and_permitted_user_only(): void
    {
        $owner = User::factory()->create();
        $bench = Exercise::factory()->create(['name' => 'ベンチプレス']);
        Workout::factory()->for($owner)->create([
            'exercise_id' => $bench->id,
            'weight' => 82.5,
            'performed_at' => '2026-10-16 10:00:00',
        ]);
        $viewer = User::factory()->create();
        $owner->viewers()->attach($viewer);

        foreach ([$owner, $viewer] as $user) {
            $this->actingAs($user)
                ->get(route('users.show', $owner))
                ->assertOk()
                ->assertSee('最高重量の推移')
                ->assertSee('weightChart(', false)
                ->assertSee('2026-10-16');
        }

        $this->actingAs(User::factory()->create())
            ->get(route('users.show', $owner))
            ->assertDontSee('最高重量の推移')
            ->assertDontSee('weightChart(', false);
    }

    public function test_weight_chart_shows_message_without_weighted_records(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('users.show', $user))
            ->assertSee('ここに種目ごとの推移が表示されます')
            ->assertDontSee('weightChart(', false);
    }

    public function test_owner_without_personal_best_sees_how_to_add_one(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('users.show', $user))
            ->assertSee('ここに種目ごとの自己ベストが表示されます');
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

    public function test_user_list_shows_only_users_who_granted_permission(): void
    {
        $viewer = User::factory()->create(['name' => 'Viewer']);
        $granted = User::factory()->create(['name' => 'Granted']);
        $notGranted = User::factory()->create(['name' => 'NotGranted']);
        // 自分が見せているだけの相手は対象外
        $grantedByMe = User::factory()->create(['name' => 'GrantedByMe']);
        $granted->viewers()->attach($viewer);
        $viewer->viewers()->attach($grantedByMe);

        $this->actingAs($viewer)->get(route('users.index'))
            ->assertOk()
            ->assertSee(route('users.show', $granted), false)
            ->assertDontSee(route('users.show', $notGranted), false)
            ->assertDontSee(route('users.show', $grantedByMe), false)
            ->assertDontSee(route('users.show', $viewer), false);
    }

    public function test_user_list_shows_message_when_no_one_granted_permission(): void
    {
        $this->actingAs(User::factory()->create())->get(route('users.index'))
            ->assertOk()
            ->assertSee('まだ閲覧を許可してくれたユーザーはいません。');
    }

    public function test_guest_cannot_view_user_pages(): void
    {
        $this->get(route('users.show', User::factory()->create()))->assertRedirect(route('login'));
    }
}
