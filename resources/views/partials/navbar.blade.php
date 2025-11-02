<nav class="w-full bg-white shadow-md py-3 px-6 flex justify-between fixed top-0 left-0 z-50">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex justify-between items-center h-16">
      
      {{-- Logo / Brand --}}
      <div class="flex items-center -ml-2">
        <a href="{{ route('home') }}" 
          class="text-2xl font-bold bg-gradient-to-r from-indigo-600 to-blue-600 bg-clip-text text-transparent">
          🤖 AI Learning Tools
        </a>
      </div>

      {{-- Navigation Links --}}
      <div class="hidden md:flex items-center space-x-2 ml-8">

        {{-- 🏠 Home --}}
        <a href="{{ route('home') }}"
          class="px-4 py-2 rounded-xl text-sm font-medium transition-all duration-200 
          {{ request()->routeIs('home') 
              ? 'bg-gradient-to-r from-indigo-600 to-blue-700 text-white shadow-md scale-105' 
              : 'text-gray-700 hover:text-indigo-700 hover:bg-indigo-50' }}">
          🏠 Home
        </a>

        {{-- 📘 Quiz Solver --}}
        <a href="{{ route('solve.index') }}"
          class="px-4 py-2 rounded-xl text-sm font-medium transition-all duration-200 
          {{ request()->routeIs('solve.*') 
              ? 'bg-gradient-to-r from-blue-600 to-indigo-700 text-white shadow-md scale-105' 
              : 'text-gray-700 hover:text-blue-700 hover:bg-blue-50' }}">
          📘 Quiz Solver
        </a>

        {{-- ✍️ Corrector --}}
        <a href="{{ route('corrector.index') }}"
          class="px-4 py-2 rounded-xl text-sm font-medium transition-all duration-200 
          {{ request()->routeIs('corrector.*') 
              ? 'bg-gradient-to-r from-emerald-600 to-green-700 text-white shadow-md scale-105' 
              : 'text-gray-700 hover:text-emerald-700 hover:bg-emerald-50' }}">
          ✍️ Corrector
        </a>

        {{-- 🧠 Generator --}}
        <a href="{{ route('generator.index') }}"
          class="px-4 py-2 rounded-xl text-sm font-medium transition-all duration-200 
          {{ request()->routeIs('generator.*') 
              ? 'bg-gradient-to-r from-purple-600 to-pink-700 text-white shadow-md scale-105' 
              : 'text-gray-700 hover:text-purple-700 hover:bg-purple-50' }}">
          🧠 Generator
        </a>

        {{-- 🏫 Grader (New) --}}
        <a href="{{ route('grader') }}"
          class="px-4 py-2 rounded-xl text-sm font-medium transition-all duration-200 
          {{ request()->routeIs('grader*') 
              ? 'bg-gradient-to-r from-yellow-500 to-orange-600 text-white shadow-md scale-105' 
              : 'text-gray-700 hover:text-yellow-700 hover:bg-yellow-50' }}">
          🏫 Grader
        </a>

      </div>

      {{-- Mobile menu button --}}
      <div class="md:hidden">
        <button id="mobile-menu-button" class="text-gray-700 hover:text-indigo-600 focus:outline-none">
          <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
          </svg>
        </button>
      </div>
    </div>

    {{-- Mobile menu --}}
    <div id="mobile-menu" class="hidden md:hidden pb-4">
      <a href="{{ route('home') }}" 
        class="block px-3 py-2 rounded-md text-base font-medium 
        {{ request()->routeIs('home') ? 'bg-indigo-100 text-indigo-700' : 'text-gray-700 hover:bg-gray-100' }}">
        🏠 Home
      </a>

      <a href="{{ route('solve.index') }}" 
        class="block px-3 py-2 rounded-md text-base font-medium 
        {{ request()->routeIs('solve.*') ? 'bg-blue-100 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}">
        📘 Quiz Solver
      </a>

      <a href="{{ route('corrector.index') }}" 
        class="block px-3 py-2 rounded-md text-base font-medium 
        {{ request()->routeIs('corrector.*') ? 'bg-emerald-100 text-emerald-700' : 'text-gray-700 hover:bg-gray-100' }}">
        ✍️ Corrector
      </a>

      <a href="{{ route('generator.index') }}" 
        class="block px-3 py-2 rounded-md text-base font-medium 
        {{ request()->routeIs('generator.*') ? 'bg-purple-100 text-purple-700' : 'text-gray-700 hover:bg-gray-100' }}">
        🧠 Generator
      </a>

      {{-- 🏫 Grader (New Mobile Item) --}}
      <a href="{{ route('grader') }}" 
        class="block px-3 py-2 rounded-md text-base font-medium 
        {{ request()->routeIs('grader*') ? 'bg-yellow-100 text-yellow-700' : 'text-gray-700 hover:bg-gray-100' }}">
        🏫 Grader
      </a>
    </div>
  </div>
</nav>

<script>
  // Toggle mobile menu
  document.getElementById('mobile-menu-button')?.addEventListener('click', () => {
    const menu = document.getElementById('mobile-menu');
    menu.classList.toggle('hidden');
  });
</script>
