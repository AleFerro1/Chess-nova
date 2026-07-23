<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Leaderboard — ChessNova</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
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
    --gold:       #f5c842;
    --gold-bg:    rgba(245,200,66,0.10);
    --silver:     #b0b8c8;
    --silver-bg:  rgba(176,184,200,0.10);
    --bronze:     #cd7f4a;
    --bronze-bg:  rgba(205,127,74,0.10);
    --win:        #5dde8a;
    --loss:       rgba(255,127,127,1);
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
    max-width: 1000px; margin: 0 auto;
    padding: 48px 40px;
    display: flex; flex-direction: column; gap: 28px;
}

/* ── PAGE HEADER ── */
.page-header {
    display: flex; align-items: flex-end; justify-content: space-between;
    gap: 20px;
}
.page-header-left {}
.page-eyebrow {
    display: inline-flex; align-items: center; gap: 7px;
    font-size: 0.72rem; font-weight: 600;
    letter-spacing: 0.1em; text-transform: uppercase;
    color: var(--red);
    background: rgba(255,0,0,0.08);
    border: 1px solid rgba(255,0,0,0.18);
    border-radius: 30px; padding: 5px 13px;
    margin-bottom: 12px;
}
.eyebrow-dot {
    width: 5px; height: 5px; border-radius: 50%;
    background: var(--red);
    animation: pulse 2s ease-in-out infinite;
}
@keyframes pulse {
    0%,100% { opacity:1; transform:scale(1); }
    50%      { opacity:0.5; transform:scale(0.7); }
}
.page-title {
    font-family: var(--font-head);
    font-size: 2rem; font-weight: 700;
    letter-spacing: -0.5px; line-height: 1;
}
.page-sub {
    font-size: 0.88rem; color: var(--text-muted);
    margin-top: 8px; line-height: 1.6;
}

/* ── GAME TOGGLE ── */
.game-toggle {
    display: flex; align-items: center; justify-content: center;
}
.game-toggle-inner {
    display: inline-flex; align-items: center;
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: 16px; padding: 5px; gap: 4px;
    position: relative;
}
.toggle-pill {
    position: absolute; top: 5px; left: 5px;
    height: calc(100% - 10px); width: calc(50% - 7px);
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
    padding: 10px 22px; border-radius: 11px; cursor: pointer;
    font-family: var(--font-head); font-size: 0.85rem; font-weight: 700;
    color: var(--text-muted); transition: color 0.25s;
    user-select: none; white-space: nowrap;
}
.toggle-option.active { color: var(--text); }
.toggle-icon { font-size: 1rem; transition: transform 0.25s; display: inline-block; }
.toggle-option.active .toggle-icon { transform: scale(1.15); }

/* ── MODE TABS ── */
.mode-tabs {
    display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px;
}
.mode-tab {
    background: var(--bg-card); border: 1px solid var(--border);
    border-radius: 16px; padding: 14px 12px;
    cursor: pointer; text-align: center;
    transition: border-color .2s, background .2s, transform .18s;
    position: relative; overflow: hidden;
}
.mode-tab::after {
    content: ''; position: absolute; bottom: 0; left: 0; right: 0; height: 2px;
    background: linear-gradient(90deg, transparent, var(--red), transparent);
    opacity: 0; transition: opacity .2s;
}
.mode-tab:hover { border-color: var(--red-dim); transform: translateY(-2px); }
.mode-tab:hover::after { opacity: 0.6; }
.mode-tab.active {
    border-color: var(--red); background: rgba(255,0,0,0.07);
    box-shadow: 0 0 0 1px var(--red-dim), 0 8px 24px var(--red-glow);
}
.mode-tab.active::after { opacity: 1; }
.mode-tab-icon { font-size: 1.2rem; margin-bottom: 4px; display: block; }
.mode-tab-name {
    font-family: var(--font-head); font-size: 0.82rem; font-weight: 700;
    color: var(--text-muted); transition: color .2s;
}
.mode-tab.active .mode-tab-name { color: var(--text); }

/* ── PODIUM ── */
.podium {
    display: grid; grid-template-columns: 1fr 1.1fr 1fr;
    gap: 12px; align-items: end;
}
.podium-card {
    background: var(--bg-card); border: 1px solid var(--border);
    border-radius: 20px; padding: 24px 16px;
    text-align: center; position: relative; overflow: hidden;
    transition: transform .2s;
}
.podium-card:hover { transform: translateY(-3px); }

.podium-card.rank-1 {
    border-color: rgba(245,200,66,0.3);
    background: linear-gradient(180deg, rgba(245,200,66,0.06) 0%, var(--bg-card) 100%);
    box-shadow: 0 0 0 1px rgba(245,200,66,0.15), 0 16px 40px rgba(245,200,66,0.08);
}
.podium-card.rank-2 {
    border-color: rgba(176,184,200,0.2);
    background: linear-gradient(180deg, rgba(176,184,200,0.04) 0%, var(--bg-card) 100%);
}
.podium-card.rank-3 {
    border-color: rgba(205,127,74,0.2);
    background: linear-gradient(180deg, rgba(205,127,74,0.04) 0%, var(--bg-card) 100%);
}

.podium-crown {
    font-size: 1.6rem; margin-bottom: 8px; display: block;
    animation: float 3s ease-in-out infinite;
}
@keyframes float {
    0%,100% { transform: translateY(0); }
    50%      { transform: translateY(-5px); }
}

.podium-rank {
    font-family: var(--font-head); font-size: 0.7rem; font-weight: 700;
    letter-spacing: 0.12em; text-transform: uppercase;
    margin-bottom: 12px;
}
.rank-1 .podium-rank { color: var(--gold); }
.rank-2 .podium-rank { color: var(--silver); }
.rank-3 .podium-rank { color: var(--bronze); }

.podium-avatar {
    width: 64px; height: 64px; border-radius: 50%;
    margin: 0 auto 12px; object-fit: cover; display: block;
}
.rank-1 .podium-avatar { border: 2.5px solid rgba(245,200,66,0.5); box-shadow: 0 0 20px rgba(245,200,66,0.2); width: 72px; height: 72px; }
.rank-2 .podium-avatar { border: 2px solid rgba(176,184,200,0.3); }
.rank-3 .podium-avatar { border: 2px solid rgba(205,127,74,0.3); }

.podium-username {
    font-family: var(--font-head); font-size: 1rem; font-weight: 700;
    margin-bottom: 6px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.podium-elo {
    font-family: var(--font-head); font-size: 1.3rem; font-weight: 700;
}
.rank-1 .podium-elo { color: var(--gold); font-size: 1.5rem; }
.rank-2 .podium-elo { color: var(--silver); }
.rank-3 .podium-elo { color: var(--bronze); }

.podium-games {
    font-size: 0.75rem; color: var(--text-muted); margin-top: 4px;
}

/* ── TABLE PANEL ── */
.panel {
    background: var(--bg-card); border: 1px solid var(--border);
    border-radius: 24px; padding: 28px;
    backdrop-filter: blur(10px);
    box-shadow: 0 20px 40px rgba(0,0,0,0.35);
}
.panel-header {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 20px;
}
.panel-title {
    font-family: var(--font-head); font-size: 1.1rem; font-weight: 700;
    display: flex; align-items: center; gap: 10px;
}
.panel-title-icon {
    width: 28px; height: 28px;
    background: rgba(201,93,93,0.12); border: 1px solid rgba(201,93,93,0.22);
    border-radius: 8px; display: flex; align-items: center; justify-content: center;
    font-size: 0.9rem;
}
.panel-badge {
    font-size: 0.75rem; font-weight: 600;
    letter-spacing: 0.08em; text-transform: uppercase;
    color: var(--red); background: rgba(255,0,0,0.1);
    border: 1px solid var(--red-dim); border-radius: 30px; padding: 4px 12px;
}

/* ── LEADERBOARD TABLE ── */
.lb-table { width: 100%; border-collapse: collapse; }

.lb-table thead th {
    font-size: 0.7rem; font-weight: 600;
    letter-spacing: 0.1em; text-transform: uppercase;
    color: var(--text-muted); padding: 0 12px 14px;
    text-align: left; border-bottom: 1px solid var(--border);
}
.lb-table thead th:last-child { text-align: right; }
.lb-table thead th.center { text-align: center; }

.lb-row {
    transition: background .15s;
    border-bottom: 1px solid rgba(255,255,255,0.03);
}
.lb-row:last-child { border-bottom: none; }
.lb-row:hover { background: rgba(255,255,255,0.03); }
.lb-row.is-me { background: rgba(255,0,0,0.04); }
.lb-row.is-me:hover { background: rgba(255,0,0,0.07); }

.lb-row td { padding: 14px 12px; vertical-align: middle; }

/* rank cell */
.lb-rank {
    font-family: var(--font-head); font-size: 0.9rem; font-weight: 700;
    color: var(--text-muted); width: 48px;
}
.lb-rank.gold   { color: var(--gold); }
.lb-rank.silver { color: var(--silver); }
.lb-rank.bronze { color: var(--bronze); }

/* player cell */
.lb-player { display: flex; align-items: center; gap: 12px; }
.lb-avatar {
    width: 36px; height: 36px; border-radius: 50%;
    object-fit: cover; flex-shrink: 0;
    border: 1.5px solid var(--border);
}
.lb-username {
    font-size: 0.92rem; font-weight: 600;
}
.lb-you {
    font-size: 0.68rem; font-weight: 600;
    color: var(--red); background: rgba(255,0,0,0.1);
    border: 1px solid var(--red-dim);
    border-radius: 20px; padding: 2px 8px; margin-left: 6px;
}

/* elo cell */
.lb-elo {
    font-family: var(--font-head); font-size: 1.05rem; font-weight: 700;
    color: var(--text);
}

/* winrate cell */
.lb-winrate { text-align: center; }
.lb-winrate-bar-wrap {
    display: flex; align-items: center; gap: 8px;
}
.lb-winrate-bar {
    flex: 1; height: 4px; border-radius: 99px;
    background: rgba(255,255,255,0.06); overflow: hidden;
}
.lb-winrate-fill {
    height: 100%; border-radius: 99px;
    background: linear-gradient(90deg, var(--win), rgba(93,222,138,0.6));
    transition: width .4s ease;
}
.lb-winrate-pct {
    font-size: 0.8rem; font-weight: 600; color: var(--win);
    min-width: 36px; text-align: right;
}

/* games cell */
.lb-games {
    font-size: 0.85rem; color: var(--text-muted);
    text-align: center;
}

/* trend cell */
.lb-trend { text-align: right; }
.lb-trend-badge {
    display: inline-flex; align-items: center; gap: 4px;
    font-size: 0.78rem; font-weight: 600;
    padding: 4px 10px; border-radius: 8px;
}
.lb-trend-badge.up   { color: var(--win);  background: rgba(93,222,138,0.1); }
.lb-trend-badge.down { color: var(--loss); background: rgba(255,100,100,0.1); }
.lb-trend-badge.flat { color: var(--text-muted); background: rgba(255,255,255,0.05); }

/* empty */
.lb-empty {
    text-align: center; padding: 48px 20px;
    color: var(--text-muted); font-size: 0.9rem;
}
.lb-empty-icon { font-size: 2rem; margin-bottom: 10px; }

/* spinner */
.lb-loading {
    text-align: center; padding: 40px;
    color: var(--text-muted); font-size: 0.88rem;
    display: flex; flex-direction: column; align-items: center; gap: 12px;
}
.spinner {
    width: 24px; height: 24px;
    border: 2.5px solid var(--border);
    border-top-color: var(--red);
    border-radius: 50%;
    animation: spin .7s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* ── RESPONSIVE ── */
@media (max-width: 800px) {
    .podium { grid-template-columns: 1fr; gap: 10px; align-items: stretch; }
    .podium-card.rank-1 { order: -1; }
    .mode-tabs { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 640px) {
    header { padding: 0 20px; }
    .page { padding: 24px 16px; gap: 16px; }
    .page-header { flex-direction: column; align-items: flex-start; }
    .lb-table thead th.hide-mobile,
    .lb-row td.hide-mobile { display: none; }
    .toggle-option { padding: 10px 14px; font-size: 0.8rem; }
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
        <a class="nav-link" href="./profile">Profile</a>
        <a class="nav-logout" href="./logout">Logout</a>
    </nav>
</header>

<?php
/*
    Variabili attese dal controller:
    $leaderboard  = array di ['username', 'avatar', 'elo', 'wins', 'losses', 'games', 'trend']
                    già ordinati per Elo DESC, per la modalità corrente
    $mode         = 'rapid' (default)
    $tipo         = 'scacchi' (default)
    $currentUser  = $_SESSION['username']
    $myRank       = posizione dell'utente corrente (int|null)
*/
$currentUser = $_SESSION['username'] ?? '';
$leaderboard = $leaderboard ?? [];
$currentMode = $mode ?? 'rapid';
$currentTipo = $tipo ?? 'scacchi';

$modeLabels = [
    'bullet'    => 'Bullet',
    'blitz'     => 'Blitz',
    'rapid'     => 'Rapid',
    'classical' => 'Classical',
];

$avatarDefault = '/public/assets/img/redking.jpg';

// Podio: prime 3 righe
$podium = array_slice($leaderboard, 0, 3);
// Resto: dal 4° in poi
$rest   = array_slice($leaderboard, 3);
?>

<div class="page">

    <!-- PAGE HEADER -->
    <div class="page-header">
        <div class="page-header-left">
            <div class="page-eyebrow">
                <span class="eyebrow-dot"></span>
                Live rankings
            </div>
            <h1 class="page-title">Leaderboard</h1>
            <p class="page-sub">Top players ranked by Elo — <?= htmlspecialchars($modeLabels[$currentMode] ?? 'Rapid') ?> · <?= ucfirst(htmlspecialchars($currentTipo)) ?></p>
        </div>

        <!-- TOGGLE SCACCHI / DAMA -->
        <div class="game-toggle">
            <div class="game-toggle-inner">
                <div class="toggle-pill <?= $currentTipo === 'dama' ? 'right' : '' ?>" id="togglePill"></div>
                <div class="toggle-option <?= $currentTipo === 'scacchi' ? 'active' : '' ?>" onclick="selectGame('scacchi')">
                    <span class="toggle-icon">♟</span> Scacchi
                </div>
                <div class="toggle-option <?= $currentTipo === 'dama' ? 'active' : '' ?>" onclick="selectGame('dama')">
                    <span class="toggle-icon">⬤</span> Dama
                </div>
            </div>
        </div>
    </div>

    <!-- MODE TABS -->
    <div class="mode-tabs">
        <?php
        $modeIcons = ['bullet'=>'⚡','blitz'=>'🔥','rapid'=>'⏱','classical'=>'♟'];
        foreach ($modeLabels as $key => $label):
        ?>
        <div class="mode-tab <?= $key === $currentMode ? 'active' : '' ?>"
             onclick="switchMode('<?= $key ?>')">
            <span class="mode-tab-icon"><?= $modeIcons[$key] ?></span>
            <div class="mode-tab-name"><?= $label ?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- PODIUM -->
    <?php if (count($podium) >= 1): ?>
    <div class="podium">

        <!-- 2° posto (sinistra) -->
        <?php if (isset($podium[1])): $p = $podium[1]; $wins=$p['wins']??0; $games=$p['games']??1; ?>
        <div class="podium-card rank-2">
            <span class="podium-rank">🥈 2nd place</span>
            <?php
                $av = !empty($p['avatar']) ? '/public/'.$p['avatar'] : $avatarDefault;
            ?>
            <img class="podium-avatar" src="<?= htmlspecialchars($av) ?>" alt="avatar">
            <div class="podium-username"><?= htmlspecialchars($p['username']) ?></div>
            <div class="podium-elo"><?= (int)$p['elo'] ?></div>
            <div class="podium-games"><?= (int)$games ?> games</div>
        </div>
        <?php else: ?><div></div><?php endif; ?>

        <!-- 1° posto (centro) -->
        <?php $p = $podium[0]; $wins=$p['wins']??0; $games=$p['games']??1; ?>
        <div class="podium-card rank-1">
            <span class="podium-crown">👑</span>
            <span class="podium-rank">1st place</span>
            <?php $av = !empty($p['avatar']) ? '/public/'.$p['avatar'] : $avatarDefault; ?>
            <img class="podium-avatar" src="<?= htmlspecialchars($av) ?>" alt="avatar">
            <div class="podium-username"><?= htmlspecialchars($p['username']) ?></div>
            <div class="podium-elo"><?= (int)$p['elo'] ?></div>
            <div class="podium-games"><?= (int)$games ?> games</div>
        </div>

        <!-- 3° posto (destra) -->
        <?php if (isset($podium[2])): $p = $podium[2]; $games=$p['games']??1; ?>
        <div class="podium-card rank-3">
            <span class="podium-rank">🥉 3rd place</span>
            <?php $av = !empty($p['avatar']) ? '/public/'.$p['avatar'] : $avatarDefault; ?>
            <img class="podium-avatar" src="<?= htmlspecialchars($av) ?>" alt="avatar">
            <div class="podium-username"><?= htmlspecialchars($p['username']) ?></div>
            <div class="podium-elo"><?= (int)$p['elo'] ?></div>
            <div class="podium-games"><?= (int)$games ?> games</div>
        </div>
        <?php else: ?><div></div><?php endif; ?>

    </div>
    <?php endif; ?>

    <!-- TABLE: dal 4° in poi -->
    <div class="panel">
        <div class="panel-header">
            <div class="panel-title">
                <span class="panel-title-icon">♟</span>
                Full rankings
            </div>
            <span class="panel-badge" id="panelBadge"><?= htmlspecialchars($modeLabels[$currentMode] ?? 'Rapid') ?></span>
        </div>

        <?php if (empty($leaderboard)): ?>
        <div class="lb-empty">
            <div class="lb-empty-icon">♟</div>
            No players ranked yet. <a href="./timecontrol" style="color:var(--red)">Play now!</a>
        </div>
        <?php else: ?>
        <table class="lb-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Player</th>
                    <th>Elo</th>
                    <th class="center hide-mobile">Winrate</th>
                    <th class="center hide-mobile">Games</th>
                    <th>Trend</th>
                </tr>
            </thead>
            <tbody id="lbBody">
            <?php foreach ($leaderboard as $i => $row):
                $rank     = $i + 1;
                $isMe     = ($row['username'] ?? '') === $currentUser;
                $wins     = (int)($row['wins']   ?? 0);
                $losses   = (int)($row['losses'] ?? 0);
                $games    = (int)($row['games']  ?? 0);
                $winpct   = $games > 0 ? round(($wins / $games) * 100) : 0;
                $trend    = (int)($row['trend']  ?? 0); // differenza Elo rispetto a ieri
                $av       = !empty($row['avatar']) ? '/public/'.$row['avatar'] : $avatarDefault;

                if ($rank === 1) $rankClass = 'gold';
                elseif ($rank === 2) $rankClass = 'silver';
                elseif ($rank === 3) $rankClass = 'bronze';
                else $rankClass = '';

                if ($trend > 0)       { $trendClass = 'up';   $trendIcon = '▲'; $trendLabel = '+' . $trend; }
                elseif ($trend < 0)   { $trendClass = 'down'; $trendIcon = '▼'; $trendLabel = $trend; }
                else                  { $trendClass = 'flat'; $trendIcon = '—'; $trendLabel = '—'; }
            ?>
            <tr class="lb-row <?= $isMe ? 'is-me' : '' ?>">
                <td class="lb-rank <?= $rankClass ?>"><?= $rank ?></td>
                <td>
                    <div class="lb-player">
                        <img class="lb-avatar" src="<?= htmlspecialchars($av) ?>" alt="avatar">
                        <span class="lb-username">
                            <?= htmlspecialchars($row['username']) ?>
                            <?php if ($isMe): ?><span class="lb-you">You</span><?php endif; ?>
                        </span>
                    </div>
                </td>
                <td class="lb-elo"><?= (int)$row['elo'] ?></td>
                <td class="hide-mobile">
                    <div class="lb-winrate-bar-wrap">
                        <div class="lb-winrate-bar">
                            <div class="lb-winrate-fill" style="width:<?= $winpct ?>%"></div>
                        </div>
                        <span class="lb-winrate-pct"><?= $winpct ?>%</span>
                    </div>
                </td>
                <td class="lb-games hide-mobile"><?= $games ?></td>
                <td class="lb-trend">
                    <span class="lb-trend-badge <?= $trendClass ?>">
                        <?= $trendIcon ?> <?= $trendLabel ?>
                    </span>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

</div>

<script>
let currentGame = '<?= htmlspecialchars($currentTipo) ?>';
let currentMode = '<?= htmlspecialchars($currentMode) ?>';

function selectGame(game) {
    currentGame = game;
    document.getElementById('togglePill').classList.toggle('right', game === 'dama');
    document.getElementById('optChess')?.classList.toggle('active', game === 'scacchi');
    document.getElementById('optDama')?.classList.toggle('active', game === 'dama');
    reload();
}

function switchMode(mode) {
    currentMode = mode;
    document.querySelectorAll('.mode-tab').forEach(tab => {
        tab.classList.toggle('active', tab.dataset.mode === mode);
    });
    reload();
}

// Aggiunge data-mode ai tab già renderizzati
document.querySelectorAll('.mode-tab').forEach((tab, i) => {
    const modes = ['bullet', 'blitz', 'rapid', 'classical'];
    tab.dataset.mode = modes[i];
});

function reload() {
    window.location.href = `?tipo=${currentGame}&mode=${currentMode}`;
}
</script>

</body>
</html>