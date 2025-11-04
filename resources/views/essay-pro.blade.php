@extends('layouts.app')

@section('title', '✍️ Essay Pro — AI Grader')

@section('content')
<div class="min-h-[70vh] flex flex-col items-center justify-center p-4">
  <div class="bg-white shadow-2xl rounded-2xl p-6 w-full max-w-5xl text-left transition-all duration-300">

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

    <p class="text-gray-600 mb-4">Upload image/PDF → AI extracts & corrects → local-only history. <small>(No server storage)</small></p>

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

          <div class="mt-4 flex items-center gap-3">
            <button id="btnDirect" class="px-4 py-2 rounded-xl bg-indigo-600 text-white font-semibold hover:bg-indigo-700">
              🔎 Extract & Correct (AI)
            </button>
            <span id="directStatus" class="text-sm text-gray-500"></span>
          </div>
        </div>
      </div>

      {{-- Right --}}
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Essay Text (editable)</label>
        <textarea id="essayText" rows="14" placeholder="After AI extraction/correction, you can edit here…"
                  class="w-full rounded-xl border-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"></textarea>

        <div class="mt-4 flex items-center gap-3">
          <button id="btnScore" class="px-4 py-2 rounded-xl bg-blue-600 text-white font-semibold hover:bg-blue-700">
            ⚡ Get AI score & suggestions
          </button>
          <span id="scoreStatus" class="text-sm text-gray-500"></span>
        </div>
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
      <h2 class="text-xl font-bold">Result</h2>
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
      <div class="mt-4">
        <h3 class="text-base font-semibold">Revision suggestions</h3>
        <ul id="suggestions" class="list-disc pl-6 mt-2 space-y-1 text-gray-700"></ul>
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
  const btnDirect = $('btnDirect'), directStatus = $('directStatus');
  const essayText = $('essayText'), titleEl = $('title'), rubricEl = $('rubric');
  const btnScore = $('btnScore'), scoreStatus = $('scoreStatus');
  const resultCard = $('resultCard'), scContent = $('scContent'), scComm = $('scComm'), scOrg = $('scOrg'), scLang = $('scLang'), scTotal = $('scTotal');
  const suggestions = $('suggestions'), btnExportDocx = $('btnExportDocx');
  const rubricRef = $('rubricRef');
  const btnSaveSnapshot = $('btnSaveSnapshot'), btnClearHistory = $('btnClearHistory'), historyList = $('historyList');

  // ===== State =====
  let selectedFile = null, isPdf = false, compressedDataURL = null;
  let history = [];

  // ===== Init rubric reference（可编辑模板）=====
  rubricRef.value = `
SPM Writing

Part 1 — Assessment scale（5/3/1/0）：
5 分：内容完全相关、读者充分获知；能用任务体裁传达直白想法；有简单连接词/少量衔接手段；基础词汇与简单语法控制良好，虽有错但不影响理解。
3 分：轻微跑题/遗漏；整体能被告知；用简单方式表达简单想法；主要靠高频连接词；基础词汇/简单语法有时出错并影响理解。
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
  try {
    history = JSON.parse(localStorage.getItem('essayProHistory') || '[]');
  } catch (_) { history = []; }
  renderHistory();

  // ===== PDF -> Long Image =====
  // 将 PDF 前 maxPages 页按 scale 渲成画布并纵向拼接，输出 dataURL（jpeg）
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

    // 纵向拼接
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
    while(num>=1024 && i<units.length-1){ num/=1024; i++;
    }
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
      // —— 前端把 PDF 渲成一张长图，后续按图片提交流程走 ——
      previewPdf.classList.add('hidden');
      previewImg.classList.remove('hidden');

      try {
        const longImageDataURL = await pdfToLongImage(file, { maxPages: 3, scale: 1.6, quality: 0.9 });
        previewImg.src = longImageDataURL;

        // 让后续提交流程按“图片上传”处理
        compressedDataURL = longImageDataURL;
        isPdf = false; // 重要：标记成非 PDF，从而走图片分支
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

    // 图片：正常压缩预览
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

  // ===== Direct Extract & Correct =====
  btnDirect.addEventListener('click', async ()=>{
    directStatus.textContent = '';
    btnDirect.disabled = true; const old = btnDirect.textContent; btnDirect.textContent = 'Working…';

    try{
      const fd = new FormData();
      fd.append('title', titleEl.value || '');

      if(selectedFile){
        if(isPdf){
          // 理论上不会走到这，因为 PDF 已经在前端转成了图片并把 isPdf=false
          // 加个兜底：直接上传原 PDF，也能被后端处理（若服务器装了 Imagick/PdfParser）
          fd.append('file', selectedFile, selectedFile.name);
          // fd.append('max_pages', '3');
        }else{
          if(!compressedDataURL) throw new Error('Image not ready yet.');
          const blob = dataURLtoBlob(compressedDataURL);
          fd.append('file', blob, (selectedFile.name||'image')+'.jpg');
        }
      }else if(essayText.value.trim()){
        fd.append('text', essayText.value.trim());
      }else{
        throw new Error('Provide a file or text.');
      }

      const res = await fetch('/api/essay/direct-correct', { method:'POST', body:fd });
      const json = await res.json();
      if(!res.ok || !json.ok) throw new Error(json.error || 'Failed');

      // 写入编辑器
      const corrected = json.corrected || json.extracted || '';
      essayText.value = corrected;

      // 保存到 localStorage
      pushHistory({
        time: new Date().toLocaleString(),
        title: titleEl.value || '',
        rubric: rubricEl.value || '',
        extracted: json.extracted || '',
        corrected: corrected,
        explanations: Array.isArray(json.explanations) ? json.explanations : []
      });

      directStatus.textContent = '✅ Done.';
    }catch(err){
      console.error(err);
      directStatus.textContent = '❌ Failed. Please check /api/essay/direct-correct.';
    }finally{
      btnDirect.disabled = false; btnDirect.textContent = old;
    }
  });

  // ===== Score =====
  btnScore.addEventListener('click', async ()=>{
    scoreStatus.textContent = '';
    const text = (essayText.value || '').trim();
    if(!text){ scoreStatus.textContent = 'Provide essay text first.'; return; }

    btnScore.disabled = true; const old = btnScore.textContent; btnScore.textContent = 'Scoring…';
    try{
      const res = await fetch('/api/grade', {
        method:'POST',
        headers:{ 'Content-Type':'application/json' },
        body: JSON.stringify({ title: titleEl.value || '', rubric: rubricEl.value, text })
      });
      const json = await res.json();
      if(!res.ok || !json.ok) throw new Error(json.error || 'Grade failed');
      renderScore(json);
      scoreStatus.textContent = '✅ Scored.';
    }catch(err){
      console.error(err);
      scoreStatus.textContent = '❌ Score failed.';
    }finally{
      btnScore.disabled = false; btnScore.textContent = old;
    }
  });

  function renderScore(payload){
    resultCard.classList.remove('hidden');
    const s = payload.scores || {};
    scContent.textContent = s.content ?? '-';
    scComm.textContent   = s.communicative ?? s.communicative_achievement ?? '-';
    scOrg.textContent    = s.organisation ?? '-';
    scLang.textContent   = s.language ?? '-';
    scTotal.textContent  = s.total ?? '-';

    suggestions.innerHTML = '';
    (payload.suggestions || []).forEach(x=>{
      const li = document.createElement('li'); li.textContent = x; suggestions.appendChild(li);
    });
  }

  // ===== Export DOCX =====
  btnExportDocx.addEventListener('click', async ()=>{
    const corrected = (essayText.value || '').trim();
    if(!corrected){ alert('Nothing to export.'); return; }
    try{
      const res = await fetch('/api/essay/export-docx', {
        method:'POST',
        headers:{ 'Content-Type': 'application/json' },
        body: JSON.stringify({
          title: $('title').value || 'Essay Report',
          extracted: '',          // 如需可写入最近 extracted
          corrected: corrected,
          explanations: []        // 如需可写入最近 explanations
        })
      });
      const json = await res.json();
      if(!res.ok || !json.ok) throw new Error(json.error || 'Export failed');

      const a = document.createElement('a');
      a.href = json.url; a.download = 'essay-report.docx';
      document.body.appendChild(a); a.click(); a.remove();
    }catch(err){
      alert('❌ Export failed.');
      console.error(err);
    }
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

  btnSaveSnapshot.addEventListener('click', ()=>{
    pushHistory({
      time: new Date().toLocaleString(),
      title: titleEl.value || '',
      rubric: rubricEl.value || '',
      extracted: '',
      corrected: (essayText.value||'').trim(),
      explanations: []
    });
  });

  btnClearHistory.addEventListener('click', ()=>{
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
