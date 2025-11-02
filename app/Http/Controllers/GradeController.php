<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class GradeController extends Controller
{
    public function index()
    {
        return view('grader');
    }

    public function evaluate(Request $request)
    {
        $request->validate([
            'student_name' => 'required|string',
            'image' => 'required|string' // base64 string from frontend
        ]);

        $imageBase64 = $request->input('image');
        $student = $request->input('student_name');

        $prompt = "
You are an English teacher grading short-answer or essay-style questions. 
Please read the student's handwritten or typed response in the image, then grade it using this rubric:

1. Content Completeness (40 pts) – Does the answer address all parts of the question?
2. Logical Clarity (30 pts) – Are ideas coherent and reasoning clear?
3. Language Use (20 pts) – Grammar, vocabulary, and organization.
4. Originality (10 pts) – Insight or creativity.

Return:
- Subscores for each criterion
- Total score (0–100)
- 1–2 sentence feedback summary.
Student Name: {$student}
";

        // GPT-4o vision call
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
            'Content-Type' => 'application/json',
        ])->post('https://api.openai.com/v1/chat/completions', [
            'model' => 'gpt-4o',
            'messages' => [
                ['role' => 'system', 'content' => 'You are a fair, objective teacher.'],
                [
                    'role' => 'user',
                    'content' => [
                        ['type' => 'text', 'text' => $prompt],
                        ['type' => 'image_url', 'image_url' => ['url' => $imageBase64]]
                    ]
                ]
            ],
        ]);

        $result = $response->json();
        $grade = $result['choices'][0]['message']['content'] ?? '⚠️ Unable to generate grading result.';

        return response()->json([
            'ok' => true,
            'data' => [
                'student' => $student,
                'grade' => $grade,
            ]
        ]);
    }
}
