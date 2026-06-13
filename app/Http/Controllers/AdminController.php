<?php

namespace App\Http\Controllers;

use App\Models\ExamDate;
use App\Models\Notice;
use App\Models\Question;
use App\Models\Result;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    public function overview(Request $request): JsonResponse
    {
        $this->requireStaff($request);

        return response()->json([
            'students' => Student::query()->count(),
            'teachers' => Teacher::query()->count(),
            'questions' => Question::query()->count(),
            'results' => Result::query()->latest('mark_obtained')->limit(25)->get(),
            'student_rows' => Student::query()
                ->orderBy('id')
                ->get(['id', 'name', 'address', 'fatname', 'dob', 'phone', 'email', 'gender', 'exam_status']),
            'teacher_rows' => Teacher::query()
                ->orderBy('t_id')
                ->get(['t_id', 't_name', 'subject', 't_gender', 't_address', 't_phone', 't_email', 'permission']),
            'exam_date' => ExamDate::query()->latest('id')->value('edate'),
        ]);
    }

    public function questions(Request $request): JsonResponse
    {
        $this->requireStaff($request);

        return response()->json([
            'questions' => Question::query()
                ->latest('q_id')
                ->get()
                ->makeVisible('correct_ans'),
        ]);
    }

    public function storeQuestion(Request $request): JsonResponse
    {
        $user = $this->requireStaff($request);
        $data = $request->validate([
            'question' => ['required', 'string', 'max:2000'],
            'choice1' => ['required', 'string', 'max:255'],
            'choice2' => ['required', 'string', 'max:255'],
            'choice3' => ['required', 'string', 'max:255'],
            'choice4' => ['required', 'string', 'max:255'],
            'correct_ans' => ['required', 'string', 'max:255'],
            'mark' => ['required', 'numeric', 'min:0'],
        ]);

        abort_unless(in_array($data['correct_ans'], [
            $data['choice1'],
            $data['choice2'],
            $data['choice3'],
            $data['choice4'],
        ], true), 422, 'Correct answer must match one of the choices.');

        $question = Question::create([
            ...$data,
            'uploaded_by' => $user['email'],
        ])->makeVisible('correct_ans');

        return response()->json(['question' => $question], 201);
    }

    public function deleteQuestion(Request $request, Question $question): JsonResponse
    {
        $this->requireStaff($request);
        $question->delete();

        return response()->json(['message' => 'Question deleted.']);
    }

    public function storeNotice(Request $request): JsonResponse
    {
        $this->requireStaff($request);
        $data = $request->validate([
            'n_heading' => ['required', 'string', 'max:255'],
            'n_text' => ['nullable', 'string', 'max:255'],
            'n_description' => ['nullable', 'string', 'max:5000'],
        ]);

        $notice = Notice::create([
            ...$data,
            'n_date' => now(),
        ]);

        return response()->json(['notice' => $notice], 201);
    }

    public function storeStudent(Request $request): JsonResponse
    {
        $this->requireStaff($request, ['admin']);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:100'],
            'fatname' => ['nullable', 'string', 'max:255'],
            'dob' => ['nullable', 'date'],
            'phone' => ['nullable', 'string', 'max:110'],
            'email' => ['required', 'email', 'max:255', Rule::unique('stu_reg', 'email')],
            'password' => ['required', 'string', 'min:6'],
            'gender' => ['nullable', 'string', 'max:20'],
            'exam_status' => ['nullable', Rule::in(['not taken', 'taken'])],
        ]);

        $student = Student::create([
            ...$data,
            'reg_date' => now()->toDateString(),
            'exam_status' => $data['exam_status'] ?? 'not taken',
        ]);

        return response()->json(['student' => $student], 201);
    }

    public function storeResult(Request $request): JsonResponse
    {
        $this->requireStaff($request, ['admin']);
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'ques_attempted' => ['required', 'integer', 'min:0'],
            'mark_obtained' => ['required', 'numeric'],
            'right_answer' => ['required', 'integer', 'min:0'],
            'wrong_answer' => ['required', 'integer', 'min:0'],
            'status' => ['required', Rule::in(['not taken', 'Passed', 'Failed'])],
        ]);

        $result = Result::updateOrCreate(
            ['email' => $data['email']],
            $data
        );

        return response()->json(['result' => $result], 201);
    }

    public function setExamDate(Request $request): JsonResponse
    {
        $this->requireStaff($request, ['admin']);
        $data = $request->validate([
            'edate' => ['required', 'date'],
        ]);

        $examDate = ExamDate::updateOrCreate(['id' => 1], $data);

        return response()->json(['exam_date' => $examDate]);
    }

    public function storeTeacher(Request $request): JsonResponse
    {
        $this->requireStaff($request, ['admin']);
        $data = $request->validate([
            't_name' => ['required', 'string', 'max:50'],
            't_gender' => ['nullable', 'string', 'max:20'],
            't_address' => ['nullable', 'string', 'max:50'],
            't_phone' => ['nullable', 'string', 'max:100'],
            't_email' => ['required', 'email', 'max:50', Rule::unique('teacher_reg', 't_email')],
            't_password' => ['required', 'string', 'min:6'],
            'subject' => ['nullable', 'string', 'max:50'],
            'permission' => ['nullable', 'string', 'max:200'],
        ]);

        $teacher = Teacher::create([
            ...$data,
            'rdate' => now()->toDateString(),
        ]);

        return response()->json(['teacher' => $teacher], 201);
    }

    private function requireStaff(Request $request, array $roles = ['admin', 'teacher']): array
    {
        abort_unless($request->session()->has('oee_user'), 401, 'Authentication required.');

        $user = $request->session()->get('oee_user');
        abort_unless(in_array($user['role'], $roles, true), 403, 'Insufficient permission.');

        return $user;
    }
}
