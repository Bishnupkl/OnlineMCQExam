<?php

namespace Database\Seeders;

use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Seeder;

class TeacherUserSeeder extends Seeder
{
    public function run(): void
    {
        $teacher = Teacher::updateOrCreate(
            ['t_email' => 'teacher@mcq.com'],
            [
                't_name' => 'MCQ Teacher',
                't_gender' => 'not specified',
                't_address' => 'Campus',
                't_phone' => '0000000000',
                't_password' => 'teacher',
                'subject' => 'General',
                'rdate' => now()->toDateString(),
                'permission' => 'active',
            ]
        );

        User::updateOrCreate(
            ['email' => 'teacher@mcq.com'],
            [
                'name' => $teacher->t_name,
                'password' => 'teacher',
                'role' => 'teacher',
                'profile_type' => Teacher::class,
                'profile_id' => $teacher->t_id,
            ]
        );
    }
}
