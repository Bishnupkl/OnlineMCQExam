<?php

namespace App\Http\Controllers;

use App\Models\AdminAccount;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function session(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $request->session()->get('oee_user'),
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
            'email' => ['required', 'email', 'max:255', Rule::unique('stu_reg', 'email')],
            'password' => ['required', 'string', 'min:6'],
            'gender' => ['nullable', 'string', 'max:20'],
        ]);

        $student = Student::create([
            ...$data,
            'reg_date' => now()->toDateString(),
            'exam_status' => 'not taken',
        ]);

        $request->session()->put('oee_user', [
            'role' => 'student',
            'email' => $student->email,
            'name' => $student->name,
        ]);

        return response()->json(['user' => $request->session()->get('oee_user')], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'role' => ['required', Rule::in(['student', 'teacher', 'admin'])],
        ]);

        $user = match ($data['role']) {
            'student' => Student::where('email', $data['email'])->first(),
            'teacher' => Teacher::where('t_email', $data['email'])->first(),
            'admin' => AdminAccount::where('email', $data['email'])->first(),
        };

        if (!$user || !$this->passwordMatches($user, $data['role'], $data['password'])) {
            return response()->json(['message' => 'Invalid email or password.'], 422);
        }

        if ($data['role'] === 'student' && $user->exam_status === 'taken') {
            return response()->json(['message' => 'This student has already taken the exam.'], 409);
        }

        $sessionUser = [
            'role' => $data['role'],
            'email' => $data['role'] === 'teacher' ? $user->t_email : $user->email,
            'name' => $data['role'] === 'teacher' ? $user->t_name : ($user->name ?? 'Administrator'),
        ];

        $request->session()->regenerate();
        $request->session()->put('oee_user', $sessionUser);

        return response()->json(['user' => $sessionUser]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->session()->forget('oee_user');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Logged out.']);
    }

    private function passwordMatches(object $user, string $role, string $plainPassword): bool
    {
        $field = match ($role) {
            'teacher' => 't_password',
            default => 'password',
        };

        $stored = $user->{$field};

        if (Hash::check($plainPassword, $stored)) {
            return true;
        }

        if (hash_equals($stored, $plainPassword)) {
            $user->{$field} = $plainPassword;
            $user->save();

            return true;
        }

        return false;
    }
}
