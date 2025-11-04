@extends('layouts.app')

@section('title', '✍️ Essay Pro — AI Grader')

@section('content')
<div class="w-full max-w-5xl mx-auto">
  <!-- Header -->
  <div class="bg-white shadow-2xl rounded-2xl p-6 mb-6">
    <div class="flex items-center justify-between gap-4">
      <div>
        <h1 class="text-3xl font-extrabold bg-gradient-to-r from-indigo-600 to-blue-600 bg-clip-text text-transparent">
          ✍️ Essay Pro — AI Grader
        </h1>
        <p class="text-gray-500 mt-1">Upload → AI extract & correct → export Word → (optional) rubric scoring</p>
      </div>
      <div class="flex items-center gap-2">
        <button id="btnExportDocx" class="px-4 py-2 rounded-xl bg-emerald-600 text-white font-semibold hover:bg-emerald-700 transition">
          📄 Export Word (DOCX)
        </button>
        <a href="{{ route('home') ?? '#' }}" class="px-4 py-2 rounded-xl bg-gray-100 text-gray-700 hover:bg-gray-200 transition">Back</a>
      </div>
    </div>
  </div>

  <!-- Form Card -->
  <div class="bg-white shadow-2xl rounded-2xl p-6 mb-6">
    <div class="grid md:grid-cols-2 gap-6">
      <!-- Left: meta + upload -->
      <div>
        <!-- Title -->
        <label class="block text-sm font-medium text-gray-700 mb-1">Essay Title</label>
        <input id="title" type="text" placeholder="e.g., The Importance of Reading"
               class="w-full rounded-xl border-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">

        <!-- Rubric -->
        <div class="mt-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">Rubric (optional)</label>
          <select id="rubric" class="w-full rounded-xl border-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            <option value="">— None —</option>
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
          <p class="text-xs text-gray-400 mt-1">评分维度：Content · Communicative Achievement · Organisation · Language（0–5）。</p>

          <!-- Rubric Reference (editable) -->
          <details id="rubricRef" class="mt-2 bg-indigo-50/40 rounded-lg p-3">
            <summary class="cursor-pointer text-indigo-700 font-semibold">Rubric Reference (editable, auto-saved)</summary>
            <textarea id="rubricText" rows="10"
              class="w-full mt-2 rounded-lg border-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm"></textarea>
            <div class="text-xs text-gray-500 mt-1">内容将保存在本地浏览器（LocalStorage），评分时可作为参考。</div>
          </details>
        </div>

        <!-- Uploader (采用 Quiz Solver 方式) -->
        <div class="mt-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">Upload / Take Photo (Image/PDF)</label>

          <!-- hidden inputs -->
          <input type="file" id="fileInput" accept="image/*,application/pdf" class="hidden">
          <input type="file" id="cameraInput" accept="image/*" capture="environment" class="hidden">

          <!-- buttons -->
          <div class="flex gap-3">
            <button id="cameraButton" class="px-4 py-2 rounded-xl bg-green-600 text-white font-semibold hover:bg-green-700">
              📷 Take Photo
            </button>
            <button id="chooseButton" class="px-4 py-2 rounded-xl bg-blue-600 text-white font-semibold hover:bg-blue-700">
              📁 Choose File (Image/PDF)
            </button>
          </div>

          <!-- preview -->
          <div id="previewWrap" class="mt-3 hidden">
            <img id="previewImg" class="max-h-56 rounded-xl shadow border border-gray-100 mx-auto hidden" alt="preview">
            <div id="previewPdf" class="text-sm text-gray-600 mt-2 hidden"></div>
            <div id="previewMeta" class="text-xs text-gray-500 mt-1"></div>
          </div>

          <div class="mt-4 flex items-center gap-3">
            <button id="btnDirect" class="px-4 py-2 rounded-xl bg-indigo-600 text-white font-semibold hover:bg-indigo-700">
              🔎 Extract & Correct (AI)
            </button>
            <span id="ocrStatus" class="text-sm text-gray-500"></span>
          </div>
        </div>
      </div>

      <!-- Right: text + actions -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Essay Text</label>
        <textarea id="essayText" rows="14" placeholder="Result will appear here after AI extract & correct; you can also paste text directly."
                  class="w-full rounded-xl border-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"></textarea>

        <div class="mt-4 flex items-center gap-3">
          <button id="btnScore"
                  class="px-4 py-2 rounded-xl bg-blue-600 text-white font-semibold hover:bg-blue-700 transition">
            ⚡ Get AI score & suggestions
          </button>
          <span id="scoreStatus" class="text-sm text-gray-500"></span>
        </div>
      </div>
    </div>
  </div>

  <!-- Result Card -->
  <div class="bg-white shadow-2xl rounded-2xl p-6 mb-8" id="resultCard" style="display:none;">
    <h2 class="text-2xl font-bold text-gray-800">Result</h2>
    <div class="grid md:grid-cols-5 gap-4 mt-4">
      <div class="p-4 rounded-xl bg-indigo-50">
        <div class="text-xs uppercase text-gray-500">Content</div>
        <div id="scContent" class="text-3xl font-extrabold">-</div>
        <div class="text-xs text-gray-400">0–5</div>
      </div>
      <div class="p-4 rounded-xl bg-indigo-50">
        <div class="text-xs uppercase text-gray-500">Communicative</div>
        <div id="scComm" class="text-3xl font-extrabold">-</div>
        <div class="text-xs text-gray-400">0–5</div>
      </div>
      <div class="p-4 rounded-xl bg-indigo-50">
        <div class="text-xs uppercase text-gray-500">Organisation</div>
        <div id="scOrg" class="text-3xl font-extrabold">-</div>
        <div class="text-xs text-gray-400">0–5</div>
      </div>
      <div class="p-4 rounded-xl bg-indigo-50">
        <div class="text-xs uppercase text-gray-500">Language</div>
        <div id="scLang" class="text-3xl font-extrabold">-</div>
        <div class="text-xs text-gray-400">0–5</div>
      </div>
      <div class="p-4 rounded-xl bg-emerald-50">
        <div class="text-xs uppercase text-gray-500">Total</div>
        <div id="scTotal" class="text-3xl font-extrabold">-</div>
        <div class="text-xs text-gray-400">/20</div>
      </div>
    </div>

    <div class="mt-6">
      <h3 class="text-lg font-semibold text-gray-800">Revision suggestions</h3>
      <ul id="suggestions" class="list-disc pl-6 mt-2 space-y-1 text-gray-700"></ul>
    </div>
  </div>

  <div class="text-center text-xs text-gray-400 pb-10">
    <span>No data stored • Works with your existing APIs</span>
  </div>
</div>

{{-- ===== Scripts ===== --}}
<script>
  const $ = (id) => document.getElementById(id);
  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

  function setLoading(el, loading, textIdle = '', textLoading = 'Working…') {
    if (!el) return;
    if (loading) { el.disabled = true; el.dataset._old = el.textContent; el.textContent = textLoading; }
    else { el.disabled = false; el.textContent = el.dataset._old || textIdle; }
  }
  function escapeHTML(s) {
    return String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
      .replace(/"/g,'&quot;').replace(/'/g,'&#39;');
  }
  function humanSize(bytes) {
    if (bytes === 0 || bytes) {
      const units = ['B','KB','MB','GB']; let i=0, num=bytes;
      while (num >= 1024 && i < units.length-1) { num/=1024; i++; }
      return `${num.toFixed(1)} ${units[i]}`;
    }
    return '';
  }
  // 压图（最长边 1000px）
  function compressImage(dataURL, maxEdge = 1000, quality = 0.9) {
    return new Promise((resolve) => {
      const img = new Image();
      img.onload = () => {
        const scale = Math.min(maxEdge / img.width, maxEdge / img.height, 1);
        const w = Math.round(img.width * scale), h = Math.round(img.height * scale);
        const canvas = document.createElement('canvas'); canvas.width = w; canvas.height = h;
        const ctx = canvas.getContext('2d'); ctx.drawImage(img, 0, 0, w, h);
        resolve(canvas.toDataURL('image/jpeg', quality));
      };
      img.src = dataURL;
    });
  }
  function dataURLtoBlob(dataURL) {
    const arr = dataURL.split(','), mime = arr[0].match(/:(.*?);/)[1];
    const bstr = atob(arr[1]); let n = bstr.length; const u8 = new Uint8Array(n);
    while (n--) u8[n] = bstr.charCodeAt(n);
    return new Blob([u8], { type: mime });
  }

  // Rubric Reference（可编辑 + LocalStorage）
  const RUBRIC_DEFAULT = `SPM Writing

Part 1 — Assessment scale (5/3/1/0):
5: 内容完全相关、读者充分获知；能用任务体裁传达直白想法；有简单连接词/少量衔接手段；基础词汇与简单语法控制良好，虽有错但不影响理解。
3: 轻微跑题/遗漏；整体能被告知；用简单方式表达简单想法；主要靠高频连接词；基础词汇/简单语法有时出错并影响理解。
1: 可能误解任务；读者仅被最低限度告知；多为短小片段，衔接弱；词汇以孤立词/短语为主；少量简单语法且控制有限。
0: 内容完全不相关。

Part 2 — Assessment scale:
5: 内容完全相关、读者充分获知；能用体裁抓住读者并恰当表达直白想法；组织连贯，多样衔接；日常词汇范围较广（偶有较少见词用不当）；简单与部分复杂语法控制良好，错误不阻碍交流。
3: 轻微跑题/遗漏；总体被告知；体裁使用基本得当；以简单连接词/有限衔接手段为主；基础词汇与简单语法控制较好，虽有错仍可理解。
0–1: 同 Part 1 的定义。

Part 3 — Assessment scale:
5: 内容完全相关、读者充分获知；体裁运用有效、交流自如、目的达成；组织良好、衔接多样且效果佳；词汇范围广含较少见词；简单与复杂语法兼具控制与灵活度，仅偶发疏漏。
3: 轻微跑题/遗漏；总体被告知；能用体裁保持读者注意并表达直白想法；组织较好且有多样衔接；词汇范围较广（偶有较少见词用不当）；简单与部分复杂语法控制良好、错误不阻碍交流。
0–1: 同 Part 1 的定义。

UASA / Form 3 Writing

Part 1 — Assessment scale:
5: 内容全相关、读者充分获知；能用体裁较好地传达直白想法；简单连接词/少量衔接手段；基础词汇与简单语法控制良好（可见但不致命的错误）。
3: 轻微跑题/遗漏；整体被告知；简单方式表达简单想法；以高频连接词为主；基础词汇/简单语法有时影响理解。
1–0: 与 SPM Part 1 同类定义。

Part 2 — Assessment scale:
5: 内容全相关、读者充分获知；体裁能抓住读者并传达直白想法；组织连贯、衔接多样；日常词汇范围较广；简单+部分复杂语法控制良好、错误不阻碍交流。
3: 轻微跑题/遗漏；总体被告知；体裁使用“尚可”；以简单连接词/有限衔接手段为主；基础词汇与简单语法控制较好（可理解）。
1–0: 与上同。`;
  const rubricText = $('rubricText');
  rubricText.value = localStorage.getItem('essay_pro_rubric') || RUBRIC_DEFAULT;
  rubricText.addEventListener('input', () => {
    localStorage.setItem('essay_pro_rubric', rubricText.value);
  });

  // 上传交互（同 Quiz Solver）
  const chooseButton = $('chooseButton');
  const cameraButton = $('cameraButton');
  const fileInput    = $('fileInput');
  const cameraInput  = $('cameraInput');
  const previewWrap  = $('previewWrap');
  const previewImg   = $('previewImg');
  const previewPdf   = $('previewPdf');
  const previewMeta  = $('previewMeta');
  const btnDirect    = $('btnDirect');
  const ocrStatus    = $('ocrStatus');

  let selectedFile = null, isPdf = false, compressedDataURL = null;

  chooseButton.addEventListener('click', () => fileInput.click());
  cameraButton.addEventListener('click', () => cameraInput.click());
  fileInput.addEventListener('change', handleFile);
  cameraInput.addEventListener('change', handleFile);

  async function handleFile(e) {
    const file = e.target.files?.[0];
    if (!file) return;
    selectedFile = file;
    isPdf = file.type === 'application/pdf' || /\.pdf$/i.test(file.name);

    const limit = isPdf ? 20*1024*1024 : 10*1024*1024;
    if (file.size > limit) {
      alert(`File exceeds ${limit/1024/1024} MB, please select a smaller one.`);
      selectedFile = null; previewWrap.classList.add('hidden'); return;
    }

    previewWrap.classList.remove('hidden');
    previewMeta.textContent = `File: ${file.name} · Size: ${humanSize(file.size)}`;

    if (isPdf) {
      previewImg.classList.add('hidden');
      previewPdf.classList.remove('hidden');
      previewPdf.textContent = 'PDF selected.';
      compressedDataURL = null; return;
    }

    const reader = new FileReader();
    reader.onload = async (ev) => {
      const originalDataURL = ev.target.result;
      previewPdf.classList.add('hidden');
      previewImg.classList.remove('hidden');
      previewImg.src = originalDataURL;
      try { compressedDataURL = await compressImage(originalDataURL, 1000, 0.9); }
      catch { compressedDataURL = originalDataURL; }
    };
    reader.readAsDataURL(file);
  }

  // 直接“提取+润色”
  btnDirect.addEventListener('click', async () => {
    setLoading(btnDirect, true, '', 'Processing…'); ocrStatus.textContent = '';
    try {
      const fd = new FormData();
      fd.append('make_docx', '0');
      fd.append('title', $('title').value.trim());

      if (selectedFile) {
        if (isPdf) {
          fd.append('file', selectedFile, selectedFile.name);
        } else {
          if (!compressedDataURL) throw new Error('Image not ready yet.');
          const blob = dataURLtoBlob(compressedDataURL);
          fd.append('file', blob, selectedFile.name.replace(/\.\w+$/, '.jpg'));
        }
      } else {
        // 没文件时，允许从右侧文本直接纠
        const txt = $('essayText').value.trim();
        if (!txt) { ocrStatus.textContent = 'Please upload a file or paste text.'; setLoading(btnDirect, false); return; }
        fd.append('text', txt);
      }

      const res = await fetch('/api/essay/direct-correct', { method:'POST', body: fd });
      const data = await res.json().catch(()=>({}));
      if (!res.ok || !data.ok) {
        const msg = data?.error || `HTTP ${res.status}`;
        ocrStatus.textContent = `❌ Failed. ${msg}`;
        console.error('direct-correct error:', data?.details || data);
        return;
      }

      $('essayText').value = data.corrected || data.extracted || '';
      ocrStatus.textContent = '✅ Done. Text inserted (corrected).';
    } catch (e) {
      console.error(e);
      ocrStatus.textContent = `❌ Failed. ${e.message || e}`;
    } finally {
      setLoading(btnDirect, false);
    }
  });

  // 渲染评分
  function renderResult(result) {
    $('resultCard').style.display = 'block';
    $('scContent').textContent = result?.scores?.content ?? '-';
    $('scComm').textContent   = result?.scores?.communicative ?? result?.scores?.communicative_achievement ?? '-';
    $('scOrg').textContent    = result?.scores?.organisation ?? '-';
    $('scLang').textContent   = result?.scores?.language ?? '-';
    $('scTotal').textContent  = result?.scores?.total ?? '-';
    const sug = result?.suggestions ?? [];
    $('suggestions').innerHTML = sug.map(s => `<li>${escapeHTML(s)}</li>`).join('');
  }

  // AI 评分
  $('btnScore').addEventListener('click', async () => {
    const payload = {
      title:  $('title').value.trim(),
      rubric: $('rubric').value || 'SPM_P1', // 若未选则给默认
      text:   $('essayText').value.trim()
    };
    if (!payload.text) { $('scoreStatus').textContent = 'Please provide essay text.'; return; }
    setLoading($('btnScore'), true, '', 'Scoring…'); $('scoreStatus').textContent = '';

    try {
      const res = await fetch('/api/grade', {
        method: 'POST',
        headers: { 'Content-Type':'application/json', ...(csrf ? {'X-CSRF-TOKEN': csrf} : {}) },
        body: JSON.stringify(payload)
      });
      const data = await res.json().catch(()=>({}));
      if (!res.ok || data.ok === false) throw new Error(data?.error || `HTTP ${res.status}`);

      const normalized = data?.data ?? data;
      const result = normalized?.scores ? normalized : {
        scores: {
          content: normalized?.content ?? normalized?.scores?.content ?? null,
          communicative: normalized?.communicative ?? normalized?.scores?.communicative ?? normalized?.scores?.communicative_achievement ?? null,
          organisation: normalized?.organisation ?? normalized?.scores?.organisation ?? null,
          language: normalized?.language ?? normalized?.scores?.language ?? null,
          total: normalized?.total ?? normalized?.scores?.total ?? null,
        },
        suggestions: normalized?.suggestions ?? normalized?.explanations ?? []
      };
      renderResult(result);
      $('scoreStatus').textContent = '✅ Scored.';
    } catch (err) {
      console.error(err);
      $('scoreStatus').textContent = '❌ Score failed.';
    } finally {
      setLoading($('btnScore'), false);
    }
  });

  // 导出 Word（DOCX）
  $('btnExportDocx').addEventListener('click', async () => {
    const title = $('title').value.trim();
    const text  = $('essayText').value.trim();
    if (!text) { alert('No essay text to export.'); return; }

    // 简单把“提取”和“改写”都用当前文本；如果你保留 extracted，可自行存储后传入
    const payload = {
      title: title || 'Essay Report',
      extracted: text,
      corrected: text,
      explanations: Array.from(document.querySelectorAll('#suggestions li')).map(li => li.textContent)
    };

    try {
      const res = await fetch('/api/essay/export-docx', {
        method:'POST',
        headers:{ 'Content-Type':'application/json' },
        body: JSON.stringify(payload)
      });
      const data = await res.json();
      if (!res.ok || !data.ok) throw new Error(data?.error || `HTTP ${res.status}`);
      window.open(data.url, '_blank');
    } catch (e) {
      alert('Export failed: ' + (e.message || e));
    }
  });
</script>
@endsection
