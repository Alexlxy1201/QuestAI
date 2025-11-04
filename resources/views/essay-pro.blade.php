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

    {{-- Rubric reference（可编辑，仅本地） --}}
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

      {{-- 评分细则解释 --}}
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

    {{-- ✅ Annotated Changes --}}
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
          <div class="text-xs uppercase text-gray-500 mb-1">Corrected</div>
          <div id="corrText" class="text-sm whitespace-pre-wrap break-words"></div>
        </div>
      </div>
      <div class="mt-4">
        <h3 class="text-base font-semibold">Inline Diff</h3>
        <div id="diffHtml" class="prose prose-sm max-w-none leading-7 whitespace-pre-wrap break-words"></div>
      </div>
    </div>

    {{-- History (localStorage only) --}}
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

  </div>
</div>

{{-- ===== 简单样式：标注颜色（可按需微调） ===== --}}
<style>
  .annot-ins { background: #DCFCE7; border-radius: .25rem; text-decoration: none; }
  .annot-del { background: #FEE2E2; border-radius: .25rem; text-decoration: line-through; }
  #diffHtml ins { background: #DCFCE7; text-decoration: none; padding: 0 .15rem; border-radius: .2rem; }
  #diffHtml del { background: #FEE2E2; padding: 0 .15rem; border-radius: .2rem; }

  /* Loading overlay */
  #overlay {
    position: fixed; inset: 0; background: rgba(255,255,255,.6);
    display: none; align-items: center; justify-content: center; z-index: 60;
    backdrop-filter: blur(2px);
  }
  #overlay.show { display: flex; }
</style>

<div id="overlay">
  <div class="flex items-center gap-3 px-4 py-2 rounded-xl bg-white shadow">
    <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24" fill="none">
      <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" opacity=".2"/>
      <path d="M22 12a10 10 0 0 1-10 10" stroke="currentColor" stroke-width="4"/>
    </svg>
    <span class="text-sm text-gray-700">Processing with AI…</span>
  </div>
</div>

{{-- ===== pdf.js（必须在你的脚本前加载） ===== --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script>
  pdfjsLib.GlobalWorkerOptions.workerSrc = "https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js";
</script>

{{-- ===== Script ===== --}}
<script>
  // ===== Elements =====
  const $ = (id) => document.getElementById(id);
  const fileInput = $('fileInput'), cameraInput = $('cameraInput');
  const chooseButton = $('chooseButton'), cameraButton = $('cameraButton');
  const previewWrap = $('previewWrap'), previewImg = $('previewImg'), previewPdf = $('previewPdf'), previewMeta = $('previewMeta');
  const btnRun = $('btnRun'), runStatus = $('runStatus');
  const essayText = $('essayText'), titleEl = $('title'), rubricEl = $('rubric');
  const resultCard = $('resultCard'), scContent = $('scContent'), scComm = $('scComm'), scOrg = $('scOrg'), scLang = $('scLang'), scTotal = $('scTotal'), badgeRubric = $('badgeRubric');
  const suggestions = $('suggestions'), rationaleList = $('rationaleList'), btnExportDocx = $('btnExportDocx');
  const rubricRef = $('rubricRef');
  const btnSaveSnapshot = $('btnSaveSnapshot'), btnClearHistory = $('btnClearHistory'), historyList = $('historyList');
  const annotCard = $('annotCard'), origTextEl = $('origText'), corrTextEl = $('corrText'), diffHtmlEl = $('diffHtml');
  const overlay = $('overlay');

  // ===== State =====
  let selectedFile = null, isPdf = false, compressedDataURL = null;
  let history = [];

  // ===== Init rubric reference（可编辑模板）=====
  rubricRef.value = `
SPM Writing

Part 1 — Assessment scale（5/3/1/0）：
5 分：内容完全相关、读者充分获知；能用任务体裁传达直白想法；有简单连接词/少量衔接手段；基础词汇与简单语法控制良好，虽有错但不影响理解。
3 分：轻微跑题/遗漏；整体能被告知；用简单方式表达简单想法；主要靠高频连接词；基础词汇与简单语法有时出错并影响理解。
1 分：可能误解任务；读者仅被最低限度告知；多为短小片段，衔接弱；词汇以孤立词/短语为主；少量简单语法且控制有限。0 分：内容完全不相关。

Part 2 — Assessment scale：
5 分：内容完全相关、读者充分获知；体裁得当且能抓住读者；组织连贯、衔接多样；日常词汇较广（偶有少见词不当）；简单+部分复杂语法控制良好，错误不阻碍交流。
3 分：轻微跑题/遗漏；总体被告知；体裁使用基本得当；简单连接词/有限衔接；基础词汇与简单语法控制较好，虽有错但可理解。0–1 分：同 Part 1。

Part 3 — Assessment scale：
5 分：内容完全相关、目的达成；组织良好、衔接多样；词汇范围广含较少见词；简单与复杂语法兼具控制与灵活度，仅偶发疏漏。
3 分：轻微跑题/遗漏；总体被告知；能保持读者注意；组织较好且衔接多样；词汇范围较广（偶有较少见词用不当）；简单与部分复杂语法控制良好。0–1 分：同 Part 1。

UASA / Form 3 Writing

Part 1：
5 分：内容全相关、读者充分获知；能用体裁较好地传达直白想法；简单连接词/少量衔接手段；基础词汇与简单语法控制良好（可见但不致命的错误）。
3 分：轻微跑题/遗漏；整体被告知；简单方式表达简单想法；以高频连接词为主；基础词汇/简单语法有时影响理解。1–0 分：同 SPM Part 1。

Part 2：
5 分：内容全相关、读者充分获知；体裁能抓住读者并传达直白想法；组织连贯、衔接多样；日常词汇较广；简单+部分复杂语法控制良好、错误不阻碍交流。
3 分：轻微跑题/遗漏；总体被告知；体裁使用“尚可”；以简单连接词/有限衔接为主；基础词汇与简单语法控制较好（可理解）。1–0 分：同上。
`.trim();

  // ===== History init =====
  try { history = JSON.parse(localStorage.getItem('essayProHistory') || '[]'); } catch (_) { history = []; }
  renderHistory();

  // ===== PDF -> Long Image =====
  async function pdfToLongImage(file, { maxPages = 3, scale = 1.6, quality = 0.9 } = {}) {
    const arrayBuf = await file.arrayBuffer();
    const pdf = await pdfjsLib.getDocument({ data: arrayBuf }).promise;

    const pageCanvases = [];
    const count = Math.min(pdf.numPages, maxPages);

    for (let i = 1; i <= count; i++) {
      const page = await pdf.getPage(i);
      const viewport = page.getViewport({ scale });
      const canvas = document.createElement("canvas");
      canvas.width = Math.floor(viewport.width);
      canvas.height = Math.floor(viewport.height);
      const ctx = canvas.getContext("2d");
      await page.render({ canvasContext: ctx, viewport }).promise;
      pageCanvases.push(canvas);
    }

    const totalHeight = pageCanvases.reduce((sum, c) => sum + c.height, 0);
    const maxWidth = Math.max(...pageCanvases.map(c => c.width));
    const out = document.createElement("canvas");
    out.width = maxWidth;
    out.height = totalHeight;
    const outCtx = out.getContext("2d");

    let y = 0;
    for (const c of pageCanvases) {
      outCtx.drawImage(c, 0, y);
      y += c.height;
    }
    return out.toDataURL("image/jpeg", quality);
  }

  // ===== File handlers =====
  chooseButton.addEventListener('click', () => fileInput.click());
  cameraButton.addEventListener('click', () => cameraInput.click());
  fileInput.addEventListener('change', handleFile);
  cameraInput.addEventListener('change', handleFile);

  function humanSize(bytes){
    const units=['B','KB','MB','GB']; let i=0, num=bytes||0;
    while(num>=1024 && i<units.length-1){ num/=1024; i++; }
    return `${num.toFixed(1)} ${units[i]}`;
  }

  async function handleFile(e){
    const file = e.target.files?.[0];
    if(!file) return;
    selectedFile = file;
    isPdf = file.type === 'application/pdf' || /\.pdf$/i.test(file.name);

    const limit = isPdf ? 20*1024*1024 : 10*1024*1024;
    if(file.size > limit){
      alert(`File exceeds ${limit/1024/1024} MB`);
      selectedFile = null; return;
    }

    $('previewWrap').classList.remove('hidden');
    $('previewMeta').textContent = `File: ${file.name} · Size: ${humanSize(file.size)}`;

    if(isPdf){
      previewPdf.classList.add('hidden');
      previewImg.classList.remove('hidden');

      try {
        const longImageDataURL = await pdfToLongImage(file, { maxPages: 3, scale: 1.6, quality: 0.9 });
        previewImg.src = longImageDataURL;

        compressedDataURL = longImageDataURL;
        isPdf = false;
        selectedFile = new File(
          [dataURLtoBlob(longImageDataURL)],
          (file.name.replace(/\.pdf$/i, '') || 'document') + '.jpg',
          { type: 'image/jpeg' }
        );

        $('previewMeta').textContent += ` · Rendered as long image (~${Math.round((compressedDataURL.length * 3 / 4)/1024)} KB)`;
      } catch (err) {
        console.error(err);
        previewImg.classList.add('hidden');
        previewPdf.classList.remove('hidden');
        previewPdf.textContent = 'Failed to render PDF in browser.';
        compressedDataURL = null;
      }
      return;
    }

    const reader = new FileReader();
    reader.onload = async (ev)=>{
      const dataURL = ev.target.result;
      previewPdf.classList.add('hidden');
      previewImg.classList.remove('hidden');
      previewImg.src = dataURL;
      compressedDataURL = await compressImage(dataURL, 1000, 0.9).catch(()=>dataURL);
    };
    reader.readAsDataURL(file);
  }

  function compressImage(dataURL, maxEdge=1000, quality=0.9){
    return new Promise(resolve=>{
      const img = new Image();
      img.onload = ()=>{
        const scale = Math.min(maxEdge/img.width, maxEdge/img.height, 1);
        const w = Math.round(img.width*scale), h = Math.round(img.height*scale);
        const c = document.createElement('canvas'); c.width=w; c.height=h;
        const ctx = c.getContext('2d'); ctx.drawImage(img,0,0,w,h);
        resolve(c.toDataURL('image/jpeg', quality));
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

  // ===== Single-step: Extract + Score =====
  btnRun.addEventListener('click', runExtractAndScore);

  async function runExtractAndScore(){
    runStatus.textContent = '';
    const old = btnRun.textContent;
    btnRun.disabled = true; btnRun.textContent = 'Processing…';
    overlay.classList.add('show');

    try{
      // 1) Extract & Correct
      const { original, corrected, dc_explanations } = await doDirectCorrect();

      // 更新编辑器、标注
      essayText.value = corrected || original || '';
      renderAnnotations(original || '', corrected || original || '');

      // 2) Grade
      const gradePayload = await doGrade(essayText.value.trim(), rubricEl.value, titleEl.value || '');
      renderScore(gradePayload, rubricEl.value);

      // 3) Save history
      pushHistory({
        time: new Date().toLocaleString(),
        title: titleEl.value || '',
        rubric: rubricEl.value || '',
        extracted: original || '',
        corrected: corrected || original || '',
        explanations: Array.isArray(dc_explanations) ? dc_explanations : []
      });

      runStatus.textContent = '✅ Done.';
    }catch(err){
      console.error(err);
      runStatus.textContent = '❌ Failed. Check API / network.';
      alert(err.message || 'AI processing failed.');
    }finally{
      btnRun.disabled = false; btnRun.textContent = old;
      overlay.classList.remove('show');
    }
  }

  async function doDirectCorrect(){
    const fd = new FormData();
    fd.append('title', titleEl.value || '');

    // 优先文件；否则用文本
    const rawText = (essayText.value || '').trim();
    if(selectedFile){
      if(isPdf){
        // 保险分支：正常不会走到这，PDF 已转 JPG
        fd.append('file', selectedFile, selectedFile.name);
      }else{
        if(!compressedDataURL) throw new Error('Image not ready yet.');
        const blob = dataURLtoBlob(compressedDataURL);
        fd.append('file', blob, (selectedFile.name||'image')+'.jpg');
      }
    }else if(rawText){
      fd.append('text', rawText);
    }else{
      throw new Error('Provide a file or text.');
    }

    const res = await fetch('/api/essay/direct-correct', { method:'POST', body:fd });
    const json = await res.json().catch(()=>({}));
    if(!res.ok || !json.ok){
      throw new Error(json.error || 'Extract/Correct failed.');
    }
    return {
      original: json.extracted || '',
      corrected: json.corrected || json.extracted || '',
      dc_explanations: json.explanations || []
    };
  }

  async function doGrade(text, rubric, title){
    if(!text) throw new Error('Empty text to grade.');
    const res = await fetch('/api/grade', {
      method:'POST',
      headers:{ 'Content-Type':'application/json' },
      body: JSON.stringify({ title: title || '', rubric, text })
    });
    const json = await res.json().catch(()=>({}));
    if(!res.ok || !json.ok){
      throw new Error(json.error || 'Grade failed.');
    }
    return json;
  }

  function renderScore(payload, rubricCode){
    resultCard.classList.remove('hidden');
    badgeRubric.textContent = rubricCode || '-';

    const s = payload.scores || {};
    scContent.textContent = valNum(s.content);
    scComm.textContent   = valNum(s.communicative ?? s.communicative_achievement);
    scOrg.textContent    = valNum(s.organisation);
    scLang.textContent   = valNum(s.language);
    scTotal.textContent  = valNum(s.total);

    // 评分细则解释（兼容多字段命名）
    const rationales = []
      .concat(payload.rationales || [])
      .concat(payload.explanations || [])
      .concat(payload.criteria_explanations || [])
      .concat(payload.rubric_breakdown || []);

    rationaleList.innerHTML = '';
    rationales.forEach(x=>{
      if(!x) return;
      const li = document.createElement('li'); li.textContent = typeof x === 'string' ? x : JSON.stringify(x);
      rationaleList.appendChild(li);
    });
    if(!rationales.length){
      const li = document.createElement('li'); li.textContent = 'No detailed explanations returned by the API.';
      rationaleList.appendChild(li);
    }

    suggestions.innerHTML = '';
    (payload.suggestions || []).forEach(x=>{
      const li = document.createElement('li'); li.textContent = x; suggestions.appendChild(li);
    });
  }

  function valNum(x){ return (x ?? '-') }

  // ===== 原文标注渲染 =====
  function renderAnnotations(original, corrected){
    origTextEl.textContent = original || '-';
    corrTextEl.textContent = corrected || '-';
    const diffHtml = makeAnnotatedDiff(original || '', corrected || '');
    diffHtmlEl.innerHTML = diffHtml;
    annotCard.classList.remove('hidden');
  }

  // 词级 LCS 比对：输出含 <ins>/<del> 的 HTML
  function makeAnnotatedDiff(a, b){
    const at = tokenize(a);
    const bt = tokenize(b);
    const lcs = buildLCS(at, bt);

    let i=0, j=0, html='';
    for (const [ti, tj] of lcs){
      while(i < ti){ html += `<del>${escapeHTML(at[i])}</del>`; i++; }
      while(j < tj){ html += `<ins>${escapeHTML(bt[j])}</ins>`; j++; }
      if (ti < at.length && tj < bt.length){
        html += escapeHTML(at[ti]);
        i = ti + 1; j = tj + 1;
      }
    }
    while(i < at.length){ html += `<del>${escapeHTML(at[i++])}</del>`; }
    while(j < bt.length){ html += `<ins>${escapeHTML(bt[j++])}</ins>`; }
    return html;
  }

  function tokenize(s){
    const re = /[A-Za-z0-9’'’-]+|\s+|[^\sA-Za-z0-9]/g;
    const out = []; let m;
    while((m = re.exec(s))){ out.push(m[0]); }
    return out.length ? out : [s];
  }

  function buildLCS(a, b){
    const n=a.length, m=b.length;
    const dp = Array.from({length:n+1},()=>Array(m+1).fill(0));
    for(let i=n-1;i>=0;i--){
      for(let j=m-1;j>=0;j--){
        dp[i][j] = (a[i]===b[j]) ? dp[i+1][j+1]+1 : Math.max(dp[i+1][j], dp[i][j+1]);
      }
    }
    const path=[];
    let i=0, j=0;
    while(i<n && j<m){
      if(a[i]===b[j]){ path.push([i,j]); i++; j++; }
      else if(dp[i+1][j] >= dp[i][j+1]) i++;
      else j++;
    }
    return path;
  }
  btnExportDocx.addEventListener('click', async ()=>{
  const corrected = (essayText.value || '').trim();
  if(!corrected){ alert('没有可导出的内容'); return; }

  const explanations = Array.from(document.querySelectorAll('#rationaleList li'))
                            .map(li => li.textContent).slice(0, 20);

  // 1) 正常 API 路径（绝对 URL）
  const tryUrls = [
    "{{ url('/api/essay/export-docx') }}",
    "{{ url('/index.php/api/essay/export-docx') }}" // 2) 兜底：显式走 index.php，绕过重写
  ];

  for (const u of tryUrls) {
    try {
      const res = await fetch(u, {
        method:'POST',
        headers:{ 'Content-Type': 'application/json' },
        body: JSON.stringify({
          title: document.getElementById('title').value || 'Essay Report',
          extracted: document.getElementById('origText')?.textContent || '',
          corrected: corrected,
          explanations: explanations
        })
      });

      const ct = (res.headers.get('content-type') || '').toLowerCase();
      if (res.ok && ct.includes('application/vnd.openxmlformats-officedocument.wordprocessingml.document')) {
        const blob = await res.blob();
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url; a.download = 'essay-report.docx';
        document.body.appendChild(a); a.click(); a.remove();
        URL.revokeObjectURL(url);
        return; // ✅ 成功后直接返回
      } else {
        // 打印前 300 字便于排错
        const text = await res.text();
        console.warn('Export fallback - not docx from', u, res.status, ct, text.slice(0,300));
      }
    } catch (e) {
      console.warn('Export request failed for', u, e);
    }
  }

  alert('❌ 导出失败：后端未返回 DOCX（路由未命中或被重定向到页面）。');
});


  // ===== Local history (domain + current browser only) =====
  function pushHistory(item){
    history.unshift(item);
    history = history.slice(0, 50);
    localStorage.setItem('essayProHistory', JSON.stringify(history));
    renderHistory();
  }

  function renderHistory(){
    historyList.innerHTML = history.map((h,idx)=>`
      <details class="bg-gray-50 rounded-lg p-3 border">
        <summary class="cursor-pointer font-semibold text-gray-800 truncate">
          ${escapeHTML(h.time)} — ${escapeHTML(h.title||'(No title)')}
        </summary>
        <div class="mt-2 text-sm text-gray-700 space-y-2">
          <p><strong>Rubric:</strong> ${escapeHTML(h.rubric||'-')}</p>
          ${h.extracted ? `<div><strong>Extracted:</strong><br>${escapeHTML(h.extracted)}</div>`:''}
          ${h.corrected ? `<div><strong>Corrected:</strong><br>${escapeHTML(h.corrected)}</div>`:''}
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
        const h = history[i];
        if(!h) return;
        titleEl.value = h.title || '';
        rubricEl.value = h.rubric || 'SPM_P1';
        essayText.value = h.corrected || h.extracted || '';
        renderAnnotations(h.extracted || '', h.corrected || '');
        window.scrollTo({ top: 0, behavior: 'smooth' });
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

  $('btnSaveSnapshot').addEventListener('click', ()=>{
    pushHistory({
      time: new Date().toLocaleString(),
      title: titleEl.value || '',
      rubric: rubricEl.value || '',
      extracted: origTextEl?.textContent || '',
      corrected: (essayText.value||'').trim(),
      explanations: Array.from(rationaleList.querySelectorAll('li')).map(li=>li.textContent).slice(0,10)
    });
  });

  $('btnClearHistory').addEventListener('click', ()=>{
    if(confirm('Clear all local history?')){
      history = [];
      localStorage.removeItem('essayProHistory');
      renderHistory();
    }
  });

  function escapeHTML(s){
    return String(s||'')
      .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
      .replace(/"/g,'&quot;').replace(/'/g,'&#39;');
  }
</script> 
@endsection
