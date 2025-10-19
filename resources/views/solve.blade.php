@extends('layouts.app')

@section('title', 'AI Quiz Solver')

@section('content')
  <div class="bg-white shadow-2xl rounded-2xl p-6 w-full max-w-2xl text-center transition-all duration-300">
    <h1 class="text-3xl font-extrabold mb-3 bg-gradient-to-r from-indigo-600 to-blue-600 bg-clip-text text-transparent">📘 AI Quiz Solver</h1>
    <p class="text-gray-600 mb-6">
      Upload or take a photo to get instant answers and explanations.<br>
      <small>(No data is stored)</small>
    </p>

    {{-- your existing solver form/buttons stay here --}}
  </div>
@endsection
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>AI Quiz Solver</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-green-50 to-emerald-100 min-h-screen flex flex-col items-center justify-center p-4 pt-24">
  <div class="bg-white shadow-2xl rounded-2xl p-6 w-full max-w-3xl text-center transition-all duration-300 mt-8">

    <h1 class="text-3xl font-extrabold mb-3 bg-gradient-to-r from-indigo-600 to-blue-600 bg-clip-text text-transparent">
      📘 AI Quiz Solver
    </h1>
    <p class="text-gray-600 mb-6">
      Upload or take a photo to get instant answers and explanations.<br>
      <small>(No data is stored)</small>
    </p>

    <!-- Hidden inputs -->
    <input type="file" id="fileInput" accept="image/*" class="hidden">
    <input type="file" id="cameraInput" accept="image/*" capture="environment" class="hidden">

    <!-- Buttons -->
    <div class="flex justify-center gap-4 mb-2">
      <button id="cameraButton" class="bg-green-600 hover:bg-green-700 text-white px-5 py-2.5 rounded-lg shadow transition">
        📷 Take Photo
      </button>
      <button id="chooseButton" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-lg shadow transition">
        📁 Choose File
      </button>
    </div>

    <p class="text-xs text-gray-500 mb-4">
      📌 Supports JPG/PNG. Recommended image &lt; 3 MB; automatically compressed to ~800 px for faster processing.
    </p>

    <div id="preview" class="mt-2"></div>

    <button id="solveButton" class="bg-gradient-to-r from-indigo-500 to-blue-500 hover:from-indigo-600 hover:to-blue-600 text-white px-6 py-2.5 rounded-lg shadow-md mt-4 transition">
      🔍 Solve with AI
    </button>

    <div class="mt-3 flex justify-center gap-3">
      <button id="copyResultBtn" class="text-blue-600 text-sm underline hidden">Copy Result</button>
      <button id="clearHistoryBtn" class="text-red-600 text-sm underline">Clear History</button>
    </div>

    <div id="result" class="mt-6 p-5 bg-gray-50 rounded-lg shadow-inner hidden text-left"></div>

    <!-- 🕓 History -->
    <div id="history" class="mt-8 text-left hidden">
      <h2 class="text-xl font-bold mb-2 text-indigo-700">📜 Solution History</h2>
      <div id="historyList" class="space-y-3"></div>
    </div>
  </div>

  <!-- Loading overlay -->
  <div id="loading" class="hidden fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50">
    <div class="bg-white p-5 rounded-xl shadow-lg text-center">
      <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-indigo-600 mx-auto mb-3"></div>
      <p class="text-sm text-gray-700">
        Recognizing text → analyzing question → generating answer…
      </p>
    </div>
  </div>

  <script>
    // ===== Elements =====
    const fileInput = document.getElementById('fileInput');
    const cameraInput = document.getElementById('cameraInput');
    const chooseButton = document.getElementById('chooseButton');
    const cameraButton = document.getElementById('cameraButton');
    const solveButton = document.getElementById('solveButton');
    const preview = document.getElementById('preview');
    const resultDiv = document.getElementById('result');
    const historySection = document.getElementById('history');
    const historyList = document.getElementById('historyList');
    const loading = document.getElementById('loading');
    const copyResultBtn = document.getElementById('copyResultBtn');
    const clearHistoryBtn = document.getElementById('clearHistoryBtn');

    // ===== State =====
    let selectedFile = null;
    let compressedDataURL = null;
    let history = [];

    // ===== Init from localStorage =====
    try {
      history = JSON.parse(localStorage.getItem('aiQuizHistory') || '[]');
      if (history.length) {
        updateHistoryUI();
        historySection.classList.remove('hidden');
      }
    } catch (_) {}

    // ===== Event bindings =====
    chooseButton.addEventListener('click', () => fileInput.click());
    cameraButton.addEventListener('click', () => cameraInput.click());
    fileInput.addEventListener('change', handleFile);
    cameraInput.addEventListener('change', handleFile);
    solveButton.addEventListener('click', onSolve);
    copyResultBtn.addEventListener('click', copyResult);
    clearHistoryBtn.addEventListener('click', clearHistory);

    // ===== Helpers =====
    function humanSize(bytes) {
      if (!bytes && bytes !== 0) return '';
      const units = ['B','KB','MB','GB'];
      let i = 0, num = bytes;
      while (num >= 1024 && i < units.length - 1) { num /= 1024; i++; }
      return `${num.toFixed(1)} ${units[i]}`;
    }

    function toggleLoading(show) {
      loading.classList.toggle('hidden', !show);
      solveButton.disabled = !!show;
      solveButton.classList.toggle('opacity-60', !!show);
      solveButton.classList.toggle('cursor-not-allowed', !!show);
    }

    function setResultVisible(show) {
      resultDiv.classList.toggle('hidden', !show);
      copyResultBtn.classList.toggle('hidden', !show);
    }

    // Compress to max 800 px edge, JPEG quality 0.8
    function compressImage(dataURL, maxEdge = 800, quality = 0.8) {
      return new Promise((resolve) => {
        const img = new Image();
        img.onload = () => {
          const scale = Math.min(maxEdge / img.width, maxEdge / img.height, 1);
          const w = Math.round(img.width * scale);
          const h = Math.round(img.height * scale);
          const canvas = document.createElement('canvas');
          canvas.width = w; canvas.height = h;
          const ctx = canvas.getContext('2d');
          ctx.drawImage(img, 0, 0, w, h);
          const out = canvas.toDataURL('image/jpeg', quality);
          resolve(out);
        };
        img.src = dataURL;
      });
    }

    async function handleFile(event) {
      const file = event.target.files[0];
      if (!file) return;

      if (file.size > 10 * 1024 * 1024) {
        alert("Image exceeds 10 MB, please select a smaller one.");
        return;
      }

      selectedFile = file;

      const reader = new FileReader();
      reader.onload = async e => {
        const originalDataURL = e.target.result;

        preview.innerHTML = `
          <div class="text-sm text-gray-500 mt-1">
            File: <span class="font-medium">${file.name}</span>
            <span class="ml-2">Size: ${humanSize(file.size)}</span>
          </div>
          <img src="${originalDataURL}" alt="preview" class="rounded-lg mt-2 shadow-md max-h-60 mx-auto">
        `;

        try {
          compressedDataURL = await compressImage(originalDataURL, 800, 0.8);
        } catch {
          compressedDataURL = originalDataURL;
        }
      };
      reader.readAsDataURL(file);
    }

    async function onSolve() {
      if (!selectedFile || !compressedDataURL) {
        alert("Please choose or take a photo before proceeding.");
        return;
      }

      setResultVisible(true);
      resultDiv.innerHTML = `<p class="text-gray-600">⏳ Analyzing question, please wait...</p>`;
      toggleLoading(true);

      const payload = {
        image: compressedDataURL,
        meta: {
          filename: selectedFile.name,
          mime: selectedFile.type || 'image/jpeg',
          original_size: selectedFile.size
        }
      };

      try {
        const data = await postJSONWithRetry('/api/solve', payload, { retries: 2, timeoutMs: 30000 });

        if (data && data.ok && data.data) {
          const d = data.data;
          renderResult(d);

          history.unshift({
            time: new Date().toLocaleString(),
            question: d.question || '(Unrecognized question)',
            answer: d.answer || '(No answer)',
            reasoning: Array.isArray(d.reasoning) ? d.reasoning : [],
            knowledge_points: Array.isArray(d.knowledge_points) ? d.knowledge_points : [],
            options: Array.isArray(d.options) ? d.options : null,
            confidence: typeof d.confidence === 'number' ? d.confidence : null
          });
          history = history.slice(0, 50);
          localStorage.setItem('aiQuizHistory', JSON.stringify(history));
          updateHistoryUI();
          historySection.classList.remove('hidden');
        } else {
          const errMsg = (data && data.error) ? data.error : 'Error solving question.';
          resultDiv.innerHTML = `<p class="text-red-600">❌ ${escapeHTML(errMsg)}</p>`;
        }
      } catch (err) {
        resultDiv.innerHTML = `<p class="text-red-600">❌ Network or server error: ${escapeHTML(err.message || String(err))}</p>`;
      } finally {
        toggleLoading(false);
      }
    }

    function renderResult(d) {
      const question = d.question || '(Unrecognized question)';
      const answer = d.answer || '(No answer)';
      const reasoningArr = Array.isArray(d.reasoning) && d.reasoning.length ? d.reasoning : ['No reasoning provided'];
      const kpArr = Array.isArray(d.knowledge_points) && d.knowledge_points.length ? d.knowledge_points : ['No knowledge points'];
      const options = Array.isArray(d.options) && d.options.length ? d.options : null;
      const confidence = typeof d.confidence === 'number' ? Math.max(0, Math.min(100, d.confidence)) : null;

      const optionsHtml = options ? `
        <h4 class="font-semibold mt-3 text-indigo-600">🔎 Options</h4>
        <ul class="list-disc pl-5 text-gray-700">
          ${options.map((o, idx) => `<li>${String.fromCharCode(65 + idx)}. ${escapeHTML(o)}</li>`).join('')}
        </ul>
      ` : '';

      const confHtml = confidence !== null ? `
        <div class="mt-3">
          <div class="flex items-center justify-between text-xs text-gray-500 mb-1">
            <span>AI Confidence</span><span>${confidence}%</span>
          </div>
          <div class="bg-gray-200 rounded-full h-2">
            <div class="bg-green-500 h-2 rounded-full" style="width:${confidence}%"></div>
          </div>
        </div>
      ` : '';

      const html = `
        <h3 class="text-lg font-bold mb-2 text-green-600">✅ AI Answer</h3>

        <p class="mb-1"><strong>Question:</strong> ${escapeHTML(question)}</p>

        ${optionsHtml}

        <p class="mt-2"><strong>Answer:</strong>
          <span class="text-green-700 font-semibold"> ${escapeHTML(answer)} </span>
        </p>

        ${confHtml}

        <h4 class="font-semibold mt-3 text-indigo-600">🧩 Reasoning</h4>
        <ul class="list-disc pl-5 text-gray-700">
          ${reasoningArr.map(r => `<li>${escapeHTML(r)}</li>`).join('')}
        </ul>

        <h4 class="font-semibold mt-3 text-indigo-600">📘 Knowledge Points</h4>
        <ul class="list-disc pl-5 text-gray-700">
          ${kpArr.map(k => `<li>${escapeHTML(k)}</li>`).join('')}
        </ul>
      `;
      resultDiv.innerHTML = html;
    }

    function updateHistoryUI() {
      historyList.innerHTML = history.map(h => `
        <details class="bg-white rounded-lg p-3 shadow-md">
          <summary class="cursor-pointer font-semibold text-gray-800 truncate">
            🕓 ${escapeHTML(h.time)} — ${escapeHTML((h.question || '').substring(0, 60))}...
          </summary>
          <div class="mt-2 text-sm text-gray-700">
            ${h.options && h.options.length ? `
              <p class="mt-1"><strong>Options:</strong></p>
              <ul class="list-disc pl-5">
                ${h.options.map((o, idx) => `<li>${String.fromCharCode(65 + idx)}. ${escapeHTML(o)}</li>`).join('')}
              </ul>
            ` : ''}
            <p class="mt-1"><strong>Answer:</strong> ${escapeHTML(h.answer)}</p>
            ${typeof h.confidence === 'number' ? `
              <div class="mt-2">
                <div class="flex items-center justify-between text-xs text-gray-500 mb-1">
                  <span>AI Confidence</span><span>${h.confidence}%</span>
                </div>
                <div class="bg-gray-200 rounded-full h-2">
                  <div class="bg-green-500 h-2 rounded-full" style="width:${h.confidence}%"></div>
                </div>
              </div>
            ` : ''}
            <p class="mt-2"><strong>Reasoning:</strong></p>
            <ul class="list-disc pl-5">${(h.reasoning || []).map(r => `<li>${escapeHTML(r)}</li>`).join('')}</ul>
            <p class="mt-2"><strong>Knowledge Points:</strong></p>
            <ul class="list-disc pl-5">${(h.knowledge_points || []).map(k => `<li>${escapeHTML(k)}</li>`).join('')}</ul>
          </div>
        </details>
      `).join('');
    }

    function copyResult() {
      const text = resultDiv.innerText || '';
      if (!text.trim()) return;
      navigator.clipboard.writeText(text).then(() => {
        copyResultBtn.textContent = 'Copied ✔';
        setTimeout(() => (copyResultBtn.textContent = 'Copy Result'), 1200);
      });
    }

    function clearHistory() {
      history = [];
      localStorage.removeItem('aiQuizHistory');
      historyList.innerHTML = '';
      historySection.classList.add('hidden');
    }

    // JSON request with retry + timeout
    async function postJSONWithRetry(url, data, { retries = 2, timeoutMs = 30000 } = {}) {
      let lastErr;
      for (let attempt = 0; attempt <= retries; attempt++) {
        try {
          const controller = new AbortController();
          const t = setTimeout(() => controller.abort(), timeoutMs);
          const res = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data),
            signal: controller.signal
          });
          clearTimeout(t);

          const json = await res.json().catch(() => ({}));
          if (!res.ok) {
            throw new Error(json.error || `HTTP ${res.status}`);
          }
          return json;
        } catch (err) {
          lastErr = err;
          if (attempt === retries) throw err;
          await new Promise(r => setTimeout(r, 600 * (attempt + 1)));
        }
      }
      throw lastErr || new Error('Unknown error');
    }

    // Simple HTML escaping
    function escapeHTML(s) {
      return String(s || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
    }
  </script>
</body>
</html>
