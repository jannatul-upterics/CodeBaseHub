<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Sign In — CodeBaseHub</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        :root {
            --cbh-brand-500: #3b82f6;
            --cbh-brand-600: #2563eb;
            --cbh-brand-700: #1d4ed8;
            --cbh-neutral-50: #f8fafc;
            --cbh-neutral-100: #f1f5f9;
            --cbh-neutral-200: #e2e8f0;
            --cbh-neutral-400: #94a3b8;
            --cbh-neutral-500: #64748b;
            --cbh-neutral-600: #475569;
            --cbh-neutral-700: #334155;
            --cbh-neutral-800: #1e293b;
            --cbh-neutral-900: #0f172a;
            --cbh-danger-bg: #fef2f2;
            --cbh-danger-fg: #b91c1c;
            --cbh-danger-border: #fecaca;
            --cbh-font: 'Instrument Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        *, *::before, *::after { box-sizing: border-box; }

        body {
            font-family: var(--cbh-font);
            background: var(--cbh-neutral-50);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            color: var(--cbh-neutral-700);
            -webkit-font-smoothing: antialiased;
        }

        .login-panel {
            width: 100%;
            max-width: 420px;
        }

        /* Brand header */
        .login-brand {
            text-align: center;
            margin-bottom: 2rem;
        }

        .login-brand-mark {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: linear-gradient(135deg, #3b82f6 0%, #6366f1 100%);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 1.4rem;
            box-shadow: 0 6px 16px rgba(59 130 246 / 0.35);
            margin-bottom: 0.875rem;
        }

        .login-brand-name {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--cbh-neutral-900);
            letter-spacing: -0.03em;
            line-height: 1;
        }

        .login-brand-sub {
            font-size: 0.82rem;
            color: var(--cbh-neutral-400);
            margin-top: 0.25rem;
        }

        /* Card */
        .login-card {
            background: #fff;
            border: 1px solid var(--cbh-neutral-200);
            border-radius: 14px;
            box-shadow: 0 4px 24px -4px rgba(0 0 0 / 0.08), 0 1px 3px rgba(0 0 0 / 0.04);
            padding: 2rem;
        }

        .login-heading {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--cbh-neutral-800);
            margin: 0 0 0.25rem;
        }

        .login-subheading {
            font-size: 0.82rem;
            color: var(--cbh-neutral-500);
            margin: 0 0 1.5rem;
        }

        /* Form */
        .form-group { margin-bottom: 1rem; }

        .form-label {
            display: block;
            font-size: 0.82rem;
            font-weight: 600;
            color: var(--cbh-neutral-700);
            margin-bottom: 0.375rem;
        }

        .form-control-cbh {
            display: block;
            width: 100%;
            padding: 0.5rem 0.75rem;
            font-size: 0.9rem;
            font-family: var(--cbh-font);
            color: var(--cbh-neutral-800);
            background: #fff;
            border: 1px solid var(--cbh-neutral-200);
            border-radius: 8px;
            transition: border-color 0.12s, box-shadow 0.12s;
            line-height: 1.5;
        }

        .form-control-cbh::placeholder { color: var(--cbh-neutral-400); }

        .form-control-cbh:focus {
            outline: none;
            border-color: var(--cbh-brand-500);
            box-shadow: 0 0 0 3px rgba(59 130 246 / 0.1);
        }

        .form-control-cbh.is-invalid {
            border-color: #ef4444;
        }

        /* Password toggle wrapper */
        .password-wrap { position: relative; }

        .password-toggle {
            position: absolute;
            right: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: var(--cbh-neutral-400);
            padding: 0;
            font-size: 1rem;
            line-height: 1;
        }

        .password-toggle:hover { color: var(--cbh-neutral-600); }

        /* Error alert */
        .login-error {
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
            background: var(--cbh-danger-bg);
            border: 1px solid var(--cbh-danger-border);
            border-radius: 8px;
            padding: 0.75rem 1rem;
            font-size: 0.85rem;
            color: var(--cbh-danger-fg);
            margin-bottom: 1.25rem;
        }

        /* Submit button */
        .login-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.375rem;
            width: 100%;
            padding: 0.625rem 1rem;
            font-size: 0.9rem;
            font-weight: 600;
            font-family: var(--cbh-font);
            color: #fff;
            background: var(--cbh-brand-600);
            border: 1px solid var(--cbh-brand-600);
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.12s, transform 0.1s;
            box-shadow: 0 1px 3px rgba(0 0 0 / 0.1);
        }

        .login-btn:hover {
            background: var(--cbh-brand-700);
        }

        .login-btn:active {
            transform: scale(0.99);
        }

        /* Footer */
        .login-footer {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.75rem;
            color: var(--cbh-neutral-400);
        }
    </style>
</head>
<body>
    <div class="login-panel">

        {{-- Brand --}}
        <div class="login-brand">
            <div class="login-brand-mark">
                <i class="bi bi-layers-fill"></i>
            </div>
            <div class="login-brand-name">CodeBase<span style="color:#3b82f6;">Hub</span></div>
            <div class="login-brand-sub">Website Management Platform</div>
        </div>

        {{-- Card --}}
        <div class="login-card">
            <h1 class="login-heading">Administrator Login</h1>
            <p class="login-subheading">Sign In with your credentials to access the admin console.</p>

            {{-- Status --}}
            @if (session('status'))
                <div class="login-error" style="background:#f0fdf4;border-color:#bbf7d0;color:#15803d;">
                    <i class="bi bi-check-circle-fill" style="flex-shrink:0;margin-top:0.05rem;"></i>
                    {{ session('status') }}
                </div>
            @endif

            {{-- Validation Errors --}}
            @if ($errors->any())
                <div class="login-error">
                    <i class="bi bi-exclamation-triangle-fill" style="flex-shrink:0;margin-top:0.05rem;"></i>
                    <div>
                        @foreach ($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf

                {{-- Email --}}
                <div class="form-group">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email"
                           id="email"
                           name="email"
                           value="{{ old('email') }}"
                           class="form-control-cbh {{ $errors->has('email') ? 'is-invalid' : '' }}"
                           placeholder="admin@codebasehub.local"
                           required
                           autocomplete="email"
                           autofocus>
                </div>

                {{-- Password --}}
                <div class="form-group">
                    <div style="display:flex;justify-content:space-between;margin-bottom:0.375rem;">
                        <label for="password" class="form-label" style="margin-bottom:0;">Password</label>
                    </div>
                    <div class="password-wrap">
                        <input type="password"
                               id="password"
                               name="password"
                               class="form-control-cbh {{ $errors->has('password') ? 'is-invalid' : '' }}"
                               placeholder="••••••••"
                               required
                               autocomplete="current-password">
                        <button type="button"
                                class="password-toggle"
                                onclick="togglePassword()"
                                id="passwordToggleBtn"
                                aria-label="Toggle password visibility">
                            <i class="bi bi-eye" id="passwordToggleIcon"></i>
                        </button>
                    </div>
                </div>

                {{-- Remember --}}
                <div class="form-group" style="display:flex;align-items:center;gap:0.5rem;margin-top:0.5rem;">
                    <input type="checkbox"
                           id="remember"
                           name="remember"
                           class="form-check-input"
                           style="margin:0;width:16px;height:16px;">
                    <label for="remember" style="font-size:0.82rem;color:var(--cbh-neutral-600);margin:0;cursor:pointer;">
                        Keep me signed in
                    </label>
                </div>

                {{-- Submit --}}
                <div style="margin-top:1.25rem;">
                    <button type="submit" class="login-btn">
                        <i class="bi bi-box-arrow-in-right"></i>
                        Sign In
                    </button>
                </div>
            </form>
        </div>

        <div class="login-footer">
            <div>&copy; {{ date('Y') }} CodeBaseHub &mdash; Secure Admin Access</div>
            <div style="font-size:0.72rem;color:var(--cbh-neutral-500);margin-top:0.35rem;">
                Protected area. Public registration is restricted.
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const input = document.getElementById('password');
            const icon = document.getElementById('passwordToggleIcon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'bi bi-eye-slash';
            } else {
                input.type = 'password';
                icon.className = 'bi bi-eye';
            }
        }
    </script>
</body>
</html>
