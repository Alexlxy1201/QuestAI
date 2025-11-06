{{-- resources/views/essay-pro.blade.php --}}
@extends('layouts.app')

@section('title', '✍️ Essay Pro — 3-Button Flow (English UI)')

@section('content')
<div class="min-h-[70vh] flex flex-col items-center justify-center p-4">
  <div class="bg-white shadow-2xl rounded-2xl p-6 w-full max-w-5xl text-left transition-all duration-300 overflow-x-hidden">

    {{-- Header --}}
    <div class="flex items-center justify-between gap-4 mb-4">
      <h1 class="text-2xl md:text-3xl font-extrabold bg-gradient-to-r from-indigo-600 to-blue-600 bg-clip-text text-transparent">
        ✍️ Essay Pro — OCR → Edit → Grade
      </h1>
      <a href="{{ route('home') ?? url('/') }}" class="px-4 py-2 rounded-xl bg-gray-100 text-gray-700 hover:bg-gray-200 transition">Back</a>
    </div>

    {{-- Basics --}}
    <div class="grid md:grid-cols-2 gap-6">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Essay Title</label>
        <input id="title" type="text" placeholder="OCR or type a title"
               class="w-full rounded-xl border-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
      </div>
      <div>
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
      </div>
    </div>

    {{-- ONLY THREE MAIN BUTTONS --}}
    <div class="mt-6 grid sm:grid-cols-3 gap-3">
      <button id="btnTitleOCR" class="px-4 py-3 rounded-xl bg-purple-600 text-white font-semibold hover:bg-purple-700">
        1) Extract Title (camera/file/PDF)
      </button>
      <button id="btnAnswerOCR" class="px-4 py-3 rounded-xl bg-blue-600 text-white font-semibold hover:bg-blue-700">
        2) Extract Student Answer & Edit
      </button>
      <button id="btnGrade" class="px-4 py-3 rounded-xl bg-emerald-600 text-white font-semibold hover:bg-emerald-700" disabled>
        3) Grade (use edited answer)
      </button>
    </div>

    {{-- Hidden file inputs (not counted as buttons) --}}
    <input type="file" id="inpTitleFile"  accept="image/*,application/pdf" capture="environment" class="hidden">
    <input type="file" id="inpAnswerFiles" accept="image/*,application/pdf" multiple capture="environment" class="hidden">

    {{-- Final Answer (shows saved edited text; editing itself happens in modal) --}}
    <div class="mt-6">
      <label class="block text-sm font-medium text-gray-700 mb-1">Student Answer (saved result)</label>
      <textarea id="answerText" rows="8" placeholder="After you finish editing in the popup, the text will appear here."
                class="w-full rounded-xl border-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"></textarea>
    </div>

    {{-- Result --}}
    <div class="bg-white rounded-2xl border mt-6 p-4 hidden" id="resultCard">
      <div class="flex items-center justify-between gap-4">
        <h2 class="text-xl font-bold">AI Scoring Result</h2>
        <span id="badgeRubric" class="text-xs px-2 py-1 rounded-full bg-indigo-50 text-indigo-700">-</span>
      </div>
      <div class="grid md:grid-cols-5 gap-4 mt-3">
        <div class="p-3 rounded-xl bg-indigo-50 text-center">
          <div class="text-xs uppercase text-gray-500">Content</div>
          <div id="scContent" class="text-2xl font-extrabold">-</div>
          <div class="text-xs text-gray-400">0–5</div>
        </div>
        <div class="p-3 rounded-xl bg-indigo-50 text-center">
          <div class="text-xs uppercase text-gray-500">Communicative</div>
          <div id="scComm" class="text-2xl font-extrabold">-</div>
          <div class="text-xs text-gray-400">0–5</div>
        </div>
        <div class="p-3 rounded-xl bg-indigo-50 text-center">
          <div class="text-xs uppercase text-gray-500">Organisation</div>
          <div id="scOrg" class="text-2xl font-extrabold">-</div>
          <div class="text-xs text-gray-400">0–5</div>
        </div>
        <div class="p-3 rounded-xl bg-indigo-50 text-center">
          <div class="text-xs uppercase text-gray-500">Language</div>
          <div id="scLang" class="text-2xl font-extrabold">-</div>
          <div class="text-xs text-gray-400">0–5</div>
        </div>
        <div class="p-3 rounded-xl bg-emerald-50 text-center">
          <div class="text-xs uppercase text-gray-500">Total</div>
          <div id="scTotal" class="text-2xl font-extrabold">-</div>
          <div class="text-xs text-gray-400">/20</div>
        </div>
      </div>

      <div class="mt-4 grid md:grid-cols-2 gap-4" id="rationaleWrap">
        <div class="p-3 rounded-xl bg-gray-50">
          <div class="text-xs uppercase text-gray-500 mb-1">Rationales</div>
          <ul id="rationaleList" class="list-disc pl-6 space-y-1 text-gray-700"></ul>
        </div>
        <div class="p-3 rounded-xl bg-gray-50">
          <div class="text-xs uppercase text-gray-500 mb-1">Suggestions</div>
          <ul id="suggestions" class="list-disc pl-6 space-y-1 text-gray-700"></ul>
        </div>
      </div>
    </div>

  </div>
</div>

{{-- ===== Styles ===== --}}
<style>
  /* Keep layout stable when scrollbar appears (fixes left margin shift) */
  html { scrollbar-gutter: stable both-edges; }
  body { min-height: 100vh; overflow-y: scroll; }

  /* Global overlay spinner */
  #overlay { position: fixed; inset: 0; background: rgba(255,255,255,.6); display: none; align-items: center; justify-content: center; z-index: 60; backdrop-filter: blur(2px); }
  #overlay.show { display: flex; }

  /* Modal */
  #modalBackdrop { position: fixed; inset: 0; background: rgba(0,0,0,.35); display: none; align-items: center; justify-content: center; z-index: 70; }
  #modalBackdrop.show { display: flex; }
</style>

{{-- Global overlay (spinner) --}}
<div id="overlay">
  <div class="flex items-center gap-3 px-4 py-2 rounded-xl bg-white shadow">
    <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24" fill="none">
      <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" opacity=".2"/>
      <path d="M22 12a10 10 0 0 1-10 10" stroke="currentColor" stroke-width="4"/>
    </svg>
    <span class="text-sm text-gray-700">Working…</span>
  </div>
</div>

{{-- Answer Edit Modal --}}
<div id="modalBackdrop" aria-hidden="true">
  <div class="bg-white rounded-2xl shadow-2xl w-[90vw] max-w-3xl p-4 md:p-6">
    <div class="flex items-center justify-between mb-3">
      <h3 class="text-lg font-semibold">Edit Extracted Answer</h3>
      <button id="modalClose" class="px-3 py-1 rounded-lg bg-gray-100 hover:bg-gray-200">✕</button>
    </div>
    <p class="text-sm text-gray-500 mb-2">Please edit the OCR result below. Click <strong>Save</strong> to use this text for grading.</p>
    <textarea id="modalAnswerText" rows="12" class="w-full rounded-xl border-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="OCR result will appear here..."></textarea>
    <div class="mt-4 flex items-center justify-end gap-3">
      <button id="modalCancel" class="px-4 py-2 rounded-xl bg-gray-100 text-gray-700 hover:bg-gray-200">Cancel</button>
      <button id="modalSave" class="px-4 py-2 rounded-xl bg-emerald-600 text-white font-semibold hover:bg-emerald-700">Save</button>
    </div>
  </div>
</div>

<meta name="csrf-token" content="{{ csrf_token() }}">

<script>
/* ========= Boot & Refs ========= */
const APP_ABS = "{{ rtrim(config('app.url') ?? url('/'), '/') }}";
const ORIGIN  = (location && location.origin) ? location.origin : APP_ABS;
const CSRF    = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

const $ = (id)=>document.getElementById(id);

const titleEl   = $('title');
const rubricEl  = $('rubric');
const answerEl  = $('answerText');

const btnTitleOCR  = $('btnTitleOCR');
const btnAnswerOCR = $('btnAnswerOCR');
const btnGrade     = $('btnGrade');

const inpTitleFile   = $('inpTitleFile');
const inpAnswerFiles = $('inpAnswerFiles');

const resultCard     = $('resultCard');
const badgeRubric    = $('badgeRubric');
const scContent      = $('scContent');
const scComm         = $('scComm');
const scOrg          = $('scOrg');
const scLang         = $('scLang');
const scTotal        = $('scTotal');
const rationaleList  = $('rationaleList');
const suggestions    = $('suggestions');

const overlay        = $('overlay');

const modalBackdrop  = $('modalBackdrop');
const modalClose     = $('modalClose');
const modalCancel    = $('modalCancel');
const modalSave      = $('modalSave');
const modalAnswerTxt = $('modalAnswerText');

/* ========= State ========= */
let lastExtractedTitle  = '';
let lastExtractedAnswer = '';

/* ========= Helpers ========= */
function showOverlay(on=true){ overlay.classList.toggle('show', !!on); }
function openModal(){ modalBackdrop.classList.add('show'); }
function closeModal(){ modalBackdrop.classList.remove('show'); }

function pickBestText(obj){
  if(!obj || typeof obj !== 'object') return '';
  return (obj.text || obj.extracted || obj.ocr || '').toString();
}

function renderScore(payload, rubricCode){
  resultCard.classList.remove('hidden');
  badgeRubric.textContent = rubricCode || '-';

  const s = payload.scores || {};
  scContent.textContent = (s.content ?? '-');
  scComm.textContent    = (s.communicative ?? s.communicative_achievement ?? '-');
  scOrg.textContent     = (s.organisation ?? '-');
  scLang.textContent    = (s.language ?? '-');
  scTotal.textContent   = (s.total ?? '-');

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

/* ========= OCR Calls ========= */
async function ocrFile(file){
  const fd = new FormData();
  fd.append('file', file, file.name || 'upload.bin');
  const res = await fetch(ORIGIN + '/api/ocr', { method:'POST', headers:{ 'X-CSRF-TOKEN': CSRF }, body: fd });
  const json = await res.json().catch(()=>({}));
  if(!res.ok) throw new Error(json?.error || 'OCR failed.');
  return pickBestText(json);
}

async function ocrMany(files){
  // Rules: either 1 PDF OR one/more images
  const pdfs = files.filter(f => f.type === 'application/pdf' || /\.pdf$/i.test(f.name));
  const imgs = files.filter(f => f.type.startsWith('image/'));

  if (pdfs.length > 1 || (pdfs.length === 1 && imgs.length > 0)) {
    throw new Error('Please select either a single PDF or images (not both).');
  }

  if (pdfs.length === 1) {
    return await ocrFile(pdfs[0]);
  }

  // Images: OCR each and concatenate with separators
  const parts = [];
  let i = 0;
  for (const img of imgs) {
    i++;
    const t = await ocrFile(img);
    parts.push(`--- Image ${i} ---\n${t.trim()}`);
  }
  return parts.join('\n\n');
}

/* ========= Button 1: Extract Title ========= */
btnTitleOCR.addEventListener('click', () => inpTitleFile.click());
inpTitleFile.addEventListener('change', async (e)=>{
  const file = e.target.files?.[0];
  if(!file) return;
  try{
    showOverlay(true);
    const text = await ocrFile(file);
    lastExtractedTitle = (text || '').trim();
    if (lastExtractedTitle) titleEl.value = lastExtractedTitle.slice(0, 200);
    else alert('OCR returned empty text for title.');
  }catch(err){
    console.error(err);
    alert(err.message || 'Title OCR failed.');
  }finally{
    showOverlay(false);
    e.target.value = '';
  }
});

/* ========= Button 2: Extract Answer & Edit (in modal) ========= */
btnAnswerOCR.addEventListener('click', () => inpAnswerFiles.click());
inpAnswerFiles.addEventListener('change', async (e)=>{
  const files = Array.from(e.target.files || []);
  if(!files.length) return;

  // basic total size guard (25 MB)
  const total = files.reduce((s,f)=>s+f.size,0);
  if(total > 25*1024*1024){
    alert('Selected files exceed 25 MB in total.');
    e.target.value = '';
    return;
  }

  try{
    showOverlay(true);
    const text = await ocrMany(files);
    lastExtractedAnswer = (text || '').trim();
    if (!lastExtractedAnswer) {
      alert('OCR returned empty text for answer.');
      return;
    }
    // put text into modal editor and open
    modalAnswerTxt.value = lastExtractedAnswer;
    openModal();
  }catch(err){
    console.error(err);
    alert(err.message || 'Answer OCR failed.');
  }finally{
    showOverlay(false);
    e.target.value = '';
  }
});

/* ========= Modal controls ========= */
[modalClose, modalCancel].forEach(btn=>{
  btn.addEventListener('click', ()=> closeModal());
});

modalSave.addEventListener('click', ()=>{
  const edited = (modalAnswerTxt.value || '').trim();
  if(!edited){
    alert('Answer is empty. Please type something.');
    return;
  }
  answerEl.value = edited;     // save to final answer box
  btnGrade.disabled = false;   // enable grading
  closeModal();
});

/* ========= Button 3: Grade (use edited answer) ========= */
btnGrade.addEventListener('click', async ()=>{
  const text = (answerEl.value || '').trim();
  if(!text){ alert('Answer is empty.'); return; }

  try{
    showOverlay(true);
    const payload = { title: titleEl.value || '', rubric: rubricEl.value, text };
    const res = await fetch(ORIGIN + '/api/grade', {
      method:'POST',
      headers:{ 'Content-Type':'application/json', 'X-CSRF-TOKEN': CSRF },
      body: JSON.stringify(payload)
    });
    const json = await res.json().catch(()=>({}));
    if(!res.ok || !json.ok) throw new Error(json?.error || 'Grading failed.');

    renderScore(json, rubricEl.value);
  }catch(err){
    console.error(err);
    alert(err.message || 'Grading failed.');
  }finally{
    showOverlay(false);
  }
});
</script>
@endsection
