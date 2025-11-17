<nav class="w-full bg-white shadow-md py-3 px-6 flex justify-between fixed top-0 left-0 z-50">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex justify-between items-center h-16">
      
      {{-- 🔹 Logo --}}
      <div class="flex items-center -ml-2">
        <a href="{{ route('home') }}" 
          class="text-2xl font-extrabold bg-gradient-to-r from-indigo-600 to-blue-600 bg-clip-text text-transparent">
          🤖 AI Learning Tools
        </a>
      </div>

      {{-- 🔹 Navigation Links --}}
      <div class="hidden md:flex items-center space-x-2 ml-8">

        {{-- 🏠 Home --}}
        <a href="{{ route('home') }}"
          class="px-4 py-2 rounded-xl text-sm font-semibold transition-all duration-200 
          {{ request()->routeIs('home') 
              ? 'bg-indigo-700 text-white shadow-md scale-105' 
              : 'text-gray-800 hover:bg-indigo-50 hover:text-indigo-700' }}">
          🏠 Home
        </a>

        {{-- 📝 SmartMark (rename from Essay Pro) --}}
        <a href="{{ route('essay.pro') }}"
           class="px-4 py-2 rounded-xl text-sm font-semibold transition
           {{ request()->routeIs('essay.pro') ? 'bg-sky-700 text-white shadow-md scale-105'
                                              : 'text-gray-800 hover:bg-sky-50 hover:text-sky-700' }}">
          📝 SmartMark
        </a>
        
        {{-- 📘 Quiz Solver --}}
        <a href="{{ route('solve.index') }}"
          class="px-4 py-2 rounded-xl text-sm font-semibold transition-all duration-200 
          {{ request()->routeIs('solve.*') 
              ? 'bg-blue-700 text-white shadow-md scale-105' 
              : 'text-gray-800 hover:bg-blue-50 hover:text-blue-700' }}">
          📘 Quiz Solver
        </a>

        {{-- ✍️ Corrector --}}
        <a href="{{ route('corrector.index') }}"
          class="px-4 py-2 rounded-xl text-sm font-semibold transition-all duration-200 
          {{ request()->routeIs('corrector.*') 
              ? 'bg-violet-700 text-white shadow-md scale-105' 
              : 'text-gray-800 hover:bg-violet-50 hover:text-violet-700' }}">
          ✍️ Corrector
        </a>

        {{-- 🧠 Generator --}}
        <a href="{{ route('generator.index') }}"
          class="px-4 py-2 rounded-xl text-sm font-semibold transition-all duration-200 
          {{ request()->routeIs('generator.*') 
              ? 'bg-purple-700 text-white shadow-md scale-105' 
              : 'text-gray-800 hover:bg-purple-50 hover:text-purple-700' }}">
          🧠 Generator
        </a>

        {{-- 🏫 Grader --}}
        <a href="{{ route('grader') }}"
          class="px-4 py-2 rounded-xl text-sm font-semibold transition-all duration-200 
          {{ request()->routeIs('grader*') 
              ? 'bg-cyan-700 text-white shadow-md scale-105' 
              : 'text-gray-800 hover:bg-cyan-50 hover:text-cyan-700' }}">
          🏫 Grader
        </a>

      </div>

      {{-- 🔹 Mobile Menu Button --}}
      <div class="md:hidden">
        <button id="mobile-menu-button" class="text-gray-700 hover:text-indigo-600 focus:outline-none">
          <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                  d="M4 6h16M4 12h16M4 18h16"/>
          </svg>
        </button>
      </div>
    </div>

    {{-- 🔹 Mobile Menu --}}
    <div id="mobile-menu" class="hidden md:hidden pb-4">
      {{-- 🏠 Home --}}
      <a href="{{ route('home') }}" 
        class="block px-3 py-2 rounded-md text-base font-semibold 
        {{ request()->routeIs('home') ? 'bg-indigo-100 text-indigo-800' : 'text-gray-700 hover:bg-gray-100' }}">
        🏠 Home
      </a>

      {{-- 📝 SmartMark --}}
      <a href="{{ route('essay.pro') }}" 
        class="block px-3 py-2 rounded-md text-base font-semibold 
        {{ request()->routeIs('essay.pro') ? 'bg-sky-100 text-sky-800' : 'text-gray-700 hover:bg-gray-100' }}">
        📝 SmartMark
      </a>
      
      {{-- 📘 Quiz Solver --}}
      <a href="{{ route('solve.index') }}" 
        class="block px-3 py-2 rounded-md text-base font-semibold 
        {{ request()->routeIs('solve.*') ? 'bg-blue-100 text-blue-800' : 'text-gray-700 hover:bg-gray-100' }}">
        📘 Quiz Solver
      </a>

      {{-- ✍️ Corrector --}}
      <a href="{{ route('corrector.index') }}" 
        class="block px-3 py-2 rounded-md text-base font-semibold 
        {{ request()->routeIs('corrector.*') ? 'bg-violet-100 text-violet-800' : 'text-gray-700 hover:bg-gray-100' }}">
        ✍️ Corrector
      </a>

      {{-- 🧠 Generator --}}
      <a href="{{ route('generator.index') }}" 
        class="block px-3 py-2 rounded-md text-base font-semibold 
        {{ request()->routeIs('generator.*') ? 'bg-purple-100 text-purple-800' : 'text-gray-700 hover:bg-gray-100' }}">
        🧠 Generator
      </a>

      {{-- 🏫 Grader --}}
      <a href="{{ route('grader') }}" 
        class="block px-3 py-2 rounded-md text-base font-semibold 
        {{ request()->routeIs('grader*') ? 'bg-cyan-100 text-cyan-800' : 'text-gray-700 hover:bg-gray-100' }}">
        🏫 Grader
      </a>

    </div>
  </div>
</nav>

<script>
  // 🔹 Toggle mobile menu
  document.getElementById('mobile-menu-button')?.addEventListener('click', () => {
    const menu = document.getElementById('mobile-menu');
    menu.classList.toggle('hidden');
  });
</script>
