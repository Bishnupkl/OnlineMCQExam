<?php

namespace Database\Seeders;

use App\Models\AdminAccount;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = AdminAccount::updateOrCreate(
            ['email' => 'admin@mcq.com'],
            ['password' => 'admin']
        );

        User::updateOrCreate(
            ['email' => 'admin@mcq.com'],
            [
                'name' => 'MCQ Admin',
                'password' => 'admin',
                'role' => 'admin',
                'profile_type' => AdminAccount::class,
                'profile_id' => $admin->id,
            ]
        );
    }
}
