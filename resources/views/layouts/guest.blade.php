<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="csrf-token" content="{{ csrf_token() }}" />
  <title>@yield('title', 'Bus Depot Management System')</title>
  @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>

<div class="guest-root">
  @yield('content')
</div>

<script>
  document.addEventListener('submit', function (e) {
    const btn = e.target.querySelector('[type="submit"]');
    if (btn) {
      btn.disabled = true;
      btn.style.opacity = '0.55';
      btn.style.cursor = 'not-allowed';
    }
  });
</script>
</body>
</html>
