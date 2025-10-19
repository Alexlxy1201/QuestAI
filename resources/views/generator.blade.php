@extends('layouts.app')

@section('title', 'AI Quiz Generator')

@section('content')
  {{-- your existing generator content --}}
@endsection

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>AI Quiz Generator</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-green-50 to-emerald-100 min-h-screen flex flex-col items-center justify-center p-4 pt-24">
  <div class="bg-white shadow-2xl rounded-2xl p-6 w-full max-w-3xl text-center transition-all duration-300 mt-8">

    <h1 class="text-3xl font-extrabold mb-3 bg-gradient-to-r from-purple-600 to-indigo-600 bg-clip-text text-transparent">
      🧠 AI Quiz Generator
    </h1>
    <p class="text-gray-600 mb-6">
      Paste an English paragraph below and let AI create multiple-choice or true/false questions.
      <br><small>(No data is stored)</small>
    </p>

    <!-- Text input -->
    <textarea id="inputText"
      placeholder="Paste your English passage here..."
      class="w-full h-48 p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 mb-4 resize-none"></textarea>

    <!-- Options -->
    <div class="flex justify-center items-center gap-4 mb-4">
      <label for="numQuestions" class="text-gray-700">Number of Questions:</label>
      <select id="numQuestions" class="border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-purple-500">
        <option value="3">3</option>
        <option value="5" selected>5</option>
        <option value="10">10</option>
      </select>
    </div>

    <!-- Generate button -->
    <div class="flex justify-center">
      <button id="generateBtn"
        class="bg-gradient-to-r from-purple-500 to-indigo-500 hover:from-purple-600 hover:to-indigo-600 text-white px-6 py-2.5 rounded-lg shadow-md transition">
        ⚡ Generate Quiz
      </button>
    </div>

    <!-- Result area -->
    <div id="resultArea" class="hidden mt-8 text-left">
      <h2 class="text-xl font-bold text-indigo-700 mb-4">🧩 Generated Quiz</h2>
      <div id="quizList" class="space-y-5"></div>

      <div class="mt-6 flex gap-3">
        <button id="copyBtn" class="text-blue-600 text-sm underline">Copy Quiz</button>
        <button id="saveBtn" class="text-gray-600 text-sm underline">Save to History</button>
      </div>
    </div>

    <!-- History section -->
    <div id="historySection" class="mt-8 text-left hidden">
      <h2 class="text-xl font-bold mb-2 text-purple-700">📜 Quiz History</h2>
      <div id="historyList" class="space-y-3"></div>
    </div>
  </div>

  <!-- Loading overlay -->
  <div id="loading" class="hidden fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50">
    <div class="bg-white p-5 rounded-xl shadow-lg text-center">
      <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-purple-600 mx-auto mb-3"></div>
      <p class="text-sm text-gray-700">Generating questions, please wait...</p>
    </div>
  </div>

  <script>
    const inputText = document.getElementById('inputText');
    const numQuestions = document.getElementById('numQuestions');
    const generateBtn = document.getElementById('generateBtn');
    const resultArea = document.getElementById('resultArea');
    const quizList = document.getElementById('quizList');
    const loading = document.getElementById('loading');
    const copyBtn = document.getElementById('copyBtn');
    const saveBtn = document.getElementById('saveBtn');
    const historySection = document.getElementById('historySection');
    const historyList = document.getElementById('historyList');

    let history = JSON.parse(localStorage.getItem('aiQuizHistory') || '[]');

    if (history.length > 0) {
      updateHistoryUI();
      historySection.classList.remove('hidden');
    }

    generateBtn.addEventListener('click', async () => {
      const text = inputText.value.trim();
      const count = parseInt(numQuestions.value);
      if (!text) {
        alert("Please paste a paragraph before generating.");
        return;
      }

      toggleLoading(true);
      resultArea.classList.remove('hidden');
      quizList.innerHTML = `<p class="text-gray-600">⏳ AI is creating your quiz...</p>`;

      try {
        const res = await fetch('/api/generate-quiz', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ text, count })
        });
        const data = await res.json();

        if (data.ok && data.data && data.data.questions) {
          renderQuiz(data.data.questions);
          saveHistoryEntry(data.data.questions);
        } else {
          quizList.innerHTML = `<p class="text-red-600">❌ ${data.error || 'Failed to generate quiz.'}</p>`;
        }
      } catch (err) {
        quizList.innerHTML = `<p class="text-red-600">❌ Error: ${err.message}</p>`;
      } finally {
        toggleLoading(false);
      }
    });

    copyBtn.addEventListener('click', () => {
      const text = quizList.innerText;
      navigator.clipboard.writeText(text);
      copyBtn.innerText = 'Copied!';
      setTimeout(() => copyBtn.innerText = 'Copy Quiz', 1000);
    });

    saveBtn.addEventListener('click', () => {
      alert('Saved to history ✅');
    });

    function renderQuiz(questions) {
      quizList.innerHTML = `
        <div class="flex justify-end mb-4">
          <button id="toggleAllBtn" 
            class="text-sm px-3 py-1.5 rounded-md bg-indigo-600 text-white hover:bg-indigo-700 transition">
            👁️ Show All Answers
          </button>
        </div>
        ${questions.map((q, i) => {
          const isMC = q.type === 'multiple_choice';
          const optionsHTML = isMC
            ? `<ul class="list-disc pl-6 text-gray-700 mt-2">
                ${q.options.map(opt => `<li>${opt}</li>`).join('')}
              </ul>`
            : '';

          return `
            <div class="bg-gray-50 p-4 rounded-lg shadow-inner">
              <p class="font-semibold text-gray-800">${i + 1}. ${q.question}</p>
              ${optionsHTML}
              <button 
                class="mt-3 text-sm text-indigo-600 underline hover:text-indigo-800 show-answer-btn"
                data-index="${i}">
                👁️ Show Answer
              </button>
              <p class="text-green-700 mt-2 hidden answer-text">
                <strong>Answer:</strong> ${q.answer}
              </p>
            </div>
          `;
        }).join('')}
      `;

      // ✅ 单题“Show / Hide Answer”按钮
      document.querySelectorAll('.show-answer-btn').forEach(btn => {
        btn.addEventListener('click', () => {
          const parent = btn.closest('div');
          const answer = parent.querySelector('.answer-text');
          const isHidden = answer.classList.contains('hidden');

          if (isHidden) {
            answer.classList.remove('hidden');
            btn.textContent = '🙈 Hide Answer';
            btn.classList.replace('text-indigo-600', 'text-red-600');
          } else {
            answer.classList.add('hidden');
            btn.textContent = '👁️ Show Answer';
            btn.classList.replace('text-red-600', 'text-indigo-600');
          }
        });
      });

      // ✅ 全部显示 / 隐藏按钮
      const toggleAllBtn = document.getElementById('toggleAllBtn');
      let allShown = false;

      toggleAllBtn.addEventListener('click', () => {
        const allAnswers = document.querySelectorAll('.answer-text');
        const allButtons = document.querySelectorAll('.show-answer-btn');

        allShown = !allShown;

        if (allShown) {
          allAnswers.forEach(a => a.classList.remove('hidden'));
          allButtons.forEach(b => {
            b.textContent = '🙈 Hide Answer';
            b.classList.replace('text-indigo-600', 'text-red-600');
          });
          toggleAllBtn.textContent = '🙈 Hide All Answers';
          toggleAllBtn.classList.replace('bg-indigo-600', 'bg-red-600');
        } else {
          allAnswers.forEach(a => a.classList.add('hidden'));
          allButtons.forEach(b => {
            b.textContent = '👁️ Show Answer';
            b.classList.replace('text-red-600', 'text-indigo-600');
          });
          toggleAllBtn.textContent = '👁️ Show All Answers';
          toggleAllBtn.classList.replace('bg-red-600', 'bg-indigo-600');
        }
      });
    }


    function saveHistoryEntry(questions) {
      const entry = {
        time: new Date().toLocaleString(),
        questions
      };
      history.unshift(entry);
      history = history.slice(0, 20);
      localStorage.setItem('aiQuizHistory', JSON.stringify(history));
      updateHistoryUI();
      historySection.classList.remove('hidden');
    }

    function updateHistoryUI() {
      historyList.innerHTML = history.map(h => `
        <details class="bg-white rounded-lg p-3 shadow-md">
          <summary class="cursor-pointer font-semibold text-gray-800 truncate">
            🕓 ${h.time}
          </summary>
          <div class="mt-2 text-sm text-gray-700">
            ${h.questions.map((q, i) => `
              <p><strong>${i + 1}. ${q.question}</strong></p>
              ${q.options ? `<ul class="list-disc pl-5">${q.options.map(o => `<li>${o}</li>`).join('')}</ul>` : ''}
              <p class="text-green-700"><strong>Answer:</strong> ${q.answer}</p>
            `).join('')}
          </div>
        </details>
      `).join('');
    }

    function toggleLoading(show) {
      loading.classList.toggle('hidden', !show);
      generateBtn.disabled = !!show;
    }
  </script>

</body>
</html>
