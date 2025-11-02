@extends('layouts.app')

@section('title', 'AI English Corrector')

@section('content')
  {{-- your existing corrector HTML stays here, remove <html>/<body> --}}
@endsection

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>AI English Corrector</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-green-50 to-emerald-100 min-h-screen flex flex-col items-center justify-center p-4 pt-24">
  <div class="bg-white shadow-2xl rounded-2xl p-6 w-full max-w-3xl text-center transition-all duration-300 mt-8">

    <h1 class="text-3xl font-extrabold mb-3 bg-gradient-to-r from-green-600 to-emerald-600 bg-clip-text text-transparent">
      ✍️ AI English Corrector
    </h1>
    <p class="text-gray-600 mb-5">
      Instantly fix grammar, vocabulary, and clarity using AI.<br>
      <small>(No data is stored)</small>
    </p>

    <textarea id="inputText"
      placeholder="Type or paste your English text here..."
      class="w-full h-40 p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 mb-4 resize-none"></textarea>

    <div class="flex justify-center gap-4 mb-4">
      <button id="correctBtn" class="bg-green-600 hover:bg-green-700 text-white px-5 py-2.5 rounded-lg shadow transition">
        ✅ Correct Grammar
      </button>
      <button id="clearBtn" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-5 py-2.5 rounded-lg shadow transition">
        🧹 Clear
      </button>
    </div>

    <div id="resultArea" class="hidden mt-4 bg-gray-50 p-5 rounded-lg shadow-inner text-left">
      <h3 class="text-lg font-semibold text-green-700 mb-2">✅ Corrected Version</h3>
      <p id="correctedText" class="text-gray-800 mb-4"></p>

      <h4 class="text-indigo-700 font-semibold">🧠 Explanation</h4>
      <ul id="explanationList" class="list-disc pl-5 text-gray-700"></ul>

      <div class="mt-3 flex gap-3">
        <button id="copyBtn" class="text-blue-600 text-sm underline">Copy</button>
        <button id="saveBtn" class="text-gray-600 text-sm underline">Save to History</button>
      </div>
    </div>

    <div id="historySection" class="mt-8 hidden text-left">
      <h2 class="text-xl font-bold mb-2 text-green-700">📜 Correction History</h2>
      <div id="historyList" class="space-y-3"></div>
    </div>
  </div>

  <script>
    const inputText = document.getElementById('inputText');
    const correctBtn = document.getElementById('correctBtn');
    const clearBtn = document.getElementById('clearBtn');
    const resultArea = document.getElementById('resultArea');
    const correctedText = document.getElementById('correctedText');
    const explanationList = document.getElementById('explanationList');
    const copyBtn = document.getElementById('copyBtn');
    const saveBtn = document.getElementById('saveBtn');
    const historySection = document.getElementById('historySection');
    const historyList = document.getElementById('historyList');

    let history = JSON.parse(localStorage.getItem('aiCorrectHistory') || '[]');

    if (history.length > 0) {
      updateHistoryUI();
      historySection.classList.remove('hidden');
    }

    clearBtn.addEventListener('click', () => {
      inputText.value = '';
      resultArea.classList.add('hidden');
    });

    correctBtn.addEventListener('click', async () => {
      const text = inputText.value.trim();
      if (!text) {
        alert('Please enter some text to correct.');
        return;
      }

      resultArea.classList.remove('hidden');
      correctedText.innerText = '⏳ Analyzing text...';
      explanationList.innerHTML = '';

      try {
        const res = await fetch('/api/correct', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ text })
        });

        const data = await res.json();
        if (data.ok && data.data) {
          correctedText.innerText = data.data.corrected;
          explanationList.innerHTML = data.data.explanations
            .map(e => `<li>${e}</li>`)
            .join('');

          history.unshift({
            time: new Date().toLocaleString(),
            original: data.data.original,
            corrected: data.data.corrected,
            explanations: data.data.explanations
          });

          localStorage.setItem('aiCorrectHistory', JSON.stringify(history));
          updateHistoryUI();
          historySection.classList.remove('hidden');
        } else {
          correctedText.innerText = '❌ Unable to process the text.';
        }
      } catch (err) {
        correctedText.innerText = `❌ Error: ${err.message}`;
      }
    });

    copyBtn.addEventListener('click', () => {
      navigator.clipboard.writeText(correctedText.innerText);
      copyBtn.innerText = 'Copied!';
      setTimeout(() => (copyBtn.innerText = 'Copy'), 1200);
    });

    saveBtn.addEventListener('click', () => {
      alert('Saved to history.');
    });
    
    function updateHistoryUI() {
      historyList.innerHTML = history.map(h => `
        <details class="bg-white rounded-lg p-3 shadow-md">
          <summary class="cursor-pointer font-semibold text-gray-800 truncate">
            🕓 ${h.time} — ${h.original.substring(0, 50)}...
          </summary>
          <div class="mt-2 text-sm text-gray-700">
            <p><strong>Original:</strong> ${h.original}</p>
            <p><strong>Corrected:</strong> ${h.corrected}</p>
            <p class="mt-1"><strong>Explanation:</strong></p>
            <ul class="list-disc pl-5">${h.explanations.map(e => `<li>${e}</li>`).join('')}</ul>
          </div>
        </details>
      `).join('');
    }
  </script>
</body>
</html>
