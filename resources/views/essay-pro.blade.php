{{-- resources/views/essay-pro.blade.php --}}
@extends('layouts.app')  

@section('title', '🧠 SmartMark — Essay grading for UASA & SPM') 

@section('content')  
<div class="min-h-[70vh] flex flex-col items-center justify-center p-4"> 
  <div class="bg-white shadow-2xl rounded-2xl p-6 w-full max-w-6xl text-left transition-all duration-300 overflow-x-hidden">

    {{-- Header --}}
    <div class="flex items-center justify-between gap-4 mb-4"> 
      <h1 class="text-3xl font-extrabold bg-gradient-to-r from-indigo-600 to-blue-600 bg-clip-text text-transparent"> 
        🧠 SmartMark — Rater Buddy for UASA &amp; SPM
      </h1>
      <div class="flex items-center gap-2"> 
        <button id="btnExportDocx" class="px-4 py-2 rounded-xl bg-emerald-600 text-white font-semibold hover:bg-emerald-700 transition"> 
          ⬇️ Export (.docx) 
        </button>
        <a href="{{ route('home') ?? url('/') }}" class="px-4 py-2 rounded-xl bg-gray-100 text-gray-700 hover:bg-gray-200 transition">Back</a>
      </div>
    </div>

    {{-- Stepper --}}
    <div class="mb-6">
      <ol class="flex items-center text-sm text-gray-600 gap-2">
        <li class="flex items-center gap-2">
          <span class="w-6 h-6 grid place-items-center rounded-full bg-indigo-600 text-white text-xs font-bold">1</span>
          Extract original text (OCR)
        </li>
        <span class="text-gray-300">—</span>
        <li class="flex items-center gap-2">
          <span class="w-6 h-6 grid place-items-center rounded-full bg-gray-200 text-gray-700 text-xs font-bold">2</span>
          Edit text
        </li>
        <span class="text-gray-300">—</span>
        <li class="flex items-center gap-2">
          <span class="w-6 h-6 grid place-items-center rounded-full bg-gray-200 text-gray-700 text-xs font-bold">3</span>
          Analyze & Grade
        </li>
      </ol>
    </div>

    <div class="grid lg:grid-cols-2 gap-6">
      {{-- Left: Inputs + Files + OCR --}}
      <div>
        {{-- Title row --}}
        <label class="block text-sm font-medium text-gray-700 mb-1">Essay Title</label>
        <div class="flex flex-wrap gap-2">
          <input id="title" type="text" placeholder="e.g., The Importance of Reading"
                 class="flex-1 min-w-[180px] rounded-xl border-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
          <button id="cameraTitleButton" class="px-3 py-2 rounded-lg bg-purple-600 text-white hover:bg-purple-700 text-sm whitespace-nowrap">
            📷 Take Photo
          </button>
          <button id="uploadTitleButton" class="px-3 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700 text-sm whitespace-nowrap">
            📁 Upload from device
          </button>
          <input type="file" id="cameraTitleInput" accept="image/*" capture="environment" class="hidden">
          <input type="file" id="uploadTitleInput" accept="image/*" class="hidden">
        </div>

        {{-- Rubric --}}
        <div class="mt-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">Rubric</label>
          <select id="rubric" class="w-full rounded-xl border-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            <optgroup label="SPM">
              <option value="SPM_P1">SPM — Part 1</option>
              <option value="SPM_P2">SPM — Part 2</option>
              <option value="SPM_P3">SPM — Part 3</option>
            </optgroup>
            <optgroup label="UASA">
              <option value="UASA_P1">UASA — Part 1</option>
              <option value="UASA_P2">UASA — Part 2</option>
            </optgroup>
          </select>
          <p class="text-xs text-gray-400 mt-1">Scoring dimensions: Content · Communicative Achievement · Organisation · Language (0–5 each).</p>
        </div>

        {{-- Files --}}
        <div class="mt-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">Upload / Take Photo (Image or single PDF)</label>
          <input type="file" id="fileInput" accept="image/*,application/pdf" multiple class="hidden">
          <input type="file" id="cameraInput" accept="image/*" capture="environment" multiple class="hidden">
          <div class="flex flex-wrap items-center gap-3">
            <button id="cameraButton" class="px-4 py-2 rounded-xl bg-green-600 text-white font-semibold hover:bg-green-700">
              📷 Take Photo
            </button>
            <button id="chooseButton" class="px-4 py-2 rounded-xl bg-blue-600 text-white font-semibold hover:bg-blue-700">
              📁 Upload from device
            </button>
            <label class="inline-flex items-center gap-2 text-sm text-gray-700">
              <input id="stitchToggle" type="checkbox" class="rounded" checked>
              Stitch images before OCR (recommended)
            </label>
          </div>

          {{-- Previews --}}
          <div id="previewWrap" class="mt-3 hidden">
            <img id="previewImg" class="max-h-56 rounded-xl shadow border border-gray-100 mx-auto hidden" alt="preview image">
            <div id="previewPdf" class="text-sm text-gray-600 mt-2 hidden"></div>
            <canvas id="pdfCanvas" class="hidden max-h-56 rounded-xl shadow border border-gray-100 mx-auto"></canvas>
            <div id="previewMeta" class="text-xs text-gray-500 mt-1"></div>
            <div id="thumbGrid" class="mt-2 grid grid-cols-6 gap-2"></div>
          </div>

          {{-- Step 1 Actions: OCR only (no changes to text) --}}
          <div class="mt-4 flex items-center gap-3 flex-wrap">
            <button id="btnExtract" class="px-4 py-2 rounded-xl bg-indigo-600 text-white font-semibold hover:bg-indigo-700">
              🧠 Extract Text (OCR)
            </button>
            <span id="extractStatus" class="text-sm text-gray-500"></span>
          </div>
          <p class="text-xs text-gray-400 mt-2">Step 1 extracts the original text only. No edits or corrections are applied.</p>
        </div>
      </div>

      {{-- Right: Editor --}}
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Essay Text (editable)</label>
        <textarea id="essayText" rows="16" placeholder="After OCR, the original text will appear here. You may freely edit it before analysis."
                  class="w-full rounded-xl border-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"></textarea>

        {{-- Step 3 Actions: Analyze AFTER editing --}}
        <div class="mt-4 flex flex-wrap items-center gap-3">
          <button id="btnAnalyze" class="px-4 py-2 rounded-xl bg-emerald-600 text-white font-semibold hover:bg-emerald-700" disabled>
            📊 Analyze & Grade (AI)
          </button>
          <button id="btnSuggest" class="px-4 py-2 rounded-xl bg-amber-500 text-white font-semibold hover:bg-amber-600" disabled>
            💡 Suggest Corrections (optional)
          </button>
          <span id="analyzeStatus" class="text-sm text-gray-500"></span>
        </div>
      </div>
    </div>

    {{-- Rubric reference --}}
    <div class="mt-6">
      <label class="block text-sm font-medium text-gray-700 mb-1">Rubric Reference (editable)</label>
      <textarea id="rubricRef" rows="8" class="w-full rounded-xl border-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"></textarea>
      <p class="text-xs text-gray-400 mt-1">This stays local and is not sent to the backend.</p>
    </div>

    {{-- Score --}}
    <div class="bg-white rounded-2xl border mt-6 p-4 hidden" id="resultCard">
      <div class="flex items-center justify-between gap-4">
        <h2 class="text-xl font-bold">AI Score</h2>
        <span id="badgeRubric" class="text-xs px-2 py-1 rounded-full bg-indigo-50 text-indigo-700">-</span>
      </div>

      <div class="grid md:grid-cols-5 gap-4 mt-3">
        <div class="p-3 rounded-xl bg-indigo-50">
          <div class="text-xs uppercase text-gray-500">Content</div>
          <div id="scContent" class="text-2xl font-extrabold">-</div>
          <div class="text-xs text-gray-400">0–5</div>
        </div>
        <div class="p-3 rounded-xl bg-indigo-50">
          <div class="text-xs uppercase text-gray-500">Communicative</div>
          <div id="scComm" class="text-2xl font-extrabold">-</div>
          <div class="text-xs text-gray-400">0–5</div>
        </div>
        <div class="p-3 rounded-xl bg-indigo-50">
          <div class="text-xs uppercase text-gray-500">Organisation</div>
          <div id="scOrg" class="text-2xl font-extrabold">-</div>
          <div class="text-xs text-gray-400">0–5</div>
        </div>
        <div class="p-3 rounded-xl bg-indigo-50">
          <div class="text-xs uppercase text-gray-500">Language</div>
          <div id="scLang" class="text-2xl font-extrabold">-</div>
          <div class="text-xs text-gray-400">0–5</div>
        </div>
        <div class="p-3 rounded-xl bg-emerald-50">
          <div class="text-xs uppercase text-gray-500">Total</div>
          <div id="scTotal" class="text-2xl font-extrabold">-</div>
          <div class="text-xs text-gray-400">/20</div>
        </div>
      </div>

      <div class="mt-4 grid md:grid-cols-2 gap-4" id="rationaleWrap">
        <div class="p-3 rounded-xl bg-gray-50">
          <div class="text-xs uppercase text-gray-500 mb-1">Criterion Explanations</div>
          <ul id="rationaleList" class="list-disc pl-6 space-y-1 text-gray-700"></ul>
        </div>
        <div class="p-3 rounded-xl bg-gray-50">
          <div class="text-xs uppercase text-gray-500 mb-1">Revision Suggestions</div>
          <ul id="suggestions" class="list-disc pl-6 space-y-1 text-gray-700"></ul>
        </div>
      </div>
    </div>

    {{-- Annotated Corrections (optional) --}}
    <div class="bg-white rounded-2xl border mt-6 p-4 hidden" id="annotCard">
      <div class="flex items-center justify-between">
        <h2 class="text-xl font-bold">Annotated Changes</h2>
        <div class="text-xs text-gray-400">Legend: <ins class="annot-ins px-1 mx-1">added/replaced</ins> <del class="annot-del px-1 mx-1">removed</del></div>
      </div>
      <div class="grid md:grid-cols-2 gap-4 mt-3">
        <div class="p-3 rounded-xl bg-gray-50">
          <div class="text-xs uppercase text-gray-500 mb-1">Original</div>
          <div id="origText" class="text-sm whitespace-pre-wrap break-words"></div>
        </div>
        <div class="p-3 rounded-xl bg-gray-50">
          <div class="text-xs uppercase text-gray-500 mb-1">Corrected (AI)</div>
          <div id="corrText" class="text-sm whitespace-pre-wrap break-words"></div>
        </div>
      </div>
      <div class="mt-4">
        <h3 class="text-base font-semibold">Inline Diff</h3>
        <div id="diffHtml" class="prose prose-sm max-w-none leading-7 whitespace-pre-wrap break-words"></div>
      </div>
    </div>

    {{-- History --}}
    <details class="mt-8 group">
      <summary class="flex items-center justify-between cursor-pointer select-none">
        <h2 class="text-xl font-bold text-indigo-700">📜 History (Local Only)</h2>
        <span class="text-sm text-gray-500 group-open:hidden">Click to expand</span>
        <span class="text-sm text-gray-500 hidden group-open:inline">Click to collapse</span>
      </summary>
      <div class="flex items-center justify-between mb-2 mt-3">
        <div class="flex gap-3">
          <button id="btnSaveSnapshot" class="text-sm text-blue-600 underline">Save snapshot</button>
          <button id="btnClearHistory" class="text-sm text-red-600 underline">Clear</button>
        </div>
        <span id="histCount" class="text-xs text-gray-400"></span>
      </div>
      <div id="historyList" class="space-y-3 max-h-80 overflow-y-auto pr-1"></div>
    </details>

  </div>
</div>

{{-- ===== Styles ===== --}}
<style>
  /* Keep layout stable when details open/close or scrollbar appears */
  html { scrollbar-gutter: stable both-edges; }
  body { min-height: 100vh; overflow-y: scroll; }

  .annot-ins { background: #DCFCE7; border-radius: .25rem; text-decoration: none; }
  .annot-del { background: #FEE2E2; border-radius: .25rem; text-decoration: line-through; }
  #diffHtml ins { background: #DCFCE7; text-decoration: none; padding: 0 .15rem; border-radius: .2rem; }
  #diffHtml del { background: #FEE2E2; padding: 0 .15rem; border-radius: .2rem; }
  #overlay { position: fixed; inset: 0; background: rgba(255,255,255,.6); display: none; align-items: center; justify-content: center; z-index: 60; backdrop-filter: blur(2px); }
  #overlay.show { display: flex; }
</style>

<div id="overlay">
  <div class="flex items-center gap-3 px-4 py-2 rounded-xl bg-white shadow">
    <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24" fill="none">
      <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" opacity=".2"/>
      <path d="M22 12a10 10 0 0 1-10 10" stroke="currentColor" stroke-width="4"/>
    </svg>
    <span class="text-sm text-gray-700">Working…</span>
  </div>
</div>

<meta name="csrf-token" content="{{ csrf_token() }}">

<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script>pdfjsLib.GlobalWorkerOptions.workerSrc = "https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js";</script>

<script>
/* =========================
   Boot / Refs
========================= */
const APP_ABS = "{{ rtrim(config('app.url') ?? url('/'), '/') }}";
const ORIGIN  = (location && location.origin) ? location.origin : APP_ABS;
const CSRF    = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

const $ = (id)=>document.getElementById(id);

// Inputs
const titleEl = $('title');
const rubricEl = $('rubric');
const essayText = $('essayText');
const rubricRef = $('rubricRef');

// File controls
const fileInput = $('fileInput'), cameraInput = $('cameraInput');
const chooseButton = $('chooseButton'), cameraButton = $('cameraButton');
const stitchToggle = $('stitchToggle');

// Previews
const previewWrap = $('previewWrap'), previewImg = $('previewImg'), previewPdf = $('previewPdf');
const pdfCanvas = $('pdfCanvas');
const previewMeta = $('previewMeta'), thumbGrid = $('thumbGrid');

// Buttons / Status
const btnExtract = $('btnExtract');
const extractStatus = $('extractStatus');
const btnAnalyze = $('btnAnalyze'), btnSuggest = $('btnSuggest');
const analyzeStatus = $('analyzeStatus');
const btnSaveSnapshot = $('btnSaveSnapshot'), btnClearHistory = $('btnClearHistory');
const btnExportDocx = $('btnExportDocx');
const overlay = $('overlay');

// Title snap
const cameraTitleButton = $('cameraTitleButton'),
      cameraTitleInput  = $('cameraTitleInput'),
      uploadTitleButton = $('uploadTitleButton'),
      uploadTitleInput  = $('uploadTitleInput');

// Results
const resultCard = $('resultCard');
const badgeRubric = $('badgeRubric');
const scContent = $('scContent'), scComm = $('scComm'), scOrg = $('scOrg'), scLang = $('scLang'), scTotal = $('scTotal');
const rationaleList = $('rationaleList'), suggestions = $('suggestions');

// Annotated
const annotCard = $('annotCard'), origTextEl = $('origText'), corrTextEl = $('corrText'), diffHtmlEl = $('diffHtml');

// History
const historyList = $('historyList'), histCount = $('histCount');

// State
let selectedFiles = []; // chosen files (images and/or single pdf)
let lastOCRText = '';
let history = [];
try { history = JSON.parse(localStorage.getItem('essayProHistory') || '[]'); } catch (_) { history = []; }
renderHistory();

// Default rubric text
rubricRef.value = `SPM Writing

Part 1 — Assessment scale (5/3/1/0):
5: Content fully relevant; reader well informed; conveys straightforward ideas using an appropriate text type; uses simple linkers/few cohesive devices; basic vocabulary and simple grammar well controlled; errors do not impede understanding.
3: Slight irrelevance/omission; reader generally informed; simple ideas expressed simply; relies on common linkers; basic vocabulary and simple grammar sometimes inaccurate and may affect understanding.
1: Task may be misunderstood; reader minimally informed; mostly short, disconnected sentences; weak cohesion; vocabulary mainly isolated words/phrases; limited control of simple grammar. 0: Completely irrelevant.

Part 2 — Assessment scale:
5: Content fully relevant; reader well informed; appropriate text type and engaging; coherent organization with varied cohesion; fairly wide everyday vocabulary (occasional misuse of less common words); good control of simple and some complex grammar; errors do not hinder communication.
3: Slight irrelevance/omission; reader generally informed; text type used adequately; mainly simple linkers with limited cohesion; fair control of vocabulary and grammar though errors occur. 0–1: Same as Part 1 low bands.

Part 3 — Assessment scale:
5: Content fully relevant; purpose achieved; well organized with varied cohesion; wide vocabulary including some less common items; flexible use of simple + complex grammar with good control; only occasional slips.
3: Slight irrelevance/omission; reader generally informed and engaged; fairly well organized with some variety of linking; reasonably wide vocabulary (occasional misuse of less common words); good control of simple and some complex grammar. 0–1: Same as Part 1 low bands.

UASA / Form 3 Writing

Part 1:
5: Fully relevant; reader well informed; conveys straightforward ideas with an appropriate text type; uses simple linkers/few cohesive devices; good control of basic vocabulary and simple grammar (errors noticeable but not serious).
3: Slight irrelevance/omission; reader generally informed; simple ideas in simple forms; relies on common linkers; basic vocabulary/grammar sometimes affect understanding. 1–0: Same as SPM Part 1 low bands.

Part 2:
5: Fully relevant; reader well informed; text type engages and informs; coherent and organized with some varied cohesion; fairly wide everyday vocabulary; good control of simple + some complex grammar; errors do not impede understanding.
3: Slight irrelevance/omission; reader generally informed; text type adequate; mostly simple linkers/limited cohesion; basic vocabulary and simple grammar mostly accurate and understandable. 1–0: Same as above.`;

/* =========================
   File Selection
========================= */
chooseButton.addEventListener('click',()=>fileInput.click());
cameraButton.addEventListener('click',()=>cameraInput.click());
fileInput.addEventListener('change', handleFiles);
cameraInput.addEventListener('change', handleFiles);

async function handleFiles(e){
  const files = Array.from(e.target.files || []);
  if(!files.length) return;

  // Enforce: single PDF OR one/more images
  const pdfs = files.filter(f => f.type === 'application/pdf' || /\.pdf$/i.test((f.name || '')));
  const imgs = files.filter(f => {
    const name = (f.name || '').toLowerCase();
    return (f.type && f.type.startsWith('image/')) ||
           /\.(jpe?g|png|gif|webp|heic|heif|bmp)$/i.test(name);
  });

  if (pdfs.length > 1 || (pdfs.length === 1 && imgs.length > 0)) {
    alert('Please choose either a single PDF or images (not both).');
    return;
  }

  // Size hint
  const total = files.reduce((s,f)=>s+f.size,0);
  const limit = pdfs.length ? 20*1024*1024 : 25*1024*1024;
  if (total > limit) {
    alert(`Selected files exceed ${limit/1024/1024} MB total.`);
    return;
  }

  selectedFiles = files;
  previewWrap.classList.remove('hidden');
  thumbGrid.innerHTML = '';
  previewMeta.textContent = `Files: ${files.length} · Total: ${humanSize(total)}`;

  // Reset UI enabling Step 1
  btnExtract.disabled = false;
  btnAnalyze.disabled = true;
  btnSuggest.disabled = true;
  analyzeStatus.textContent = '';
  extractStatus.textContent = '';

  if (pdfs.length === 1) {
    // Show simple PDF preview (first page)
    previewImg.classList.add('hidden');
    previewPdf.classList.remove('hidden');
    previewPdf.textContent = `PDF selected: ${pdfs[0].name}`;
    await renderPdfFirstPage(pdfs[0]).catch(()=>{});
    return;
  }

  // Images: show thumbnails
  for (const f of imgs) {
    const url = URL.createObjectURL(f);
    const im = document.createElement('img');
    im.src = url; im.className = 'w-full h-16 object-cover rounded border';
    thumbGrid.appendChild(im);
  }
  previewImg.classList.add('hidden');
  previewPdf.classList.add('hidden');
}

/* =========================
   Step 1: OCR only (no changes)
========================= */
btnExtract.addEventListener('click', doOCR);

async function doOCR(){
  if (!selectedFiles.length) {
    return alert('Please choose a file (image or single PDF) first.');
  }
  extractStatus.textContent = '';
  overlay.classList.add('show');
  btnExtract.disabled = true;
  btnAnalyze.disabled = true;
  btnSuggest.disabled = true;

  try {
    const pdfs = selectedFiles.filter(f => f.type === 'application/pdf' || /\.pdf$/i.test((f.name || '')));
    const imgs = selectedFiles.filter(f => {
      const name = (f.name || '').toLowerCase();
      return (f.type && f.type.startsWith('image/')) ||
             /\.(jpe?g|png|gif|webp|heic|heif|bmp)$/i.test(name);
    });

    let text = '';

    if (pdfs.length === 1) {
      // Send the PDF directly to OCR endpoint
      text = await ocrSingle(pdfs[0]);
    } else if (imgs.length > 0) {
      // Either stitch and send once, or OCR each image and concatenate (no extra labels, no trims)
      if (stitchToggle.checked && imgs.length > 1) {
        // stitchImages 里已经会把各图片转成 JPEG 再拼接
        const stitched = await stitchImages(imgs);
        showStitchedPreview(stitched);
        const stitchedFile = dataURLtoFile(stitched, `images_bundle_${Date.now()}.jpg`);
        text = await ocrSingle(stitchedFile);
      } else if (imgs.length === 1) {
        // ✅ 单张图：统一转成 JPEG 再发给后端，避免 HEIC 等格式不兼容
        const normalized = await normalizeImageFile(imgs[0]);
        text = await ocrSingle(normalized);
      } else {
        // Multi-image, per-image OCR concatenate WITHOUT adding markers or trimming
        const chunks = [];
        for (const f of imgs) {
          const normalized = await normalizeImageFile(f);
          const t = await ocrSingle(normalized);
          chunks.push(t); // keep as-is
        }
        text = chunks.join('\n\n'); // minimal separation only
      }
    }

    // Keep original OCR output "as-is" (no trim, no decoration)
    lastOCRText = (text || '');

    if (!lastOCRText) {
      extractStatus.textContent = '❌ OCR returned empty text.';
      btnExtract.disabled = false;
      return;
    }

    // Put OCR text into editor for manual editing
    essayText.value = lastOCRText;

    // Enable Step 3 after user has something to edit
    btnAnalyze.disabled = false;
    btnSuggest.disabled = false;

    extractStatus.textContent = '✅ OCR complete. Original text placed in the editor.';
  } catch (err) {
    console.error(err);
    extractStatus.textContent = '❌ OCR failed. See console.';
  } finally {
    overlay.classList.remove('show');
  }
}

async function ocrSingle(file){
  const fd = new FormData();
  fd.append('file', file, file.name || 'upload.bin');
  // Optional hint for backend if you later want to tune OCR:
  // fd.append('mode', 'essay'); 
  const res = await fetch(ORIGIN + '/api/ocr', { method:'POST', headers: { 'X-CSRF-TOKEN': CSRF }, body: fd });
  const json = await res.json().catch(()=>({}));
  if (!res.ok) throw new Error('OCR error: ' + (json?.error || res.status));
  // Return whichever field server uses; do not trim/modify
  return json.text ?? json.extracted ?? json.ocr ?? '';
}

/* =========================
   Step 3: Analyze AFTER editing
========================= */
btnAnalyze.addEventListener('click', analyzeEdited);
btnSuggest.addEventListener('click', suggestCorrections);

async function analyzeEdited(){
  const text = (essayText.value || '').trim();
  if (!text) return alert('Empty text. Please OCR first, then edit, then analyze.');

  overlay.classList.add('show');
  analyzeStatus.textContent = 'Analyzing…';
  btnAnalyze.disabled = true;

  try {
    const payload = { title: titleEl.value || '', rubric: rubricEl.value, text };
    const res = await fetch(ORIGIN + '/api/grade', {
      method:'POST',
      headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN': CSRF },
      body: JSON.stringify(payload)
    });
    const json = await res.json().catch(()=>({}));
    if (!res.ok || !json.ok) throw new Error(json.error || 'Grade failed.');

    renderScore(json, rubricEl.value);
    analyzeStatus.textContent = '✅ Done.';
    // keep for export
    window.__lastGrade = json;

    // Save to history
    pushHistory({
      time: new Date().toLocaleString(),
      title: titleEl.value || '',
      rubric: rubricEl.value || '',
      extracted: lastOCRText || '',
      corrected: text,
      explanations: (json.rationales || json.explanations || json.criteria_explanations || json.rubric_breakdown || [])
    });
  } catch (e) {
    console.error(e);
    analyzeStatus.textContent = '❌ Analyze failed.';
    alert(e.message || 'Analyze failed.');
  } finally {
    overlay.classList.remove('show');
    btnAnalyze.disabled = false;
  }
}

async function suggestCorrections(){
  const text = (essayText.value || '').trim();
  if (!text) return alert('Empty text. Nothing to suggest.');
  overlay.classList.add('show');
  btnSuggest.disabled = true;
  analyzeStatus.textContent = 'Requesting suggestions…';

  try {
    // Use the "direct-correct" endpoint in TEXT mode (no file), so we correct what user edited
    const fd = new FormData();
    fd.append('text', text);
    fd.append('title', titleEl.value || '');
    const res = await fetch(ORIGIN + '/api/essay/direct-correct', { method:'POST', headers:{ 'X-CSRF-TOKEN': CSRF }, body: fd });
    const json = await res.json().catch(()=>({}));
    if (!res.ok || !json.ok) throw new Error(json.error || 'Suggest failed.');

    const original = json.extracted || text;
    const corrected = json.corrected || json.extracted || text;

    // show annotated panel
    renderAnnotations(original, corrected);
    analyzeStatus.textContent = '💡 Suggestions ready (see Annotated Changes).';

    // Optionally apply corrected text to editor? Keep original for user's control.
    // essayText.value = corrected;

  } catch (e) {
    console.error(e);
    analyzeStatus.textContent = '❌ Suggest failed.';
  } finally {
    overlay.classList.remove('show');
    btnSuggest.disabled = false;
  }
}

/* =========================
   Score / Annotated / Utils
========================= */
function renderScore(payload, rubricCode){
  resultCard.classList.remove('hidden');
  badgeRubric.textContent = rubricCode || '-';

  const s = payload.scores || {};
  scContent.textContent = num(s.content);
  scComm.textContent    = num(s.communicative ?? s.communicative_achievement);
  scOrg.textContent     = num(s.organisation);
  scLang.textContent    = num(s.language);
  scTotal.textContent   = num(s.total);

  const rationales = []
    .concat(payload.rationales || [])
    .concat(payload.explanations || [])
    .concat(payload.criteria_explanations || [])
    .concat(payload.rubric_breakdown || []);

  rationaleList.innerHTML = '';
  if (rationales.length) {
    for (const r of rationales) {
      const li = document.createElement('li');
      li.textContent = typeof r === 'string' ? r : JSON.stringify(r);
      rationaleList.appendChild(li);
    }
  } else {
    const li = document.createElement('li');
    li.textContent = 'No detailed explanations returned by the API.';
    rationaleList.appendChild(li);
  }

  suggestions.innerHTML = '';
  (payload.suggestions || []).forEach(x=>{
    const li = document.createElement('li'); li.textContent = x;
    suggestions.appendChild(li);
  });
}

function renderAnnotations(original, corrected){
  origTextEl.textContent = original || '-';
  corrTextEl.textContent = corrected || '-';
  diffHtmlEl.innerHTML = makeAnnotatedDiff(original || '', corrected || '');
  annotCard.classList.remove('hidden');
}

function makeAnnotatedDiff(a, b){
  const at = tokenize(a), bt = tokenize(b), lcs = buildLCS(at, bt);
  let i=0, j=0, html='';
  for (const [ti, tj] of lcs){
    while(i < ti){ html += `<del>${escapeHTML(at[i])}</del>`; i++; }
    while(j < tj){ html += `<ins>${escapeHTML(bt[j])}</ins>`; j++; }
    if (ti < at.length && tj < bt.length){ html += escapeHTML(at[ti]); i = ti + 1; j = tj + 1; }
  }
  while(i < at.length){ html += `<del>${escapeHTML(at[i++])}</del>`; }
  while(j < bt.length){ html += `<ins>${escapeHTML(bt[j++])}</ins>`; }
  return html;
}

function tokenize(s){ const re=/[A-Za-z0-9’'’-]+|\s+|[^\sA-Za-z0-9]/g; const out=[]; let m; while((m=re.exec(s))){ out.push(m[0]); } return out.length?out:[s]; }
function buildLCS(a,b){ const n=a.length,m=b.length,dp=Array.from({length:n+1},()=>Array(m+1).fill(0)); for(let i=n-1;i>=0;i--){ for(let j=m-1;j>=0;j--){ dp[i][j]=(a[i]===b[j])?dp[i+1][j+1]+1:Math.max(dp[i+1][j],dp[i][j+1]); } } const path=[]; let i=0,j=0; while(i<n&&j<m){ if(a[i]===b[j]){ path.push([i,j]); i++; j++; } else if(dp[i+1][j]>=dp[i][j+1]) i++; else j++; } return path; }
function escapeHTML(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;'); }
function num(x){ return (x ?? '-'); }
function humanSize(bytes){ const u=['B','KB','MB','GB']; let i=0,n=bytes||0; while(n>=1024&&i<u.length-1){n/=1024;i++;} return `${n.toFixed(1)} ${u[i]}`; }

/* =========================
   Image tools (stitch) + PDF preview
   (slightly higher width & quality to help OCR a bit)
========================= */
async function stitchImages(files){
  // Compress & equalize widths, then vertical stitch
  const pieces = [];
  for (const f of files) {
    const dataURL = await readAsDataURL(f);
    const compressed = await compressImage(dataURL).catch(()=>dataURL);
    const img = await loadImage(compressed);
    pieces.push({ img, w: img.width, h: img.height });
  }
  const width = Math.max(...pieces.map(p => p.w));
  const totalHeight = pieces.reduce((s,p)=> s + Math.round(p.h * (width / p.w)), 0);
  const out = document.createElement('canvas');
  out.width = width; out.height = totalHeight;
  const ctx = out.getContext('2d');
  let y = 0;
  for (const p of pieces) {
    const nh = Math.round(p.h * (width / p.w));
    ctx.drawImage(p.img, 0, y, width, nh);
    y += nh;
  }
  return out.toDataURL('image/jpeg', 0.95);
}

function showStitchedPreview(dataURL){
  previewImg.src = dataURL;
  previewImg.classList.remove('hidden');
  previewPdf.classList.add('hidden');
  pdfCanvas.classList.add('hidden');
}

function dataURLtoFile(dataURL, filename){
  const [h,b] = dataURL.split(',');
  const mime = (h.match(/:(.*?);/)||[])[1] || 'image/jpeg';
  const bin = atob(b);
  const u8 = new Uint8Array(bin.length);
  for (let i=0;i<bin.length;i++) u8[i] = bin.charCodeAt(i);
  return new File([u8], filename, { type: mime });
}

function readAsDataURL(file){
  return new Promise((resolve,reject)=>{
    const fr = new FileReader();
    fr.onload = ()=> resolve(fr.result);
    fr.onerror = reject;
    fr.readAsDataURL(file);
  });
}
function loadImage(dataURL){
  return new Promise((resolve,reject)=>{
    const img = new Image();
    img.onload = ()=> resolve(img);
    img.onerror = reject;
    img.src = dataURL;
  });
}

async function renderPdfFirstPage(file){
  try{
    const arrayBuf = await file.arrayBuffer();
    const pdf = await pdfjsLib.getDocument({ data: arrayBuf }).promise;
    const page = await pdf.getPage(1);
    const viewport = page.getViewport({ scale: 1.1 });
    const ctx = pdfCanvas.getContext('2d');
    pdfCanvas.width = Math.floor(viewport.width);
    pdfCanvas.height = Math.floor(viewport.height);
    pdfCanvas.classList.remove('hidden');
    previewPdf.classList.remove('hidden');
    await page.render({ canvasContext: ctx, viewport }).promise;
  }catch(e){
    console.warn('PDF preview failed', e);
  }
}

/* =========================
   👉 Normalize image file to JPEG (for camera / HEIC etc.)
========================= */
async function normalizeImageFile(file, maxWidth=1600, quality=0.95){
  const dataURL = await readAsDataURL(file);
  const compressed = await compressImage(dataURL, maxWidth, quality).catch(()=>dataURL);
  const base = (file.name || 'image').replace(/\.[^.]+$/, '');
  return dataURLtoFile(compressed, base + '.jpg');
}

/* =========================
   Title Snap (OCR) — Take Photo + Upload
========================= */
cameraTitleButton.addEventListener('click', ()=>cameraTitleInput.click());
uploadTitleButton.addEventListener('click', ()=>uploadTitleInput.click());
cameraTitleInput.addEventListener('change', handleTitleImage);
uploadTitleInput.addEventListener('change', handleTitleImage);

async function handleTitleImage(e){
  const f = e.target.files?.[0]; if (!f) return;
  overlay.classList.add('show');
  try{
    // ✅ Title 这边也统一转成 JPEG
    const srcFile = await normalizeImageFile(f, 1600, 0.95);
    const fd = new FormData();
    fd.append('file', srcFile, srcFile.name || 'title.jpg');
    // Optional hint for backend tuning:
    // fd.append('mode', 'title');
    const res = await fetch(ORIGIN + '/api/ocr', { method:'POST', headers: { 'X-CSRF-TOKEN': CSRF }, body: fd });
    const json = await res.json().catch(()=>({}));
    const t = (json.text || json.extracted || json.ocr || '').trim();
    if (t) titleEl.value = t.slice(0, 200);
    else alert('Failed to extract title text. Please try a clearer photo or type manually.');
  }catch(err){
    console.error(err); alert('Title OCR error.');
  }finally{
    overlay.classList.remove('show');
    e.target.value = '';
  }
}

/* =========================
   DOCX Export
========================= */
btnExportDocx.addEventListener('click', async (ev)=>{
  ev.preventDefault();
  const old = btnExportDocx.textContent;
  btnExportDocx.disabled = true; btnExportDocx.textContent = 'Exporting…';

  try{
    const extracted = (lastOCRText || essayText.value || '');
    const corrected = (essayText.value || '').trim();
    if (!corrected) { alert('Nothing to export.'); return; }

    const fromDom = Array.from(document.querySelectorAll('#rationaleList li')).map(li => li.textContent);
    const last = window.__lastGrade || {};
    const explanations = [
      ...(last.rationales || []),
      ...(last.explanations || []),
      ...(last.criteria_explanations || []),
      ...(last.rubric_breakdown || []),
      ...fromDom
    ].filter(Boolean).slice(0, 80);

    const payload = {
      title: (titleEl.value || 'Essay Report').slice(0, 200),
      extracted,
      corrected,
      explanations
    };

    const tryUrls = [
      ORIGIN + "/api/essay/export-docx",
      APP_ABS + "/api/essay/export-docx",
      "{{ route('api.essay.exportDocx', [], false) }}",
    ].filter(Boolean);

    for (const u of tryUrls) {
      try {
        const res = await fetch(u, {
          method:'POST',
          headers:{
            'Content-Type':'application/json',
            'Accept':'application/vnd.openxmlformats-officedocument.wordprocessingml.document, application/octet-stream',
            'X-CSRF-TOKEN': CSRF
          },
          body: JSON.stringify(payload),
          redirect: 'follow',
          cache: 'no-store',
        });

        const blob = await res.blob();
        const ct = (res.headers.get('content-type') || '').toLowerCase();
        const cd = res.headers.get('content-disposition') || '';

        const okDoc =
          res.ok && (
            ct.includes('application/vnd.openxmlformats-officedocument.wordprocessingml.document') ||
            ct.includes('application/octet-stream') ||
            /filename=.*\.docx/i.test(cd) ||
            (blob && blob.size > 1000)
          );

        if (okDoc) {
          const url = URL.createObjectURL(blob);
          const a = document.createElement('a');
          const m = cd.match(/filename\*=UTF-8''([^;]+)|filename="?([^"]+)"?/i);
          const fname = m ? decodeURIComponent(m[1] || m[2]) : 'essay-report.docx';
          a.href = url; a.download = fname.endsWith('.docx') ? fname : (fname + '.docx');
          document.body.appendChild(a); a.click(); a.remove();
          URL.revokeObjectURL(url);
          return;
        } else {
          try { console.warn('[Export] Not DOCX:', (await blob.text()).slice(0,300)); } catch(_){}
        }
      } catch (e) {
        console.warn('[Export] fetch error:', u, e);
      }
    }

    alert('❌ Export failed: server did not return DOCX.');
  } finally {
    btnExportDocx.disabled = false; btnExportDocx.textContent = old;
  }
});

/* =========================
   Local History
========================= */
function pushHistory(item){
  history.unshift(item);
  history = history.slice(0, 60);
  localStorage.setItem('essayProHistory', JSON.stringify(history));
  renderHistory();
}
function renderHistory(){
  histCount.textContent = `${history.length} record(s)`;
  historyList.innerHTML = history.map((h,idx)=>`
    <details class="bg-gray-50 rounded-lg p-3 border">
      <summary class="cursor-pointer font-semibold text-gray-800 truncate">
        ${escapeHTML(h.time)} — ${escapeHTML(h.title||'(No title)')}
      </summary>
      <div class="mt-2 text-sm text-gray-700 space-y-2">
        <p><strong>Rubric:</strong> ${escapeHTML(h.rubric||'-')}</p>
        ${h.extracted ? `<div><strong>Extracted:</strong><br>${escapeHTML(h.extracted)}</div>`:''}
        ${h.corrected ? `<div><strong>Edited:</strong><br>${escapeHTML(h.corrected)}</div>`:''}
        ${(h.explanations||[]).length ? `
          <div><strong>Explanations:</strong>
            <ul class="list-disc pl-5">${h.explanations.map(x=>`<li>${escapeHTML(x)}</li>`).join('')}</ul>
          </div>` : ''
        }
        <div class="pt-1">
          <button data-idx="${idx}" class="btnLoad text-blue-600 underline">Load to editor</button>
          <button data-idx="${idx}" class="btnDelete text-red-600 underline ml-3">Delete</button>
        </div>
      </div>
    </details>
  `).join('');

  historyList.querySelectorAll('.btnLoad').forEach(btn=>{
    btn.onclick = ()=>{
      const i = +btn.getAttribute('data-idx');
      const h = history[i]; if(!h) return;
      titleEl.value = h.title || '';
      rubricEl.value = h.rubric || 'SPM_P1';
      essayText.value = h.corrected || h.extracted || '';
      lastOCRText = h.extracted || '';
      window.scrollTo({ top: 0, behavior: 'smooth' });
      // Enable analyze since text is present
      btnAnalyze.disabled = !(essayText.value || '').trim();
      btnSuggest.disabled = !(essayText.value || '').trim();
    };
  });
  historyList.querySelectorAll('.btnDelete').forEach(btn=>{
    btn.onclick = ()=>{
      const i = +btn.getAttribute('data-idx');
      history.splice(i,1);
      localStorage.setItem('essayProHistory', JSON.stringify(history));
      renderHistory();
    };
  });
}

btnSaveSnapshot.addEventListener('click', ()=>{
  pushHistory({
    time: new Date().toLocaleString(),
    title: titleEl.value || '',
    rubric: rubricEl.value || '',
    extracted: lastOCRText || '',
    corrected: (essayText.value||'').trim(),
    explanations: Array.from(document.querySelectorAll('#rationaleList li')).map(li=>li.textContent).slice(0,20)
  });
});

btnClearHistory.addEventListener('click', ()=>{
  if(confirm('Clear all local history?')){
    history = [];
    localStorage.removeItem('essayProHistory');
    renderHistory();
  }
});

/* ===== helpers ===== */
function compressImage(dataURL, maxWidth=1600, quality=0.95){
  return new Promise((resolve,reject)=>{
    const img = new Image();
    img.onload = ()=>{
      const scale = Math.min(1, maxWidth / img.width);
      const canvas = document.createElement('canvas');
      canvas.width = Math.round(img.width * scale);
      canvas.height = Math.round(img.height * scale);
      const ctx = canvas.getContext('2d'); 
      ctx.drawImage(img, 0, 0, canvas.width, canvas.height);  
      resolve(canvas.toDataURL('image/jpeg', quality)); 
    };
    img.onerror = reject;
    img.src = dataURL;
  });
}
</script>
@endsection
