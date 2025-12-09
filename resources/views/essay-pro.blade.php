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
        <label class="block text-sm font-medium text-gray-700 mb-1">Essay Question (prompt)</label>
        <div class="flex flex-col md:flex-row md:items-start gap-2">
          <textarea id="title" rows="3" placeholder="e.g., Write a story about a time you helped someone in need."
            class="w-full md:flex-1 rounded-xl border-gray-200 px-3 py-2 text-sm md:text-base min-h-[80px] focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"></textarea>
          <div class="flex flex-row md:flex-col gap-2">
            <button id="cameraTitleButton" class="px-3 py-2 rounded-lg bg-purple-600 text-white hover:bg-purple-700 text-sm whitespace-nowrap">📷 Take Photo</button>
            <button id="uploadTitleButton" class="px-3 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700 text-sm whitespace-nowrap">📁 Upload from device</button>
          </div>
          <input type="file" id="cameraTitleInput" accept="image/*" capture="environment" class="hidden">
          <input type="file" id="uploadTitleInput" accept="image/*" class="hidden">
        </div>
        <p class="mt-1 text-xs text-gray-500">Take a photo or upload the essay question. OCR will try to capture the full question text here.</p>

        {{-- Rubric selector --}}
        <div class="mt-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">Rubric template</label>
          <select id="rubric" class="w-full rounded-xl border-gray-200 px-3 py-2 text-sm md:text-base focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
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
          <p class="text-xs text-gray-400 mt-1">This dropdown only picks a template. The actual scoring will always use the text in “Rubric Reference” below (even if you edit it).</p>
        </div>

        {{-- Files --}}
        <div class="mt-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">Upload / Take Photo (Image or single PDF)</label>
          <input type="file" id="fileInput" accept="image/*,application/pdf" multiple class="hidden">
          <input type="file" id="cameraInput" accept="image/*" capture="environment" multiple class="hidden">
          <div class="flex flex-wrap items-center gap-3">
            <button id="cameraButton" class="px-4 py-2 rounded-xl bg-green-600 text-white font-semibold hover:bg-green-700">📷 Take Photo</button>
            <button id="chooseButton" class="px-4 py-2 rounded-xl bg-blue-600 text-white font-semibold hover:bg-blue-700">📁 Upload from device</button>
            <label class="inline-flex items-center gap-2 text-sm text-gray-700">
              <input id="stitchToggle" type="checkbox" class="rounded" checked> Stitch images before OCR (recommended)
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
            <button id="btnExtract" class="px-4 py-2 rounded-xl bg-indigo-600 text-white font-semibold hover:bg-indigo-700">🧠 Extract Text (OCR)</button>
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
          <button id="btnAnalyze" class="px-4 py-2 rounded-xl bg-emerald-600 text-white font-semibold hover:bg-emerald-700" disabled>📊 Analyze & Grade (AI)</button>
          <button id="btnSuggest" class="px-4 py-2 rounded-xl bg-amber-500 text-white font-semibold hover:bg-amber-600" disabled>💡 Suggest Corrections (optional)</button>
          <span id="analyzeStatus" class="text-sm text-gray-500"></span>
        </div>
      </div>
    </div>

    {{-- Rubric reference --}}
    <div class="mt-6">
      <label class="block text-sm font-medium text-gray-700 mb-1">Rubric Reference (editable)</label>
      <textarea id="rubricRef" rows="8" class="w-full rounded-xl border-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"></textarea>
      <p class="text-xs text-gray-400 mt-1">This text is the actual marking rubric used by AI. You can edit it freely.</p>
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

<!-- docx browser UMD -->
<script src="https://unpkg.com/docx@7.6.1/build/index.umd.js"></script>

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
   Rubric templates per part
   (short templates; you can expand; user asked to keep SPM rubric strict in rubricRef)
========================= */
const RUBRIC_TEMPLATES = {
  "SPM — Part 1": `SPM Writing
Part 1 — Assessment scale (5/3/1/0):
5: Content is fully relevant; readers are well informed; answer all the questions asked; conveys simple ideas using an appropriate text type and tone smoothly; uses simple linkers and at least one cohesive device; punctuations are used correctly and ideas are well-structured; basic vocabulary are used appropriately and uses simple grammatical forms with good control; errors are noticeable but meaning can still be determined.
4: Performances shared features of Score 3 and 5
3: Slight irrelevance/omission; misinterpreted one or two questions but readers are generally informed; simple ideas expressed simply; relies on common linkers and no cohesive device is used; use basic vocabulary and simple grammar with some degree of control; errors are sometimes inaccurate and may affect understanding.
2: Performances shared features of Score 1 and 3
1: Task may be misunderstood; readers are minimally informed; mostly short, disconnected sentences; ideas are simple but not always communicated successfully; weak cohesion; incorrect use of punctuation; vocabulary mainly isolated words/phrases; limited control of simple grammar. 0: Completely irrelevant.`,

  "SPM — Part 2": `SPM Writing
Part 2 — Assessment scale:
5: Content fully relevant; reader well informed, answer all the questions appropriately; conveys straightforward ideas using an appropriate text type and tone smoothly; coherent organization with a variety of cohesive devices; fairly wide everyday vocabulary with occasional misuse of less common words; good control of simple and some complex grammar; errors do not hinder communication.
4: Performances shared features of Score 3 and 5
3: Slight irrelevance/omission; misinterpreted one or two questions but reader generally informed; conveys simple ideas using an appropriate text type and tone smoothly; use simple sentence connectors and some cohesive devices appropriately; use basic vocabulary and simple grammar with good control; errors are noticeable but meaning can still be determined.
2: Performances shared features of Score 1 and 3
1: Task may be misunderstood; readers are minimally informed; simple ideas expressed simply; relies on common linkers and no cohesive device is used; ; incorrect use of punctuation; use basic vocabulary and simple grammar with some degree of control; errors are sometimes inaccurate and may affect understanding.
0: Content is totally irrelevant and any performance is below score 1.`,

  "SPM — Part 3": `SPM Writing
Part 3 — Assessment scale:
5: Content fully relevant and answered all the questions; communicative purpose achieved; complex ideas are delivered smoothly; well organized with a variety of cohesive devices that are used effectively; use wide vocabulary including some less common vocabulary correctly; flexible use of simple + complex grammar with good control; only occasional slips.
4: Performances shared features of Score 3 and 5
3: Slight irrelevance/omission; misinterpreted one or two questions but reader generally informed and engaged; conveys straightforward ideas using an appropriate text type and tone smoothly; coherent organization with a variety of cohesive devices; fairly wide everyday vocabulary with occasional misuse of less common words; good control of simple and some complex grammar; errors do not hinder communication.`,

  "UASA — Part 1": `UASA / Form 3 Writing
Part 1:
5: Content is fully relevant; readers are well informed; answer all the questions asked; conveys simple ideas using an appropriate text type and tone smoothly; uses simple linkers and at least one cohesive device; punctuations are used correctly and ideas are well-structured; basic vocabulary are used appropriately and uses simple grammatical forms with good control; errors are noticeable but meaning can still be determined.
4: Performances shared features of Score 3 and 5
3: Slight irrelevance/omission; misinterpreted one or two questions but readers are generally informed; simple ideas expressed simply; relies on common linkers and no cohesive device is used; use basic vocabulary and simple grammar with some degree of control; errors are sometimes inaccurate and may affect understanding.`,

  "UASA — Part 2": `UASA / Form 3 Writing
Part 2:
5: Content fully relevant; reader well informed, answer all the questions appropriately; conveys straightforward ideas using an appropriate text type and tone smoothly; coherent organization with a variety of cohesive devices; fairly wide everyday vocabulary with occasional misuse of less common words; good control of simple and some complex grammar; errors do not hinder communication.
4: Performances shared features of Score 3 and 5
3: Slight irrelevance/omission; misinterpreted one or two questions but reader generally informed; conveys simple ideas using an appropriate text type and tone smoothly; use simple sentence connectors and some cohesive devices appropriately; use basic vocabulary and simple grammar with good control; errors are noticeable but meaning can still be determined.`
};

function applyRubricTemplateFromSelect() {
  const key = rubricEl.value;
  rubricRef.value = RUBRIC_TEMPLATES[key] || rubricRef.value;
}
applyRubricTemplateFromSelect();
rubricEl.addEventListener('change', applyRubricTemplateFromSelect);

/* =========================
   Utilities & simple UX wiring
========================= */
function updateActionButtons(){
  const hasText = (essayText.value || '').trim().length > 0;
  btnAnalyze.disabled = !hasText;
  btnSuggest.disabled = !hasText;
}
essayText.addEventListener('input', updateActionButtons);
document.addEventListener('DOMContentLoaded', updateActionButtons);

/* =========================
   File selection and OCR functions (same behavior as before)
   - handleFiles, doOCR, ocrSingle, etc.
   (omitted here for brevity since earlier you already had them; include yours)
========================= */

/* For brevity in this snippet I re-use the previous functions already in your page:
   - handleFiles
   - doOCR
   - ocrSingle
   - stitchImages / showStitchedPreview / normalizeImageFile / renderPdfFirstPage / readAsDataURL / etc.
   Ensure these functions are present (they were in the earlier full view you shared).
*/

/* =========================
   Analyze & Suggest (keep existing behaviour)
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
    const rubricText  = rubricRef.value || '';
    const rubricLabel = rubricEl.value || '';

    const payload = {
      title: titleEl.value || '',
      rubric: rubricText,
      rubric_ref: rubricText,
      rubric_label: rubricLabel,
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
   renderScore, renderAnnotations, diff utils (same as your existing ones)
   Keep implementations you already had (I will include the same functions below)
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

  // inline diff injection
  const inlineHtml = payload.inline_diff_html || payload.inline_diff || payload.diffHtml || '';
  if (inlineHtml && diffHtmlEl) {
    diffHtmlEl.innerHTML = inlineHtml;
    annotCard.classList.remove('hidden');
    if (payload.original_text) origTextEl.textContent = payload.original_text;
    if (payload.corrected) corrTextEl.textContent = payload.corrected;
  }
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

/* fallback rationale builders (same as earlier) */
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

/* =========================
   Client-side DOCX generation flow:
   1) POST /api/essay/export-docx -> returns JSON report
   2) Use docx lib to build and download .docx locally
   (Revision suggestions intentionally omitted in generated docx)
========================= */

async function buildAndDownloadDocxFromReport(report, filename = 'essay-report.docx') {
  const { Document, Packer, Paragraph, Table, TableRow, TableCell, TextRun, WidthType, HeadingLevel, BorderStyle } = window.docx;

  const doc = new Document({
    styles: {
      paragraphStyles: [
        { id: "Heading1", name: "Heading 1", basedOn: "Normal", next: "Normal", quickFormat: true, run: { size: 32, bold: true } }
      ]
    }
  });

  const children = [];

  children.push(new Paragraph({ text: "Essay Report", heading: HeadingLevel.HEADING_1 }));
  children.push(new Paragraph({ text: "" }));

  // Metadata
  children.push(new Paragraph({ children: [ new TextRun({ text: 'Title: ', bold: true }), new TextRun({ text: report.title || '-' }) ] }));
  if (report.rubric_text) {
    children.push(new Paragraph({ text: '' }));
    children.push(new Paragraph({ children: [ new TextRun({ text: 'Rubric (used):', bold: true }) ] }));
    const lines = (report.rubric_text || '').split(/\r?\n/).filter(Boolean).slice(0,200);
    lines.forEach(ln => children.push(new Paragraph({ children: [ new TextRun({ text: ln }) ] })));
    children.push(new Paragraph({ text: '' }));
  } else {
    children.push(new Paragraph({ text: '' }));
  }

  // Scores table
  const scores = report.scores || {};
  const tableRows = [];

  // Header
  tableRows.push(new TableRow({
    children: [
      new TableCell({ width: { size: 60, type: WidthType.PERCENTAGE }, children: [ new Paragraph({ children: [ new TextRun({ text: 'Criterion', bold: true }) ] }) ] }),
      new TableCell({ width: { size: 20, type: WidthType.PERCENTAGE }, children: [ new Paragraph({ children: [ new TextRun({ text: 'Score', bold: true }) ] }) ] }),
      new TableCell({ width: { size: 20, type: WidthType.PERCENTAGE }, children: [ new Paragraph({ children: [ new TextRun({ text: 'Range', bold: true }) ] }) ] }),
    ]
  }));

  const criteriaOrder = [
    ['Content', scores.content],
    ['Communicative', scores.communicative ?? scores.communicative_achievement ?? null],
    ['Organisation', scores.organisation],
    ['Language', scores.language],
    ['Total', scores.total]
  ];

  criteriaOrder.forEach(([label, val])=>{
    tableRows.push(new TableRow({
      children: [
        new TableCell({ children: [ new Paragraph(label) ] }),
        new TableCell({ children: [ new Paragraph(val !== null && typeof val !== 'undefined' && val !== '' ? String(val) : '-') ] }),
        new TableCell({ children: [ new Paragraph(label === 'Total' ? '/20' : '0–5') ] }),
      ]
    }));
  });

  const scoresTable = new window.docx.Table({
    rows: tableRows,
    width: { size: 100, type: WidthType.PERCENTAGE },
    borders: {
      top: { style: BorderStyle.SINGLE, size: 1, color: "999999" },
      bottom: { style: BorderStyle.SINGLE, size: 1, color: "999999" },
      left: { style: BorderStyle.SINGLE, size: 1, color: "999999" },
      right: { style: BorderStyle.SINGLE, size: 1, color: "999999" },
      insideHorizontal: { style: BorderStyle.SINGLE, size: 1, color: "999999" },
      insideVertical: { style: BorderStyle.SINGLE, size: 1, color: "999999" },
    }
  });

  children.push(new Paragraph({ text: '' }));
  children.push(new Paragraph({ children: [ new TextRun({ text: 'Scores', bold: true }) ] }));
  children.push(scoresTable);
  children.push(new Paragraph({ text: '' }));

  // Criterion explanations
  children.push(new Paragraph({ children: [ new TextRun({ text: 'Criterion Explanations', bold: true }) ] }));
  const rationales = report.rationales || report.explanations || report.criterion_explanations || [];
  if (!rationales.length) {
    children.push(new Paragraph('No detailed explanations returned by the API.'));
  } else {
    rationales.forEach(r => children.push(new Paragraph({ children: [ new TextRun({ text: '• ' }), new TextRun({ text: String(r) }) ] })));
  }
  children.push(new Paragraph({ text: '' }));

  // Inline diff
  children.push(new Paragraph({ children: [ new TextRun({ text: 'Inline Diff (corrections)', bold: true }) ] }));
  const inlineHtml = report.inline_diff_html || report.inline_diff || report.diffHtml || '';
  if (inlineHtml) {
    const runs = parseInlineDiffToRuns(inlineHtml);
    const MAX_RUNS = 300;
    for (let i=0; i<runs.length; i+=MAX_RUNS) {
      children.push(new Paragraph({ children: runs.slice(i, i+MAX_RUNS) }));
    }
  } else {
    children.push(new Paragraph('No inline diff data available.'));
  }
  children.push(new Paragraph({ text: '' }));

  // Original & Corrected
  children.push(new Paragraph({ children: [ new TextRun({ text: 'Original Essay', bold: true }) ] }));
  children.push(new Paragraph(String(report.original_text || report.extracted || '-')));
  children.push(new Paragraph({ text: '' }));
  children.push(new Paragraph({ children: [ new TextRun({ text: 'Corrected Essay', bold: true }) ] }));
  children.push(new Paragraph(String(report.corrected || '-')));
  children.push(new Paragraph({ text: '' }));

  doc.addSection({ children });

  const blob = await window.docx.Packer.toBlob(doc);
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = filename;
  document.body.appendChild(a);
  a.click();
  setTimeout(()=>{ a.remove(); URL.revokeObjectURL(url); }, 1500);
}

// parse inline diff HTML (with <ins> / <del>) to docx TextRun array
function parseInlineDiffToRuns(html) {
  const runs = [];
  try {
    const dp = new DOMParser();
    const doc = dp.parseFromString(html, 'text/html');

    function walk(node) {
      if (!node) return;
      if (node.nodeType === Node.TEXT_NODE) {
        const t = node.nodeValue || '';
        if (t) runs.push(new window.docx.TextRun({ text: t }));
      } else if (node.nodeType === Node.ELEMENT_NODE) {
        const tag = node.tagName.toLowerCase();
        if (tag === 'ins') {
          const text = node.textContent || '';
          runs.push(new window.docx.TextRun({ text, color: '1b8a19' }));
        } else if (tag === 'del') {
          const text = node.textContent || '';
          runs.push(new window.docx.TextRun({ text, color: 'b30000', strike: true }));
        } else if (tag === 'br' || tag === 'p' || tag === 'div') {
          Array.from(node.childNodes || []).forEach(child => walk(child));
          runs.push(new window.docx.TextRun({ text: '\n' }));
        } else {
          Array.from(node.childNodes || []).forEach(child => walk(child));
        }
      }
    }

    Array.from(doc.body.childNodes).forEach(n => walk(n));
  } catch (e) {
    runs.push(new window.docx.TextRun({ text: html.replace(/<\/?[^>]+>/g,'') }));
  }
  return runs;
}

/* =========================
   Replace btnExportDocx handler:
   1) POST /api/essay/export-docx -> expect JSON { ok:true, report: {...} }
   2) Build docx from report (client-side) and download
========================= */
btnExportDocx.addEventListener('click', async (ev) => {
  ev.preventDefault();
  const oldText = btnExportDocx.textContent;
  btnExportDocx.disabled = true;
  btnExportDocx.textContent = 'Preparing…';
  overlay.classList.add('show');

  try {
    const extracted = (lastOCRText || essayText.value || '');
    const corrected = (essayText.value || '').trim();
    if (!corrected) { alert('Nothing to export.'); return; }

    const payload = {
      title: (titleEl.value || 'Essay Report').slice(0, 200),
      rubric_text: rubricRef.value || '',
      rubric: rubricEl.value || '',
      extracted,
      corrected,
      // include any visible UI content that may be useful for server-side processing
      // server will return structured 'report' (JSON)
    };

    const res = await fetch(ORIGIN + '/api/essay/export-docx', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': CSRF,
        'Accept': 'application/json'
      },
      body: JSON.stringify(payload)
    });

    const txt = await res.text().catch(()=>null);
    let json = null;
    try { json = txt ? JSON.parse(txt) : null; } catch(e) { json = null; }

    if (!res.ok) {
      // fallback: if backend returned a docx binary (older behavior), download it
      const ct = res.headers.get('content-type') || '';
      if (ct.includes('application/vnd.openxmlformats-officedocument.wordprocessingml.document')) {
        const blob = await res.blob();
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url; a.download = 'essay-report.docx'; document.body.appendChild(a); a.click(); a.remove();
        URL.revokeObjectURL(url);
        return;
      }
      let errMsg = 'Export failed';
      if (json && json.error) errMsg += ': ' + json.error;
      else errMsg += ': ' + (txt ? txt.slice(0,400) : 'No response');
      alert(errMsg);
      return;
    }

    // Expect JSON with report
    let report = null;
    if (json && json.report) report = json.report;
    else if (json && (json.ok === true) && (json.report || json.scores)) {
      report = json.report || { scores: json.scores, explanations: json.explanations };
    } else if (json) {
      report = {
        title: payload.title,
        rubric_text: payload.rubric_text,
        scores: json.scores || {},
        rationales: json.rationales || json.explanations || [],
        suggestions: json.suggestions || [],
        inline_diff_html: json.inline_diff_html || json.inline_diff || ''
      };
      report.extracted = payload.extracted;
      report.corrected = payload.corrected;
    } else {
      // fallback: create minimal report from local data
      report = {
        title: payload.title,
        rubric_text: payload.rubric_text,
        scores: {},
        rationales: [ (txt || '').slice(0,2000) ],
        inline_diff_html: '',
        original_text: payload.extracted,
        corrected: payload.corrected
      };
    }

    // Ensure normalized keys
    const normalized = {
      title: report.title || payload.title,
      rubric_text: report.rubric_text || payload.rubric_text || payload.rubric,
      scores: report.scores || {},
      rationales: report.rationales || report.explanations || [],
      suggestions: report.suggestions || [],
      inline_diff_html: report.inline_diff_html || report.inline_diff || report.diffHtml || '',
      original_text: report.original_text || report.extracted || payload.extracted || '',
      corrected: report.corrected || payload.corrected || ''
    };

    // filename safe
    const safeTitle = (normalized.title || 'essay-report').replace(/[^\w\- ]+/g,'').slice(0,80).replace(/\s+/g,'-');
    const fname = `${safeTitle || 'essay-report'}-${(new Date()).toISOString().slice(0,19).replace(/[:T]/g,'-')}.docx`;

    await buildAndDownloadDocxFromReport(normalized, fname);

  } catch (err) {
    console.error(err);
    alert('Export failed: ' + (err.message || err));
  } finally {
    overlay.classList.remove('show');
    btnExportDocx.disabled = false;
    btnExportDocx.textContent = oldText;
  }
});
</script>
@endsection
