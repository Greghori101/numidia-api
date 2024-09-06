<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Answer;
use App\Models\Exam;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExamController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string',
            'duration' => 'required|integer',
            'nb_question' => 'required|integer',
            'date' => 'required',
            'questions.*.nb_choice' => 'required|integer',
            'questions.*.content' => 'required|string',
            'questions.*.answer' => 'required|string',
            'questions.*.choices' => 'required|array',
            'questions.*.audio' => 'nullable|file',
        ]);

        return DB::transaction(function () use ($data) {
            $exam = Exam::create([
                'title' => $data['title'],
                'duration' => $data['duration'],
                'nb_question' => $data['nb_question'],
                'status' => "pending",
                'date' => $data['date'],
            ]);

            DB::transaction(function () use ($data, $exam) {
                foreach ($data['questions'] as $questionData) {
                    $question = $exam->questions()->create([
                        'nb_choice' => $questionData['nb_choice'],
                        'content' => $questionData['content'],
                        'answer' => $questionData['answer'],
                    ]);

                    if (isset($questionData['audio'])) {
                        $file = $questionData['audio'];
                        $content = file_get_contents($file);
                        $extension = $file->getClientOriginalExtension();
                        $question->audio()->create([
                            'name' => 'question audio',
                            'content' => base64_encode($content),
                            'extension' => $extension,
                        ]);
                    }



                    foreach ($questionData['choices'] as $choiceData) {
                        $question->choices()->create(['content' => $choiceData['content']]);
                    }
                }
            });

            return response()->json(200);
        });
    }

    public function create_answers(Request $request, $exam)
    {
        return DB::transaction(function () use ($request, $exam) {
            // Validate the incoming request data
            $data = $request->validate([
                'answers.*.question_id' => 'required|exists:questions,id',
                'answers.*.score' => 'required',
            ]);

            // Find the exam by ID or fail if not found
            $exam = Exam::findOrFail($exam);

            // Find the user and check if they are a student
            $user = User::findOrFail($request->user['id']);
            if ($user->student) {
                $studentId = $user->student->id;

                // Check if the student has already answered any question for this exam
                $existingAnswer = Answer::where('student_id', $studentId)
                    ->where('exam_id', $exam->id)
                    ->exists();

                if ($existingAnswer) {
                    // Return a response indicating the student cannot take the exam again
                    return response()->json(['message' => 'You have already answered this exam.'], 403);
                }

                foreach ($data['answers'] as $answerData) {
                    $question = $exam->questions()->findOrFail($answerData['question_id']);

                    // Create a new answer
                    $answer = new Answer([
                        'answer' => $answerData['answer'],
                        'score' => $answerData['score'],
                        'student_id' => $studentId,
                        'exam_id' => $exam->id, // Assuming you want to associate the answer with the exam
                    ]);

                    $answer->save();

                    // Associate the answer with the question
                    $question->answers()->save($answer);
                    $exam->answers()->save($answer);
                }
            }

            return response()->json(['message' => 'Answers submitted successfully'], 200);
        });
    }


    public function index()
    {
        $exams = Exam::with(['teacher'])->get();
        return response()->json($exams, 200);
    }

    public function student_exam($exam, $studentId)
    {
        $exam = Exam::findOrFail($exam);
        $student_exam = $exam->answers()->where('student_id', $studentId)->get();
        return response()->json($student_exam, 200);
    }

    public function student_exams($studentId)
    {
        $student = Student::with(['user'])->findOrFail($studentId);
        $student['exams'] = $student->exams;
        return response()->json($student, 200);
    }
    public function Teacher_exams($teacherId)
    {
        $teacher_exams = Teacher::with(['exams.answers', 'exams.questions.choices', 'exams.questions.audio',])->find($teacherId);
        return response()->json($teacher_exams, 200);
    }

    public function delete($exam)
    {
        return DB::transaction(function () use ($exam) {

            $exam = Exam::findOrFail($exam);
            $exam->delete();
            return response()->json(200);
        });
    }
    public function show($exam)
    {
        $exam = Exam::with(['questions.choices', 'questions.audio', 'students.answers.question.choices', 'students.user', "students.level"])->findOrFail($exam);
        return response()->json($exam, 200);
    }

    public function update(Request $request, $exam)
    {
        $data = $request->validate([
            'title' => 'required|string',
            'duration' => 'required|integer',
            'nb_question' => 'required|integer',
        ]);
        return DB::transaction(function () use ($data, $exam) {
            $exam = Exam::find($exam);
            if ($exam->status === 'pending') {
                $exam->update([
                    'title' => $data['title'],
                    'duration' => $data['duration'],
                    'nb_question' => $data['nb_question'],
                    'status' => $data['status'],
                ]);

                return response()->json($exam);
            } else {
                return response()->json(['message' => 'the exam is not in pending state cannot perform any updates'], 400);
            }
        });
    }

    public function close_exam($exam)
    {
        $exam = Exam::findOrFail($exam);
        $exam->update(['status' => 'closed']);
        return response()->json(200);
    }

    public function open_exam($exam)
    {
        $exam = Exam::findOrFail($exam);
        $exam->update(['status' => 'opened']);
        return response()->json(200);
    }
}
