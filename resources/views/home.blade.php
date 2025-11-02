@extends('layouts.app')

@section('title', 'Home - AI Learning Tools')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 via-slate-100 to-gray-200 flex items-center justify-center p-6">
  <div class="bg-white/90 backdrop-blur-xl shadow-xl rounded-2xl p-10 text-center w-full max-w-4xl border border-gray-200">
    <h1 class="text-5xl font-extrabold mb-4 bg-gradient-to-r from-indigo-600 via-blue-600 to-cyan-500 bg-clip-text text-transparent">
      🤖 AI Learning Assistant
    </h1>
    <p class="text-gray-600 mb-10 text-lg">Your smart study companion — powered by GPT & Vision AI</p>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">

      {{-- 📘 AI Quiz Solver --}}
      <a href="{{ route('solve.index') }}" 
         class="group relative overflow-hidden rounded-2xl p-6 bg-white border-2 border-transparent shadow-md hover:border-indigo-500 hover:shadow-indigo-200 transition transform hover:-translate-y-1">
        <div class="text-4xl mb-3">📘</div>
        <div class="font-bold text-xl mb-1 text-gray-900 group-hover:text-indigo-700">AI Quiz Solver</div>
        <p class="text-sm text-gray-500">Upload or snap a question to solve instantly</p>
      </a>

      {{-- ✍️ English Corrector --}}
      <a href="{{ route('corrector.index') }}" 
         class="group relative overflow-hidden rounded-2xl p-6 bg-white border-2 border-transparent shadow-md hover:border-emerald-500 hover:shadow-emerald-200 transition transform hover:-translate-y-1">
        <div class="text-4xl mb-3">✍️</div>
        <div class="font-bold text-xl mb-1 text-gray-900 group-hover:text-emerald-700">AI English Corrector</div>
        <p class="text-sm text-gray-500">Fix grammar and vocabulary errors instantly</p>
      </a>

      {{-- 🧠 Quiz Generator --}}
      <a href="{{ route('generator.index') }}" 
         class="group relative overflow-hidden rounded-2xl p-6 bg-white border-2 border-transparent shadow-md hover:border-fuchsia-500 hover:shadow-fuchsia-200 transition transform hover:-translate-y-1">
        <div class="text-4xl mb-3">🧠</div>
        <div class="font-bold text-xl mb-1 text-gray-900 group-hover:text-fuchsia-700">AI Quiz Generator</div>
        <p class="text-sm text-gray-500">Generate comprehension questions from text</p>
      </a>

      {{-- 🏫 AI Grader --}}
      <a href="{{ url('/grader') }}" 
         class="group relative overflow-hidden rounded-2xl p-6 bg-white border-2 border-transparent shadow-md hover:border-amber-500 hover:shadow-amber-200 transition transform hover:-translate-y-1">
        <div class="text-4xl mb-3">🏫</div>
        <div class="font-bold text-xl mb-1 text-gray-900 group-hover:text-amber-700">AI Grader (Essay Evaluation)</div>
        <p class="text-sm text-gray-500">Upload answers for instant grading and feedback</p>
      </a>
    </div>
  </div>
</div>
@endsection
