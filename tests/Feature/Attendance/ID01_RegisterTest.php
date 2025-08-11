<?php

namespace Tests\Feature\Attendance;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * ID01 会員登録機能（一般ユーザー）
 *
 * テスト内容（シート準拠）：
 * - 名前が未入力の場合、バリデーションメッセージが表示される
 * - メールアドレスが未入力の場合、バリデーションメッセージが表示される
 * - パスワードが8文字未満の場合、バリデーションメッセージが表示される
 * - パスワードが一致しない場合、バリデーションメッセージが表示される
 * - パスワードが未入力の場合、バリデーションメッセージが表示される
 * - フォームに内容が入力されていた場合、データが正常に保存される
 */
class ID01_RegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_名前が未入力だとエラーになる(): void
    {
        $res = $this->post('/register', [
            'email' => 'a@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $res->assertSessionHasErrors(['name']);
    }

    public function test_メールアドレス未入力でエラー(): void
    {
        $res = $this->post('/register', [
            'name' => 'あみ',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $res->assertSessionHasErrors(['email']);
    }

    public function test_パスワード8文字未満でエラー(): void
    {
        $res = $this->post('/register', [
            'name' => 'あみ',
            'email' => 'a@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $res->assertSessionHasErrors(['password']);
    }

    public function test_パスワード不一致でエラー(): void
    {
        $res = $this->post('/register', [
            'name' => 'あみ',
            'email' => 'a@example.com',
            'password' => 'password123',
            'password_confirmation' => 'DIFF-password',
        ]);

        $res->assertSessionHasErrors(['password']);
    }

    public function test_パスワード未入力でエラー(): void
    {
        $res = $this->post('/register', [
            'name' => 'あみ',
            'email' => 'a@example.com',
            'password_confirmation' => '',
        ]);

        $res->assertSessionHasErrors(['password']);
    }

    public function test_正しく入力すると保存され_認証メールが送られる(): void
    {
        Notification::fake();

        $res = $this->post('/register', [
            'name' => 'あみ',
            'email' => 'a@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $res->assertRedirect();

        $this->assertDatabaseHas('users', ['email' => 'a@example.com']);
        $user = User::where('email', 'a@example.com')->first();
        $this->assertNotNull($user);

        Notification::assertSentTo($user, VerifyEmail::class);
    }
}