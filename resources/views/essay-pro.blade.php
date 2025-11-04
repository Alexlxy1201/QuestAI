@extends('layouts.app')

@section('title', '✍️ Essay Pro — AI Grader')

@section('content')
<div class="min-h-[70vh] flex flex-col items-center justify-center p-4">
  <div class="bg-white shadow-2xl rounded-2xl p-6 w-full max-w-5xl text-left transition-all duration-300">

    {{-- Header --}}
    <div class="flex items-center justify-between gap-4 mb-4">
      <h1 class="text-3xl font-extrabold bg-gradient-to-r from-indigo-600 to-blue-600 bg-clip-text text-transparent">
        ✍️ Essay Pro — AI Grader
      </h1>
      <div class="flex items-center gap-2">
        <button id="btnExportDocx" class="px-4 py-2 rounded-xl bg-emerald-600 text-white font-semibold hover:bg-emerald-700 transition">
          ⬇️ Export (.docx)
        </button>
        <a href="{{ route('home') ?? '#' }}" class="px-4 py-2 rounded-xl bg-gray-100 text-gray-700 hover:bg-gray-200 transition">Back</a>
      </div>
    </div>

    <p class="text-gray-600 mb-4">
      Upload image/PDF → AI extracts & grades in one go. <small>(No server storage)</small>
    </p>

    <div class="grid md:grid-cols-2 gap-6">
      {{-- Left --}}
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Essay Title</label>
        <input id="title" type="text" placeholder="e.g., The Importance of Reading"
               class="w-full rounded-xl border-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">

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
          <p class="text-xs text-gray-400 mt-1">评分维度：Content · Communicative Achievement · Organisation · Language（每项 0–5）。</p>
        </div>

        <div class="mt-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">Upload / Take Photo (Image/PDF)</label>
          <input type="file" id="fileInput" accept="image/*,application/pdf" class="hidden">
          <input type="file" id="cameraInput" accept="image/*" capture="environment" class="hidden">

          <div class="flex gap-3">
            <button id="cameraButton" class="px-4 py-2 rounded-xl bg-green-600 text-white font-semibold hover:bg-green-700">
              📷 Take Photo
            </button>
            <button id="chooseButton" class="px-4 py-2 rounded-xl bg-blue-600 text-white font-semibold hover:bg-blue-700">
              📁 Choose File (Image/PDF)
            </button>
          </div>

          <div id="previewWrap" class="mt-3 hidden">
            <img id="previewImg" class="max-h-56 rounded-xl shadow border border-gray-100 mx-auto hidden" alt="preview">
            <div id="previewPdf" class="text-sm text-gray-600 mt-2 hidden"></div>
            <div id="previewMeta" class="text-xs text-gray-500 mt-1"></div>
          </div>

          {{-- ✅ Single-step: Extract + Score --}}
          <div class="mt-4 flex items-center gap-3">
            <button id="btnRun" class="px-4 py-2 rounded-xl bg-indigo-600 text-white font-semibold hover:bg-indigo-700">
              🧠 Extract + Score (AI)
            </button>
            <span id="runStatus" class="text-sm text-gray-500"></span>
          </div>
        </div>
      </div>

      {{-- Right --}}
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Essay Text (editable)</label>
        <textarea id="essayText" rows="14" placeholder="You may also paste/edit text here…"
                  class="w-full rounded-xl border-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"></textarea>
      </div>
    </div>

    {{-- Rubric Reference --}}
    <div class="mt-6">
      <label class="block text-sm font-medium text-gray-700 mb-1">Rubric Reference (editable)</label>
      <textarea id="rubricRef" rows="8" class="w-full rounded-xl border-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"></textarea>
      <p class="text-xs text-gray-400 mt-1">你可修改此处文本；仅用于参考，不会发送给后台。</p>
    </div>

    {{-- Score Result --}}
    <div class="bg-white rounded-2xl border mt-6 p-4 hidden" id="resultCard">
      <div class="flex items-center justify-between gap-4">
        <h2 class="text-xl font-bold">AI Score</h2>
        <span id="badgeRubric" class="text-xs px-2 py-1 rounded-full bg-indigo-50 text-indigo-700">-</span>
      </div>
      <div class="grid md:grid-cols-5 gap-4 mt-3">
        <div class="p-3 rounded-xl bg-indigo-50"><div class="text-xs text-gray-500">Content</div><div id="scContent" class="text-2xl font-extrabold">-</div></div>
        <div class="p-3 rounded-xl bg-indigo-50"><div class="text-xs text-gray-500">Communicative</div><div id="scComm" class="text-2xl font-extrabold">-</div></div>
        <div class="p-3 rounded-xl bg-indigo-50"><div class="text-xs text-gray-500">Organisation</div><div id="scOrg" class="text-2xl font-extrabold">-</div></div>
        <div class="p-3 rounded-xl bg-indigo-50"><div class="text-xs text-gray-500">Language</div><div id="scLang" class="text-2xl font-extrabold">-</div></div>
        <div class="p-3 rounded-xl bg-emerald-50"><div class="text-xs text-gray-500">Total</div><div id="scTotal" class="text-2xl font-extrabold">-</div></div>
      </div>

      {{-- Explanations --}}
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

    {{-- Annotated --}}
    <div class="bg-white rounded-2xl border mt-6 p-4 hidden" id="annotCard">
      <div class="flex items-center justify-between">
        <h2 class="text-xl font-bold">Annotated Changes</h2>
        <div class="text-xs text-gray-400">Legend: <ins class="annot-ins px-1 mx-1">added</ins> <del class="annot-del px-1 mx-1">removed</del></div>
      </div>
      <div class="grid md:grid-cols-2 gap-4 mt-3">
        <div class="p-3 rounded-xl bg-gray-50"><div class="text-xs text-gray-500">Original</div><div id="origText" class="text-sm whitespace-pre-wrap break-words"></div></div>
        <div class="p-3 rounded-xl bg-gray-50"><div class="text-xs text-gray-500">Corrected</div><div id="corrText" class="text-sm whitespace-pre-wrap break-words"></div></div>
      </div>
    </div>

    {{-- History --}}
    <div class="mt-8">
      <div class="flex items-center justify-between mb-2">
        <h2 class="text-xl font-bold text-indigo-700">📜 History (local only)</h2>
        <div class="flex gap-3">
          <button id="btnSaveSnapshot" class="text-sm text-blue-600 underline">Save snapshot</button>
          <button id="btnClearHistory" class="text-sm text-red-600 underline">Clear</button>
        </div>
      </div>
      <div id="historyList" class="space-y-3"></div>
    </div>

    {{-- Hidden iframe+form for export --}}
    <iframe id="dlFrame" name="dlFrame" style="display:none;"></iframe>
    <form id="exportForm" method="POST" target="dlFrame" style="display:none;"></form>

  </div>
</div>

<style>
  .annot-ins { background:#DCFCE7; border-radius:.25rem; text-decoration:none; }
  .annot-del { background:#FEE2E2; border-radius:.25rem; text-decoration:line-through; }
</style>

<script>
document.addEventListener('DOMContentLoaded',()=>{
  const btn=document.getElementById('btnExportDocx');
  const form=document.getElementById('exportForm');
  const essay=document.getElementById('essayText');
  const EXPORT_URLS=['/index.php/api/essay/export-docx','/api/essay/export-docx'];

  function addField(name,value){
    const i=document.createElement('input');
    i.type='hidden'; i.name=name;
    i.value=typeof value==='string'?value:JSON.stringify(value);
    form.appendChild(i);
  }

  btn.addEventListener('click',e=>{
    e.preventDefault();
    const corrected=(essay.value||'').trim();
    const extracted=(document.getElementById('origText')?.textContent||'').trim();
    if(!corrected&&!extracted){alert('没有可导出的内容');return;}
    const explanations=Array.from(document.querySelectorAll('#rationaleList li')).map(li=>li.textContent).slice(0,50);

    const payload={
      title:document.getElementById('title').value||'Essay Report',
      extracted:extracted||corrected,
      corrected:corrected||extracted,
      explanations
    };

    for(const url of EXPORT_URLS){
      try{
        form.innerHTML='';
        form.action=url;
        addField('title',payload.title);
        addField('extracted',payload.extracted);
        addField('corrected',payload.corrected);
        addField('explanations',payload.explanations);
        form.submit(); 
        console.log('[Export] submitted to',url);
        return;
      }catch(err){console.warn('[Export] failed',url,err);}
    }
    alert('❌ 导出失败：请确认 Railway 将 /api/* 指向 PHP 服务。');
  });
});
</script>
@endsection
