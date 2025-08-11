<?php

namespace Tests\Feature\Attendance;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * ID02 ログイン認証機能（一般ユーザー）
 *
 * テスト内容（シート準拠）：
 * - メールアドレスが未入力の場合、バリデーションメッセージが表示される
 * - パスワードが未入力の場合、バリデーションメッセージが表示される
 * - 登録内容と一致しない場合、バリデーションメッセージが表示される
 * - 正しい情報を入力した場合にログインできる（正しく動作することも念の為確認する）
 */
class ID02_LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_メール未入力でエラー(): void
    {
        $res = $this->post('/login', [
            'password' => 'password123',
        ]);

        $res->assertSessionHasErrors(['email']);
        $this->assertGuest();
    }

    public function test_パスワード未入力でエラー(): void
    {
        $res = $this->post('/login', [
            'email' => 'a@example.com',
        ]);

        $res->assertSessionHasErrors(['password']);
        $this->assertGuest();
    }

    public function test_登録と一致しないとエラー(): void
    {
        User::factory()->create([
            'email' => 'real@example.com',
            'password' => Hash::make('password123'),
        ]);

        $res = $this->post('/login', [
            'email' => 'fake@example.com',
            'password' => 'password123',
        ]);

        $res->assertSessionHasErrors(['email']);
        $this->assertGuest();
    }

    public function test_正しい情報でログインできる(): void
    {
        $user = User::factory()->create([
            'email' => 'a@example.com',
            'password' => Hash::make('password123'),
        ]);

        $res = $this->post('/login', [
            'email'    => 'a@example.com',
            'password' => 'password123',
        ]);

        $res->assertRedirect(route('attendance.index'));
        $this->assertAuthenticatedAs($user);
    }
}
