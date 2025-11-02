<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class GradeController extends Controller
{
    public function evaluate(Request $request)
    {
        $request->validate([
            'student_name' => 'required|string',
            'image' => 'required|string', // base64 Data URL
        ]);

        $student = $request->input('student_name');
        $imageData = $request->input('image');

        $prompt = "
You are an English teacher grading short-answer or essay questions.
Please assess the student's answer based on the following rubric:

1. Content Completeness (40 pts)
2. Logical Clarity (30 pts)
3. Language Use (20 pts)
4. Originality (10 pts)

Return the result in clear English with:
- Subscores per criterion
- Total score (0–100)
- 1–2 sentences of feedback.

Student name: {$student}
";

        // === GPT-4o Vision Request ===
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
            'Content-Type'  => 'application/json',
        ])->post('https://api.openai.com/v1/chat/completions', [
            'model' => 'gpt-4o',
            'messages' => [
                ['role' => 'system', 'content' => 'You are a fair, concise English grader.'],
                [
                    'role' => 'user',
                    'content' => [
                        ['type' => 'text', 'text' => $prompt],
                        ['type' => 'image_url', 'image_url' => ['url' => $imageData]]
                    ],
                ],
            ],
        ]);

        if ($response->failed()) {
            return response()->json([
                'ok' => false,
                'error' => 'Failed to contact OpenAI: ' . $response->body(),
            ], 500);
        }

        $result = $response->json();
        $gradeText = $result['choices'][0]['message']['content'] ?? '⚠️ Unable to generate grading result.';

        return response()->json([
            'ok' => true,
            'data' => [
                'student' => $student,
                'grade'   => $gradeText,
            ]
        ]);
    }
}
