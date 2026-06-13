<?php

namespace Database\Seeders;

use App\Models\ExamDate;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;

class StudentUserSeeder extends Seeder
{
    public function run(): void
    {
        $student = Student::updateOrCreate(
            ['email' => 'student@mcq.com'],
            [
                'name' => 'MCQ Student',
                'address' => 'Campus',
                'fatname' => 'Guardian',
                'dob' => '2000-01-01',
                'phone' => '0000000000',
                'password' => 'student',
                'reg_date' => now()->toDateString(),
                'gender' => 'not specified',
                'exam_status' => 'not taken',
            ]
        );

        User::updateOrCreate(
            ['email' => 'student@mcq.com'],
            [
                'name' => $student->name,
                'password' => 'student',
                'role' => 'student',
                'profile_type' => Student::class,
                'profile_id' => $student->id,
            ]
        );

        ExamDate::updateOrCreate(['id' => 1], ['edate' => now()->toDateString()]);
    }
}
