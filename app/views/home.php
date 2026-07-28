<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= $_SESSION['csrf_token'] ?>">
    <title>ChessNova — Play Chess and Checkers Online</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" sizes="64x64" href="./images/favicon.png">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --red:        rgba(255, 0, 0, 0.85);
            --red-dim:    rgba(242, 37, 37, 0.52);
            --red-glow:   rgba(240, 1, 1, 0.22);
            --bg-deep:    #080b10;
            --bg-mid:     #0f1218;
            --bg-surface: rgba(255,255,255,0.04);
            --border:     rgba(255,255,255,0.07);
            --text:       #ffffff;
            --text-muted: rgba(255,255,255,0.55);
            --sq-light:   #e8cfa0;
            --sq-dark:    #7a4828;
            --dama-board-light: #f0d9b5;
            --dama-board-dark:  #b58863;
            --radius:     18px;
            --font-head:  'Syne', sans-serif;
            --font-body:  'DM Sans', sans-serif;
        }

        html { scroll-behavior: smooth; }

        body {
            font-family: var(--font-body);
            background: var(--bg-deep);
            color: var(--text);
            min-height: 100vh;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: fixed; inset: 0;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.04'/%3E%3C/svg%3E");
            background-size: 200px;
            pointer-events: none; z-index: 0;
        }

        .ambient {
            position: fixed; top: -200px; left: 50%;
            transform: translateX(-50%);
            width: 900px; height: 600px;
            background: radial-gradient(ellipse, rgba(201,93,93,0.09) 0%, transparent 65%);
            pointer-events: none; z-index: 0;
        }

        header {
            position: sticky; top: 0; z-index: 100;
            display: flex; justify-content: space-between; align-items: center;
            padding: 0 48px; height: 72px;
            background: rgba(8,11,16,0.82);
            backdrop-filter: blur(20px) saturate(150%);
            border-bottom: 1px solid var(--border);
        }

        .logo {
            font-family: var(--font-head);
            font-size: 1.35rem; font-weight: 700;
            color: var(--red); text-decoration: none;
            display: flex; align-items: center; gap: 10px;
        }
        .logo-icon {
            width: 32px; height: 32px; border-radius: 8px;
            overflow: hidden; display: flex; align-items: center; justify-content: center;
        }
        .logo-icon img { width: 100%; height: 100%; object-fit: cover; display: block; }

        nav { display: flex; align-items: center; gap: 8px; }

        .nav-link {
            text-decoration: none; color: var(--text-muted);
            font-size: 0.9rem; font-weight: 500;
            padding: 8px 14px; border-radius: 10px;
            transition: color 0.2s, background 0.2s;
        }
        .nav-link:hover { color: var(--text); background: var(--bg-surface); }

        .nav-avatar {
            display: flex; align-items: center; gap: 10px;
            text-decoration: none; color: var(--text);
            font-size: 0.88rem; font-weight: 500;
            padding: 6px 6px 6px 14px; border-radius: 40px;
            border: 1px solid var(--border); background: var(--bg-surface);
            transition: border-color 0.2s, background 0.2s;
        }
        .nav-avatar:hover { border-color: var(--red-dim); background: rgba(255,0,0,0.1); }
        .nav-avatar-img {
            width: 36px; height: 36px; border-radius: 50%;
            object-fit: cover; border: 1.5px solid var(--red-dim); display: block;
        }

        .hero {
            position: relative; z-index: 1;
            min-height: calc(100vh - 72px);
            display: grid; grid-template-columns: 1fr 1fr;
            align-items: center; gap: 60px;
            padding: 60px 80px;
            max-width: 1280px; margin: 0 auto;
        }

        .hero-text { display: flex; flex-direction: column; align-items: flex-start; }

        .eyebrow {
            display: inline-flex; align-items: center; gap: 8px;
            font-size: 0.78rem; font-weight: 600;
            letter-spacing: 0.08em; text-transform: uppercase;
            color: var(--red);
            background: rgba(255,0,0,0.1); border: 1px solid var(--red-dim);
            border-radius: 40px; padding: 6px 14px; margin-bottom: 28px;
        }
        .eyebrow-dot {
            width: 6px; height: 6px; border-radius: 50%;
            background: var(--red);
            animation: pulse 2s ease-in-out infinite;
        }
        @keyframes pulse {
            0%,100% { opacity:1; transform:scale(1); }
            50%      { opacity:0.5; transform:scale(0.7); }
        }

        .hero-title {
            font-family: var(--font-head);
            font-size: clamp(2.2rem, 4.2vw, 3.6rem);
            font-weight: 700; line-height: 1.15; letter-spacing: -0.5px;
            margin-bottom: 22px;
        }
        .hero-title span { color: var(--red); }

        .hero-sub {
            font-size: 1.05rem; line-height: 1.65;
            color: var(--text-muted); max-width: 460px; margin-bottom: 42px;
        }

        .stats-strip {
            display: flex; gap: 32px;
            margin-bottom: 44px; padding-bottom: 38px;
            border-bottom: 1px solid var(--border); width: 100%;
        }
        .stat { display: flex; flex-direction: column; gap: 2px; }
        .stat-num {
            font-family: var(--font-head); font-size: 1.4rem; font-weight: 700;
        }
        .stat-label {
            font-size: 0.78rem; color: var(--text-muted);
            font-weight: 500; letter-spacing: 0.04em;
        }

        .cta-row { display: flex; gap: 12px; flex-wrap: wrap; }

        .btn {
            display: inline-flex; align-items: center; gap: 9px;
            padding: 15px 26px; border: none; border-radius: 14px;
            font-family: var(--font-body); font-size: 0.95rem; font-weight: 600;
            cursor: pointer; text-decoration: none;
            transition: transform 0.18s ease, box-shadow 0.18s ease;
        }
        .btn:hover  { transform: translateY(-2px); }
        .btn:active { transform: translateY(0); }

        .btn-play {
            background: var(--red); color: #0d1a06;
            box-shadow: 0 8px 28px var(--red-glow);
        }
        .btn-play:hover { box-shadow: 0 14px 36px var(--red-dim); }

        .btn-secondary {
            background: var(--bg-surface); color: var(--text);
            border: 1px solid var(--border);
        }
        .btn-secondary:hover { background: rgba(255,255,255,0.07); }

        .board-side { display: flex; justify-content: center; align-items: center; }

        .board-wrap {
            position: relative;
            filter: drop-shadow(0 40px 80px rgba(0,0,0,0.7));
        }
        .board-wrap::before {
            content: ''; position: absolute; inset: -50px;
            background: radial-gradient(circle, rgba(141,201,93,0.15), transparent 65%);
            pointer-events: none; z-index: 0;
        }

        .board {
            position: relative; z-index: 1;
            width: 420px; height: 420px;
            display: grid; grid-template-columns: repeat(8, 1fr);
            border-radius: 20px; overflow: hidden;
            border: 4px solid rgba(255,255,255,0.08);
            animation: float 6s ease-in-out infinite;
        }
        
        .sq {
            width: 100%; aspect-ratio: 1;
            display: flex; align-items: center; justify-content: center;
            position: relative;
        }
        .sq.l { background: var(--sq-light); }
        .sq.d { background: var(--sq-dark); }

        .board-label {
            position: absolute; z-index: 2;
            font-family: var(--font-head); font-size: 0.65rem;
            font-weight: 700; opacity: 0.45; color: #fff;
        }
        .board-label.tl { top: 8px; left: 10px; }
        .board-label.br { bottom: 8px; right: 10px; }

        .piece-img {
            width: 78%; height: 78%; object-fit: contain;
            pointer-events: none; user-select: none;
            filter: drop-shadow(0 4px 6px rgba(0,0,0,0.4));
            transition: transform 0.2s ease;
        }
        .sq:hover .piece-img { transform: scale(1.06); }

        /* SEZIONE DAMA ITALIANA */
        .dama-section {
            position: relative; z-index: 1;
            max-width: 1280px; margin: 60px auto 80px;
            padding: 0 80px;
        }

        .dama-header {
            display: flex; align-items: center; gap: 16px;
            margin-bottom: 28px;
        }

        .dama-title {
            font-family: var(--font-head);
            font-size: clamp(1.6rem, 2.5vw, 2.2rem);
            font-weight: 700;
            color: var(--text);
        }

        .dama-badge {
            font-size: 0.7rem; font-weight: 600;
            letter-spacing: 0.07em; text-transform: uppercase;
            padding: 5px 14px; border-radius: 40px;
            background: rgba(255,0,0,0.12);
            color: var(--red);
            border: 1px solid var(--red-dim);
            margin-top: 4px;
            animation: badgePulse 2.5s ease-in-out infinite;
        }

        

        .dama-feature-card {
            display: flex;
            gap: 40px;
            background: var(--bg-surface);
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 36px;
            backdrop-filter: blur(12px);
            transition: border-color 0.3s, box-shadow 0.3s;
            align-items: center;
            position: relative;
            overflow: hidden;
        }

        .dama-feature-card:hover {
            border-color: var(--red-dim);
            box-shadow: 0 16px 48px rgba(255,0,0,0.15);
        }

        .dama-feature-card::after {
            content: '';
            position: absolute;
            top: -40px; right: -40px;
            width: 200px; height: 200px;
            background: radial-gradient(circle, rgba(255,0,0,0.08) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }

        .dama-info {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 16px;
            z-index: 1;
        }

        .dama-info .dama-card-title {
            font-family: var(--font-head);
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .dama-info .dama-icon {
            width: 48px; height: 48px;
            border-radius: 14px;
            background: rgba(255,0,0,0.08);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.8rem;
        }

        .dama-desc {
            font-size: 0.95rem;
            color: var(--text-muted);
            line-height: 1.6;
            max-width: 480px;
        }

        .dama-rules-list {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin: 8px 0 4px;
        }

        .dama-rules-list li {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.88rem;
            color: var(--text-muted);
        }

        .dama-rules-list li::before {
            content: '';
            width: 6px; height: 6px;
            border-radius: 50%;
            background: var(--red);
            flex-shrink: 0;
            opacity: 0.8;
        }

        .dama-cta-row {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 8px;
        }

        .dama-board-preview {
            flex-shrink: 0;
            z-index: 1;
        }

        .dama-mini-board {
            width: 220px;
            height: 220px;
            display: grid;
            grid-template-columns: repeat(8, 1fr);
            border-radius: 14px;
            overflow: hidden;
            border: 3px solid rgba(255,255,255,0.1);
            box-shadow: 0 20px 40px rgba(0,0,0,0.5);
            animation: floatDama 5s ease-in-out infinite;
        }

        

        .dama-sq {
            aspect-ratio: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }
        .dama-sq.light { background: var(--dama-board-light); }
        .dama-sq.dark  { background: var(--dama-board-dark); }

        .dama-piece-img {
            width: 72%;
            height: 72%;
            object-fit: contain;
            pointer-events: none;
            user-select: none;
            filter: drop-shadow(0 4px 6px rgba(0,0,0,0.55));
            transition: transform 0.2s ease;
            position: absolute;
        }

        .dama-sq:hover .dama-piece-img {
            transform: scale(1.08);
        }

        @media (max-width: 1050px) {
            .hero { grid-template-columns: 1fr; text-align: center; padding: 50px 40px; gap: 50px; }
            .hero-text { align-items: center; }
            .hero-sub { max-width: 100%; }
            .stats-strip { justify-content: center; }
            .board { width: 340px; height: 340px; }
            .dama-section { padding: 0 40px; }
            .dama-feature-card { flex-direction: column; text-align: center; }
            .dama-info { align-items: center; }
            .dama-rules-list { align-items: flex-start; }
            .dama-mini-board { width: 180px; height: 180px; }
        }
        @media (max-width: 640px) {
            header { padding: 0 20px; }
            .logo span { display: none; }
            .hero { padding: 36px 20px; }
            .hero-title { font-size: 2.2rem; }
            .board { width: 280px; height: 280px; }
            .btn { width: 100%; justify-content: center; }
            .cta-row { flex-direction: column; width: 100%; }
            .dama-section { padding: 0 20px; margin-top: 50px; }
            .dama-feature-card { padding: 24px; }
            .dama-mini-board { width: 160px; height: 160px; }
        }
    </style>
</head>

<body>

    <div class="ambient"></div>

    <header>
        <a class="logo" href="home">
            <div class="logo-icon">
                <img src="./images/favicon.png" alt="ChessNova logo">
            </div>
            <span>ChessNova</span>
        </a>
        <nav>
            
            <?php
                $avatarSrc = !empty($informazioni['avatar'])
                    ? './public/' . htmlspecialchars($informazioni['avatar'])
                    : './public/assets/img/redking.jpg';
            ?>
            <a class="nav-avatar" href="profile">
                <?= htmlspecialchars($_SESSION['username']) ?>
                <img class="nav-avatar-img"
                     src="<?= $avatarSrc ?>"
                     alt="Avatar di <?= htmlspecialchars($_SESSION['username']) ?>">
            </a>
        </nav>
    </header>

    <main class="hero">
        <div class="hero-text">
            <h1 class="hero-title">
                Play with players from<br>
                <span>all over the world.</span>
            </h1>
            <p class="hero-sub">
                Challenge real players, climb the leaderboard, become the best.
            </p>
            <div class="stats-strip">
                <div class="stat">
                    <span class="stat-num"><?= $online ?></span>
                    <span class="stat-label">Active players</span>
                </div>
                <div class="stat">
                    <span class="stat-num"><?= $partite ?></span>
                    <span class="stat-label">Matches played</span>
                </div>
            </div>
            <div class="cta-row">
                <a class="btn btn-play" href="./timecontrol?tipo=1">
                    ♟ Play
                </a>
                <a class="btn btn-secondary" href="learn">
                    Coming soon
                </a>
            </div>
        </div>

        <div class="board-side">
            <div class="board-wrap">
                <div class="board" id="board">
                    <span class="board-label tl">a8</span>
                    <span class="board-label br">h1</span>
                </div>
            </div>
        </div>
    </main>

    <section class="dama-section">
        <div class="dama-header">
            <h2 class="dama-title">Checkers</h2>
            <span class="dama-badge">Play now</span>
        </div>

        <div class="dama-feature-card">
            <div class="dama-info">
                <div class="dama-card-title">
                    Traditional 8x8
                </div>
                
                <ul class="dama-rules-list">
                    <li>Pieces that move forward only</li>
                    <li>Kings that can also move backward</li>
                    <li>Mandatory captures and capture priority</li>
                    <li>8×8 board with dark and light pieces</li>
                </ul>
                <div class="dama-cta-row">
                    <a class="btn btn-play" href="./timecontrol?tipo=0">
                        Play Checkers
                    </a>
                    
                </div>
            </div>

            <div class="dama-board-preview">
                <div class="dama-mini-board" id="damaMiniBoard" aria-label="Anteprima tavoliere dama italiana"></div>
            </div>
        </div>
    </section>

    <script>
        // --- Scacchiera scacchi ---
        const pieces = {
            0:  "DarkRook",   1: "DarkKnight",  2: "DarkBishop", 3: "DarkQueen",
            4:  "DarkKing",   5: "DarkBishop",  6: "DarkKnight", 7: "DarkRook",
            8:  "DarkPawn",   9: "DarkPawn",   10: "DarkPawn",  11: "DarkPawn",
            12: "DarkPawn",  13: "DarkPawn",   14: "DarkPawn",  15: "DarkPawn",
            48: "LightPawn",  49: "LightPawn",  50: "LightPawn", 51: "LightPawn",
            52: "LightPawn",  53: "LightPawn",  54: "LightPawn", 55: "LightPawn",
            56: "LightRook",  57: "LightKnight",58: "LightBishop",59: "LightQueen",
            60: "LightKing",  61: "LightBishop",62: "LightKnight",63: "LightRook"
        };

        const board = document.getElementById('board');
        for (let i = 0; i < 64; i++) {
            const sq = document.createElement('div');
            sq.className = 'sq ' + ((i + Math.floor(i / 8)) % 2 === 0 ? 'l' : 'd');
            if (pieces[i]) {
                const img = document.createElement('img');
                img.src = `./images/${pieces[i]}.webp`;
                img.className = 'piece-img';
                img.draggable = false;
                img.alt = pieces[i];
                sq.appendChild(img);
            }
            board.appendChild(sq);
        }

        // --- Mini board Dama Italiana (usa le tue immagini) ---
        (function buildDamaBoard() {
            const damaBoard = document.getElementById('damaMiniBoard');
            if (!damaBoard) return;

            // Posizione iniziale dama italiana
            const whiteRows = [0,1,2];
            const blackRows = [5,6,7];
            const pieceMap = {};

            for (let row = 0; row < 8; row++) {
                for (let col = 0; col < 8; col++) {
                    const isDark = (row + col) % 2 !== 0;
                    if (!isDark) continue;
                    if (whiteRows.includes(row)) {
                        pieceMap[`${row}-${col}`] = 'white';
                    } else if (blackRows.includes(row)) {
                        pieceMap[`${row}-${col}`] = 'black';
                    }
                }
            }

            for (let row = 0; row < 8; row++) {
                for (let col = 0; col < 8; col++) {
                    const sq = document.createElement('div');
                    const isLight = (row + col) % 2 === 0;
                    sq.className = 'dama-sq ' + (isLight ? 'light' : 'dark');
                    const key = `${row}-${col}`;
                    if (pieceMap[key]) {
                        const img = document.createElement('img');
                        img.src = pieceMap[key] === 'white' 
                            ? './images/damaPiccolaBianca.png' 
                            : './images/damaPiccolaNera.png';
                        img.className = 'dama-piece-img';
                        img.draggable = false;
                        img.alt = pieceMap[key] === 'white' ? 'Pedina bianca' : 'Pedina nera';
                        sq.appendChild(img);
                    }
                    damaBoard.appendChild(sq);
                }
            }
        })();

        // Heartbeat
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

        function sendHeartbeat() {
            fetch('/heartbeat', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'X-CSRF-Token': csrfToken
                }
            })
            .catch(err => console.warn('Heartbeat failed:', err));
        }

        // Avvia il heartbeat periodico
        setInterval(sendHeartbeat, 30000);
        sendHeartbeat(); // primo invio
    </script>

</body>
</html>