<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Profile - ChessNova</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="icon" type="image/png" sizes="64x64" href="/images/favicon.png">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
    --red:        rgba(255, 0, 0, 0.85);
    --red-dim:    rgba(242, 37, 37, 0.52);
    --red-glow:   rgba(240, 1, 1, 0.22);
    --bg-deep:    #080b10;
    --bg-card:    rgba(255,255,255,0.03);
    --bg-surface: rgba(255,255,255,0.04);
    --border:     rgba(255,255,255,0.07);
    --text:       #ffffff;
    --text-muted: rgba(255,255,255,0.55);
    --win:        #5dde8a;
    --win-bg:     rgba(93,222,138,0.12);
    --loss:       rgba(255,127,127,1);
    --loss-bg:    rgba(255,100,100,0.12);
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
    position: fixed; top: -180px; left: 50%;
    transform: translateX(-50%);
    width: 800px; height: 500px;
    background: radial-gradient(ellipse, rgba(201,93,93,0.07) 0%, transparent 65%);
    pointer-events: none; z-index: 0;
}

/* ── HEADER ── */
header {
    position: sticky; top: 0; z-index: 100;
    display: flex; justify-content: space-between; align-items: center;
    padding: 0 48px; height: 72px;
    background: rgba(8,11,16,0.85);
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
    font-size: 0.88rem; font-weight: 500;
    padding: 8px 14px; border-radius: 10px;
    transition: color .2s, background .2s;
}
.nav-link:hover { color: var(--text); background: var(--bg-surface); }
.nav-logout {
    text-decoration: none; font-size: 0.88rem; font-weight: 600;
    color: var(--loss); padding: 8px 16px; border-radius: 10px;
    border: 1px solid rgba(255,127,127,0.2);
    background: rgba(255,70,70,0.06);
    transition: background .2s, border-color .2s;
}
.nav-logout:hover { background: rgba(255,70,70,0.12); border-color: rgba(255,127,127,0.4); }

/* ── PAGE ── */
.page {
    position: relative; z-index: 1;
    max-width: 1300px; margin: 0 auto;
    padding: 48px 40px;
    display: grid;
    grid-template-columns: 300px 1fr;
    gap: 28px;
    align-items: start;
}

/* ── SIDEBAR ── */
.sidebar {
    display: flex; flex-direction: column; gap: 20px;
    position: sticky; top: 96px;
}

.profile-card {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: 24px; padding: 32px 28px;
    backdrop-filter: blur(12px);
    box-shadow: 0 30px 60px rgba(0,0,0,0.5);
    text-align: center;
}
.avatar-wrap {
    position: relative; width: 100px; height: 100px;
    margin: 0 auto 20px;
}
.avatar-wrap img {
    width: 100%; height: 100%; border-radius: 50%;
    object-fit: cover; display: block;
    border: 2.5px solid rgba(201,93,93,0.4);
    box-shadow: 0 0 28px rgba(201,93,93,0.25);
}
.avatar-online {
    position: absolute; bottom: 4px; right: 4px;
    width: 14px; height: 14px; border-radius: 50%;
    background: var(--red); border: 2px solid var(--bg-deep);
    box-shadow: 0 0 8px rgba(201,93,93,0.6);
}
.profile-username {
    font-family: var(--font-head);
    font-size: 1.5rem; font-weight: 700;
    letter-spacing: -0.3px; margin-bottom: 14px;
}

.profile-elo {
    display: inline-flex; align-items: center; gap: 8px;
    font-size: 0.88rem; font-weight: 600;
    color: var(--red);
    background: rgba(201,93,93,0.1);
    border: 1px solid rgba(201,93,93,0.22);
    border-radius: 30px; padding: 6px 16px;
    margin-bottom: 20px;
    transition: all 0.25s ease;
}
.elo-dot { width: 6px; height: 6px; border-radius: 50%; background: var(--red); }
.elo-mode-label { font-size: 0.75rem; opacity: 0.7; font-weight: 500; }

.profile-bio {
    font-size: 0.9rem; line-height: 1.7;
    color: var(--text-muted); margin-bottom: 24px; min-height: 40px;
}
.divider { height: 1px; background: var(--border); margin-bottom: 24px; }
.edit-btn {
    display: block; width: 100%; padding: 13px;
    border: none; border-radius: 14px;
    font-family: var(--font-body); font-size: 0.9rem; font-weight: 700;
    cursor: pointer;
    background: linear-gradient(145deg, var(--red), var(--red-dim));
    color: var(--text);
    box-shadow: 0 8px 22px rgba(201,93,93,0.28);
    transition: transform .18s, box-shadow .18s;
    text-decoration: none; text-align: center;
}
.edit-btn:hover { transform: translateY(-2px); box-shadow: 0 12px 28px rgba(201,93,93,0.42); }

/* ── WINRATE RING ── */
.winrate-ring-wrap {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: 20px; padding: 24px;
    backdrop-filter: blur(12px);
    display: flex; align-items: center; gap: 20px;
}
.ring-bg   { fill: none; stroke: rgba(255,255,255,0.06); stroke-width: 8; }
.ring-fill {
    fill: none; stroke: var(--red); stroke-width: 8;
    stroke-linecap: round;
    stroke-dasharray: 226.2;
    stroke-dashoffset: calc(226.2 - (226.2 * var(--pct) / 100));
    transform: rotate(-90deg); transform-origin: 50% 50%;
    transition: stroke-dashoffset .6s ease;
}
.ring-wrap-inner { position: relative; width: 88px; height: 88px; flex-shrink: 0; }
.ring-wrap-inner svg { display: block; }
.ring-center {
    position: absolute; inset: 0;
    display: flex; align-items: center; justify-content: center;
    font-family: var(--font-head); font-size: 1rem; font-weight: 700;
}
.ring-meta { flex: 1; }
.ring-meta-title {
    font-size: 0.75rem; font-weight: 600;
    letter-spacing: 0.08em; text-transform: uppercase;
    color: var(--text-muted); margin-bottom: 8px;
}
.ring-meta-row {
    display: flex; justify-content: space-between;
    font-size: 0.82rem; margin-bottom: 4px;
}
.ring-meta-row span:last-child { font-weight: 600; }

/* ── MAIN ── */
.content { display: flex; flex-direction: column; gap: 24px; }

/* ── GAME TOGGLE ── */
.game-toggle {
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 4px;
}
.game-toggle-inner {
    display: inline-flex;
    align-items: center;
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: 16px;
    padding: 5px;
    gap: 4px;
    position: relative;
}
.toggle-pill {
    position: absolute;
    top: 5px; left: 5px;
    height: calc(100% - 10px);
    width: calc(50% - 7px);
    background: linear-gradient(145deg, var(--red), var(--red-dim));
    border-radius: 11px;
    transition: transform 0.28s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 0 18px var(--red-glow);
    pointer-events: none;
}
.toggle-pill.right { transform: translateX(calc(100% + 4px)); }
.toggle-option {
    position: relative; z-index: 1;
    display: flex; align-items: center; gap: 7px;
    padding: 10px 22px; border-radius: 11px;
    cursor: pointer;
    font-family: var(--font-head);
    font-size: 0.85rem; font-weight: 700;
    color: var(--text-muted);
    transition: color 0.25s;
    user-select: none; white-space: nowrap;
}
.toggle-option.active { color: var(--text); }
.toggle-icon {
    font-size: 1rem; transition: transform 0.25s;
    display: inline-block;
}
.toggle-option.active .toggle-icon { transform: scale(1.15); }

/* ── MODE TABS ── */
.mode-tabs {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 10px;
}
.mode-tab {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: 16px;
    padding: 16px 12px;
    cursor: pointer;
    text-align: center;
    transition: border-color .2s, background .2s, transform .18s;
    position: relative; overflow: hidden;
}
.mode-tab::after {
    content: '';
    position: absolute; bottom: 0; left: 0; right: 0;
    height: 2px;
    background: linear-gradient(90deg, transparent, var(--red), transparent);
    opacity: 0; transition: opacity .2s;
}
.mode-tab:hover { border-color: var(--red-dim); transform: translateY(-2px); }
.mode-tab:hover::after { opacity: 0.6; }
.mode-tab.active {
    border-color: var(--red);
    background: rgba(255,0,0,0.07);
    box-shadow: 0 0 0 1px var(--red-dim), 0 8px 24px var(--red-glow);
}
.mode-tab.active::after { opacity: 1; }
.mode-tab-icon { font-size: 1.4rem; margin-bottom: 6px; display: block; }
.mode-tab-name {
    font-family: var(--font-head);
    font-size: 0.82rem; font-weight: 700;
    color: var(--text-muted);
    transition: color .2s;
}
.mode-tab.active .mode-tab-name { color: var(--text); }
.mode-tab-elo {
    font-family: var(--font-head);
    font-size: 1.15rem; font-weight: 700;
    color: var(--text); margin-top: 4px;
    transition: color .2s;
}
.mode-tab.active .mode-tab-elo { color: var(--red); }

/* ── STATS STRIP ── */
.stats-strip {
    display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px;
}
.stat-card {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: 20px; padding: 22px 20px;
    backdrop-filter: blur(10px);
    box-shadow: 0 16px 32px rgba(0,0,0,0.3);
    transition: transform .18s, border-color .18s;
    position: relative; overflow: hidden;
}
.stat-card:hover { transform: translateY(-3px); border-color: rgba(201,93,93,0.2); }
.stat-card::after {
    content: ''; position: absolute; bottom: 0; left: 0; right: 0;
    height: 2px;
    background: linear-gradient(90deg, transparent, var(--red), transparent);
    opacity: 0; transition: opacity .2s;
}
.stat-card:hover::after { opacity: 1; }
.stat-icon { font-size: 1.3rem; margin-bottom: 10px; }
.stat-label {
    font-size: 0.75rem; font-weight: 600;
    letter-spacing: 0.08em; text-transform: uppercase;
    color: var(--text-muted); margin-bottom: 8px;
}
.stat-value {
    font-family: var(--font-head);
    font-size: 2rem; font-weight: 700; line-height: 1;
    transition: all 0.25s ease;
}

/* ── PANEL ── */
.panel {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: 24px; padding: 28px;
    backdrop-filter: blur(10px);
    box-shadow: 0 20px 40px rgba(0,0,0,0.35);
}
.panel-header {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 20px;
}
.panel-title {
    font-family: var(--font-head);
    font-size: 1.15rem; font-weight: 700;
    letter-spacing: -0.2px;
    display: flex; align-items: center; gap: 10px;
}
.panel-title-icon {
    width: 28px; height: 28px;
    background: rgba(201,93,93,0.12);
    border: 1px solid rgba(201,93,93,0.22);
    border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.9rem;
}
.panel-mode-badge {
    font-size: 0.75rem; font-weight: 600;
    letter-spacing: 0.08em; text-transform: uppercase;
    color: var(--red);
    background: rgba(255,0,0,0.1);
    border: 1px solid var(--red-dim);
    border-radius: 30px; padding: 4px 12px;
}

.matches { display: flex; flex-direction: column; gap: 10px; min-height: 60px; }

.match {
    display: flex; justify-content: space-between; align-items: center;
    padding: 16px 18px; border-radius: 14px;
    background: var(--bg-surface);
    border: 1px solid transparent;
    transition: background .15s, border-color .15s, opacity .2s, transform .2s;
}
.match:hover { background: rgba(255,255,255,0.06); border-color: var(--border); }

.match-left { display: flex; flex-direction: column; gap: 4px; }
.match-vs {
    font-size: 0.7rem; font-weight: 600;
    letter-spacing: 0.1em; text-transform: uppercase;
    color: var(--text-muted);
}
.match-opponent { font-size: 0.98rem; font-weight: 600; }
.match-meta { display: flex; align-items: center; gap: 10px; }
.match-color {
    font-size: 0.8rem; color: var(--text-muted);
    display: flex; align-items: center; gap: 5px;
}
.color-dot {
    width: 8px; height: 8px; border-radius: 50%;
    border: 1.5px solid rgba(255,255,255,0.3);
}
.color-dot.white { background: #f0d9b5; }
.color-dot.black { background: #4a3728; }
.match-tc {
    font-size: 0.75rem; color: var(--text-muted);
    background: rgba(255,255,255,0.05);
    padding: 2px 7px; border-radius: 6px;
}
.match-result {
    font-size: 0.82rem; font-weight: 700;
    padding: 7px 14px; border-radius: 10px;
    letter-spacing: 0.03em;
}
.match-result.win  { background: var(--win-bg);  color: var(--win); }
.match-result.loss { background: var(--loss-bg); color: var(--loss); }
.match-result.draw { background: rgba(255,255,255,0.07); color: rgba(255,255,255,0.7); }

.matches.fading .match { opacity: 0; transform: translateY(4px); }

.empty-state {
    text-align: center; padding: 40px 20px;
    color: var(--text-muted); font-size: 0.9rem;
}
.empty-state-icon { font-size: 2rem; margin-bottom: 10px; }

/* ── RESPONSIVE ── */
@media (max-width: 1100px) {
    .page { grid-template-columns: 1fr; }
    .sidebar { position: static; }
}
@media (max-width: 700px) {
    .mode-tabs { grid-template-columns: repeat(2, 1fr); }
    .stats-strip { grid-template-columns: repeat(3, 1fr); }
}
@media (max-width: 640px) {
    header { padding: 0 20px; }
    .page { padding: 24px 16px; gap: 16px; }
    .stats-strip { grid-template-columns: repeat(3, 1fr); gap: 10px; }
    .stat-value { font-size: 1.5rem; }
    .match { flex-direction: column; gap: 10px; align-items: flex-start; }
    .toggle-option { padding: 10px 16px; }
}
</style>
</head>
<body>

<div class="ambient"></div>

<header>
    <a class="logo" href="./home">
        <div class="logo-icon"><img src="./images/favicon.png" alt="ChessNova logo"></div>
        ChessNova
    </a>
    <nav>
        <a class="nav-link" href="./home">Home</a>
        <a class="nav-logout" href="./logout">Logout</a>
    </nav>
</header>

<?php
$eloData = [
    'bullet'    => (int)($informazioni['eloBullet']    ?? 1000),
    'blitz'     => (int)($informazioni['eloBlitz']     ?? 1000),
    'rapid'     => (int)($informazioni['elo']          ?? 1000),
    'classical' => (int)($informazioni['eloClassical'] ?? 1000),
];

$eloDataDama = [
    'bullet'    => (int)($informazioni['checkersBullet']    ?? 1200),
    'blitz'     => (int)($informazioni['checkersBlitz']     ?? 1150),
    'rapid'     => (int)($informazioni['checkersRapid']     ?? 1100),
    'classical' => (int)($informazioni['checkersClassical'] ?? 1050),
];

$gamesByModeDama = $gamesByModeDama ?? [
    'bullet'    => [],
    'blitz'     => [],
    'rapid'     => [],
    'classical' => []
];
?>

<div class="page">

    <!-- ── SIDEBAR ── -->
    <aside class="sidebar">

        <div class="profile-card">
            <?php
                $avatarSrc = !empty($informazioni['avatar'])
                    ? '/public/' . htmlspecialchars($informazioni['avatar'])
                    : '/public/assets/img/redking.jpg';
            ?>
            <div class="avatar-wrap">
                <img src="<?= $avatarSrc ?>" alt="Avatar">
                <span class="avatar-online"></span>
            </div>

            <div class="profile-username"><?= htmlspecialchars($_SESSION['username']) ?></div>

            <div class="profile-elo" id="eloBadge">
                <span class="elo-dot"></span>
                <span id="eloValue"><?= $eloData['rapid'] ?></span>
                <span class="elo-mode-label" id="eloModeLabel">Rapid</span>
            </div>

            <?php if (!empty($informazioni['biografia'])): ?>
                <p class="profile-bio"><?= htmlspecialchars($informazioni['biografia']) ?></p>
            <?php else: ?>
                <p class="profile-bio" style="font-style:italic;">No bio yet.</p>
            <?php endif; ?>

            <div class="divider"></div>
            <a class="edit-btn" href="editProfile">✎ &nbsp;Edit profile</a>
        </div>

        <?php $pct = ($partite > 0) ? round(($vittorie / $partite) * 100) : 0; ?>
        <div class="winrate-ring-wrap">
            <div class="ring-wrap-inner">
                <svg width="88" height="88" viewBox="0 0 88 88">
                    <circle class="ring-bg" cx="44" cy="44" r="36"/>
                    <circle class="ring-fill" cx="44" cy="44" r="36" style="--pct:<?= $pct ?>"/>
                </svg>
                <div class="ring-center"><?= $pct ?>%</div>
            </div>
            <div class="ring-meta">
                <div class="ring-meta-title">Overall winrate</div>
                <div class="ring-meta-row">
                    <span style="color:var(--win)">Wins</span>
                    <span><?= (int)$vittorie ?></span>
                </div>
                <div class="ring-meta-row">
                    <span style="color:var(--loss)">Losses</span>
                    <span><?= (int)$sconfitte ?></span>
                </div>
                <div class="ring-meta-row">
                    <span style="color:var(--text-muted)">Games</span>
                    <span><?= (int)$partite ?></span>
                </div>
            </div>
        </div>

    </aside>

    <!-- ── MAIN ── -->
    <div class="content">

        <!-- TOGGLE SCACCHI / DAMA -->
        <div class="game-toggle">
            <div class="game-toggle-inner">
                <div class="toggle-pill" id="togglePill"></div>
                <div class="toggle-option active" id="optChess" onclick="selectGame('scacchi')">
                    <span class="toggle-icon"><i class="fa-regular fa-chess-knight" style="color: rgb(255, 255, 255);"></i></span> Chess
                </div>
                <div class="toggle-option" id="optDama" onclick="selectGame('dama')">
                    <span class="toggle-icon">⬤</span> Checkers
                </div>
            </div>
        </div>

        <!-- MODE TABS -->
        <div class="mode-tabs" id="modeTabs"></div>

        <!-- STATS STRIP -->
        <div class="stats-strip">
            <div class="stat-card">
                <div class="stat-icon" id="chessIcon"></i></div>
                <div class="stat-label">Games</div>
                <div class="stat-value" id="statGames">–</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fa-solid fa-trophy" style="color: rgb(255, 59, 59);"></i></div>
                <div class="stat-label">Wins</div>
                <div class="stat-value" id="statWins">–</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fa-solid fa-arrow-trend-down" style="color: rgb(255, 59, 59);"></i></div>
                <div class="stat-label">Losses</div>
                <div class="stat-value" id="statLosses">–</div>
            </div>
        </div>

        <!-- MATCH HISTORY -->
        <div class="panel">
            <div class="panel-header">
                <div class="panel-title">
                    <span class="panel-title-icon" id="gameIcon"></span>
                    Last games
                </div>
                <span class="panel-mode-badge" id="panelModeBadge">Rapid</span>
            </div>
            <div class="matches" id="matchList"></div>
        </div>

    </div>
</div>

<script>
const eloDataScacchi = <?= json_encode($eloData) ?>;
const eloDataDama    = <?= json_encode($eloDataDama) ?>;

const gamesByModeScacchi = <?= json_encode($gamesByMode) ?>;
const gamesByModeDama    = <?= json_encode($gamesByModeDama) ?>;

const modeIcons = {
    bullet:    '<i class="fa-solid fa-gun" style="color: rgb(255, 255, 255);"></i>',
    blitz:     '<i class="fa-solid fa-bolt" style="color: rgb(255, 255, 255);"></i>',
    rapid:     '<i class="fa-solid fa-stopwatch" style="color: rgb(255, 255, 255);"></i>',
    classical: '<i class="fa-solid fa-building-columns" style="color: rgb(255, 255, 255);"></i>'
};
const modeLabels = {
    bullet:    'Bullet',
    blitz:     'Blitz',
    rapid:     'Rapid',
    classical: 'Classical',
};

let currentGame = 'scacchi';
let currentMode = 'rapid';

const modeTabs      = document.getElementById('modeTabs');
const eloValue      = document.getElementById('eloValue');
const eloModeLabel  = document.getElementById('eloModeLabel');
const statGames     = document.getElementById('statGames');
const statWins      = document.getElementById('statWins');
const statLosses    = document.getElementById('statLosses');
const matchList     = document.getElementById('matchList');
const panelModeBadge= document.getElementById('panelModeBadge');

function getCurrentData() {
    return currentGame === 'scacchi'
        ? { elo: eloDataScacchi, games: gamesByModeScacchi }
        : { elo: eloDataDama,    games: gamesByModeDama    };
}

function buildModeTabs() {
    modeTabs.innerHTML = '';
    ['bullet', 'blitz', 'rapid', 'classical'].forEach(mode => {
        const tab = document.createElement('div');
        tab.className = 'mode-tab';
        tab.dataset.mode = mode;
        tab.innerHTML = `
            <span class="mode-tab-icon">${modeIcons[mode]}</span>
            <div class="mode-tab-name">${modeLabels[mode]}</div>
            <div class="mode-tab-elo">${getCurrentData().elo[mode]}</div>
        `;
        tab.addEventListener('click', () => renderMode(mode));
        modeTabs.appendChild(tab);
    });
}

function renderMode(mode) {
    currentMode = mode;
    const data  = getCurrentData();
    console.log(currentGame);
    if(currentGame == 'scacchi'){
        document.getElementById('chessIcon').innerHTML = '<i class="fa-solid fa-chess" style="color: rgb(255, 59, 59);">';
        document.getElementById('gameIcon').innerHTML = '<i class="fa-regular fa-chess-pawn" style="color: rgb(255, 59, 59);"></i>';
    }  
    else{
        document.getElementById('chessIcon').innerHTML = '<i class="fa-solid fa-chess-board" style="color: rgb(255, 59, 59);"></i>';
        document.getElementById('gameIcon').innerHTML = '⛀';
        document.getElementById('gameIcon').style.color = 'rgb(255, 59, 59)';
    } 

    document.querySelectorAll('.mode-tab').forEach(tab => {
        tab.classList.toggle('active', tab.dataset.mode === mode);
    });

    eloValue.textContent      = data.elo[mode];
    eloModeLabel.textContent  = modeLabels[mode];
    panelModeBadge.textContent = modeLabels[mode];

    const games  = data.games[mode] || [];
    const wins   = games.filter(g => g.outcome === 'Winner').length;
    const losses = games.filter(g => g.outcome === 'Loser').length;

    statGames.textContent  = games.length;
    statWins.textContent   = wins;
    statLosses.textContent = losses;

    matchList.classList.add('fading');
    setTimeout(() => {
        matchList.innerHTML = '';
        if (games.length === 0) {
            matchList.innerHTML = `
                <div class="empty-state">
                    <div class="empty-state-icon">♟</div>
                    No ${modeLabels[mode]} games yet.
                    <a href="./timecontrol" style="color:var(--red)">Play now!</a>
                </div>`;
        } else {
            games.forEach(row => {
                const isWhite = row.colore_utente === 'bianco';
                const cls   = row.outcome === 'Winner' ? 'win'
                            : row.outcome === 'Loser'  ? 'loss' : 'draw';
                const label = row.outcome === 'Winner' ? 'Win'
                            : row.outcome === 'Loser'  ? 'Loss' : 'Draw';
                const el = document.createElement('div');
                el.className = 'match';
                el.innerHTML = `
                    <div class="match-left">
                        <span class="match-vs">vs</span>
                        <span class="match-opponent">${escHtml(row.opponent)}</span>
                        <div class="match-meta">
                            <span class="match-color">
                                <span class="color-dot ${isWhite ? 'white' : 'black'}"></span>
                                ${isWhite ? 'White' : 'Black'}
                            </span>
                            <span class="match-tc">${escHtml(row.timecontrol ?? '')}</span>
                        </div>
                    </div>
                    <span class="match-result ${cls}">${label}</span>`;
                matchList.appendChild(el);
            });
        }
        matchList.classList.remove('fading');
    }, 150);
}

function selectGame(game) {
    currentGame = game;
    document.getElementById('togglePill').classList.toggle('right', game === 'dama');
    document.getElementById('optChess').classList.toggle('active', game === 'scacchi');
    document.getElementById('optDama').classList.toggle('active', game === 'dama');
    
    console.log(game);
    buildModeTabs();
    renderMode(currentMode);
}

function escHtml(str) {
    return String(str)
        .replace(/&/g,'&amp;')
        .replace(/</g,'&lt;')
        .replace(/>/g,'&gt;')
        .replace(/"/g,'&quot;');
}

buildModeTabs();
renderMode('rapid');
</script>

</body>
</html>