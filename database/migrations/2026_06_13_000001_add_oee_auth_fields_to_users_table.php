<?php

use App\Models\AdminAccount;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'role')) {
                $table->string('role', 20)->default('student')->after('password');
            }

            if (!Schema::hasColumn('users', 'profile_type')) {
                $table->string('profile_type')->nullable()->after('role');
            }

            if (!Schema::hasColumn('users', 'profile_id')) {
                $table->unsignedBigInteger('profile_id')->nullable()->after('profile_type');
            }
        });

        $this->backfillUsers();
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (['profile_id', 'profile_type', 'role'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    private function backfillUsers(): void
    {
        AdminAccount::query()->each(function (AdminAccount $admin): void {
            $this->syncUser(
                $admin->email,
                'Administrator',
                $admin->password,
                'admin',
                AdminAccount::class,
                $admin->id
            );
        });

        Student::query()->each(function (Student $student): void {
            $this->syncUser(
                $student->email,
                $student->name,
                $student->password,
                'student',
                Student::class,
                $student->id
            );
        });

        Teacher::query()->each(function (Teacher $teacher): void {
            $this->syncUser(
                $teacher->t_email,
                $teacher->t_name,
                $teacher->t_password,
                'teacher',
                Teacher::class,
                $teacher->t_id
            );
        });
    }

    private function syncUser(string $email, string $name, string $password, string $role, string $profileType, int $profileId): void
    {
        User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::needsRehash($password) ? Hash::make($password) : $password,
                'role' => $role,
                'profile_type' => $profileType,
                'profile_id' => $profileId,
            ]
        );
    }
};
