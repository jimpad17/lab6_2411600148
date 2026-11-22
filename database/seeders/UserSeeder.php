<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Admin now signs in with jimpadillo@gmail.com / JimTrack2026!.
     * The legacy "ADMIN" alias is still mapped to this account in LoginRequest.
     */
    public function run(): void
    {
        $accounts = [
            [
                'email' => 'jimpadillo@gmail.com',
                'name' => 'Jim Padillo',
                'password' => 'JimTrack2026!',
                'role' => 'admin',
                'is_active' => true,
            ],
            [
                'email' => 'juan@jimtrack.local',
                'name' => 'Juan Dela Cruz',
                'password' => 'STAFF123',
                'role' => 'staff',
                'is_active' => true,
            ],
            [
                'email' => 'maria@jimtrack.local',
                'name' => 'Maria Santos',
                'password' => 'STAFF123',
                'role' => 'staff',
                'is_active' => true,
            ],
            [
                'email' => 'test@jimtrack.local',
                'name' => 'Test User',
                'password' => 'STAFF123',
                'role' => 'staff',
                'is_active' => false,
            ],
        ];

        foreach ($accounts as $account) {
            $password = $account['password'];
            unset($account['password']);

            User::updateOrCreate(
                ['email' => $account['email']],
                array_merge($account, [
                    'password' => Hash::make($password),
                    'email_verified_at' => now(),
                ])
            );
        }
    }
}
