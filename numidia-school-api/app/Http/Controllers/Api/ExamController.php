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
        ]);

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

                foreach ($questionData['choices'] as $choiceData) {
                    $question->choices()->create(['content' => $choiceData['content']]);
                }
            }
        });

        return response()->json(200);
    }



    public function create_answers(Request $request, $exam)
    {
        $exam = Exam::find($exam);
        $data = $request->validate([
            'answers.*.question_id' => 'required|exists:questions,id',
            'answers.*.answer' => 'required',
            'answers.*.score' => 'required',
        ]);

        $user = User::find($request->user_id);
        if ($user->student) {
            $studentId = $user->student->id;
            DB::transaction(function () use ($data, $exam, $studentId) {

                foreach ($data['answers'] as $answerData) {
                    $question = $exam->questions()->find($answerData['question_id']);


                    $answer = new Answer([
                        'answer' => $answerData['answer'],
                        'score' => $answerData['score'],
                    ]);

                    $answer->student_id = $studentId;
                    $answer->save();

                    $question->answers()->save($answer);
                    $exam->answers()->save($answer);
                }
            });
        }


        return response()->json(200);
    }


    public function index()
    {
        $exams = Exam::with(['teacher'])->get();
        return response()->json($exams, 200);
    }

    public function all()
    {
        $exams = Exam::with(['questions.choices', 'answers', 'teacher'])->get();
        return response()->json($exams, 200);
    }


    public function student_exam($exam, $studentId)
    {
        $exam = Exam::find($exam);
        $student_exam = $exam->answers()->where('student_id', $studentId)->get();
        return response()->json($student_exam, 200);
    }

    public function student_exams($studentId)
    {
        $student = Student::with(['user'])->find($studentId);
        $student['exams'] = $student->exams;
        return response()->json($student, 200);
    }
    public function Teacher_exams($teacherId)
    {
        $student_exams = Teacher::with(['exam.answers', 'exam.questions.choices'])->find($teacherId);
        return response()->json($student_exams, 200);
    }

    public function delete($exam)
    {
        $exam = Exam::find($exam);
        $exam->delete();
        return response()->json(200);
    }
    public function show($exam)
    {
        $exam = Exam::with(['questions.choices', 'students.answers.question.choices', 'students.user', "students.level"])->find($exam);
        return response()->json($exam, 200);
    }

    public function update(Request $request, $exam)
    {
        $data = $request->validate([
            'title' => 'required|string',
            'duration' => 'required|integer',
            'nb_question' => 'required|integer',
        ]);

        $exam = Exam::find($exam);
        $exam->update([
            'title' => $data['title'],
            'duration' => $data['duration'],
            'nb_question' => $data['nb_question'],
            'status' => $data['status'],
        ]);

        return response()->json($exam);
    }

    public function close_exam($exam)
    {
        $exam = Exam::find($exam);
        $exam->update(['status' => 'closed']);
        return response()->json(200);
    }

    public function open_exam($exam)
    {
        $exam = Exam::find($exam);
        $exam->update(['status' => 'opened']);
        return response()->json(200);
    }
}
