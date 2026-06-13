<?php

namespace Database\Seeders;

use App\Models\AdminAccount;
use App\Models\ExamDate;
use App\Models\Notice;
use App\Models\Question;
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

        $questions = [
            [
                'question' => 'A train 110m long travels at 60 km/hr. A man walks at 6 km/hr in the opposite direction. How long does the train take to pass him?',
                'choice1' => '6.6 sec',
                'choice2' => '66 sec',
                'choice3' => '6 sec',
                'choice4' => '5.4 sec',
                'correct_ans' => '6 sec',
            ],
            [
                'question' => 'The maximum speed of a car on a level road of radius 80m, with friction coefficient 0.25, is approximately:',
                'choice1' => '10 m/s',
                'choice2' => '8 m/s',
                'choice3' => '12 m/s',
                'choice4' => '14 m/s',
                'correct_ans' => '14 m/s',
            ],
            [
                'question' => 'Inscription on a tomb is called:',
                'choice1' => 'Epitaph',
                'choice2' => 'Cemetery',
                'choice3' => 'Morgue',
                'choice4' => 'Demagogue',
                'correct_ans' => 'Epitaph',
            ],
        ];

        foreach ($questions as $question) {
            Question::updateOrCreate(
                ['question' => $question['question']],
                [
                    ...$question,
                    'mark' => 1,
                    'uploaded_by' => 'teacher@example.com',
                ]
            );
        }
    }
}
