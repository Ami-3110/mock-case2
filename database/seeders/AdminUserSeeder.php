<?php

// database/seeders/AdminUserSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => '管理者',
                'email' => 'admin@example.com',
                'password' => Hash::make('password123'), // 本番用は必ず変更してください
                'email_verified_at' => now(),
                'is_admin' => true,
            ]
        );
    }
}
