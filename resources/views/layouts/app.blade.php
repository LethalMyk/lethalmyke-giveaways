
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>@yield('title', 'Lethal Myke Giveaways')</title>

    <meta name="theme-color" content="#101014">

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --canvas: #101014;
            --surface: #18181e;
            --surface-raised: #202027;
            --line: #34343d;
            --gold: #efb632;
            --gold-light: #ffd36d;
            --twitch: #a970ff;
            --text: #eeeef2;
            --muted: #92929d;
        }

        * { letter-spacing: 0; }

        body {
            background: var(--canvas);
            color: var(--text);
            min-height: 100vh;
            font-family: 'DM Sans', 'Segoe UI', sans-serif;
        }

        a { color: var(--gold-light); }
        a:hover { color: #ffe6a8; }

        .site-header {
            position: sticky;
            top: 0;
            z-index: 1000;
            background: rgba(16, 16, 20, .97);
            border-bottom: 1px solid #292930;
        }

        .site-nav-inner {
            display: flex;
            align-items: center;
            gap: 28px;
            width: min(100% - 40px, 1120px);
            min-height: 62px;
            margin: 0 auto;
        }

        .site-brand {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            flex: 0 0 auto;
            color: var(--text);
            text-decoration: none;
        }

        .brand-mark {
            display: grid;
            place-items: center;
            width: 31px;
            aspect-ratio: 1;
            border: 1px solid var(--gold);
            border-radius: 50%;
            color: var(--gold-light);
            font-size: .7rem;
            font-weight: 800;
        }

        .brand-copy { font-size: .88rem; font-weight: 800; line-height: 1.05; }
        .brand-copy span { color: var(--gold); }

        .site-links {
            display: flex;
            align-items: stretch;
            align-self: stretch;
            gap: 4px;
            min-width: 0;
        }

        .site-links a {
            display: inline-flex;
            align-items: center;
            padding: 0 12px;
            border-bottom: 2px solid transparent;
            color: #b9b9c2;
            font-size: .84rem;
            font-weight: 600;
            text-decoration: none;
            white-space: nowrap;
        }

        .site-links a:hover, .site-links a.is-active {
            border-bottom-color: var(--gold);
            color: var(--text);
        }

        .site-account {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-left: auto;
            white-space: nowrap;
        }

        .site-account-name { color: #d2d2d8; font-size: .82rem; font-weight: 600; }

        .avatar {
            width: 34px;
            height: 34px;
            border: 2px solid var(--gold);
            border-radius: 50%;
            object-fit: cover;
        }

        .btn-twitch, .btn-primary {
            border: 1px solid #dca52c;
            background: var(--gold);
            color: #211a0b;
            font-weight: 800;
        }

        .btn-twitch:hover, .btn-primary:hover {
            border-color: var(--gold-light);
            background: var(--gold-light);
            color: #211a0b;
        }

        .btn-outline-light, .btn-outline-secondary {
            border-color: #4a4a54;
            color: #d4d4dc;
        }

        .btn-outline-light:hover, .btn-outline-secondary:hover {
            border-color: var(--gold);
            background: rgba(239, 182, 50, .1);
            color: var(--gold-light);
        }

        .btn-outline-primary {
            border-color: var(--gold);
            color: var(--gold-light);
        }

        .btn-outline-primary:hover {
            border-color: var(--gold);
            background: var(--gold);
            color: #211a0b;
        }

        .container { max-width: 1100px; }

        .card, .panel {
            border: 1px solid var(--line);
            border-radius: 8px;
            background: var(--surface);
            color: var(--text);
        }

        .table {
            --bs-table-bg: transparent;
            --bs-table-color: var(--text);
            --bs-table-border-color: var(--line);
            --bs-table-hover-bg: rgba(255, 255, 255, .035);
            --bs-table-hover-color: var(--text);
        }

        .table thead th { color: var(--muted); font-size: .76rem; font-weight: 700; }
        .form-label { color: #d8d8df; font-size: .87rem; font-weight: 600; }
        .form-control, .form-select {
            border-color: #41414b;
            background-color: #121216;
            color: var(--text);
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--gold);
            background-color: #121216;
            color: var(--text);
            box-shadow: 0 0 0 .2rem rgba(239, 182, 50, .14);
        }

        .form-control::placeholder { color: #777781; }
        .text-muted { color: var(--muted) !important; }
        .pagination { --bs-pagination-bg: transparent; --bs-pagination-color: #d4d4dc; --bs-pagination-border-color: var(--line); --bs-pagination-hover-bg: var(--surface-raised); --bs-pagination-hover-color: var(--gold-light); --bs-pagination-hover-border-color: var(--gold); --bs-pagination-active-bg: var(--gold); --bs-pagination-active-border-color: var(--gold); --bs-pagination-active-color: #211a0b; }

        .page-shell { width: min(100% - 40px, 1040px); margin: 0 auto; }

        .profile-banner {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
            overflow: hidden;
            min-height: 132px;
            margin: 28px auto 16px;
            padding: 22px 26px;
            border: 1px solid #3a3427;
            border-radius: 10px;
            background: linear-gradient(110deg, #1b1a1d, #17171b 70%);
        }

        .profile-banner::before {
            position: absolute;
            top: 0;
            right: 0;
            left: 0;
            height: 3px;
            background: linear-gradient(90deg, #8c641b, var(--gold-light), #8c641b);
            content: '';
        }

        .profile-identity { display: flex; align-items: center; gap: 18px; min-width: 0; }
        .profile-avatar, .profile-monogram { width: 74px; height: 74px; border: 3px solid #6d531d; border-radius: 50%; object-fit: cover; }
        .profile-monogram { display: grid; place-items: center; background: #242127; color: var(--gold-light); font-size: 1.2rem; font-weight: 800; }
        .profile-eyebrow { margin-bottom: 4px; color: var(--muted); font-size: .7rem; font-weight: 800; text-transform: uppercase; }
        .profile-title { margin: 0; color: var(--text); font-size: 1.35rem; font-weight: 800; }
        .profile-subtitle { margin: 5px 0 0; color: #aaaab4; font-size: .84rem; }
        .profile-status { display: flex; align-items: center; gap: 8px; color: #b8b8c1; font-size: .8rem; }
        .status-dot { width: 7px; height: 7px; border-radius: 50%; background: #52cc8b; box-shadow: 0 0 9px #52cc8b70; }

        .community-grid { display: grid; grid-template-columns: 190px minmax(0, 1fr); gap: 14px; margin: 0 auto 44px; }
        .community-sidebar, .community-feed { min-width: 0; border: 1px solid #2e2e36; border-radius: 9px; background: var(--surface); }
        .community-sidebar { padding: 12px; }
        .sidebar-label { margin: 4px 8px 10px; color: #777781; font-size: .67rem; font-weight: 800; text-transform: uppercase; }
        .sidebar-link { display: flex; align-items: center; min-height: 38px; padding: 0 10px; border: 1px solid transparent; border-radius: 6px; color: #bfc0c8; font-size: .82rem; font-weight: 600; text-decoration: none; }
        .sidebar-link:hover, .sidebar-link.is-active { border-color: #6b501d; background: rgba(239, 182, 50, .08); color: var(--gold-light); }
        .community-feed { padding: 19px; }
        .feed-heading { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding-bottom: 14px; border-bottom: 1px solid var(--line); }
        .feed-heading h2 { margin: 0; color: var(--text); font-size: 1.05rem; font-weight: 800; }
        .feed-count { color: var(--muted); font-size: .76rem; }
        .giveaway-row { display: flex; align-items: center; justify-content: space-between; gap: 18px; padding: 15px 12px; border-bottom: 1px solid #2d2d35; }
        .giveaway-row:last-child { border-bottom: 0; }
        .giveaway-title { margin: 0; color: #e2e2e8; font-size: .9rem; font-weight: 700; }
        .giveaway-description { margin: 4px 0 0; color: var(--muted); font-size: .78rem; }
        .giveaway-date { flex: 0 0 auto; color: #92929d; font-size: .72rem; text-align: right; }
        .empty-state { padding: 34px 14px; color: var(--muted); text-align: center; }

        .alert { border-radius: 7px; }
        footer { border-top: 1px solid #292930; color: var(--muted); }

        @media (max-width: 700px) {
            .site-nav-inner { width: calc(100% - 28px); min-height: 58px; flex-wrap: wrap; gap: 0 12px; padding-top: 9px; }
            .site-links { order: 3; width: 100%; height: 39px; overflow-x: auto; }
            .site-links a { padding: 0 10px; font-size: .78rem; }
            .site-account { gap: 7px; }
            .site-account-name { display: none; }
            .page-shell { width: calc(100% - 28px); }
            .profile-banner { align-items: flex-start; flex-direction: column; padding: 19px; }
            .profile-avatar, .profile-monogram { width: 60px; height: 60px; }
            .community-grid { grid-template-columns: 1fr; }
            .community-sidebar { display: flex; align-items: center; gap: 5px; overflow-x: auto; }
            .sidebar-label { display: none; }
            .sidebar-link { flex: 0 0 auto; }
            .community-feed { padding: 14px; }
            .giveaway-row { align-items: flex-start; flex-direction: column; gap: 7px; }
            .giveaway-date { text-align: left; }
        }

        @media (prefers-reduced-motion: no-preference) {
            .profile-banner, .community-feed { animation: surface-in .35s ease-out both; }
            .community-feed { animation-delay: .06s; }
            @keyframes surface-in { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
        }
    </style>
</head>

<body>
    <header class="site-header">
        <div class="site-nav-inner">
            <a class="site-brand" href="{{ route('home') }}">
                <span class="brand-mark">LM</span>
                <span class="brand-copy">LETHAL<br><span>MYKE</span></span>
            </a>

            <nav class="site-links" aria-label="Navegação principal">
                <a href="{{ route('home') }}" @class(['is-active' => request()->routeIs('home', 'home.page')])>Início</a>
                <a href="{{ route('home') }}#sorteios">Sorteios</a>
                @auth
                    @if (auth()->user()->role === 'admin')
                        <a href="{{ route('admin.dashboard') }}" @class(['is-active' => request()->routeIs('admin.*')])>Painel</a>
                    @endif
                @endauth
            </nav>

            <div class="site-account">
                @auth
                    @if (auth()->user()->twitch_avatar)
                        <img
                            class="avatar"
                            src="{{ auth()->user()->twitch_avatar }}"
                            alt="Avatar Twitch"
                        >
                    @endif

                    <span class="site-account-name">
                        {{ auth()->user()->twitch_username
                            ?: auth()->user()->name }}
                    </span>

                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button class="btn btn-outline-light btn-sm" aria-label="Sair da conta">
                            Sair
                        </button>
                    </form>
                @else
                    <a
                        href="{{ route('twitch.redirect') }}"
                        class="btn btn-twitch btn-sm"
                    >
                        Entrar com Twitch
                    </a>
                @endauth
            </div>
        </div>
    </header>

    <main>
        @if (session('success'))
            <div class="container mt-4">
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            </div>
        @endif

        @if (session('error'))
            <div class="container mt-4">
                <div class="alert alert-danger">
                    {{ session('error') }}
                </div>
            </div>
        @endif

        @yield('content')
    </main>

    <footer class="py-4 mt-5">
        <div class="container text-center small">
            Lethal Myke Giveaways · Feito para a comunidade
        </div>
    </footer>
</body>
</html>
