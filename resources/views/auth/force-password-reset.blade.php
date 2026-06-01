<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Reset Your Password — {{ config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #0f0c29 0%, #302b63 50%, #24243e 100%);
            font-family: 'Segoe UI', system-ui, sans-serif;
            padding: 1rem;
        }

        /* Animated background orbs */
        body::before, body::after {
            content: '';
            position: fixed;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.15;
            animation: float 8s ease-in-out infinite;
            pointer-events: none;
        }
        body::before {
            width: 400px; height: 400px;
            background: #6c63ff;
            top: -100px; left: -100px;
        }
        body::after {
            width: 300px; height: 300px;
            background: #f59e0b;
            bottom: -80px; right: -80px;
            animation-delay: -4s;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0) scale(1); }
            50%       { transform: translateY(-30px) scale(1.05); }
        }

        .reset-card {
            width: 100%;
            max-width: 460px;
            background: rgba(255, 255, 255, 0.04);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 2.5rem 2.75rem;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.4), inset 0 1px 0 rgba(255,255,255,0.08);
            position: relative;
            z-index: 1;
        }

        /* Warning badge */
        .warning-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(245, 158, 11, 0.15);
            border: 1px solid rgba(245, 158, 11, 0.3);
            color: #fbbf24;
            font-size: 0.72rem;
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            padding: 4px 12px;
            border-radius: 999px;
            margin-bottom: 1.25rem;
        }
        .warning-badge i { font-size: 0.8rem; }

        .reset-icon {
            width: 68px; height: 68px;
            background: linear-gradient(135deg, #f59e0b, #ef4444);
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            margin: 0 auto 1.5rem;
            box-shadow: 0 8px 24px rgba(245, 158, 11, 0.35);
        }

        h1.page-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #ffffff;
            text-align: center;
            margin-bottom: 0.5rem;
        }
        .page-subtitle {
            text-align: center;
            color: rgba(255,255,255,0.5);
            font-size: 0.875rem;
            margin-bottom: 2rem;
            line-height: 1.6;
        }

        /* Divider */
        .section-divider {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 1.75rem 0;
        }
        .section-divider::before, .section-divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: rgba(255,255,255,0.1);
        }
        .section-divider span {
            color: rgba(255,255,255,0.3);
            font-size: 0.75rem;
            font-weight: 500;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        /* Form inputs */
        .form-label-custom {
            color: rgba(255,255,255,0.65);
            font-size: 0.82rem;
            font-weight: 500;
            margin-bottom: 0.4rem;
            display: block;
        }
        .form-control-custom {
            width: 100%;
            background: rgba(255,255,255,0.07);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 10px;
            padding: 0.7rem 1rem;
            color: #e2e8f0;
            font-size: 0.9rem;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
        }
        .form-control-custom::placeholder { color: rgba(255,255,255,0.3); }
        .form-control-custom:focus {
            border-color: #6c63ff;
            background: rgba(108, 99, 255, 0.1);
            box-shadow: 0 0 0 3px rgba(108, 99, 255, 0.2);
        }
        .form-control-custom.is-invalid {
            border-color: #f87171;
            background: rgba(248, 113, 113, 0.08);
        }
        .invalid-msg {
            color: #f87171;
            font-size: 0.78rem;
            margin-top: 0.35rem;
        }

        /* Password strength */
        .strength-bar {
            height: 4px;
            border-radius: 999px;
            background: rgba(255,255,255,0.08);
            margin-top: 8px;
            overflow: hidden;
        }
        .strength-fill {
            height: 100%;
            border-radius: 999px;
            width: 0%;
            transition: width 0.3s ease, background 0.3s ease;
        }
        .strength-label {
            font-size: 0.72rem;
            color: rgba(255,255,255,0.4);
            margin-top: 4px;
            text-align: right;
        }

        /* Buttons */
        .btn-primary-custom {
            width: 100%;
            background: linear-gradient(135deg, #6c63ff 0%, #3b82f6 100%);
            border: none;
            border-radius: 10px;
            padding: 0.75rem 1rem;
            color: #fff;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s, opacity 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .btn-primary-custom:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 25px rgba(108,99,255,0.4);
        }
        .btn-primary-custom:active { transform: translateY(0); }

        .btn-danger-custom {
            width: 100%;
            background: rgba(239, 68, 68, 0.12);
            border: 1px solid rgba(239, 68, 68, 0.3);
            border-radius: 10px;
            padding: 0.7rem 1rem;
            color: #f87171;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            transition: background 0.2s, border-color 0.2s, transform 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
        }
        .btn-danger-custom:hover {
            background: rgba(239, 68, 68, 0.2);
            border-color: rgba(239, 68, 68, 0.5);
            transform: translateY(-1px);
            color: #fca5a5;
        }

        /* Alert */
        .alert-custom {
            background: rgba(251, 191, 36, 0.1);
            border: 1px solid rgba(251, 191, 36, 0.25);
            border-radius: 10px;
            padding: 0.75rem 1rem;
            color: #fde68a;
            font-size: 0.82rem;
            display: flex;
            align-items: flex-start;
            gap: 8px;
            margin-bottom: 1.5rem;
        }
        .alert-custom i { margin-top: 1px; flex-shrink: 0; }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.25);
            border-radius: 10px;
            padding: 0.75rem 1rem;
            color: #fca5a5;
            font-size: 0.82rem;
            display: flex;
            align-items: flex-start;
            gap: 8px;
            margin-bottom: 1.5rem;
        }

        /* Password toggle */
        .input-wrapper {
            position: relative;
        }
        .pw-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: rgba(255,255,255,0.35);
            cursor: pointer;
            padding: 0;
            font-size: 1rem;
            transition: color 0.2s;
        }
        .pw-toggle:hover { color: rgba(255,255,255,0.7); }

        /* Info note */
        .info-note {
            font-size: 0.75rem;
            color: rgba(255,255,255,0.3);
            text-align: center;
            margin-top: 1.5rem;
            line-height: 1.5;
        }
        .info-note i { color: rgba(108,99,255,0.7); }
    </style>
</head>
<body>
    <div class="reset-card">

        {{-- Warning badge --}}
        <div class="text-center">
            <span class="warning-badge">
                <i class="bi bi-exclamation-triangle-fill"></i> Action Required
            </span>
        </div>

        {{-- Icon --}}
        <div class="reset-icon text-white">
            <i class="bi bi-shield-exclamation"></i>
        </div>

        {{-- Heading --}}
        <h1 class="page-title">Password Reset Required</h1>
        <p class="page-subtitle">
            Your account requires a password change before you can continue.<br>
            Please update your password or sign out below.
        </p>

        {{-- Warning flash --}}
        @if (session('warning'))
            <div class="alert-custom">
                <i class="bi bi-info-circle-fill"></i>
                <span>{{ session('warning') }}</span>
            </div>
        @endif

        {{-- Validation errors --}}
        @if ($errors->any())
            <div class="alert-error">
                <i class="bi bi-x-circle-fill"></i>
                <div>
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- ═══════════════════════════════════════ --}}
        {{--  OPTION 1 — Update Password             --}}
        {{-- ═══════════════════════════════════════ --}}
        <form method="POST" action="{{ route('password.update') }}" id="pw-reset-form">
            @csrf
            @method('PUT')

            {{-- Current Password --}}
            <div class="mb-3">
                <label for="current_password" class="form-label-custom">Current Password</label>
                <div class="input-wrapper">
                    <input
                        type="password"
                        id="current_password"
                        name="current_password"
                        class="form-control-custom @error('current_password', 'updatePassword') is-invalid @enderror"
                        placeholder="Your current password"
                        required
                        autocomplete="current-password"
                    >
                    <button type="button" class="pw-toggle" onclick="togglePw('current_password', this)">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
                @error('current_password', 'updatePassword')
                    <p class="invalid-msg"><i class="bi bi-x-circle me-1"></i>{{ $message }}</p>
                @enderror
            </div>

            {{-- New Password --}}
            <div class="mb-2">
                <label for="password" class="form-label-custom">New Password</label>
                <div class="input-wrapper">
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control-custom @error('password', 'updatePassword') is-invalid @enderror"
                        placeholder="Choose a strong password"
                        required
                        autocomplete="new-password"
                        oninput="checkStrength(this.value)"
                    >
                    <button type="button" class="pw-toggle" onclick="togglePw('password', this)">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
                {{-- Strength meter --}}
                <div class="strength-bar"><div class="strength-fill" id="strength-fill"></div></div>
                <p class="strength-label" id="strength-label">Enter a password</p>
                @error('password', 'updatePassword')
                    <p class="invalid-msg"><i class="bi bi-x-circle me-1"></i>{{ $message }}</p>
                @enderror
            </div>

            {{-- Confirm Password --}}
            <div class="mb-4">
                <label for="password_confirmation" class="form-label-custom">Confirm New Password</label>
                <div class="input-wrapper">
                    <input
                        type="password"
                        id="password_confirmation"
                        name="password_confirmation"
                        class="form-control-custom"
                        placeholder="Re-enter new password"
                        required
                        autocomplete="new-password"
                    >
                    <button type="button" class="pw-toggle" onclick="togglePw('password_confirmation', this)">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-primary-custom" id="submit-btn">
                <i class="bi bi-shield-check"></i>
                Update Password &amp; Continue
            </button>
        </form>

        {{-- ═══════════════════════════════════════ --}}
        {{--  Divider                                --}}
        {{-- ═══════════════════════════════════════ --}}
        <div class="section-divider">
            <span>or</span>
        </div>

        {{-- ═══════════════════════════════════════ --}}
        {{--  OPTION 2 — Logout                     --}}
        {{-- ═══════════════════════════════════════ --}}
        <form method="POST" action="{{ route('logout') }}" id="logout-form">
            @csrf
            <button type="submit" class="btn-danger-custom">
                <i class="bi bi-box-arrow-right"></i>
                Sign Out Instead
            </button>
        </form>

        <p class="info-note">
            <i class="bi bi-lock-fill"></i>
            You cannot access other pages until your password is updated.
        </p>
    </div>

    <script>
        /* ── Toggle password visibility ── */
        function togglePw(fieldId, btn) {
            const field = document.getElementById(fieldId);
            const icon  = btn.querySelector('i');
            const isHidden = field.type === 'password';
            field.type = isHidden ? 'text' : 'password';
            icon.className = isHidden ? 'bi bi-eye-slash' : 'bi bi-eye';
        }

        /* ── Password strength meter ── */
        function checkStrength(val) {
            const fill  = document.getElementById('strength-fill');
            const label = document.getElementById('strength-label');

            let score = 0;
            if (val.length >= 8)              score++;
            if (/[A-Z]/.test(val))            score++;
            if (/[0-9]/.test(val))            score++;
            if (/[^A-Za-z0-9]/.test(val))    score++;

            const levels = [
                { pct: '0%',   color: 'transparent',          text: 'Enter a password' },
                { pct: '25%',  color: '#ef4444',              text: 'Weak' },
                { pct: '50%',  color: '#f97316',              text: 'Fair' },
                { pct: '75%',  color: '#eab308',              text: 'Good' },
                { pct: '100%', color: '#22c55e',              text: 'Strong ✓' },
            ];

            const level = val.length === 0 ? levels[0] : levels[score];
            fill.style.width      = level.pct;
            fill.style.background = level.color;
            label.textContent     = level.text;
            label.style.color     = val.length === 0 ? 'rgba(255,255,255,0.3)' : level.color;
        }

        /* ── Prevent double submit ── */
        document.getElementById('pw-reset-form').addEventListener('submit', function () {
            const btn = document.getElementById('submit-btn');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Updating…';
        });
    </script>
</body>
</html>
