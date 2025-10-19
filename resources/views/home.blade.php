@extends('layouts.app')

@section('title', 'Home - AI Learning Tools')

@section('content')
  <div class="bg-white shadow-2xl rounded-2xl p-8 text-center w-full max-w-3xl">
    <h1 class="text-4xl font-extrabold mb-4 bg-gradient-to-r from-indigo-600 to-blue-600 bg-clip-text text-transparent">
      🤖 AI Learning Assistant
    </h1>
    <p class="text-gray-600 mb-8">Choose a tool below to get started:</p>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <a href="{{ route('solve.index') }}" class="block bg-gradient-to-r from-indigo-500 to-blue-500 hover:from-indigo-600 hover:to-blue-600 text-white px-6 py-5 rounded-xl shadow-lg transition transform hover:scale-105">
        <div class="text-3xl mb-2">📘</div>
        <div class="font-bold text-lg">AI Quiz Solver</div>
        <span class="text-sm text-blue-100">Upload or snap a question to solve instantly</span>
      </a>

      <a href="{{ route('corrector.index') }}" class="block bg-gradient-to-r from-green-500 to-emerald-500 hover:from-green-600 hover:to-emerald-600 text-white px-6 py-5 rounded-xl shadow-lg transition transform hover:scale-105">
        <div class="text-3xl mb-2">✍️</div>
        <div class="font-bold text-lg">AI English Corrector</div>
        <span class="text-sm text-emerald-100">Fix grammar and vocabulary errors</span>
      </a>

      <a href="{{ route('generator.index') }}" class="block bg-gradient-to-r from-purple-500 to-indigo-500 hover:from-purple-600 hover:to-indigo-600 text-white px-6 py-5 rounded-xl shadow-lg transition transform hover:scale-105 md:col-span-2">
        <div class="text-3xl mb-2">🧠</div>
        <div class="font-bold text-lg">AI Quiz Generator</div>
        <span class="text-sm text-indigo-100">Generate comprehension questions from text</span>
      </a>
    </div>
  </div>
@endsection
