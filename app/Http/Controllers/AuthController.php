<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function session(Request $request): JsonResponse
    {
        return response()->json([
            'user' => Auth::user()?->sessionPayload(),
        ]);
    }

    public function registerStudent(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:100'],
            'fatname' => ['nullable', 'string', 'max:255'],
            'dob' => ['nullable', 'date'],
            'phone' => ['nullable', 'string', 'max:110'],
            'email' => ['required', 'email', 'max:255', Rule::unique('stu_reg', 'email'), Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:6'],
            'gender' => ['nullable', 'string', 'max:20'],
        ]);

        $user = DB::transaction(function () use ($data): User {
            $student = Student::create([
                ...$data,
                'reg_date' => now()->toDateString(),
                'exam_status' => 'not taken',
            ]);

            return User::create([
                'name' => $student->name,
                'email' => $student->email,
                'password' => $data['password'],
                'role' => 'student',
                'profile_type' => Student::class,
                'profile_id' => $student->id,
            ]);
        });

        Auth::login($user);
        $request->session()->regenerate();

        return response()->json(['user' => $user->sessionPayload()], 201);
    }

    public function registerTeacher(Request $request): JsonResponse
    {
        $data = $request->validate([
            't_name' => ['required', 'string', 'max:50'],
            't_gender' => ['nullable', 'string', 'max:20'],
            't_address' => ['nullable', 'string', 'max:50'],
            't_phone' => ['nullable', 'string', 'max:100'],
            't_email' => ['required', 'email', 'max:50', Rule::unique('teacher_reg', 't_email'), Rule::unique('users', 'email')],
            't_password' => ['required', 'string', 'min:6'],
            'subject' => ['nullable', 'string', 'max:50'],
        ]);

        $user = DB::transaction(function () use ($data): User {
            $teacher = Teacher::create([
                ...$data,
                'rdate' => now()->toDateString(),
                'permission' => 'pending',
            ]);

            return User::create([
                'name' => $teacher->t_name,
                'email' => $teacher->t_email,
                'password' => $data['t_password'],
                'role' => 'teacher',
                'profile_type' => Teacher::class,
                'profile_id' => $teacher->t_id,
            ]);
        });

        Auth::login($user);
        $request->session()->regenerate();

        return response()->json(['user' => $user->sessionPayload()], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'role' => ['required', Rule::in(['student', 'teacher', 'admin'])],
        ]);

        if (!Auth::attempt(['email' => $data['email'], 'password' => $data['password']], true)) {
            return response()->json(['message' => 'Invalid email or password.'], 422);
        }

        /** @var User $user */
        $user = Auth::user();
        if ($user->role !== $data['role']) {
            Auth::logout();

            return response()->json(['message' => 'Invalid role for this account.'], 422);
        }

        $request->session()->regenerate();

        return response()->json(['user' => $user->sessionPayload()]);
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Logged out.']);
    }
}
