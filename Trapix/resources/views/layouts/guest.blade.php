<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />

    <title>{{ $title ?? config('app.name', 'Trapix') }}</title>

    {{-- ── Favicon: uses your existing logo ──────────────────── --}}
    <link rel="icon"             type="image/png" href="{{ asset('images/logo.png') }}" />
    <link rel="shortcut icon"    type="image/png" href="{{ asset('images/logo.png') }}" />
    <link rel="apple-touch-icon"                  href="{{ asset('images/logo.png') }}" />

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /* ─── CSS Design Tokens ─────────────────────────────── */
        :root {
            --green-vivid:    #00e200;
            --green-mid:      #00b400;
            --green-glow:     rgba(0, 200, 0, 0.18);
            --glass-bg:       rgba(8, 8, 8, 0.60);
            --glass-border:   rgba(255, 255, 255, 0.07);
            --input-bg:       rgba(255, 255, 255, 0.055);
            --input-border:   rgba(255, 255, 255, 0.09);
            --text-primary:   #ffffff;
            --text-muted:     rgba(255, 255, 255, 0.42);
            --radius-card:    22px;
            --radius-input:   11px;
            --font-base:      'DM Sans', sans-serif;
        }

        *, *::before, *::after { box-sizing: border-box; }

        body {
            font-family: var(--font-base);
            background: #000;
            min-height: 100vh;
            overflow: hidden;
        }

        /* ─── Animated Blobs ─────────────────────────────────── */
        .auth-bg { position: fixed; inset: 0; z-index: 0; overflow: hidden; }

        /* Grain texture overlay */
        .auth-bg::after {
            content: '';
            position: absolute;
            inset: 0;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.85' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.07'/%3E%3C/svg%3E");
            background-size: 180px 180px;
            opacity: .5;
            pointer-events: none;
        }

        .blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(90px);
        }
        .blob-top {
            width: 660px; height: 660px;
            background: radial-gradient(circle, #00e000 0%, #007000 50%, transparent 72%);
            top: -180px; left: 50%; transform: translateX(-50%);
            opacity: .80;
            animation: blobDrift 12s ease-in-out infinite alternate;
        }
        .blob-bl {
            width: 500px; height: 500px;
            background: radial-gradient(circle, #009900 0%, #003800 58%, transparent 80%);
            bottom: -120px; left: -100px;
            opacity: .50;
            animation: blobDrift2 14s ease-in-out infinite alternate;
        }
        .blob-br {
            width: 400px; height: 400px;
            background: radial-gradient(circle, #00bb00 0%, #005500 58%, transparent 80%);
            bottom: -60px; right: -80px;
            opacity: .42;
            animation: blobDrift3 10s ease-in-out infinite alternate;
        }

        @keyframes blobDrift  { 0%{transform:translateX(-50%) translateY(0) scale(1)}  100%{transform:translateX(-53%) translateY(28px) scale(1.06)} }
        @keyframes blobDrift2 { 0%{transform:translateY(0) scale(1)}   100%{transform:translateY(-38px) scale(1.09)} }
        @keyframes blobDrift3 { 0%{transform:translateY(0) scale(1)}   100%{transform:translateY(28px) scale(0.94)} }

        /* ─── Glassmorphism Card ─────────────────────────────── */
        .glass-card {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-card);
            backdrop-filter: blur(30px) saturate(160%);
            -webkit-backdrop-filter: blur(30px) saturate(160%);
            box-shadow:
                0 0 0 1px rgba(0,200,0,0.07) inset,
                0 32px 80px rgba(0,0,0,0.65),
                0 4px 28px rgba(0,180,0,0.07);
        }

        /* ─── Input fields ───────────────────────────────────── */
        .auth-input-wrap {
            display: flex;
            align-items: center;
            background: var(--input-bg);
            border: 1px solid var(--input-border);
            border-radius: var(--radius-input);
            transition: border-color .2s, box-shadow .2s;
        }
        .auth-input-wrap:focus-within {
            border-color: rgba(0, 220, 0, 0.45);
            box-shadow: 0 0 0 3px rgba(0, 200, 0, 0.12);
        }
        .auth-input {
            flex: 1;
            background: transparent;
            border: none;
            outline: none;
            color: var(--text-primary);
            font-family: var(--font-base);
            font-size: 0.92rem;
            padding: 13px 16px;
        }
        .auth-input::placeholder { color: var(--text-muted); }

        .auth-input-arrow {
            width: 36px; height: 36px;
            border-radius: 8px;
            background: var(--green-vivid);
            display: flex; align-items: center; justify-content: center;
            margin-right: 6px;
            flex-shrink: 0;
            transition: background .2s, transform .15s;
        }
        .auth-input-arrow:hover { background: var(--green-mid); transform: scale(1.08); }

        /* ─── Social / Ghost buttons ─────────────────────────── */
        .auth-social-btn {
            display: flex;
            align-items: center;
            width: 100%;
            background: var(--input-bg);
            border: 1px solid var(--input-border);
            border-radius: var(--radius-input);
            padding: 13px 16px;
            color: var(--text-primary);
            font-family: var(--font-base);
            font-size: 0.9rem;
            cursor: pointer;
            transition: background .2s, border-color .2s;
            text-decoration: none;
        }
        .auth-social-btn:hover {
            background: rgba(255,255,255,0.08);
            border-color: rgba(255,255,255,0.16);
        }
        .auth-social-btn .s-arrow { margin-left: auto; opacity: .45; }

        /* ─── Primary green button ───────────────────────────── */
        .auth-primary-btn {
            width: 100%;
            padding: 14px;
            background: var(--green-vivid);
            border: none;
            border-radius: var(--radius-input);
            color: #000;
            font-family: var(--font-base);
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: background .2s, transform .15s, box-shadow .2s;
            box-shadow: 0 4px 20px rgba(0,220,0,0.25);
        }
        .auth-primary-btn:hover {
            background: #00cc00;
            transform: translateY(-1px);
            box-shadow: 0 8px 28px rgba(0,220,0,0.35);
        }
        .auth-primary-btn:active { transform: translateY(0); }

        /* ─── Dev ghost button ───────────────────────────────── */
        .auth-ghost-btn {
            width: 100%;
            padding: 11px;
            background: rgba(255,255,255,0.055);
            border: 1px solid var(--input-border);
            border-radius: var(--radius-input);
            color: var(--text-muted);
            font-family: var(--font-base);
            font-size: 0.85rem;
            cursor: pointer;
            transition: background .2s, color .2s;
        }
        .auth-ghost-btn:hover { background: rgba(255,255,255,0.10); color: var(--text-primary); }

        /* ─── Divider ─────────────────────────────────────────── */
        .auth-divider {
            display: flex; align-items: center; gap: 12px;
        }
        .auth-divider::before, .auth-divider::after {
            content: ''; flex: 1; height: 1px; background: var(--input-border);
        }

        /* ─── Checkbox ───────────────────────────────────────── */
        input[type="checkbox"] {
            width: 15px; height: 15px;
            border-radius: 4px;
            accent-color: var(--green-vivid);
            cursor: pointer;
        }

        /* ─── Utility color helpers used by Blade components ─── */
        .text-muted-contrast  { color: var(--text-muted); }
        .text-heading-1       { color: var(--text-primary); }
        .text-green-accent    { color: var(--green-vivid); }
        .border-glass         { border-color: var(--glass-border); }

        /* ─── Top-left fixed logo ────────────────────────────── */
        .auth-top-logo {
            position: fixed;
            top: 20px;
            left: 100px;
            z-index: 20;
        }

        /* idle: soft breathing glow */
        @keyframes logoBreathe {
            0%, 100% { filter: drop-shadow(0 0 6px rgba(0,220,0,0.25)); }
            50%       { filter: drop-shadow(0 0 18px rgba(0,220,0,0.55)); }
        }

        /* hover: flare burst */
        @keyframes logoFlare {
            0%   { filter: drop-shadow(0 0 8px  rgba(0,220,0,0.5)); }
            40%  { filter: drop-shadow(0 0 30px rgba(0,220,0,1.0)) drop-shadow(0 0 60px rgba(0,220,0,0.4)); }
            100% { filter: drop-shadow(0 0 16px rgba(0,220,0,0.7)); }
        }

        /* hover: bounce-up */
        @keyframes logoBounce {
            0%   { transform: scale(1)    translateY(0); }
            30%  { transform: scale(1.12) translateY(-8px); }
            55%  { transform: scale(0.97) translateY(2px); }
            75%  { transform: scale(1.05) translateY(-3px); }
            100% { transform: scale(1.04) translateY(0); }
        }

        .auth-top-logo img {
            height: 70px;
            width: auto;
            object-fit: contain;
            animation: logoBreathe 3s ease-in-out infinite;
            cursor: pointer;
        }
        .auth-top-logo:hover img {
            animation: logoFlare .5s ease-out forwards, logoBounce .5s ease-out forwards;
        }
    </style>
</head>

<body class="text-white antialiased">

    {{-- ── Centered Content ─────────────────────────────────── --}}
    <div class="relative z-10 min-h-screen flex flex-col items-center justify-center px-4">
        <div class="w-full max-w-lg">

            {{-- ── Logo above card ─────────────────────────────── --}}
            <a href="{{ route('home') }}" class="auth-top-logo">
                <img src="{{ asset('images/logo.png') }}" alt="{{ config('app.name') }}" />
            </a>

            {{-- Glassmorphism Card --}}
            <div class="glass-card px-12 py-7">
                {{ $slot }}
            </div>

            {{-- Below-card slot (sign-up link, etc.) --}}
            @isset($footer)
                <div class="mt-5 text-center text-sm text-muted-contrast">
                    {{ $footer }}
                </div>
            @endisset

        </div>
    </div>

</body>
</html>