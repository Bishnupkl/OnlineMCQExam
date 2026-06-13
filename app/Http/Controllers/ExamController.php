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
use Illuminate\Support\Facades\DB;

class ExamController extends Controller
{
    public function dashboard(Request $request): JsonResponse
    {
        $user = $this->requireUser($request);
        $student = $user['role'] === 'student'
            ? Student::where('email', $user['email'])->first()
            : null;
        $resultPublished = $this->resultPublished();

        return response()->json([
            'user' => $user,
            'exam_date' => ExamDate::query()->latest('id')->value('edate'),
            'result_date' => DB::table('rdate')->where('id', 1)->value('rdate'),
            'result_published' => $resultPublished,
            'exam_taken' => $student?->exam_status === 'taken',
            'question_count' => Question::query()->count(),
            'notice_count' => Notice::query()->count(),
            'result' => $user['role'] === 'student' && $resultPublished
                ? Result::where('email', $user['email'])->first()
                : null,
        ]);
    }

    public function notices(): JsonResponse
    {
        return response()->json([
            'notices' => Notice::query()->latest('n_date')->limit(20)->get(),
        ]);
    }

    public function publicStats(): JsonResponse
    {
        return response()->json([
            'seats' => 55,
            'students' => Student::query()->count(),
            'teachers' => Teacher::query()->count(),
            'subjects' => Teacher::query()
                ->whereNotNull('subject')
                ->where('subject', '!=', '')
                ->distinct('subject')
                ->count('subject') ?: 4,
            'questions' => Question::query()->count(),
            'notices' => Notice::query()->count(),
        ]);
    }

    public function exam(Request $request): JsonResponse
    {
        $user = $this->requireRole($request, 'student');
        $student = Student::where('email', $user['email'])->firstOrFail();

        if ($student->exam_status === 'taken') {
            return response()->json(['message' => 'Exam is already taken.'], 409);
        }

        $examDate = ExamDate::query()->latest('id')->first();
        if ($examDate && !$examDate->edate->isToday()) {
            return response()->json(['message' => 'Exam is not scheduled for today.'], 409);
        }

        return response()->json([
            'duration_minutes' => 45,
            'questions' => Question::query()
                ->inRandomOrder()
                ->get()
                ->map(fn (Question $question) => [
                    'q_id' => $question->q_id,
                    'question' => $question->question,
                    'choices' => [
                        $question->choice1,
                        $question->choice2,
                        $question->choice3,
                        $question->choice4,
                    ],
                    'mark' => $question->mark,
                ]),
        ]);
    }

    public function submit(Request $request): JsonResponse
    {
        $user = $this->requireRole($request, 'student');
        $data = $request->validate([
            'answers' => ['required', 'array'],
            'answers.*' => ['nullable', 'string'],
        ]);

        $student = Student::where('email', $user['email'])->firstOrFail();
        if ($student->exam_status === 'taken') {
            return response()->json(['message' => 'Exam is already taken.'], 409);
        }

        $questions = Question::query()
            ->whereIn('q_id', array_keys($data['answers']))
            ->get()
            ->keyBy('q_id');

        $score = [
            'attempted' => 0,
            'marks' => 0,
            'right' => 0,
            'wrong' => 0,
        ];

        foreach ($data['answers'] as $questionId => $answer) {
            if ($answer === null || $answer === '' || !$questions->has((int) $questionId)) {
                continue;
            }

            $question = $questions[(int) $questionId];
            $score['attempted']++;

            if ($answer === $question->correct_ans) {
                $score['marks'] += (float) $question->mark;
                $score['right']++;
            } else {
                $score['marks'] -= 0.25;
                $score['wrong']++;
            }
        }

        $result = DB::transaction(function () use ($student, $user, $score) {
            $status = $score['marks'] >= 8 ? 'Passed' : 'Failed';

            $result = Result::updateOrCreate(
                ['email' => $user['email']],
                [
                    'ques_attempted' => $score['attempted'],
                    'mark_obtained' => $score['marks'],
                    'right_answer' => $score['right'],
                    'wrong_answer' => $score['wrong'],
                    'status' => $status,
                ]
            );

            $student->update(['exam_status' => 'taken']);

            return $result;
        });

        return response()->json([
            'result' => $this->resultPublished() ? $result : null,
            'result_published' => $this->resultPublished(),
            'message' => 'Exam submitted. Result will be visible after admin publishes it.',
        ]);
    }

    private function resultPublished(): bool
    {
        $resultDate = DB::table('rdate')->where('id', 1)->value('rdate');

        return $resultDate !== null && now()->toDateString() >= $resultDate;
    }

    private function requireUser(Request $request): array
    {
        abort_unless($request->user(), 401, 'Authentication required.');

        return $request->user()->sessionPayload();
    }

    private function requireRole(Request $request, string $role): array
    {
        $user = $this->requireUser($request);
        abort_unless($user['role'] === $role, 403, 'Insufficient permission.');

        return $user;
    }
}
