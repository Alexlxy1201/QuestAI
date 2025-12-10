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
        {{-- Question row --}}
        <label class="block text-sm font-medium text-gray-700 mb-1">
          Essay Question (prompt)
        </label>
        <div class="flex flex-col md:flex-row md:items-start gap-2">
          <textarea
            id="title"
            rows="3"
            placeholder="e.g., Write a story about a time you helped someone in need."
            class="w-full md:flex-1 rounded-xl border-gray-200 px-3 py-2 text-sm md:text-base min-h-[80px] focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
          ></textarea>
          <div class="flex flex-row md:flex-col gap-2">
            <button id="cameraTitleButton" class="px-3 py-2 rounded-lg bg-purple-600 text-white hover:bg-purple-700 text-sm whitespace-nowrap">
              📷 Take Photo
            </button>
            <button id="uploadTitleButton" class="px-3 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700 text-sm whitespace-nowrap">
              📁 Upload from device
            </button>
          </div>
          <input type="file" id="cameraTitleInput" accept="image/*" capture="environment" class="hidden">
          <input type="file" id="uploadTitleInput" accept="image/*" class="hidden">
        </div>
        <p class="mt-1 text-xs text-gray-500">
          Take a photo or upload the essay question. OCR will try to capture the full question text here.
        </p>

        {{-- Rubric selector (just for template picking) --}}
        <div class="mt-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Rubric template
          </label>
          <select
            id="rubric"
            class="w-full rounded-xl border-gray-200 px-3 py-2 text-sm md:text-base focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
          >
            <option value="">Select exam &amp; part</option>
            <optgroup label="SPM">
              <option value="SPM — Part 1" selected>SPM — Part 1</option>
              <option value="SPM — Part 2">SPM — Part 2</option>
              <option value="SPM — Part 3">SPM — Part 3</option>
            </optgroup>
            <optgroup label="UASA">
              <option value="UASA — Part 1">UASA — Part 1</option>
              <option value="UASA — Part 2">UASA — Part 2</option>
            </optgroup>
          </select>
          <p class="text-xs text-gray-400 mt-1">
            This dropdown only picks a template. The actual scoring will always use the text in “Rubric Reference” below (even if you edit it).
          </p>
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
            <canvas id="pdfCanvas" class="hidden max-h-56 rounded-2xl shadow border border-gray-100 mx-auto"></canvas>
            <div id="previewMeta" class="text-xs text-gray-500 mt-1"></div>
            <div id="thumbGrid" class="mt-2 grid grid-cols-6 gap-2"></div>
          </div>

          {{-- Step 1 Actions --}}
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

        {{-- Step 3 Actions --}}
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

    {{-- Rubric reference (this is what AI uses) --}}
    <div class="mt-6">
      <label class="block text-sm font-medium text-gray-700 mb-1">Rubric Reference (editable)</label>
      <textarea id="rubricRef" rows="8" class="w-full rounded-xl border-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"></textarea>
      <p class="text-xs text-gray-400 mt-1">
        This text is the actual marking rubric used by AI. You can edit it freely; changing the dropdown above will just replace it with another template.
      </p>
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

    {{-- Annotated Corrections --}}
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
const titleEl   = $('title');
const rubricEl  = $('rubric');
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
let selectedFiles = [];
let lastOCRText = '';
let history = [];
try { history = JSON.parse(localStorage.getItem('essayProHistory') || '[]'); } catch (_) { history = []; }

/* =========================
   Rubric templates per part (REPLACED with user-provided strict rubrics)
   (same as earlier — omitted here for brevity in this listing; keep as before)
========================= */
const RUBRIC_TEMPLATES = {
  "SPM — Part 1": `SPM Writing — Part 1 — Assessment scale (5/3/1/0):

5: Content is fully relevant; readers are well informed; answer all the questions asked; conveys simple ideas using an appropriate text type and tone smoothly; uses simple linkers and at least one cohesive device; punctuations are used correctly and ideas are well-structured; basic vocabulary are used appropriately and uses simple grammatical forms with good control; errors are noticeable but meaning can still be determined.
4: Performances shared features of Score 3 and 5
3: Slight irrelevance/omission; misinterpreted one or two questions but readers are generally informed; simple ideas expressed simply; relies on common linkers and no cohesive device is used; use basic vocabulary and simple grammar with some degree of control; errors are sometimes inaccurate and may affect understanding.
2: Performances shared features of Score 1 and 3
1: Task may be misunderstood; readers are minimally informed; mostly short, disconnected sentences; ideas are simple but not always communicated successfully; weak cohesion; incorrect use of punctuation; vocabulary mainly isolated words/phrases; limited control of simple grammar.
0: Completely irrelevant.`,
  // ... other templates unchanged ...
  "SPM — Part 2": `SPM Writing — Part 2 — Assessment scale:

5: Content fully relevant; reader well informed, answer all the questions appropriately; conveys straightforward ideas using an appropriate text type and tone smoothly; coherent organization with a variety of cohesive devices; fairly wide everyday vocabulary with occasional misuse of less common words; good control of simple and some complex grammar; errors do not hinder communication.
4: Performances shared features of Score 3 and 5
3: Slight irrelevance/omission; misinterpreted one or two questions but reader generally informed; conveys simple ideas using an appropriate text type and tone smoothly; use simple sentence connectors and some cohesive devices appropriately; use basic vocabulary and simple grammar with good control; errors are noticeable but meaning can still be determined.
2: Performances shared features of Score 1 and 3
1: Task may be misunderstood; readers are minimally informed; simple ideas expressed simply; relies on common linkers and no cohesive device is used; incorrect use of punctuation; use basic vocabulary and simple grammar with some degree of control; errors are sometimes inaccurate and may affect understanding.
0: Content is totally irrelevant and any performance is below score 1.`,

  "SPM — Part 3": `SPM Writing — Part 3 — Assessment scale:

5: Content fully relevant and answered all the questions; communicative purpose achieved; complex ideas are delivered smoothly; well organized with a variety of cohesive devices that are used effectively; use wide vocabulary including some less common vocabulary correctly; flexible use of simple + complex grammar with good control; only occasional slips.
4: Performances shared features of Score 3 and 5
3: Slight irrelevance/omission; misinterpreted one or two questions but reader generally informed and engaged; conveys straightforward ideas using an appropriate text type and tone smoothly; coherent organization with a variety of cohesive devices; fairly wide everyday vocabulary with occasional misuse of less common words; good control of simple and some complex grammar; errors do not hinder communication.
2: Performances shared features of Score 1 and 3
1: Only manage to answer one sub-question; misinterpreted one or two questions but reader generally informed; conveys simple ideas using an appropriate text type and tone smoothly; use simple sentence connectors and some cohesive devices appropriately; use basic vocabulary and simple grammar with good control; errors are noticeable but meaning can still be determined.
0: Completely irrelevant.`,

  "UASA — Part 1": `UASA / Form 3 Writing — Part 1:

5: Content is fully relevant; readers are well informed; answer all the questions asked; conveys simple ideas using an appropriate text type and tone smoothly; uses simple linkers and at least one cohesive device; punctuations are used correctly and ideas are well-structured; basic vocabulary are used appropriately and uses simple grammatical forms with good control; errors are noticeable but meaning can still be determined.
4: Performances shared features of Score 3 and 5
3: Slight irrelevance/omission; misinterpreted one or two questions but readers are generally informed; simple ideas expressed simply; relies on common linkers and no cohesive device is used; use basic vocabulary and simple grammar with some degree of control; errors are sometimes inaccurate and may affect understanding.
2: Performances shared features of Score 1 and 3
1: Task may be misunderstood; readers are minimally informed; mostly short, disconnected sentences; ideas are simple but not always communicated successfully; weak cohesion; incorrect use of punctuation; vocabulary mainly isolated words/phrases; limited control of simple grammar.
0: Completely irrelevant.`,

  "UASA — Part 2": `UASA / Form 3 Writing — Part 2:

5: Content fully relevant; reader well informed, answer all the questions appropriately; conveys straightforward ideas using an appropriate text type and tone smoothly; coherent organization with a variety of cohesive devices; fairly wide everyday vocabulary with occasional misuse of less common words; good control of simple and some complex grammar; errors do not hinder communication.
4: Performances shared features of Score 3 and 5
3: Slight irrelevance/omission; misinterpreted one or two questions but reader generally informed; conveys simple ideas using an appropriate text type and tone smoothly; use simple sentence connectors and some cohesive devices appropriately; use basic vocabulary and simple grammar with good control; errors are noticeable but meaning can still be determined.
2: Performances shared features of Score 1 and 3
1: Task may be misunderstood; readers are minimally informed; simple ideas expressed simply; relies on common linkers and no cohesive device is used; incorrect use of punctuation; use basic vocabulary and simple grammar with some degree of control; errors are sometimes inaccurate and may affect understanding.
0: Content is totally irrelevant and any performance is below score 1.`
};

function applyRubricTemplateFromSelect() {
  const key = rubricEl.value;
  rubricRef.value = RUBRIC_TEMPLATES[key] || '';
}
applyRubricTemplateFromSelect();
rubricEl.addEventListener('change', applyRubricTemplateFromSelect);

/* =========================
   File Selection
========================= */
renderHistory();

chooseButton.addEventListener('click', ()=>fileInput.click());
cameraButton.addEventListener('click', ()=>cameraInput.click());
fileInput.addEventListener('change', handleFiles);
cameraInput.addEventListener('change', handleFiles);

async function handleFiles(e){
  const files = Array.from(e.target.files || []);
  if(!files.length) return;

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

  btnExtract.disabled = false;
  // do not auto-enable analyze here — allow analyze when essayText not empty
  btnAnalyze.disabled = !(essayText.value || '').trim();
  btnSuggest.disabled = !(essayText.value || '').trim();
  analyzeStatus.textContent = '';
  extractStatus.textContent = '';

  if (pdfs.length === 1) {
    previewImg.classList.add('hidden');
    previewPdf.classList.remove('hidden');
    previewPdf.textContent = `PDF selected: ${pdfs[0].name}`;
    await renderPdfFirstPage(pdfs[0]).catch(()=>{});
    return;
  }

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
   Step 1: OCR
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
      text = await ocrSingle(pdfs[0]);
    } else if (imgs.length > 0) {
      if (stitchToggle.checked && imgs.length > 1) {
        const stitched = await stitchImages(imgs);
        showStitchedPreview(stitched);
        const stitchedFile = dataURLtoFile(stitched, `images_bundle_${Date.now()}.jpg`);
        text = await ocrSingle(stitchedFile);
      } else if (imgs.length === 1) {
        const normalized = await normalizeImageFile(imgs[0]);
        text = await ocrSingle(normalized);
      } else {
        const chunks = [];
        for (const f of imgs) {
          const normalized = await normalizeImageFile(f);
          const t = await ocrSingle(normalized);
          chunks.push(t);
        }
        text = chunks.join('\n\n');
      }
    }

    lastOCRText = (text || '');

    if (!lastOCRText) {
      extractStatus.textContent = '❌ OCR returned empty text.';
      btnExtract.disabled = false;
      return;
    }

    essayText.value = lastOCRText;

    // now enable analyze/suggest because essayText now has content
    btnAnalyze.disabled = !(essayText.value || '').trim();
    btnSuggest.disabled = !(essayText.value || '').trim();

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
  fd.append('mode', 'essay');
  const res = await fetch(ORIGIN + '/api/ocr', { method:'POST', headers: { 'X-CSRF-TOKEN': CSRF }, body: fd });
  const json = await res.json().catch(()=>({}));
  if (!res.ok) throw new Error('OCR error: ' + (json?.error || res.status));
  return json.text ?? json.extracted ?? json.ocr ?? '';
}

/* =========================
   Step 3: Analyze AFTER editing
   👉 Use rubricRef text as the true rubric
   Also allow direct input into essayText to enable analyze
========================= */
btnAnalyze.addEventListener('click', analyzeEdited);
btnSuggest.addEventListener('click', suggestCorrections);

// Enable analyze/suggest when essayText changes (user can paste/type directly)
essayText.addEventListener('input', ()=>{
  const ok = !!(essayText.value || '').trim();
  btnAnalyze.disabled = !ok;
  btnSuggest.disabled = !ok;
});

async function analyzeEdited(){
  const text = (essayText.value || '').trim();
  if (!text) return alert('Empty text. Please OCR first, then edit, then analyze (or paste/type the text directly).');

  overlay.classList.add('show');
  analyzeStatus.textContent = 'Analyzing…';
  btnAnalyze.disabled = true;

  try {
    const rubricText  = rubricRef.value || '';    // 真正用于评分的 rubric
    const rubricLabel = rubricEl.value || '';     // 仅作显示用（SPM — Part 1 等）

    const payload = {
      title: titleEl.value || '',
      rubric: rubricText,         // 确保后端如果看 rubric，就用这一段文字
      rubric_ref: rubricText,     // 兼容已有实现：rubric_ref 也给同样内容
      rubric_label: rubricLabel,  // 可选：后端如果想知道选的是哪一类（显示用）
      need_explanation: true,
      text
    };

    const res = await fetch(ORIGIN + '/api/grade', {
      method:'POST',
      headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN': CSRF },
      body: JSON.stringify(payload)
    });
    const json = await res.json().catch(()=>({}));
    if (!res.ok || !json.ok) throw new Error(json.error || 'Grade failed.');

    renderScore(json, rubricLabel);
    analyzeStatus.textContent = '✅ Done.';
    window.__lastGrade = json;

    pushHistory({
      time: new Date().toLocaleString(),
      title: titleEl.value || '',
      rubric: rubricLabel || '',
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
   Score / Annotated / Utils
========================= */
function renderScore(payload, rubricLabel){
  resultCard.classList.remove('hidden');
  badgeRubric.textContent = rubricLabel || '-';

  const s = payload.scores || {};
  scContent.textContent = num(s.content);
  scComm.textContent    = num(s.communicative ?? s.communicative_achievement);
  scOrg.textContent     = num(s.organisation);
  scLang.textContent    = num(s.language);
  scTotal.textContent   = num(s.total);

  let rationales = []
    .concat(payload.rationales || [])
    .concat(payload.explanations || [])
    .concat(payload.criteria_explanations || [])
    .concat(payload.rubric_breakdown || []);

  if (!rationales.length) {
    rationales = buildFallbackRationales(s, rubricLabel);
  }

  rationaleList.innerHTML = '';
  if (rationales.length) {
    for (const r of rationales) {
      const li = document.createElement('li');
      li.textContent = typeof r === 'string' ? r : JSON.stringify(r);
      rationaleList.appendChild(li);
    }
  } else {
    const li = document.createElement('li');
    li.textContent = 'No detailed explanations are available for this score.';
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
  // Join tokens with spaces to avoid Word placing each token on new line
  // Replace multiple whitespace tokens produced by tokenizer (if any)
  return html.split(/(\s+)/).filter(Boolean).join(' ');
}

function tokenize(s){ const re=/[A-Za-z0-9’'’-]+|\s+|[^\sA-Za-z0-9]/g; const out=[]; let m; while((m=re.exec(s))){ out.push(m[0]); } return out.length?out:[s]; }
function buildLCS(a,b){ const n=a.length,m=b.length,dp=Array.from({length:n+1},()=>Array(m+1).fill(0)); for(let i=n-1;i>=0;i--){ for(let j=m-1;j>=0;j--){ dp[i][j]=(a[i]===b[j])?dp[i+1][j+1]+1:Math.max(dp[i+1][j],dp[i][j+1]); } } const path=[]; let i=0,j=0; while(i<n&&j<m){ if(a[i]===b[j]){ path.push([i,j]); i++; j++; } else if(dp[i+1][j]>=dp[i][j+1]) i++; else j++; } return path; }
function escapeHTML(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;'); }
function num(x){ return (x ?? '-'); }
function humanSize(bytes){ const u=['B','KB','MB','GB']; let i=0,n=bytes||0; while(n>=1024&&i<u.length-1){n/=1024;i++;} return `${n.toFixed(1)} ${u[i]}`; }

function buildFallbackRationales(scores, rubricLabel){
  const out = [];
  if (!scores) return out;

  const mapping = {
    Content: scores.content,
    Communicative: scores.communicative ?? scores.communicative_achievement,
    Organisation: scores.organisation,
    Language: scores.language
  };

  Object.entries(mapping).forEach(([label, val])=>{
    if (val === undefined || val === null || val === '-') return;
    const n = Number(val);
    if (Number.isNaN(n)) return;
    out.push(makeBandExplanation(label, n, rubricLabel));
  });

  if (scores.total !== undefined && scores.total !== null && scores.total !== '-') {
    const t = Number(scores.total);
    if (!Number.isNaN(t)) {
      out.push(`Overall total: ${t}/20 — this reflects the combined performance across all criteria in this rubric.`);
    }
  }

  return out;
}

function makeBandExplanation(label, score, rubricLabel){
  const name = rubricLabel || 'the selected rubric';
  const base = bandTextForScore(score);
  return `${label}: ${score}/5 — ${base} (according to ${name}).`;
}

function bandTextForScore(score){
  const s = Number(score);
  if (s >= 5) return 'Excellent performance; fully meets the top band descriptors for this criterion';
  if (s >= 4) return 'Good performance with only minor weaknesses; mostly matches the higher band descriptors';
  if (s >= 3) return 'Adequate but uneven; some key expectations from the rubric are met while others are only partially achieved';
  if (s >= 2) return 'Limited performance; several rubric expectations are weak or missing for this criterion';
  if (s >= 1) return 'Very limited performance; most rubric expectations are not met';
  return 'No credit for this criterion (0); the response does not reach the minimum rubric expectations';
}

/* =========================
   Image tools + PDF preview
   (unchanged)
========================= */
async function stitchImages(files){
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
   Title Snap OCR
========================= */
cameraTitleButton.addEventListener('click', ()=>cameraTitleInput.click());
uploadTitleButton.addEventListener('click', ()=>uploadTitleInput.click());
cameraTitleInput.addEventListener('change', handleTitleImage);
uploadTitleInput.addEventListener('change', handleTitleImage);

async function handleTitleImage(e){
  const f = e.target.files?.[0]; if (!f) return;
  overlay.classList.add('show');
  try{
    const srcFile = await normalizeImageFile(f, 1600, 0.95);
    const fd = new FormData();
    fd.append('file', srcFile, srcFile.name || 'title.jpg');
    fd.append('mode', 'title');
    const res = await fetch(ORIGIN + '/api/ocr', { method:'POST', headers: { 'X-CSRF-TOKEN': CSRF }, body: fd });
    const json = await res.json().catch(()=>({}));
    const raw = (json.text || json.extracted || json.ocr || '').trim();
    if (raw) {
      const normalised = raw.replace(/\s+/g, ' ').trim();
      titleEl.value = normalised;
    } else {
      alert('Failed to extract title text. Please try a clearer photo or type manually.');
    }
  }catch(err){
    console.error(err); alert('Title OCR error.');
  }finally{
    overlay.classList.remove('show');
    e.target.value = '';
  }
}

/* =========================
   DOCX Export (changed: include scores + diffHtml; no "Revision Suggestions" in DOCX)
   Also ensure ins/del inline styles are present before sending
========================= */

// ensure <ins>/<del> have inline styles so PHPWord/Word treats them as inline text
function ensureInlineDiffStyles(html) {
  if (!html) return html || '';
  html = html.replace(/<ins(?![^>]*style=)([^>]*)>/gi, '<ins$1 style="background:#DCFCE7;text-decoration:none;display:inline;white-space:normal;">');
  html = html.replace(/<del(?![^>]*style=)([^>]*)>/gi, '<del$1 style="background:#FEE2E2;text-decoration:line-through;display:inline;white-space:normal;">');
  if (!/^\s*<(p|div)/i.test(html)) {
    html = '<p style="margin:0;line-height:1.25;white-space:normal;">' + html + '</p>';
  }
  return html;
}

btnExportDocx.addEventListener('click', async (ev)=>{
  ev.preventDefault();
  const old = btnExportDocx.textContent;
  btnExportDocx.disabled = true; btnExportDocx.textContent = 'Exporting…';

  try{
    const extracted = (lastOCRText || '').trim();
    const corrected = (essayText.value || '').trim();
    if (!corrected) { alert('Nothing to export.'); btnExportDocx.disabled = false; btnExportDocx.textContent = old; return; }

    // collect explanations from DOM and last grade
    const fromDom = Array.from(document.querySelectorAll('#rationaleList li')).map(li => li.textContent);
    const last = window.__lastGrade || {};
    const explanations = [
      ...(last.rationales || []),
      ...(last.explanations || []),
      ...(last.criteria_explanations || []),
      ...(last.rubric_breakdown || []),
      ...fromDom
    ].filter(Boolean).slice(0, 80);

    // collect scores & suggestions
    const scores = last.scores || {
      content: scContent.textContent || null,
      communicative: scComm.textContent || null,
      organisation: scOrg.textContent || null,
      language: scLang.textContent || null,
      total: scTotal.textContent || null
    };

    const suggestionsArr = Array.from(document.querySelectorAll('#suggestions li')).map(li => li.textContent);

    // inline diff: prefer current diffHtml if present, otherwise generate from extracted/corrected client-side
    let diffHtml = diffHtmlEl.innerHTML && diffHtmlEl.innerHTML.trim() ? diffHtmlEl.innerHTML : '';
    if (!diffHtml) {
      diffHtml = makeAnnotatedDiff(extracted || '', corrected || '');
    }
    diffHtml = ensureInlineDiffStyles(diffHtml);

    const payload = {
      title: (titleEl.value || 'Essay Report').slice(0, 200),
      extracted,
      corrected,
      explanations,
      scores,
      suggestions: suggestionsArr,
      diffHtml
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
          btnExportDocx.disabled = false; btnExportDocx.textContent = old;
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
        <p><strong>Rubric label:</strong> ${escapeHTML(h.rubric||'-')}</p>
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
      // we keep current rubricRef; do not overwrite teacher's current rubric when loading history
      essayText.value = h.corrected || h.extracted || '';
      lastOCRText = h.extracted || '';
      window.scrollTo({ top: 0, behavior: 'smooth' });
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

/* ===== image helpers ===== */
async function normalizeImageFile(file, maxWidth=1600, quality=0.95){
  const dataURL = await readAsDataURL(file);
  const compressed = await compressImage(dataURL, maxWidth, quality).catch(()=>dataURL);
  const base = (file.name || 'image').replace(/\.[^.]+$/, '');
  return dataURLtoFile(compressed, base + '.jpg');
}

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
