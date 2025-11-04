@extends('layouts.app')

@section('title', 'AI Quiz Solver')

@section('content')
<div class="min-h-[70vh] flex flex-col items-center justify-center p-4">
  <div class="bg-white shadow-2xl rounded-2xl p-6 w-full max-w-3xl text-center transition-all duration-300">

    <h1 class="text-3xl font-extrabold mb-3 bg-gradient-to-r from-indigo-600 to-blue-600 bg-clip-text text-transparent">
      📘 AI Quiz Solver
    </h1>
    <p class="text-gray-600 mb-6">
      Upload a photo or a PDF to get instant answers and explanations.<br>
      <small>(No data is stored)</small>
    </p>

    <!-- Hidden inputs -->
    <input type="file" id="fileInput" accept="image/*,application/pdf" class="hidden">
    <input type="file" id="cameraInput" accept="image/*" capture="environment" class="hidden">

    <!-- Buttons -->
    <div class="flex justify-center gap-4 mb-2">
      <button id="cameraButton" class="bg-green-600 hover:bg-green-700 text-white px-5 py-2.5 rounded-lg shadow transition">
        📷 Take Photo
      </button>
      <button id="chooseButton" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-lg shadow transition">
        📁 Choose File (Image/PDF)
      </button>
    </div>

    <p class="text-xs text-gray-500 mb-4">
      📌 Supports JPG/PNG/PDF. Recommended image &lt; 3 MB; automatically compressed to ~1000 px for better OCR.
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
  <div id="loading" class="hidden fixed inset-0 bg-black/40 flex items-center justify-center z-50">
    <div class="bg-white p-5 rounded-xl shadow-lg text-center">
      <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-indigo-600 mx-auto mb-3"></div>
      <p class="text-sm text-gray-700">
        Recognizing text → analyzing question → generating answer…
      </p>
    </div>
  </div>
</div>

{{-- pdf.js（用于前端把 PDF 渲成图片） --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script>
  pdfjsLib.GlobalWorkerOptions.workerSrc = "https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js";
</script>

{{-- 封装脚本 --}}
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
  let isPdf = false;
  let compressedDataURL = null; // for images (含 PDF 转图后的 dataURL)
  let history = [];

  // ===== Init from localStorage =====
  try {
    history = JSON.parse(localStorage.getItem('aiQuizHistory') || '[]');
    if (history.length) {
      updateHistoryUI();
      historySection.classList.remove('hidden');
    }
  } catch (_) {}

  // ===== Events =====
  chooseButton.addEventListener('click', () => fileInput.click());
  cameraButton.addEventListener('click', () => cameraInput.click());
  fileInput.addEventListener('change', handleFile);
  cameraInput.addEventListener('change', handleFile);
  solveButton.addEventListener('click', onSolve);
  copyResultBtn.addEventListener('click', copyResult);
  clearHistoryBtn.addEventListener('click', clearHistory);

  // ===== PDF -> Long Image（与 Essay Pro 相同思路）=====
  async function pdfToLongImage(file, { maxPages = 3, scale = 1.6, quality = 0.9 } = {}) {
    const arrayBuf = await file.arrayBuffer();
    const pdf = await pdfjsLib.getDocument({ data: arrayBuf }).promise;

    const pages = Math.min(pdf.numPages, maxPages);
    const canvases = [];

    for (let i = 1; i <= pages; i++) {
      const page = await pdf.getPage(i);
      const viewport = page.getViewport({ scale });
      const canvas = document.createElement('canvas');
      canvas.width = Math.floor(viewport.width);
      canvas.height = Math.floor(viewport.height);
      const ctx = canvas.getContext('2d');
      await page.render({ canvasContext: ctx, viewport }).promise;
      canvases.push(canvas);
    }

    // 纵向拼接为一张长图
    const totalH = canvases.reduce((s, c) => s + c.height, 0);
    const maxW = Math.max(...canvases.map(c => c.width));
    const out = document.createElement('canvas');
    out.width = maxW;
    out.height = totalH;
    const outCtx = out.getContext('2d');

    let y = 0;
    for (const c of canvases) {
      outCtx.drawImage(c, 0, y);
      y += c.height;
    }
    return out.toDataURL('image/jpeg', quality);
  }

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
  function compressImage(dataURL, maxEdge = 1000, quality = 0.9) {
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
  function dataURLtoBlob(dataURL){
    const [h,b] = dataURL.split(',');
    const mime = h.match(/:(.*?);/)[1];
    const bin = atob(b); const len=bin.length; const u8=new Uint8Array(len);
    for(let i=0;i<len;i++) u8[i]=bin.charCodeAt(i);
    return new Blob([u8],{type:mime});
  }
  function escapeHTML(s) {
    return String(s || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }
  function copyResult(){
    const text = resultDiv.innerText || '';
    navigator.clipboard.writeText(text).then(()=>{
      copyResultBtn.textContent = 'Copied!';
      setTimeout(()=> copyResultBtn.textContent = 'Copy Result', 1200);
    });
  }
  function clearHistory(){
    if(confirm('Clear all history?')){
      history = [];
      localStorage.removeItem('aiQuizHistory');
      updateHistoryUI();
      historySection.classList.add('hidden');
    }
  }

  async function handleFile(e) {
    const file = e.target.files[0];
    if (!file) return;
    selectedFile = file;
    isPdf = file.type === 'application/pdf' || /\.pdf$/i.test(file.name);

    const limit = isPdf ? 20 * 1024 * 1024 : 10 * 1024 * 1024;
    if (file.size > limit) {
      alert(`File exceeds ${limit/1024/1024} MB, please select a smaller one.`);
      selectedFile = null;
      return;
    }

    if (isPdf) {
      // ✅ 与 Essay Pro 一致：前端把 PDF 渲成长图，然后按图片流程走
      preview.innerHTML = `
        <div class="text-sm text-gray-500 mt-1">
          File: <span class="font-medium">${escapeHTML(file.name)}</span>
          <span class="ml-2">Size: ${humanSize(file.size)}</span>
          <span class="ml-2 px-2 py-0.5 rounded bg-gray-100 text-gray-600 text-xs">PDF</span>
        </div>
        <p class="text-xs text-gray-500 mt-1">Rendering PDF pages in browser…</p>
      `;
      try {
        const longImageDataURL = await pdfToLongImage(file, { maxPages: 3, scale: 1.6, quality: 0.9 });
        compressedDataURL = longImageDataURL;

        // 把 selectedFile 改造成图片文件，后续统一按图片上传
        selectedFile = new File(
          [dataURLtoBlob(longImageDataURL)],
          (file.name.replace(/\.pdf$/i, '') || 'document') + '.jpg',
          { type: 'image/jpeg' }
        );
        isPdf = false; // 标记为非 PDF，后续一律走图片分支

        preview.innerHTML = `
          <div class="text-sm text-gray-500 mt-1">
            File: <span class="font-medium">${escapeHTML(file.name)}</span>
            <span class="ml-2 px-2 py-0.5 rounded bg-gray-100 text-gray-600 text-xs">PDF → Image</span>
          </div>
          <img src="${longImageDataURL}" alt="preview" class="rounded-lg mt-2 shadow-md max-h-60 mx-auto">
          <p class="text-xs text-gray-500 mt-1">Rendered as long image. (~${Math.round((longImageDataURL.length * 3 / 4)/1024)} KB)</p>
        `;
      } catch (err) {
        console.error(err);
        preview.innerHTML = `
          <p class="text-red-600 text-sm mt-2">Failed to render PDF in browser. Please try a different file.</p>
        `;
        compressedDataURL = null;
      }
      return;
    }

    // image preview + compression
    const reader = new FileReader();
    reader.onload = async (ev) => {
      const originalDataURL = ev.target.result;
      preview.innerHTML = `
        <div class="text-sm text-gray-500 mt-1">
          File: <span class="font-medium">${escapeHTML(file.name)}</span>
          <span class="ml-2">Size: ${humanSize(file.size)}</span>
        </div>
        <img src="${originalDataURL}" alt="preview" class="rounded-lg mt-2 shadow-md max-h-60 mx-auto">
      `;
      try {
        compressedDataURL = await compressImage(originalDataURL, 1000, 0.9);
      } catch {
        compressedDataURL = originalDataURL;
      }
    };
    reader.readAsDataURL(file);
  }

  async function onSolve() {
    if (!selectedFile) {
      alert('Please choose a file (image or PDF) first.');
      return;
    }
    setResultVisible(true);
    resultDiv.innerHTML = `<p class="text-gray-600">⏳ Analyzing question, please wait...</p>`;
    toggleLoading(true);

    try {
      let data;
      // 由于我们已经把 PDF → 图片，这里一律按图片通道发送
      if (!compressedDataURL) {
        alert('Image not ready yet, please wait a second.');
        toggleLoading(false);
        return;
      }
      const payload = {
        image: compressedDataURL,
        meta: {
          filename: selectedFile.name,
          mime: selectedFile.type || 'image/jpeg',
          original_size: selectedFile.size
        }
      };
      data = await postJSONWithRetry('/api/solve', payload, { retries: 2, timeoutMs: 45000 });

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

    resultDiv.innerHTML = `
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

  async function postJSONWithRetry(url, data, { retries = 2, timeoutMs = 45000 } = {}) {
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
        if (!res.ok) throw new Error(json.error || `HTTP ${res.status}`);
        return json;
      } catch (err) {
        lastErr = err;
        if (attempt === retries) throw err;
        await new Promise(r => setTimeout(r, 600 * (attempt + 1)));
      }
    }
    throw lastErr || new Error('Unknown error');
  }
</script>
@endsection
