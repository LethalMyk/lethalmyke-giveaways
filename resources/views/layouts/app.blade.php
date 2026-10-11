
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>@yield('title', 'Lethal Myke Giveaways')</title>

    <meta name="theme-color" content="#0b0912">

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <style>
        :root {
            --bg: #0b0912;
            --panel: #15121f;
            --purple: #9146ff;
            --pink: #d946ef;
            --text: #f5f3ff;
            --muted: #aaa3bd;
        }

        body {
            background:
                radial-gradient(ellipse at 50% 0%, #291344 0%, transparent 48%),
                var(--bg);
            color: var(--text);
            min-height: 100vh;
            font-family: Arial, sans-serif;
        }

        .navbar {
            background: rgba(11, 9, 18, .92);
            border-bottom: 1px solid #30223e;
        }

        .brand {
            font-weight: 900;
            letter-spacing: 1px;
            color: white;
        }

        .brand span {
            color: var(--purple);
        }

        .hero {
            padding: 100px 0 70px;
            text-align: center;
        }

        .hero h1 {
            font-size: clamp(2.4rem, 6vw, 4.8rem);
            font-weight: 900;
            line-height: 1.05;
        }

        .neon {
            color: #c49aff;
            text-shadow: 0 0 28px #9146ff70;
        }

        .muted {
            color: var(--muted);
        }

        .panel {
            background: linear-gradient(145deg, #191425, #100e18);
            border: 1px solid #39274d;
            border-radius: 18px;
            padding: 28px;
        }

        .btn-twitch {
            background: var(--purple);
            color: white;
            font-weight: 700;
            border: 0;
            padding: 13px 22px;
            border-radius: 10px;
        }

        .btn-twitch:hover {
            background: #a66aff;
            color: white;
            box-shadow: 0 0 24px #9146ff50;
        }

        .avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--purple);
        }

        footer {
            border-top: 1px solid #30223e;
            color: var(--muted);
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container py-2">
            <a class="navbar-brand brand" href="{{ route('home') }}">
                LETHAL <span>MYKE</span>
            </a>

            <div class="d-flex align-items-center gap-3">
                @auth
                    @if (auth()->user()->twitch_avatar)
                        <img
                            class="avatar"
                            src="{{ auth()->user()->twitch_avatar }}"
                            alt="Avatar Twitch"
                        >
                    @endif

                    <span class="d-none d-md-inline">
                        {{ auth()->user()->twitch_username
                            ?: auth()->user()->name }}
                    </span>

                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button class="btn btn-outline-light btn-sm">
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
    </nav>

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
