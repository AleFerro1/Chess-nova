<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="<?= $_SESSION['csrf_token'] ?>">
<title>Choose timecontrol - ChessNova</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
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
    position: fixed; top: -250px; left: 50%;
    transform: translateX(-50%);
    width: 900px; height: 600px;
    background: radial-gradient(ellipse, rgba(255,59,59,0.08) 0%, transparent 65%);
    pointer-events: none; z-index: 0;
}

/* HEADER */
header {
    position: sticky; top: 0; z-index: 100;
    display: flex; justify-content: space-between; align-items: center;
    padding: 0 48px; height: 72px;
    background: rgba(7,11,20,0.75);
    backdrop-filter: blur(20px) saturate(180%);
    -webkit-backdrop-filter: blur(20px) saturate(180%);
    border-bottom: 1px solid var(--border);
}
.logo {
    font-family: var(--font-head);
    font-size: 1.35rem; font-weight: 700;
    color: var(--red); text-decoration: none;
    display: flex; align-items: center; gap: 10px;
    transition: text-shadow 0.3s;
}
.logo:hover { text-shadow: 0 0 20px var(--red-glow); }
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

/* PAGE */
.page {
    position: relative; z-index: 1;
    max-width: 1100px; margin: 0 auto;
    padding: 50px 40px 80px;
}
.back-link {
    display: inline-flex; align-items: center; gap: 8px;
    color: var(--text-muted); text-decoration: none;
    font-size: 0.88rem; font-weight: 500;
    margin-bottom: 40px; transition: color 0.2s;
}
.back-link:hover { color: var(--text); }
.back-link svg { transition: transform 0.2s; }
.back-link:hover svg { transform: translateX(-3px); }

.page-eyebrow {
    display: inline-flex; align-items: center; gap: 8px;
    font-size: 0.78rem; font-weight: 600;
    letter-spacing: 0.08em; text-transform: uppercase;
    color: var(--red);
    background: rgba(255,0,0,0.1);
    border: 1px solid var(--red-dim);
    border-radius: 40px; padding: 6px 14px;
    margin-bottom: 20px;
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
.page-title {
    font-family: var(--font-head);
    font-size: clamp(2rem, 4vw, 2.8rem);
    font-weight: 700; line-height: 1.15;
    margin-bottom: 12px;
}
.page-title span { color: var(--red); }
.page-sub {
    font-size: 1rem; line-height: 1.6;
    color: var(--text-muted); margin-bottom: 48px;
    max-width: 500px;
}

/* LAYOUT */
.content-layout {
    display: flex;
    gap: 50px;
    align-items: flex-start;
    margin-bottom: 48px;
}
.left-content {
    flex: 1;
    min-width: 0;
}
.right-board {
    flex-shrink: 0;
    width: 340px;
    display: flex;
    justify-content: center;
    align-items: flex-start;
    padding-top: 10px;
}

/* CATEGORY SELECTOR */
.category-selector {
    display: flex; gap: 12px; margin-bottom: 32px;
    flex-wrap: wrap;
}
.cat-pill {
    display: flex; align-items: center; gap: 8px;
    padding: 12px 20px; border-radius: 40px;
    background: var(--bg-surface);
    border: 1px solid var(--border);
    cursor: pointer;
    font-family: var(--font-head);
    font-size: 0.9rem; font-weight: 700;
    color: var(--text-muted);
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    user-select: none;
    backdrop-filter: blur(10px);
}
.cat-pill:hover {
    border-color: var(--red-dim);
    background: rgba(255,59,59,0.08);
    color: #fff;
}
.cat-pill.active {
    background: var(--red);
    border-color: var(--red);
    color: #0d1a06;
    box-shadow: 0 8px 24px var(--red-glow);
    transform: translateY(-2px);
}
.cat-icon { font-size: 1.1rem; }

/* CARDS WRAPPER */
.cards-wrapper {
    position: relative;
    min-height: 200px;
}
.cards-set {
    display: none;
    grid-template-columns: repeat(auto-fill, minmax(170px, 1fr));
    gap: 14px;
    animation: fadeSlideIn 0.3s ease forwards;
}
.cards-set.active {
    display: grid;
}
@keyframes fadeSlideIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* TC CARD */
.tc-card {
    position: relative;
    background: var(--bg-surface);
    border: 1px solid var(--border);
    border-radius: 16px;
    padding: 22px 18px 20px;
    cursor: pointer;
    text-decoration: none;
    color: var(--text);
    display: flex; flex-direction: column; gap: 6px;
    transition: border-color 0.22s, background 0.22s, transform 0.22s, box-shadow 0.22s;
    overflow: hidden;
}
.tc-card::before {
    content: '';
    position: absolute; inset: 0;
    background: radial-gradient(circle at 30% 30%, rgba(255,0,0,0.07), transparent 60%);
    opacity: 0; transition: opacity 0.3s;
}
.tc-card:hover {
    border-color: var(--red-dim);
    background: rgba(255,0,0,0.06);
    transform: translateY(-3px);
    box-shadow: 0 12px 32px rgba(0,0,0,0.4), 0 0 0 1px var(--red-dim);
}
.tc-card:hover::before { opacity: 1; }
.tc-card:active { transform: translateY(0); }

.tc-card.selected {
    border-color: var(--red);
    background: rgba(255,0,0,0.1);
    box-shadow: 0 0 0 1px var(--red), 0 8px 28px var(--red-glow);
}
.tc-card.selected::before { opacity: 1; }

.tc-icon { font-size: 1.5rem; margin-bottom: 4px; }
.tc-name {
    font-family: var(--font-head);
    font-size: 1.1rem; font-weight: 700;
}
.tc-time { font-size: 0.82rem; color: var(--text-muted); font-weight: 500; }
.tc-badge {
    margin-top: 8px;
    display: inline-block;
    font-size: 0.7rem; font-weight: 600;
    letter-spacing: 0.06em; text-transform: uppercase;
    padding: 3px 9px; border-radius: 40px;
    background: rgba(255,255,255,0.06);
    color: var(--text-muted);
    border: 1px solid var(--border);
}
.tc-card.selected .tc-badge {
    background: rgba(255,0,0,0.15);
    color: var(--red);
    border-color: var(--red-dim);
}
.popular-tag {
    position: absolute; top: 12px; right: 12px;
    font-size: 0.65rem; font-weight: 700;
    letter-spacing: 0.07em; text-transform: uppercase;
    padding: 3px 8px; border-radius: 40px;
    background: rgba(255,0,0,0.15);
    color: var(--red);
    border: 1px solid var(--red-dim);
}

/* CONFIRM BAR */
.confirm-bar {
    position: sticky; bottom: 24px;
    display: flex; align-items: center; justify-content: space-between;
    gap: 16px;
    background: rgba(15,20,32,0.92);
    backdrop-filter: blur(24px);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 18px 24px;
    margin-top: 48px;
    opacity: 0; transform: translateY(16px);
    pointer-events: none;
    transition: opacity 0.3s, transform 0.3s;
    z-index: 50;
}
.confirm-bar.show {
    opacity: 1; transform: translateY(0); pointer-events: all;
}
.confirm-info { display: flex; flex-direction: column; gap: 2px; }
.confirm-mode { font-family: var(--font-head); font-size: 1rem; font-weight: 700; }
.confirm-desc { font-size: 0.82rem; color: var(--text-muted); }

.btn-play {
    display: inline-flex; align-items: center; gap: 9px;
    padding: 14px 28px; border: none;
    border-radius: 14px;
    font-family: var(--font-body); font-size: 0.95rem; font-weight: 600;
    cursor: pointer;
    background: var(--red);
    color: #0d1a06;
    box-shadow: 0 8px 28px var(--red-glow);
    transition: transform 0.18s, box-shadow 0.18s, opacity 0.18s;
}
.btn-play:hover { transform: translateY(-2px); box-shadow: 0 14px 36px var(--red-dim); }
.btn-play:active { transform: translateY(0); }
.btn-play.loading { opacity: 0.7; pointer-events: none; }

.spinner {
    width: 16px; height: 16px;
    border: 2px solid rgba(13,26,6,0.35);
    border-top-color: #0d1a06;
    border-radius: 50%;
    animation: spin 0.7s linear infinite;
    display: none;
}
.btn-play.loading .spinner { display: block; }
.btn-play.loading .btn-icon { display: none; }
@keyframes spin { to { transform: rotate(360deg); } }

.searching-badge {
    display: none; align-items: center; gap: 8px;
    font-size: 0.82rem; color: var(--text-muted);
}
.searching-badge.show { display: flex; }
.searching-badge .dot {
    width: 6px; height: 6px; border-radius: 50%;
    background: var(--red);
    animation: blink 1.2s ease-in-out infinite;
}
.searching-badge .dot:nth-child(2) { animation-delay: 0.2s; }
.searching-badge .dot:nth-child(3) { animation-delay: 0.4s; }
@keyframes blink { 0%,100%{opacity:0.2;} 50%{opacity:1;} }

/* ANIMATED BOARD */
.board-demo {
    position: relative;
    width: 320px;
    height: 320px;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 20px 50px rgba(0,0,0,0.6);
    border: 3px solid rgba(255,255,255,0.1);
    animation: floatBoard 6s ease-in-out infinite;
    background: #000;
}
@keyframes floatBoard {
    0%,100% { transform: translateY(0); }
    50%      { transform: translateY(-8px); }
}

.sq-demo {
    position: absolute;
    width: 12.5%; height: 12.5%;
}
.piece-demo {
    position: absolute;
    width: 12.5%; height: 12.5%;
    display: flex; align-items: center; justify-content: center;
    transition: left 0.4s ease, top 0.4s ease;
    z-index: 2;
    pointer-events: none;
}
.piece-demo img {
    width: 80%; height: 80%;
    object-fit: contain;
    filter: drop-shadow(0 4px 6px rgba(0,0,0,0.4));
}

/* RESPONSIVE */
@media (max-width: 1000px) {
    .content-layout {
        flex-direction: column;
        align-items: center;
    }
    .right-board {
        width: 100%;
        max-width: 320px;
        margin-top: 30px;
    }
}
@media (max-width: 640px) {
    header { padding: 0 20px; }
    .logo span { display: none; }
    .page { padding: 40px 20px 100px; }
    .category-selector { gap: 8px; }
    .cat-pill { padding: 10px 16px; font-size: 0.8rem; }
    .cards-set { grid-template-columns: 1fr 1fr; gap: 10px; }
    .confirm-bar { flex-direction: column; align-items: stretch; text-align: center; }
    .btn-play { justify-content: center; }
    .board-demo { width: 280px; height: 280px; }
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
        <a class="nav-link" href="learn">Coming soon</a>
        <?php
            $avatarSrc = !empty($informazioni['avatar'])
                ? '/public/' . htmlspecialchars($informazioni['avatar'])
                : '/public/assets/img/redking.jpg';
        ?>
        <a class="nav-avatar" href="profile">
            <?= htmlspecialchars($_SESSION['username']) ?>
            <img class="nav-avatar-img"
                 src="<?= $avatarSrc ?>"
                 alt="Avatar di <?= htmlspecialchars($_SESSION['username']) ?>">
        </a>
    </nav>
</header>

<main class="page">

    <a class="back-link" href="home">
        <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M10 3L5 8L10 13" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        Back to home
    </a>

    <div class="content-layout">
        <!-- LEFT COLUMN -->
        <div class="left-content">

            <h1 class="page-title">
                Select a <span>time control</span>
            </h1>
            <p class="page-sub">
                Select the category, then the timecontrol. 
            </p>

            <!-- CATEGORY PILLS -->
            <div class="category-selector" id="categorySelector">
                <button class="cat-pill active" data-category="bullet">
                    <span class="cat-icon"><i class="fa fa-bullseye" aria-hidden="true"></i></span> Bullet
                </button>
                <button class="cat-pill" data-category="blitz">
                    <span class="cat-icon"><i class="fa fa-bolt" aria-hidden="true"></i></span> Blitz
                </button>
                <button class="cat-pill" data-category="rapid">
                    <span class="cat-icon">⏱</span> Rapid
                </button>
                <button class="cat-pill" data-category="classical">
                    <span class="cat-icon"><?php echo $tipo==1 ? '♟' : '⛀'; ?></span> Classical
                </button>
            </div>

            <!-- CARDS CONTAINER -->
            <div class="cards-wrapper" id="cardsWrapper">
                <!-- BULLET SET -->
                <div class="cards-set active" data-category="bullet">
                    <a class="tc-card" href="#" data-tc="bullet-1" data-name="Bullet 1+0" data-desc="1 min · No increment">
                        <span class="tc-icon"><i class="fa fa-bullseye" aria-hidden="true"></i></span>
                        <span class="tc-name">1+0</span>
                        <span class="tc-time">1 minute</span>
                        <span class="tc-badge">Bullet</span>
                    </a>
                    <a class="tc-card" href="#" data-tc="bullet-1-1" data-name="Bullet 1+1" data-desc="1 min · +1s">
                        <span class="tc-icon"><i class="fa fa-bullseye" aria-hidden="true"></i></span>
                        <span class="tc-name">1+1</span>
                        <span class="tc-time">1 min · +1s</span>
                        <span class="tc-badge">Bullet</span>
                    </a>
                    <a class="tc-card" href="#" data-tc="bullet-2-1" data-name="Bullet 2+1" data-desc="2 min · +1s">
                        <span class="tc-icon"><i class="fa fa-bullseye" aria-hidden="true"></i></span>
                        <span class="tc-name">2+1</span>
                        <span class="tc-time">2 min · +1s</span>
                        <span class="tc-badge">Bullet</span>
                    </a>
                </div>

                <!-- BLITZ SET -->
                <div class="cards-set" data-category="blitz">
                    <a class="tc-card" href="#" data-tc="blitz-3" data-name="Blitz 3+0" data-desc="3 min · No increment">
                        <span class="popular-tag">Popular</span>
                        <span class="tc-icon"><i class="fa fa-bolt" aria-hidden="true"></i></span>
                        <span class="tc-name">3+0</span>
                        <span class="tc-time">3 minutes</span>
                        <span class="tc-badge">Blitz</span>
                    </a>
                    <a class="tc-card" href="#" data-tc="blitz-3-2" data-name="Blitz 3+2" data-desc="3 min · +2s">
                        <span class="popular-tag">Popular</span>
                        <span class="tc-icon"><i class="fa fa-bolt" aria-hidden="true"></i></span>
                        <span class="tc-name">3+2</span>
                        <span class="tc-time">3 min · +2s</span>
                        <span class="tc-badge">Blitz</span>
                    </a>
                    <a class="tc-card" href="#" data-tc="blitz-5" data-name="Blitz 5+0" data-desc="5 min · No increment">
                        <span class="tc-icon"><i class="fa fa-bolt" aria-hidden="true"></i></span>
                        <span class="tc-name">5+0</span>
                        <span class="tc-time">5 minutes</span>
                        <span class="tc-badge">Blitz</span>
                    </a>
                    <a class="tc-card" href="#" data-tc="blitz-5-3" data-name="Blitz 5+3" data-desc="5 min · +3s">
                        <span class="tc-icon"><i class="fa fa-bolt" aria-hidden="true"></i></span>
                        <span class="tc-name">5+3</span>
                        <span class="tc-time">5 min · +3s</span>
                        <span class="tc-badge">Blitz</span>
                    </a>
                </div>

                <!-- RAPID SET -->
                <div class="cards-set" data-category="rapid">
                    <a class="tc-card selected" href="#" data-tc="rapid-10" data-name="Rapid 10+0" data-desc="10 min · No increment">
                        <span class="popular-tag">Default</span>
                        <span class="tc-icon">⏱</span>
                        <span class="tc-name">10+0</span>
                        <span class="tc-time">10 minutes</span>
                        <span class="tc-badge">Rapid</span>
                    </a>
                    <a class="tc-card" href="#" data-tc="rapid-10-5" data-name="Rapid 10+5" data-desc="10 min · +5s">
                        <span class="tc-icon">⏱</span>
                        <span class="tc-name">10+5</span>
                        <span class="tc-time">10 min · +5s</span>
                        <span class="tc-badge">Rapid</span>
                    </a>
                    <a class="tc-card" href="#" data-tc="rapid-15-10" data-name="Rapid 15+10" data-desc="15 min · +10s">
                        <span class="tc-icon">⏱</span>
                        <span class="tc-name">15+10</span>
                        <span class="tc-time">15 min · +10s</span>
                        <span class="tc-badge">Rapid</span>
                    </a>
                </div>

                <!-- CLASSICAL SET -->
                <div class="cards-set" data-category="classical">
                    <a class="tc-card" href="#" data-tc="classical-30" data-name="Classical 30+0" data-desc="30 min · No increment">
                        <span class="tc-icon"><?php echo $tipo==1 ? '♟' : '⛀'; ?></span>
                        <span class="tc-name">30+0</span>
                        <span class="tc-time">30 minutes</span>
                        <span class="tc-badge">Classical</span>
                    </a>
                    <a class="tc-card" href="#" data-tc="classical-30-20" data-name="Classical 30+20" data-desc="30 min · +20s">
                        <span class="tc-icon"><?php echo $tipo==1 ? '♟' : '⛀'; ?></span>
                        <span class="tc-name">30+20</span>
                        <span class="tc-time">30 min · +20s</span>
                        <span class="tc-badge">Classical</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- RIGHT COLUMN: animated board -->
        <div class="right-board">
            <div class="board-demo" id="boardDemo"></div>
        </div>
    </div>

    <!-- CONFIRM BAR -->
    <div class="confirm-bar show" id="confirmBar">
        <div class="confirm-info">
            <span class="confirm-mode" id="confirmMode">Rapid 10+0</span>
            <span class="confirm-desc" id="confirmDesc">10 min · No increment</span>
        </div>
        <div style="display:flex;align-items:center;gap:16px;">
            <div class="searching-badge" id="searchingBadge">
                <span class="dot"></span>
                <span class="dot"></span>
                <span class="dot"></span>
                Searching…
            </div>
            <button class="btn-play" id="btnPlay">
                <span class="btn-icon">♟</span>
                <div class="spinner"></div>
                Play now
            </button>
        </div>
    </div>

</main>

<script>
    window.tipo = <?= $tipo ?>;
//  Timecontrol selection + matchmaking 
(function() {
    const catPills    = document.querySelectorAll('.cat-pill');
    const cardsSets   = document.querySelectorAll('.cards-set');
    const confirmBar  = document.getElementById('confirmBar');
    const confirmMode = document.getElementById('confirmMode');
    const confirmDesc = document.getElementById('confirmDesc');
    const btnPlay     = document.getElementById('btnPlay');
    const searchBadge = document.getElementById('searchingBadge');
    
    let tipo;

    if(window.tipo == 1) tipo = "scacchi";
    if(window.tipo == 0) tipo = "dama";

    let selectedCard = document.querySelector('.tc-card.selected');
    let searching = false;

    if (selectedCard) {
        confirmMode.textContent = selectedCard.dataset.name;
        confirmDesc.textContent = selectedCard.dataset.desc;
    }

    catPills.forEach(pill => {
        pill.addEventListener('click', () => {
            const category = pill.dataset.category;
            catPills.forEach(p => p.classList.remove('active'));
            pill.classList.add('active');
            cardsSets.forEach(set => {
                set.classList.remove('active');
                if (set.dataset.category === category) set.classList.add('active');
            });
            if (selectedCard && !selectedCard.closest('.cards-set.active')) {
                selectedCard.classList.remove('selected');
                selectedCard = null;
                confirmBar.classList.remove('show');
            }
        });
    });

    document.getElementById('cardsWrapper').addEventListener('click', (e) => {
        const card = e.target.closest('.tc-card');
        if (!card || searching) return;
        e.preventDefault();

        if (selectedCard) selectedCard.classList.remove('selected');
        card.classList.add('selected');
        selectedCard = card;

        confirmMode.textContent = card.dataset.name;
        confirmDesc.textContent = card.dataset.desc;
        confirmBar.classList.add('show');
    });

    btnPlay.addEventListener('click', () => {
        if (searching || !selectedCard) return;
        searching = true;
        btnPlay.classList.add('loading');
        searchBadge.classList.add('show');

        const tc = selectedCard.dataset.tc;

        fetch('./searchMatch', {
            method: 'POST',
            body: new URLSearchParams({ timecontrol: tc , tipo: tipo})
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                goToMatch(data.id_partita);
            } else {
                startPolling(tc);
            }
        })
        .catch(() => resetSearch());
    });

    function startPolling(tc) {
        const poll = setInterval(() => {
            fetch('./checkMatch', {
                method: 'POST',
                body: new URLSearchParams({ timecontrol: tc })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    clearInterval(poll);
                    goToMatch(data.id_partita);
                }
            })
            .catch(() => {});
        }, 1000);
    }

    function goToMatch(id) {
        sessionStorage.setItem('id_partita', id);
        if(tipo == "scacchi"){
            window.location.href = './board?id=' + id;
        }
        if(tipo == "dama"){
            window.location.href = './dama?id=' + id;
        }
            
        
        
    }

    function resetSearch() {
        searching = false;
        btnPlay.classList.remove('loading');
        searchBadge.classList.remove('show');
    }

    // heartbeat
    function heartbeat() {
        fetch('./heartbeat', { method: 'POST', credentials: 'same-origin', keepalive: true }).catch(() => {});
    }
    heartbeat();
    setInterval(heartbeat, 30000);

    window.addEventListener('beforeunload', () => {
        if (searching) navigator.sendBeacon('./leaveMatchmaking');
    });
})();

const sqLight = '#eed9b0';
const sqDark = '#7c4f30';

function createSquares(){
        for (let r = 0; r < 8; r++) {
        for (let c = 0; c < 8; c++) {
            const sq = document.createElement('div');
            sq.className = 'sq-demo';
            sq.style.backgroundColor = (r + c) % 2 === 0 ? sqLight : sqDark;
            sq.style.left = (c * 12.5) + '%';
            sq.style.top = (r * 12.5) + '%';
            boardDemo.appendChild(sq);
        }
        }
    }
//  Animated chessboard 
(function() {
    console.log(window.tipo);
    function squareToIndex(sq) {
        const col = sq.charCodeAt(0) - 97;
        const row = 8 - parseInt(sq[1]);
        return row * 8 + col;
    }
    const piecesElements = new Array(64).fill(null);
    function createInitialPieces(initialPositions) {
            for (let i = 0; i < 64; i++) piecesElements[i] = null;
            boardDemo.querySelectorAll('.piece-demo').forEach(el => el.remove());

            for (const [square, pieceName] of Object.entries(initialPositions)) {
                const idx = squareToIndex(square);
                const pieceDiv = document.createElement('div');
                pieceDiv.className = 'piece-demo';
                pieceDiv.style.left = (idx % 8 * 12.5) + '%';
                pieceDiv.style.top = (Math.floor(idx / 8) * 12.5) + '%';
                const img = document.createElement('img');
                if(window.tipo === 1)   img.src = './images/' + pieceName + '.webp';
                else img.src = './images/' + pieceName + '.png';
                img.alt = pieceName;
                pieceDiv.appendChild(img);
                boardDemo.appendChild(pieceDiv);
                piecesElements[idx] = pieceDiv;
            }
        }
    function movePiece(from, to) {
        const fromIdx = squareToIndex(from);
        const toIdx = squareToIndex(to);
        const pieceEl = piecesElements[fromIdx];
        if (!pieceEl) return;
        if (piecesElements[toIdx]) {
            piecesElements[toIdx].remove();
            piecesElements[toIdx] = null;
        }
        pieceEl.style.left = (toIdx % 8 * 12.5) + '%';
        pieceEl.style.top = (Math.floor(toIdx / 8) * 12.5) + '%';
        piecesElements[toIdx] = pieceEl;
        piecesElements[fromIdx] = null;
    }
    if(window.tipo === 1){
    const boardDemo = document.getElementById('boardDemo');
    if (!boardDemo) return;

    

    // create squares
    
    createSquares();
    

    const initialPositions = {
        a8:'DarkRook', b8:'DarkKnight', c8:'DarkBishop', d8:'DarkQueen',
        e8:'DarkKing', f8:'DarkBishop', g8:'DarkKnight', h8:'DarkRook',
        a7:'DarkPawn', b7:'DarkPawn', c7:'DarkPawn', d7:'DarkPawn',
        e7:'DarkPawn', f7:'DarkPawn', g7:'DarkPawn', h7:'DarkPawn',
        a2:'LightPawn', b2:'LightPawn', c2:'LightPawn', d2:'LightPawn',
        e2:'LightPawn', f2:'LightPawn', g2:'LightPawn', h2:'LightPawn',
        a1:'LightRook', b1:'LightKnight', c1:'LightBishop', d1:'LightQueen',
        e1:'LightKing', f1:'LightBishop', g1:'LightKnight', h1:'LightRook'
    };

    createInitialPieces(initialPositions);

    const moves = [
        {from:'e2',to:'e4'}, {from:'e7',to:'e5'},
        {from:'g1',to:'f3'}, {from:'b8',to:'c6'},
        {from:'f1',to:'c4'}, {from:'f8',to:'c5'},
        {from:'d2',to:'d3'}, {from:'d7',to:'d6'},
        {from:'b1',to:'c3'}, {from:'g8',to:'f6'},
        {from:'c1',to:'g5'}, {from:'h7',to:'h6'},
        {from:'g5',to:'h4'}, {from:'g7',to:'g5'},
        {from:'h4',to:'g3'}, {from:'c8',to:'g4'}
    ];

    let moveIndex = 0;

    function playNextMove() {
        if (moveIndex >= moves.length) {
            createInitialPieces(initialPositions);
            moveIndex = 0;
            return;
        }
        movePiece(moves[moveIndex].from, moves[moveIndex].to);
        moveIndex++;
    }

    setInterval(playNextMove, 1500);
    }
    
    else if(window.tipo === 0){
        
        const boardDemo = document.getElementById('boardDemo');
        if (!boardDemo) return;

        createSquares();

        const initialPositions = {
            b8: 'damaPiccolaNera', d8: 'damaPiccolaNera', f8: 'damaPiccolaNera',
            h8: 'damaPiccolaNera', a7: 'damaPiccolaNera', c7: 'damaPiccolaNera',
            e7: 'damaPiccolaNera', g7: 'damaPiccolaNera', b6: 'damaPiccolaNera',
            d6: 'damaPiccolaNera', f6: 'damaPiccolaNera', h6: 'damaPiccolaNera',
            a3: 'damaPiccolaBianca', c3: 'damaPiccolaBianca', e3: 'damaPiccolaBianca',
            g3: 'damaPiccolaBianca', b2: 'damaPiccolaBianca', d2: 'damaPiccolaBianca',
            f2: 'damaPiccolaBianca', h2: 'damaPiccolaBianca', a1: 'damaPiccolaBianca',
            c1: 'damaPiccolaBianca', e1: 'damaPiccolaBianca', g1: 'damaPiccolaBianca'
        };

        createInitialPieces(initialPositions);

        const moves = [
            {from:'e3',to:'d4'}, {from:'d6',to:'e5'},
            {from:'f2',to:'e3'}, {from:'e7',to:'d6'},
            {from:'e3',to:'f4'}, {from:'f8',to:'e7'},
            {from:'d2',to:'e3'}, {from:'d6',to:'c5'},
            {from:'g1',to:'f2'}, {from:'e7',to:'d6'},
            {from:'e1',to:'d2'}, {from:'d8',to:'e7'}
        ];

        let moveIndex = 0;

        function playNextMove() {
        if (moveIndex >= moves.length) {
            createInitialPieces(initialPositions);
            moveIndex = 0;
            return;
        }
            movePiece(moves[moveIndex].from, moves[moveIndex].to);
            moveIndex++;
        }

        setInterval(playNextMove, 1500);
    
    }
})();
</script>

</body>
</html>