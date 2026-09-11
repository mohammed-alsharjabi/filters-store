<!doctype html>
<html lang="ar-SA" dir="rtl">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex,nofollow">
    <link rel="icon" href="{{ asset('brand/filters-store-logo.png') }}" type="image/png"><title>دخول الإدارة | {{ $siteSettings['site_name'] }}</title>
    <style id="theme-palette">{!! app(\App\Support\ThemePalette::class)->css($siteSettings) !!}</style>@vite(['resources/css/app.css'])
</head>
<body class="login-page"><main class="login-card"><a class="brand login-brand" href="{{ route('home') }}"><x-site-logo class="login-brand-logo" :alt="$siteSettings['site_name']" /></a><div><p class="eyebrow">منطقة محمية</p><h1>دخول الإدارة</h1><p>استخدم حساب المدير المخول.</p></div><form method="POST" action="{{ route('admin.login.store') }}">@csrf<label><span>البريد الإلكتروني</span><input name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="email">@error('email')<small>{{ $message }}</small>@enderror</label><label><span>كلمة المرور</span><input name="password" type="password" required autocomplete="current-password">@error('password')<small>{{ $message }}</small>@enderror</label><label class="check-label"><input type="checkbox" name="remember" value="1"> تذكرني</label><button class="button button-primary w-full" type="submit">دخول</button></form></main></body>
</html>
