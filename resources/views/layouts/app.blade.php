<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>@yield('title', 'AI Learning Tools')</title>
  <script src="https://cdn.tailwindcss.com?plugins=typography,forms"></script>

  <style>
    html {
      scroll-behavior: smooth;
    }
  </style>
</head>

<body class="bg-gradient-to-br from-indigo-50 via-blue-50 to-emerald-50 min-h-screen flex flex-col font-sans text-gray-800">
  
  {{-- 🔹 Shared Navbar --}}
  <header class="fixed top-0 left-0 w-full bg-white/90 backdrop-blur shadow-sm z-50 border-b border-gray-100">
    @include('partials.navbar')
  </header>

  {{-- 🔹 Main Content --}}
<main class="flex-1 flex flex-col justify-center items-center px-6 pt-24 pb-16">
  @yield('content')
</main>


  {{-- 🔹 Footer --}}
  <footer class="mt-auto text-center py-6 bg-white/70 backdrop-blur border-t border-gray-100 text-gray-500 text-sm">
    <p>© {{ date('Y') }} <span class="font-semibold text-indigo-600">AI Learning Tools</span> · Powered by 
      <a href="https://laravel.com" class="hover:text-indigo-500">Laravel</a> & 
      <a href="https://openai.com" class="hover:text-blue-500">OpenAI</a>
    </p>
  </footer>

</body>
</html>
