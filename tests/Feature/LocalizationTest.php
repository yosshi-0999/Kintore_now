<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class LocalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_is_displayed_in_japanese(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('メールアドレスとパスワードを入力してログインしてください')
            ->assertSee('ログイン状態を保持する');
    }

    public function test_login_failure_message_is_in_japanese(): void
    {
        $user = User::factory()->create();

        Volt::test('auth.login')
            ->set('email', $user->email)
            ->set('password', 'wrong-password')
            ->call('login')
            ->assertSee('メールアドレスまたはパスワードが正しくありません。');
    }

    public function test_common_validation_messages_are_in_japanese(): void
    {
        Volt::test('auth.register')
            ->set('name', '')
            ->set('email', 'not-an-email')
            ->call('register')
            ->assertSee('名前を入力してください。')
            ->assertSee('メールアドレスには正しいメールアドレスを指定してください。');
    }

    public function test_settings_screen_is_displayed_in_japanese(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('settings.profile'))
            ->assertOk()
            ->assertSee('名前とメールアドレスを変更します')
            ->assertSee('ダッシュボード');
    }
}
