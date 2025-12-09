{{-- resources/views/essay-pro.blade.php --}}
@extends('layouts.app')

@section('title', '✍️ Essay Pro — Two-Step OCR → Edit → Analyze')

@section('content')
<div class="min-h-[70vh] flex flex-col items-center justify-center p-4">
  <div class="bg-white shadow-2xl rounded-2xl p-6 w-full max-w-6xl text-left transition-all duration-300 overflow-x-hidden">

    {{-- Header --}}
    <div class="flex items-center justify-between gap-4 mb-4">
      <h1 class="text-3xl font-extrabold bg-gradient-to-r from-indigo-600 to-blue-600 bg-clip-text text-transparent">
        ✍️ Essay Pro — OCR → Edit → Analyze
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
        <div class="flex gap-2">
          <input id="title" type="text" placeholder="e.g., The Importance of Reading"
                 class="flex-1 rounded-xl border-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
          <button id="cameraTitleButton" class="px-3 py-2 rounded-lg bg-purple-600 text-white hover:bg-purple-700 text-sm">
            📷 Take Photo
          </button>
          <input type="file" id="cameraTitleInput" accept="image/*" capture="environment" class="hidden">
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
              📁 Choose File(s)
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

    {{-- Score (kept visible but will be filled when available) --}}
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
const cameraTitleButton = $('cameraTitleButton'), cameraTitleInput = $('cameraTitleInput');

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

// Default rubric text — DO NOT MODIFY the rubric content (user requested original rubric)
rubricRef.value = `SPM Writing
Part 1 — Assessment scale (5/3/1/0):
5: Content is fully relevant; readers are well informed; answer all the questions asked; conveys simple ideas using an appropriate text type and tone smoothly; uses simple linkers and at least one cohesive device; punctuations are used correctly and ideas are well-structured; basic vocabulary are used appropriately and uses simple grammatical forms with good control; errors are noticeable but meaning can still be determined.
4: Performances shared features of Score 3 and 5
3: Slight irrelevance/omission; misinterpreted one or two questions but readers are generally informed; simple ideas expressed simply; relies on common linkers and no cohesive device is used; use basic vocabulary and simple grammar with some degree of control; errors are sometimes inaccurate and may affect understanding.
2: Performances shared features of Score 1 and 3
1: Task may be misunderstood; readers are minimally informed; mostly short, disconnected sentences; ideas are simple but not always communicated successfully; weak cohesion; incorrect use of punctuation; vocabulary mainly isolated words/phrases; limited control of simple grammar. 0: Completely irrelevant.

Part 2 — Assessment scale:
5: Content fully relevant; reader well informed, answer all the questions appropriately; conveys straightforward ideas using an appropriate text type and tone smoothly; coherent organization with a variety of cohesive devices; fairly wide everyday vocabulary with occasional misuse of less common words; good control of simple and some complex grammar; errors do not hinder communication.
4: Performances shared features of Score 3 and 5
3: Slight irrelevance/omission; misinterpreted one or two questions but reader generally informed; conveys simple ideas using an appropriate text type and tone smoothly; use simple sentence connectors and some cohesive devices appropriately; use basic vocabulary and simple grammar with good control; errors are noticeable but meaning can still be determined.
2: Performances shared features of Score 1 and 3
1: Task may be misunderstood; readers are minimally informed; simple ideas expressed simply; relies on common linkers and no cohesive device is used; ; incorrect use of punctuation; use basic vocabulary and simple grammar with some degree of control; errors are sometimes inaccurate and may affect understanding.
0: Content is totally irrelevant and any performance is below score 1.

Part 3 — Assessment scale:
5: Content fully relevant and answered all the questions; communicative purpose achieved; complex ideas are delivered smoothly; well organized with a variety of cohesive devices that are used effectively; use wide vocabulary including some less common vocabulary correctly; flexible use of simple + complex grammar with good control; only occasional slips.
4: Performances shared features of Score 3 and 5
3: Slight irrelevance/omission; misinterpreted one or two questions but reader generally informed and engaged; conveys straightforward ideas using an appropriate text type and tone smoothly; coherent organization with a variety of cohesive devices; fairly wide everyday vocabulary with occasional misuse of less common words; good control of simple and some complex grammar; errors do not hinder communication.
2: Performances shared features of Score 1 and 3
1: Only manage to answer one sub-question; misinterpreted one or two questions but reader generally informed; conveys simple ideas using an appropriate text type and tone smoothly; use simple sentence connectors and some cohesive devices appropriately; use basic vocabulary and simple grammar with good control; errors are noticeable but meaning can still be determined.

UASA / Form 3 Writing
Part 1:
5: Content is fully relevant; readers are well informed; answer all the questions asked; conveys simple ideas using an appropriate text type and tone smoothly; uses simple linkers and at least one cohesive device; punctuations are used correctly and ideas are well-structured; basic vocabulary are used appropriately and uses simple grammatical forms with good control; errors are noticeable but meaning can still be determined.
4: Performances shared features of Score 3 and 5
3: Slight irrelevance/omission; misinterpreted one or two questions but readers are generally informed; simple ideas expressed simply; relies on common linkers and no cohesive device is used; use basic vocabulary and simple grammar with some degree of control; errors are sometimes inaccurate and may affect understanding.
2: Performances shared features of Score 1 and 3
1: Task may be misunderstood; readers are minimally informed; mostly short, disconnected sentences; ideas are simple but not always communicated successfully; weak cohesion; incorrect use of punctuation; vocabulary mainly isolated words/phrases; limited control of simple grammar. 0: Completely irrelevant.

Part 2:
5: Content fully relevant; reader well informed, answer all the questions appropriately; conveys straightforward ideas using an appropriate text type and tone smoothly; coherent organization with a variety of cohesive devices; fairly wide everyday vocabulary with occasional misuse of less common words; good control of simple and some complex grammar; errors do not hinder communication.
4: Performances shared features of Score 3 and 5
3: Slight irrelevance/omission; misinterpreted one or two questions but reader generally informed; conveys simple ideas using an appropriate text type and tone smoothly; use simple sentence connectors and some cohesive devices appropriately; use basic vocabulary and simple grammar with good control; errors are noticeable but meaning can still be determined.
2: Performances shared features of Score 1 and 3
1: Task may be misunderstood; readers are minimally informed; simple ideas expressed simply; relies on common linkers and no cohesive device is used; ; incorrect use of punctuation; use basic vocabulary and simple grammar with some degree of control; errors are sometimes inaccurate and may affect understanding.
0: Content is totally irrelevant and any performance is below score 1.
`;

/* =========================
   Utility: enable Analyze when user types/pastes into essayText (no OCR required)
========================= */
function updateActionButtons(){
  const hasText = (essayText.value || '').trim().length > 0;
  btnAnalyze.disabled = !hasText;
  btnSuggest.disabled = !hasText;
}
// enable listening for input
essayText.addEventListener('input', updateActionButtons);
document.addEventListener('DOMContentLoaded', updateActionButtons);

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
  const pdfs = files.filter(f => f.type === 'application/pdf' || /\.pdf$/i.test(f.name));
  const imgs = files.filter(f => f.type.startsWith('image/'));

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
    const pdfs = selectedFiles.filter(f => f.type === 'application/pdf' || /\.pdf$/i.test(f.name));
    const imgs = selectedFiles.filter(f => f.type.startsWith('image/'));

    let text = '';

    if (pdfs.length === 1) {
      // Send the PDF directly to OCR endpoint
      text = await ocrSingle(pdfs[0]);
    } else if (imgs.length > 0) {
      // Either stitch and send once, or OCR each image and concatenate (no extra labels, no trims)
      if (stitchToggle.checked && imgs.length > 1) {
        const stitched = await stitchImages(imgs);
        showStitchedPreview(stitched);
        const stitchedFile = dataURLtoFile(stitched, `images_bundle_${Date.now()}.jpg`);
        text = await ocrSingle(stitchedFile);
      } else if (imgs.length === 1) {
        text = await ocrSingle(imgs[0]);
      } else {
        // Multi-image, per-image OCR concatenate WITHOUT adding markers or trimming
        const chunks = [];
        for (const f of imgs) {
          const t = await ocrSingle(f);
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
    updateActionButtons();

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
  const res = await fetch(ORIGIN + '/api/ocr', { method:'POST', headers: { 'X-CSRF-TOKEN': CSRF }, body: fd });
  const json = await res.json().catch(()=>({}));
  if (!res.ok) throw new Error('OCR error: ' + (json?.error || res.status));
  // Return whichever field server uses; do not trim/modify
  return json.text ?? json.extracted ?? json.ocr ?? '';
}

/* =========================
   Robust parse for plain-text/HTML server responses
   (attempt to extract /5 /20 scores and rationales)
========================= */
function parseServerResponse(rawText){
  const out = {
    scores: { content: null, communicative: null, organisation: null, language: null, total: null },
    rationales: [],
    suggestions: [],
    raw_text: rawText || ''
  };

  if(!rawText) return out;

  // Try to convert HTML -> visible text safely
  let plain;
  try {
    const dp = new DOMParser();
    const doc = dp.parseFromString(rawText, 'text/html');
    doc.querySelectorAll('script,style,noscript').forEach(n => n.remove());
    plain = doc.body ? doc.body.textContent || '' : rawText;
  } catch(e){
    plain = rawText.replace(/<\/?[^>]+(>|$)/g, '');
  }
  plain = (plain || '').replace(/\r/g,'').replace(/\t/g,' ').trim();

  const lines = plain.split('\n').map(l=> l.replace(/\s+/g,' ').trim()).filter(Boolean);
  const joinAll = lines.join('\n');

  const findScore = (text, names) => {
    for(const n of names){
      const re1 = new RegExp(n + '\\s*[:\\-–—]?\\s*(\\d)\\s*\\/\\s*5', 'i');
      const re2 = new RegExp(n + '\\s*[:\\-–—]?\\s*(\\d)\\s*(?:$|\\s|\\W)', 'i');
      const m1 = text.match(re1);
      if(m1) return Number(m1[1]);
      const m2 = text.match(re2);
      if(m2) {
        const v = Number(m2[1]);
        if(!isNaN(v) && v>=0 && v<=5) return v;
      }
    }
    return null;
  };

  out.scores.content = findScore(joinAll, ['Content','CONTENT','content']);
  out.scores.communicative = findScore(joinAll, ['Communicative','communicative','Communicative Achievement','Communicative:']);
  out.scores.organisation = findScore(joinAll, ['Organisation','organization','Organisation','Organisation:','Organisation']);
  out.scores.language = findScore(joinAll, ['Language','language','Language:']);

  const mTotal = joinAll.match(/(?:Total|Overall total|Overall|Overall score)\s*[:\-–—]?\s*(\d{1,2})\s*\/\s*20/i) ||
                 joinAll.match(/(\d{1,2})\s*\/\s*20/);
  if(mTotal) out.scores.total = Number(mTotal[1]);

  // Fallback: find all /5 occurrences and map to criteria order if four found
  if([out.scores.content, out.scores.communicative, out.scores.organisation, out.scores.language].every(x=> x === null)){
    const all5 = Array.from(joinAll.matchAll(/(\d)\/5/g)).map(m=>Number(m[1]));
    if(all5.length >= 4){
      out.scores.content = all5[0];
      out.scores.communicative = all5[1];
      out.scores.organisation = all5[2];
      out.scores.language = all5[3];
      if(!out.scores.total){
        out.scores.total = all5.slice(0,4).reduce((a,b)=>a+b,0);
      }
    }
  }

  // Collect rationales: lines that start with criterion name or bullet points or lines with /5 or keywords
  const rationaleCandidates = [];
  for(const line of lines){
    if(/^(Content|Communicative|Organisation|Organisation|Language|Overall|Overall total)\b/i.test(line)){
      rationaleCandidates.push(line);
      continue;
    }
    if(/^[\u2022\-\*]\s+/.test(line) || /^[\d]+\.\s+/.test(line)){
      rationaleCandidates.push(line);
      continue;
    }
    if(/\/5\b|\/20\b/.test(line)) rationaleCandidates.push(line);
    if(line.length>30 && /(vocab|vocabulary|grammar|cohesion|cohesive|organis|communicat|relevant|inform)/i.test(line)) rationaleCandidates.push(line);
  }
  out.rationales = Array.from(new Set(rationaleCandidates)).slice(0,60);

  const suggestionLines = lines.filter(l => /suggestion|revise|revision|improv|improve|try to|consider|avoid|should|might|could/i.test(l));
  out.suggestions = Array.from(new Set(suggestionLines)).slice(0,40);

  if(out.rationales.length === 0){
    out.rationales = lines.slice(0,6);
  }

  return out;
}

/* =========================
   Analyze: robust handler for JSON OR plain text/HTML
   Replaces simpler analyzeEdited implementation
========================= */
btnAnalyze.addEventListener('click', analyzeEdited);
btnSuggest.addEventListener('click', suggestCorrections);

async function analyzeEdited(){
  const text = (essayText.value || '').trim();
  if (!text) return alert('Empty text. Please OCR or paste/type text, then analyze.');

  overlay.classList.add('show');
  analyzeStatus.textContent = 'Analyzing…';
  btnAnalyze.disabled = true;

  try {
    const payload = {
      title: titleEl.value || '',
      rubric_code: rubricEl.value || '',
      rubric_text: rubricRef.value || '',
      text,
      prompt_instructions: "Use rubric_text verbatim as the scoring rules. Do NOT paraphrase the rubric. Return structured JSON with scores, rationales, suggestions and (if possible) inline_diff_html. If unable to return JSON, plain text or HTML summary is acceptable."
    };

    const controller = new AbortController();
    const timeoutMs = 45_000;
    const tid = setTimeout(()=>controller.abort(), timeoutMs);

    const res = await fetch(ORIGIN + '/api/grade', {
      method:'POST',
      headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN': CSRF },
      body: JSON.stringify(payload),
      signal: controller.signal,
    }).catch(err => { throw err; });

    clearTimeout(tid);

    const resText = await res.text().catch(()=>null);

    if((!resText || !resText.trim()) && res.ok){
      analyzeStatus.textContent = '❌ Analyze returned empty body.';
      window.__lastGrade = { raw_text: '' };
      alert('Server returned empty body. Check backend.');
      return;
    }

    let json = null;
    try { json = resText ? JSON.parse(resText) : null; } catch(e){ json = null; }

    if(json && (typeof json === 'object')){
      // Structured JSON: use it directly
      window.__lastGrade = json;
      renderScore(json, rubricEl.value);
      analyzeStatus.textContent = '✅ Done (parsed JSON).';
      pushHistory({
        time: new Date().toLocaleString(),
        title: titleEl.value || '',
        rubric: rubricEl.value || '',
        extracted: lastOCRText || '',
        corrected: text,
        explanations: (json.rationales || json.explanations || json.criteria_explanations || json.rubric_breakdown || [])
      });
      return;
    }

    // Not JSON — parse plain/HTML text
    const parsed = parseServerResponse(resText || '');

    const payloadLike = {
      scores: parsed.scores,
      rationales: parsed.rationales,
      suggestions: parsed.suggestions,
      inline_diff_html: ''
    };
    payloadLike.raw_text = parsed.raw_text;

    window.__lastGrade = payloadLike;
    renderScore(payloadLike, rubricEl.value);

    analyzeStatus.textContent = '✅ Done (parsed plain text / HTML).';
    pushHistory({
      time: new Date().toLocaleString(),
      title: titleEl.value || '',
      rubric: rubricEl.value || '',
      extracted: lastOCRText || '',
      corrected: text,
      explanations: parsed.rationales
    });

  } catch (err) {
    console.error('[Analyze] error', err);
    analyzeStatus.textContent = '❌ Analyze failed.';
    if (err.name === 'AbortError') {
      alert('Request timed out after 45s. Check server health or increase timeout.');
    } else {
      alert('Analyze failed: ' + (err.message || err));
    }
  } finally {
    overlay.classList.remove('show');
    btnAnalyze.disabled = false;
  }
}

/* =========================
   Suggest Corrections (existing behavior)
========================= */
async function suggestCorrections(){
  const text = (essayText.value || '').trim();
  if (!text) return alert('Empty text. Nothing to suggest.');
  overlay.classList.add('show');
  btnSuggest.disabled = true;
  analyzeStatus.textContent = 'Requesting suggestions…';

  try {
    const fd = new FormData();
    fd.append('text', text);
    fd.append('title', titleEl.value || '');
    const res = await fetch(ORIGIN + '/api/essay/direct-correct', { method:'POST', headers:{ 'X-CSRF-TOKEN': CSRF }, body: fd });
    const json = await res.json().catch(()=>({}));
    if (!res.ok || !json.ok) throw new Error(json.error || 'Suggest failed.');

    const original = json.extracted || text;
    const corrected = json.corrected || json.extracted || text;

    renderAnnotations(original, corrected);
    analyzeStatus.textContent = '💡 Suggestions ready (see Annotated Changes).';

  } catch (e) {
    console.error(e);
    analyzeStatus.textContent = '❌ Suggest failed.';
  } finally {
    overlay.classList.remove('show');
    btnSuggest.disabled = false;
  }
}

/* =========================
   Score / Annotated / Utils (renderScore robust)
========================= */
function renderScore(payload, rubricCode){
  resultCard.classList.remove('hidden');
  badgeRubric.textContent = rubricCode || '-';

  const scoresObj = payload.scores || payload.score_map || payload.score || payload.summary || {};
  const pick = (obj, names) => {
    for (const n of names) {
      if (obj == null) continue;
      if (typeof obj[n] !== 'undefined' && obj[n] !== null) return obj[n];
    }
    return null;
  };

  const contentScore = pick(scoresObj, ['content','Content','c','content_score','score_content']);
  const commScore    = pick(scoresObj, ['communicative','communicative_achievement','comm','communicative_score','communicative']);
  const orgScore     = pick(scoresObj, ['organisation','organization','org','organisation_score','organisation']);
  const langScore    = pick(scoresObj, ['language','lang','language_score']);
  const totalScore   = pick(scoresObj, ['total','overall','total_score','score_total','sum']);

  const fallbackTotal = pick(payload, ['total','overall','score','score_total','total_score']);

  scContent.textContent = (contentScore ?? '-');
  scComm.textContent    = (commScore ?? '-');
  scOrg.textContent     = (orgScore ?? '-');
  scLang.textContent    = (langScore ?? '-');
  scTotal.textContent   = (totalScore ?? fallbackTotal ?? '-');

  const rationales = []
    .concat(payload.rationales || [])
    .concat(payload.explanations || [])
    .concat(payload.criteria_explanations || [])
    .concat(payload.rubric_breakdown || [])
    .concat(payload.explanation || [])
    .filter(Boolean);

  rationaleList.innerHTML = '';
  if (rationales.length) {
    for (const r of rationales) {
      const li = document.createElement('li');
      li.textContent = (typeof r === 'string') ? r : JSON.stringify(r);
      rationaleList.appendChild(li);
    }
  } else {
    // fallback: if payload.raw_text exists, display first lines
    const raw = payload.raw_text || payload.raw_text_stripped || '';
    if (raw) {
      const lines = raw.split('\n').map(l=>l.trim()).filter(Boolean).slice(0,8);
      for(const ln of lines){
        const li = document.createElement('li'); li.textContent = ln;
        rationaleList.appendChild(li);
      }
    } else {
      const li = document.createElement('li');
      li.textContent = 'No detailed explanations returned by the API.';
      rationaleList.appendChild(li);
    }
  }

  suggestions.innerHTML = '';
  const suggs = []
    .concat(payload.suggestions || [])
    .concat(payload.revision_suggestions || [])
    .concat(payload.advice || [])
    .filter(Boolean);

  if (suggs.length) {
    suggs.forEach(x=>{
      const li = document.createElement('li'); li.textContent = (typeof x === 'string') ? x : JSON.stringify(x);
      suggestions.appendChild(li);
    });
  } else {
    const li = document.createElement('li'); li.textContent = 'No revision suggestions returned by the API.';
    suggestions.appendChild(li);
  }

  // Inline diff: if provided by server, inject and show annotated panel
  const inlineHtml = payload.inline_diff_html || payload.inline_diff || '';
  if (inlineHtml && diffHtmlEl) {
    diffHtmlEl.innerHTML = inlineHtml;
    annotCard.classList.remove('hidden');
    if (payload.original_text) origTextEl.textContent = payload.original_text;
    if (payload.corrected_text) corrTextEl.textContent = payload.corrected_text;
  }
  window.__lastGrade = payload;
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
========================= */
async function stitchImages(files){
  const pieces = [];
  for (const f of files) {
    const dataURL = await readAsDataURL(f);
    const compressed = await compressImage(dataURL, 1200, 0.9).catch(()=>dataURL);
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
  return out.toDataURL('image/jpeg', 0.92);
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
   Title Snap (OCR)
========================= */
cameraTitleButton.addEventListener('click',()=>cameraTitleInput.click());
cameraTitleInput.addEventListener('change', async (e)=>{
  const f = e.target.files?.[0]; if (!f) return;
  overlay.classList.add('show');
  try{
    const fd = new FormData(); fd.append('file', f, f.name || 'title.jpg');
    const res = await fetch(ORIGIN + '/api/ocr', { method:'POST', headers: { 'X-CSRF-TOKEN': CSRF }, body: fd });
    const json = await res.json().catch(()=>({}));
    const t = (json.text || json.extracted || json.ocr || '').trim();
    if (t) titleEl.value = t.slice(0, 200);
    else alert('Failed to extract title text.');
  }catch(err){
    console.error(err); alert('Title OCR error.');
  }finally{
    overlay.classList.remove('show');
    e.target.value = '';
  }
});

/* =========================
   DOCX Export (include inline diff & revision suggestions)
========================= */
btnExportDocx.addEventListener('click', async (ev)=>{
  ev.preventDefault();
  const old = btnExportDocx.textContent;
  btnExportDocx.disabled = true; btnExportDocx.textContent = 'Exporting…';

  try{
    const extracted = (lastOCRText || essayText.value || '');
    const corrected = (essayText.value || '').trim();
    if (!corrected) { alert('Nothing to export.'); return; }

    const criterion_explanations = Array.from(document.querySelectorAll('#rationaleList li')).map(li => li.textContent).filter(Boolean);
    const revision_suggestions = Array.from(document.querySelectorAll('#suggestions li')).map(li => li.textContent).filter(Boolean);

    const inline_diff_html = (diffHtmlEl && diffHtmlEl.innerHTML) ? diffHtmlEl.innerHTML : '';

    const original_text = (origTextEl && origTextEl.textContent) ? origTextEl.textContent : (lastOCRText || '');
    const corrected_text = corrected;

    const last = window.__lastGrade || {};
    const scores = last.scores || last.score_map || last.score || { content: null, communicative: null, organisation: null, language: null, total: null };

    const payload = {
      title: (titleEl.value || 'Essay Report').slice(0, 200),
      rubric_code: rubricEl.value || '',
      rubric_text: rubricRef.value || '',
      extracted,
      corrected: corrected_text,
      original_text,
      scores,
      criterion_explanations,
      revision_suggestions,
      inline_diff_html,
      raw_grade_payload: last
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
   Local History (same as before)
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
      btnAnalyze.disabled = !(essayText.value || '').trim();
      btnSuggest.disabled = !(essayText.value || '').trim();
      updateActionButtons();
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
function compressImage(dataURL, maxWidth=1200, quality=0.9){
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
