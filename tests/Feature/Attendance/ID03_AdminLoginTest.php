<?php

namespace Tests\Feature\Attendance;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * ID03 ログイン認証機能（管理者）
 *
 * テスト内容（シート準拠）：
 * - メールアドレスが未入力の場合、バリデーションメッセージが表示される
 * - パスワードが未入力の場合、バリデーションメッセージが表示される
 * - 登録内容と一致しない場合、バリデーションメッセージが表示される
 * - 正しい情報を入力した場合にログインできる（正しく動作することも念の為確認する）
 */
class ID03_AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(): User
    {
        return User::factory()->create([
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'is_admin' => true,
        ]);
    }

    public function test_メール未入力でエラー(): void
    {
        $res = $this->post('/admin/login', [
            'password' => 'password123',
        ]);

        $res->assertSessionHasErrors(['email']);
    }

    public function test_パスワード未入力でエラー(): void
    {
        $res = $this->post('/admin/login', [
            'email' => 'admin@example.com',
        ]);

        $res->assertSessionHasErrors(['password']);
    }

    public function test_不一致でエラー(): void
    {
        $this->makeAdmin();

        $res = $this->post('/admin/login', [
            'email' => 'wrong@example.com',
            'password' => 'password123',
        ]);

        $res->assertSessionHasErrors(['email']);
    }

    public function test_正しくログインできる(): void
    {
        $admin = $this->makeAdmin();

        $res = $this->post('/admin/login', [
            'email' => 'admin@example.com',
            'password' => 'password123',
        ]);

        $res->assertRedirect();
        $this->assertAuthenticatedAs($admin);
    }
}
