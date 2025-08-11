<?php

namespace Tests\Feature\Attendance;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\URL;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Auth\Notifications\VerifyEmail;

/**
 * ID16 メール認証機能（一般ユーザー）
 * 
 * 対応するスプレッドシート項目：
 * - ユーザー登録後、認証メールが送信されること
 * - 認証リンクをクリックするとメール認証が完了し、指定画面へ遷移すること
 * - 認証済みユーザーが認証リンクにアクセスした場合は適切に処理されること
 */
class ID16_EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function 会員登録後に認証メールが送信される(): void
    {
        Notification::fake();

        $response = $this->post('/register', [
            'name' => '新規ユーザー',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect('/login');

        $user = \App\Models\User::where('email', 'newuser@example.com')->first();
        $this->assertNotNull($user, 'ユーザーが作成されていません');

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function メール認証誘導画面で「認証はこちらから」ボタンを押下するとメール認証サイトに遷移する(): void
    {
        $user = User::factory()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->actingAs($user)->get($verificationUrl);

        $response->assertRedirect('/attendance'); 
    }

    public function メール認証サイトのメール認証を完了すると、勤怠登録画面に遷移する(): void
    {
        $user = User::factory()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $this->assertFalse($user->hasVerifiedEmail());

        $response = $this->actingAs($user)->get($verificationUrl);
        $response->assertRedirect('/attendance');

        $user->refresh();
        $this->assertTrue($user->hasVerifiedEmail());
    }
}