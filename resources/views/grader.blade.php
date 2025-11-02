@extends('layouts.app')

@section('title', 'AI Grader (Q&A)')

@section('content')
<div class="bg-white shadow-2xl rounded-2xl p-6 w-full max-w-3xl text-center transition-all duration-300">
  <h1 class="text-3xl font-extrabold mb-3 bg-gradient-to-r from-emerald-600 to-green-600 bg-clip-text text-transparent">
    🏫 AI Grader (Q&A Evaluation)
  </h1>
  <p class="text-gray-600 mb-6">
    Upload or take a photo of the student's written answer.<br>
    The AI will read and grade it automatically.<br>
    <small>(No data is stored)</small>
  </p>

  {{-- Rubric --}}
  <div class="bg-green-50 border-l-4 border-green-400 p-4 rounded mb-5 text-left">
    <h2 class="font-semibold text-green-800 mb-1">📋 Grading Rubric (100 pts)</h2>
    <ul class="list-disc list-inside text-sm text-gray-700 space-y-1">
      <li><b>Content Completeness (40)</b> – Addresses all parts of the question.</li>
      <li><b>Logical Clarity (30)</b> – Coherent and logical explanation.</li>
      <li><b>Language Use (20)</b> – Grammar and vocabulary accuracy.</li>
      <li><b>Originality (10)</b> – Creativity or insight in response.</li>
    </ul>
  </div>

  <form id="graderForm" class="space-y-4">
    <div>
      <label class="block text-gray-700 font-semibold mb-1">Student Name</label>
      <input type="text" id="studentName"
             class="w-full border border-gray-300 rounded-md p-2 focus:ring focus:ring-green-200" required>
    </div>

    <div>
      <label class="block text-gray-700 font-semibold mb-1">Answer Image</label>
      <input type="file" id="answerImage" accept="image/*" capture="environment"
             class="w-full border border-gray-300 rounded-md p-2 focus:ring focus:ring-green-200" required>
      <p class="text-xs text-gray-400 mt-1">Supports JPG/PNG. Auto-compressed to 800 px for faster upload.</p>
    </div>

    <div id="preview" class="mt-2"></div>

    <button type="button" id="gradeBtn"
      class="bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white px-5 py-2.5 rounded-lg shadow-md transition">
      ✨ Grade Answer
    </button>
  </form>

  <div id="loading" class="hidden mt-6 text-gray-600">
    ⏳ Reading and grading the answer, please wait...
  </div>

  <div id="result" class="hidden mt-8 text-left bg-gray-50 border rounded-lg p-5 shadow-inner"></div>
</div>

<script>
const fileInput = document.getElementById('answerImage');
const preview = document.getElementById('preview');
const gradeBtn = document.getElementById('gradeBtn');
const resultDiv = document.getElementById('result');
const loading = document.getElementById('loading');

let compressedDataURL = null;

// compress image to max 800px
function compressImage(dataURL, maxEdge = 800, quality = 0.8) {
  return new Promise((resolve) => {
    const img = new Image();
    img.onload = () => {
      const scale = Math.min(maxEdge / img.width, maxEdge / img.height, 1);
      const w = Math.round(img.width * scale);
      const h = Math.round(img.height * scale);
      const canvas = document.createElement('canvas');
      canvas.width = w; canvas.height = h;
      const ctx = canvas.getContext('2d');
      ctx.drawImage(img, 0, 0, w, h);
      resolve(canvas.toDataURL('image/jpeg', quality));
    };
    img.src = dataURL;
  });
}

fileInput.addEventListener('change', async e => {
  const file = e.target.files[0];
  if (!file) return;
  if (file.size > 10 * 1024 * 1024) {
    alert("Image exceeds 10 MB. Please choose a smaller one.");
    return;
  }
  const reader = new FileReader();
  reader.onload = async ev => {
    const originalDataURL = ev.target.result;
    preview.innerHTML = `<img src="${originalDataURL}" class="rounded-lg mt-2 shadow-md max-h-64 mx-auto">`;
    compressedDataURL = await compressImage(originalDataURL, 800, 0.8);
  };
  reader.readAsDataURL(file);
});

gradeBtn.addEventListener('click', async () => {
  const studentName = document.getElementById('studentName').value.trim();
  if (!studentName || !compressedDataURL) {
    alert("Please enter name and upload an image first.");
    return;
  }

  loading.classList.remove('hidden');
  resultDiv.classList.add('hidden');
  resultDiv.innerHTML = '';

  try {
    const res = await fetch('/api/grader', {  // ✅ 改成 API 路由
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        student_name: studentName,
        image: compressedDataURL
      })
    });
    const data = await res.json();
    if (data.ok) {
      resultDiv.innerHTML = `
        <h2 class="text-xl font-bold text-green-700 mb-2">✅ Grading Result</h2>
        <p><strong>Student:</strong> ${escapeHTML(data.data.student)}</p>
        <pre class="whitespace-pre-line mt-2 text-gray-800">${escapeHTML(data.data.grade)}</pre>
      `;
      resultDiv.classList.remove('hidden');
    } else {
      resultDiv.innerHTML = `<p class="text-red-600">❌ ${escapeHTML(data.error || 'Failed to grade.')}</p>`;
      resultDiv.classList.remove('hidden');
    }
  } catch (err) {
    resultDiv.innerHTML = `<p class="text-red-600">❌ ${escapeHTML(err.message)}</p>`;
    resultDiv.classList.remove('hidden');
  } finally {
    loading.classList.add('hidden');
  }
});

function escapeHTML(s) {
  return String(s || '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}
</script>
@endsection
