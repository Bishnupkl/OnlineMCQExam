<?php

namespace Database\Seeders;

use App\Models\AdminAccount;
use App\Models\ExamDate;
use App\Models\Notice;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,
            StudentUserSeeder::class,
            TeacherUserSeeder::class,
            QuestionsFromSqlSeeder::class,
        ]);

        $admin = AdminAccount::updateOrCreate(
            ['email' => 'admin@example.com'],
            ['password' => 'password']
        );

        User::updateOrCreate(
            ['email' => $admin->email],
            [
                'name' => 'Administrator',
                'password' => 'password',
                'role' => 'admin',
                'profile_type' => AdminAccount::class,
                'profile_id' => $admin->id,
            ]
        );

        $teacher = Teacher::updateOrCreate(
            ['t_email' => 'teacher@example.com'],
            [
                't_name' => 'Exam Teacher',
                't_gender' => 'not specified',
                't_address' => 'Campus',
                't_phone' => '0000000000',
                't_password' => 'password',
                'subject' => 'General',
                'rdate' => now()->toDateString(),
                'permission' => 'active',
            ]
        );

        User::updateOrCreate(
            ['email' => $teacher->t_email],
            [
                'name' => $teacher->t_name,
                'password' => 'password',
                'role' => 'teacher',
                'profile_type' => Teacher::class,
                'profile_id' => $teacher->t_id,
            ]
        );

        ExamDate::updateOrCreate(['id' => 1], ['edate' => now()->toDateString()]);

        Notice::updateOrCreate(
            ['n_heading' => 'Entrance exam schedule'],
            [
                'n_date' => now(),
                'n_text' => 'Exam window is open today.',
                'n_description' => 'Registered students can sign in and start the exam from the dashboard.',
            ]
        );

    }
}
