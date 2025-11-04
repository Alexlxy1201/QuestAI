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
        <p class="text-gray-500 mt-1">SPM/UASA rubric · OCR · AI scoring · downloadable report</p>
      </div>
      <div class="flex items-center gap-2">
        <button id="btnExport" class="px-4 py-2 rounded-xl bg-emerald-600 text-white font-semibold hover:bg-emerald-700 transition">
          ⬇️ Export Report (HTML)
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

        <!-- Uploader -->
        <div class="mt-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">Upload / Take Photo</label>

          <div id="dropzone"
               class="w-full rounded-2xl border-2 border-dashed border-gray-300 hover:border-indigo-400 p-6 text-center cursor-pointer">
            <input id="file" type="file" accept="image/*,.pdf" class="hidden">
            <p class="text-gray-600">Drag & drop file here, or <span class="text-indigo-600 underline">browse</span></p>
            <p class="text-xs text-gray-400 mt-1">支持：JPG/PNG/PDF（手机可拍照）</p>
          </div>

          <!-- Preview -->
          <div id="previewWrap" class="mt-3 hidden">
            <img id="preview" class="max-h-56 rounded-xl shadow border border-gray-100 mx-auto" alt="preview">
          </div>

          <div class="mt-4 flex items-center gap-3">
            <button id="btnOCR"
                    class="px-4 py-2 rounded-xl bg-indigo-600 text-white font-semibold hover:bg-indigo-700 transition">
              🔎 Extract text (OCR)
            </button>
            <span id="ocrStatus" class="text-sm text-gray-500"></span>
          </div>
        </div>
      </div>

      <!-- Right: text + actions -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Essay Text</label>
        <textarea id="essayText" rows="14" placeholder="Paste or type the essay here, or use OCR to auto-extract…"
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

  <!-- Footer -->
  <div class="text-center text-xs text-gray-400 pb-10">
    <span>No data stored • Works with your existing APIs</span>
  </div>
</div>

{{-- ===== Scripts ===== --}}
<script>
  // ---------- helpers ----------
  const $ = (id) => document.getElementById(id);
  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

  function setLoading(el, loading, textIdle = '', textLoading = 'Working…') {
    if (!el) return;
    if (loading) {
      el.disabled = true;
      el.dataset._old = el.textContent;
      el.textContent = textLoading;
    } else {
      el.disabled = false;
      el.textContent = el.dataset._old || textIdle;
    }
  }

  function showPreview(file) {
    if (!file || !file.type.startsWith('image/')) {
      $('previewWrap').classList.add('hidden');
      return;
    }
    const reader = new FileReader();
    reader.onload = (e) => {
      $('preview').src = e.target.result;
      $('previewWrap').classList.remove('hidden');
    };
    reader.readAsDataURL(file);
  }

  function renderResult(data) {
    $('resultCard').style.display = 'block';
    $('scContent').textContent = data?.scores?.content ?? '-';
    $('scComm').textContent   = data?.scores?.communicative ?? data?.scores?.communicative_achievement ?? '-';
    $('scOrg').textContent    = data?.scores?.organisation ?? '-';
    $('scLang').textContent   = data?.scores?.language ?? '-';
    $('scTotal').textContent  = data?.scores?.total ?? '-';

    const sug = data?.suggestions ?? [];
    $('suggestions').innerHTML = '';
    sug.forEach(s => {
      const li = document.createElement('li');
      li.textContent = s;
      $('suggestions').appendChild(li);
    });
  }

  // ---------- dropzone ----------
  const dz = $('dropzone');
  dz.addEventListener('click', () => $('file').click());
  dz.addEventListener('dragover', (e) => { e.preventDefault(); dz.classList.add('border-indigo-400'); });
  dz.addEventListener('dragleave', () => dz.classList.remove('border-indigo-400'));
  dz.addEventListener('drop', (e) => {
    e.preventDefault();
    dz.classList.remove('border-indigo-400');
    const file = e.dataTransfer.files?.[0];
    if (file) {
      $('file').files = e.dataTransfer.files;
      showPreview(file);
    }
  });
  $('file').addEventListener('change', (e) => showPreview(e.target.files?.[0]));

  // ---------- OCR ----------
  $('btnOCR').addEventListener('click', async () => {
    const file = $('file').files?.[0];
    if (!file) { $('ocrStatus').textContent = 'Please upload an image or PDF first.'; return; }
    $('ocrStatus').textContent = '';
    setLoading($('btnOCR'), true, '', 'Extracting…');

    try {
      const fd = new FormData();
      fd.append('file', file);
      const res = await fetch('/api/ocr', {
        method: 'POST',
        headers: csrf ? {'X-CSRF-TOKEN': csrf} : {},
        body: fd
      });
      if (!res.ok) throw new Error('OCR request failed');
      const data = await res.json();
      const text = data?.text ?? data?.data?.text ?? '';
      if (!text) throw new Error('No text extracted');
      $('essayText').value = text;
      $('ocrStatus').textContent = '✅ OCR done.';
    } catch (err) {
      console.error(err);
      $('ocrStatus').textContent = '❌ OCR failed. Please check /api/ocr.';
    } finally {
      setLoading($('btnOCR'), false);
    }
  });

  // ---------- SCORE ----------
  $('btnScore').addEventListener('click', async () => {
    const payload = {
      title:  $('title').value.trim(),
      rubric: $('rubric').value,
      text:   $('essayText').value.trim()
    };
    if (!payload.text) { $('scoreStatus').textContent = 'Please provide essay text (type or OCR).'; return; }
    $('scoreStatus').textContent = '';
    setLoading($('btnScore'), true, '', 'Scoring…');

    try {
      const res = await fetch('/api/grade', {
        method: 'POST',
        headers: {
          'Content-Type':'application/json',
          ...(csrf ? {'X-CSRF-TOKEN': csrf} : {})
        },
        body: JSON.stringify(payload)
      });
      if (!res.ok) throw new Error('Grade request failed');
      const data = await res.json();

      // 兼容不同字段名：{scores:{...}, suggestions:[...]} 或直接 {content, communicative, ...}
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
      $('scoreStatus').textContent = '❌ Score failed. Please check /api/grade.';
    } finally {
      setLoading($('btnScore'), false);
    }
  });

  // ---------- Export (HTML片段，老师可直接发给学生/家长) ----------
  $('btnExport').addEventListener('click', () => {
    const data = {
      title: $('title').value.trim(),
      rubric: $('rubric').value,
      text: $('essayText').value.trim(),
      scores: {
        content: $('scContent').textContent,
        communicative: $('scComm').textContent,
        organisation: $('scOrg').textContent,
        language: $('scLang').textContent,
        total: $('scTotal').textContent
      },
      suggestions: Array.from(document.querySelectorAll('#suggestions li')).map(li => li.textContent)
    };

    const html = `
<!DOCTYPE html><html><head><meta charset="utf-8">
<title>Essay Report</title>
<style>
  body{font-family:system-ui,-apple-system,Segoe UI,Roboto;line-height:1.5;margin:24px;color:#111}
  h1{margin:0 0 6px} h2{margin:18px 0 6px}
  .tag{display:inline-block;padding:2px 8px;border-radius:999px;background:#eef;border:1px solid #ccd}
  .box{border:1px solid #eee;border-radius:12px;padding:12px;margin:8px 0;background:#fafafa}
  .grid{display:grid;grid-template-columns:repeat(5,1fr);gap:8px}
  .card{background:#f2f6ff;border:1px solid #dfe8ff;border-radius:12px;padding:10px;text-align:center}
  .muted{color:#666;font-size:12px}
  ul{margin:8px 0 0 18px}
  pre{white-space:pre-wrap;background:#fff;border:1px solid #eee;border-radius:8px;padding:10px}
</style>
</head><body>
  <h1>Essay Report</h1>
  <div class="muted">Generated by Essay Pro</div>

  <div class="box">
    <div><span class="tag">Title</span> ${data.title || '-'}</div>
    <div><span class="tag">Rubric</span> ${data.rubric}</div>
  </div>

  <h2>Scores</h2>
  <div class="grid">
    <div class="card"><div class="muted">Content</div><div style="font-size:24px;font-weight:800">${data.scores.content}</div></div>
    <div class="card"><div class="muted">Communicative</div><div style="font-size:24px;font-weight:800">${data.scores.communicative}</div></div>
    <div class="card"><div class="muted">Organisation</div><div style="font-size:24px;font-weight:800">${data.scores.organisation}</div></div>
    <div class="card"><div class="muted">Language</div><div style="font-size:24px;font-weight:800">${data.scores.language}</div></div>
    <div class="card" style="background:#e8fff4;border-color:#c7f5dc"><div class="muted">Total</div><div style="font-size:24px;font-weight:800">${data.scores.total}</div></div>
  </div>

  <h2>Revision suggestions</h2>
  <ul>${data.suggestions.map(s => `<li>${s}</li>`).join('')}</ul>

  <h2>Essay Text</h2>
  <pre>${(data.text || '').replace(/[<&>]/g, m => ({'<':'&lt;','>':'&gt;','&':'&amp;'}[m]))}</pre>
</body></html>
`;
    const blob = new Blob([html], {type:'text/html'});
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url; a.download = 'essay-report.html';
    document.body.appendChild(a); a.click(); a.remove();
    URL.revokeObjectURL(url);
  });
</script>
@endsection
