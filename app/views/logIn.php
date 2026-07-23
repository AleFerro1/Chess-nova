<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login — ChessNova</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="icon" type="image/png" sizes="64x64" href="./images/favicon.png">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
    --red:        rgba(255, 0, 0, 0.85);
    --red-dim:    #913333;
    --bg-deep:    #080b10;
    --bg-card:    rgba(14,17,26,0.97);
    --bg-surface: rgba(255,255,255,0.04);
    --border:     rgba(255,255,255,0.07);
    --text:       #ffffff;
    --text-muted: rgba(255,255,255,0.50);
    --font-head:  'Syne', sans-serif;
    --font-body:  'DM Sans', sans-serif;
}

html { scroll-behavior: smooth; }

body {
    font-family: var(--font-body);
    background: var(--bg-deep);
    color: var(--text);
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    overflow-x: hidden;
}

/* noise */
body::before {
    content: '';
    position: fixed; inset: 0;
    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.04'/%3E%3C/svg%3E");
    background-size: 200px;
    pointer-events: none; z-index: 0;
}

/* ambient glow */
.ambient {
    position: fixed;
    top: -200px; left: 50%;
    transform: translateX(-50%);
    width: 900px; height: 600px;
    background: radial-gradient(ellipse, rgba(201, 93, 93, 0.09) 0%, transparent 65%);
    pointer-events: none; z-index: 0;
}

/* HEADER */
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
    color: var(--red); letter-spacing: 0;
    text-decoration: none;
    display: flex; align-items: center; gap: 10px;
}
.logo-icon {
    width: 32px; height: 32px;
    border-radius: 8px;
    overflow: hidden;
    display: flex; align-items: center; justify-content: center;
}
.logo-icon img {
    width: 100%; height: 100%;
    object-fit: cover; display: block;
}

.header-link {
    text-decoration: none; color: var(--text-muted);
    font-size: 0.88rem; font-weight: 500;
    padding: 8px 14px; border-radius: 10px;
    border: 1px solid var(--border);
    transition: color .2s, background .2s, border-color .2s;
}
.header-link:hover {
    color: var(--text);
    background: var(--bg-surface);
    border-color: rgba(255,255,255,0.12);
}

/* MAIN */
main {
    position: relative; z-index: 1;
    flex: 1;
    display: flex; align-items: center; justify-content: center;
    padding: 48px 20px;
}

/* TWO-COLUMN LAYOUT */
.login-layout {
    display: grid;
    grid-template-columns: 1fr 1.1fr;
    max-width: 860px; width: 100%;
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: 24px;
    box-shadow: 0 32px 64px rgba(0,0,0,0.55);
    backdrop-filter: blur(16px);
    overflow: hidden;
}

/* LEFT PANEL */
.panel-left {
    background: rgba(255,255,255,0.02);
    border-right: 1px solid var(--border);
    padding: 52px 44px;
    display: flex; flex-direction: column; justify-content: space-between;
    position: relative;
    overflow: hidden;
}

.panel-left::before {
    content: '♟';
    position: absolute;
    bottom: -24px; right: -10px;
    font-size: 180px; line-height: 1;
    color: rgba(255,0,0,0.06);
    pointer-events: none;
}

.panel-tagline {
    font-family: var(--font-head);
    font-size: 2.1rem; font-weight: 700;
    line-height: 1.2;
    color: var(--text);
    letter-spacing: -0.5px;
}
.panel-tagline em {
    font-style: italic;
    color: var(--red);
    font-family: Georgia, serif;
}

.panel-sub {
    font-size: 0.88rem;
    color: var(--text-muted);
    line-height: 1.7;
    margin-top: 16px;
}

.panel-quote { margin-top: auto; padding-top: 40px; }

.quote-rule {
    width: 28px; height: 1px;
    background: var(--red-dim);
    margin-bottom: 10px;
    opacity: 0.6;
}

.quote-text {
    font-size: 0.8rem;
    color: rgba(255,255,255,0.25);
    font-style: italic;
    font-family: Georgia, serif;
    line-height: 1.6;
}

/* RIGHT PANEL */
.panel-right {
    padding: 52px 48px;
}

/* card header */
.card-eyebrow {
    display: inline-flex; align-items: center; gap: 7px;
    font-size: 0.72rem; font-weight: 600;
    letter-spacing: 0.1em; text-transform: uppercase;
    color: var(--red);
    background: rgba(255,0,0,0.08);
    border: 1px solid rgba(255,0,0,0.18);
    border-radius: 30px; padding: 5px 13px;
    margin-bottom: 20px;
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

.card-title {
    font-family: var(--font-head);
    font-size: 1.75rem; font-weight: 700;
    letter-spacing: -0.3px; margin-bottom: 6px;
}
.card-sub {
    font-size: 0.88rem; color: var(--text-muted);
    line-height: 1.6; margin-bottom: 32px;
}

.divider {
    height: 1px; background: var(--border);
    margin-bottom: 28px;
}

/* FORM */
.form-group {
    display: flex; flex-direction: column; gap: 7px;
    margin-bottom: 18px;
}

label {
    font-size: 0.8rem; font-weight: 600;
    color: rgba(255,255,255,0.72);
}

.input-wrap {
    position: relative;
    display: flex; align-items: center;
}
.input-icon {
    position: absolute; left: 14px;
    font-size: 0.9rem; opacity: 0.4;
    pointer-events: none;
}

input[type="text"],
input[type="password"] {
    width: 100%;
    padding: 12px 15px 12px 40px;
    border: 1px solid var(--border); border-radius: 12px;
    background: rgba(255,255,255,0.04);
    color: var(--text); font-family: var(--font-body); font-size: 0.92rem;
    outline: none;
    transition: border-color .2s, box-shadow .2s, background .2s;
}
input::placeholder { color: var(--text-muted); }
input:focus {
    border-color: var(--red);
    background: rgba(201, 93, 93, 0.04);
    box-shadow: 0 0 0 3px rgba(201, 93, 93, 0.1);
}
input.error {
    border-color: rgba(255,60,60,0.5) !important;
    box-shadow: 0 0 0 3px rgba(255,60,60,0.08) !important;
}

.error-msg {
    display: none;
    align-items: center; gap: 7px;
    font-size: 0.8rem; font-weight: 500; color: #ff7070;
    background: rgba(255,60,60,0.07);
    border: 1px solid rgba(255,60,60,0.18);
    border-radius: 9px; padding: 8px 12px;
    margin-bottom: 16px;
    animation: errorPop .18s ease-out;
}
.error-msg.show { display: flex; }
@keyframes errorPop {
    from { opacity:0; transform:translateY(-5px); }
    to   { opacity:1; transform:translateY(0); }
}

.btn-submit {
    width: 100%;
    padding: 13px;
    border: none; border-radius: 12px;
    font-family: var(--font-body);
    font-size: 0.92rem; font-weight: 700;
    cursor: pointer;
    background: linear-gradient(145deg, var(--red), var(--red-dim));
    color: #1a0606;
    box-shadow: 0 8px 22px rgba(201, 93, 93, 0.28);
    transition: transform .18s, box-shadow .18s, opacity .18s;
    display: flex; align-items: center; justify-content: center; gap: 8px;
}
.btn-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 28px rgba(201, 93, 93, 0.42);
}
.btn-submit:active { transform: translateY(0); }
.btn-submit.loading { opacity: 0.7; pointer-events: none; }

.btn-spinner {
    width: 15px; height: 15px;
    border: 2px solid rgba(13,26,6,0.3);
    border-top-color: #0d1a06;
    border-radius: 50%;
    animation: spin .7s linear infinite;
    display: none;
}
.btn-submit.loading .btn-spinner { display: block; }
.btn-submit.loading .btn-label  { display: none; }
@keyframes spin { to { transform: rotate(360deg); } }

.card-footer {
    margin-top: 22px;
    text-align: center;
    font-size: 0.83rem; color: var(--text-muted);
}
.card-footer a {
    color: var(--red); text-decoration: none; font-weight: 600;
    transition: opacity .15s;
}
.card-footer a:hover { opacity: 0.8; }

/* FOOTER */
footer {
    position: relative; z-index: 1;
    display: flex; justify-content: space-between; align-items: center;
    padding: 20px 80px;
    border-top: 1px solid var(--border);
    color: var(--text-muted); font-size: 0.8rem;
}
footer a { color: var(--text-muted); text-decoration: none; }
footer a:hover { color: var(--red); }

/* RESPONSIVE */
@media (max-width: 700px) {
    header { padding: 0 20px; }
    .logo span { display: none; }
    footer { flex-direction: column; gap: 6px; padding: 18px 20px; text-align: center; }
    .login-layout { grid-template-columns: 1fr; border-radius: 20px; }
    .panel-left { border-right: none; border-bottom: 1px solid var(--border); padding: 36px 28px; }
    .panel-left::before { font-size: 110px; }
    .panel-tagline { font-size: 1.6rem; }
    .panel-quote { display: none; }
    .panel-right { padding: 36px 28px; }
}
</style>
</head>
<body>

<div class="ambient"></div>

<!-- HEADER -->
<header>
    <a class="logo" href="home">
        <div class="logo-icon">
            <img src="./images/favicon.png" alt="ChessFeller logo">
        </div>
        <span>ChessNova</span>
    </a>
    <a class="header-link" href="/signin">Create account</a>
</header>

<!-- MAIN -->
<main>
    <div class="login-layout">

        <!-- LEFT PANEL -->
        <div class="panel-left">
            <div>
                <p class="panel-tagline">Play with<br><em>purpose.</em><br>Win with<br>clarity.</p>
                <p class="panel-sub">Join thousands of players sharpening their game on ChessNova.</p>
            </div>
            <div class="panel-quote">
                <div class="quote-rule"></div>
                <p class="quote-text">"Chess is the art of analysis."</p><p class="quote-text" id="author">— Mikhail Botvinnik</p>
            </div>
        </div>

        <!-- RIGHT PANEL -->
        <div class="panel-right">
            <?php if($verify): ?>
                <div style="display:flex;align-items:center;gap:10px;background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.1);border-left:3px solid rgba(255,0,0,0.7);border-radius:10px;padding:12px 16px;margin-bottom:24px;">
                    <i class="fa-regular fa-envelope" style="color:rgba(255,0,0,0.8);font-size:1rem;flex-shrink:0;"></i>
                    <span style="font-size:0.85rem;color:rgba(255,255,255,0.75);line-height:1.5;">Check your email inbox to verify your account.</span>
                </div>
            <?php endif; ?> 

            

            <h1 class="card-title">Log in</h1>
            <p class="card-sub">Insert your credentials to play.</p>

            <div class="divider"></div>

            <form id="loginForm">

                <div class="form-group">
                    <label for="username">Username</label>
                    <div class="input-wrap">
                        <span class="input-icon"><i class="fa-regular fa-chess-pawn"></i></span>
                        <input type="text" id="username" name="username"
                               placeholder="Your username" autocomplete="username">
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-wrap">
                        <span class="input-icon"><i class="fa-solid fa-lock"></i></span>
                        <input type="password" id="password" name="password"
                               placeholder="••••••••" autocomplete="current-password">
                    </div>
                </div>

                <div class="error-msg" id="errorMsg">
                    ⚠ &nbsp;<span id="errorText">Invalid credentials.</span>
                </div>

                <button type="submit" class="btn-submit" id="submitBtn">
                    <span class="btn-label">Login</span>
                    <div class="btn-spinner"></div>
                </button>

            </form>

            <div class="card-footer">
                Don't have an account?
                <a href="./signin">Sign in</a>
            </div>
        </div>
        

    </div>
</main>

<footer>
    <span>© 2026 ChessNova</span>
    
</footer>

<script>
const form      = document.getElementById('loginForm');
const submitBtn = document.getElementById('submitBtn');
const errorMsg  = document.getElementById('errorMsg');
const errorText = document.getElementById('errorText');

pickQuote();

form.addEventListener('submit', async e => {
    e.preventDefault();

    const username = document.getElementById('username').value.trim();
    const password = document.getElementById('password').value;

    errorMsg.classList.remove('show');
    document.getElementById('username').classList.remove('error');
    document.getElementById('password').classList.remove('error');

    if (!username || !password) {
        showError('Compila tutti i campi.');
        if (!username) document.getElementById('username').classList.add('error');
        if (!password) document.getElementById('password').classList.add('error');
        return;
    }

    submitBtn.classList.add('loading');

    try {
        const res  = await fetch('./loginExe', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `username=${encodeURIComponent(username)}&password=${encodeURIComponent(password)}`
        });
        const data = await res.json();

        if (data.success === "success") {
            window.location.href = './home';

        } else if (data.success === "not_verified") {
            showError('Unverified account, please check your email inbox.');
            document.getElementById('username').classList.add('error');
            document.getElementById('password').classList.add('error');

        } else {
            showError('Wrong username or password.');
            document.getElementById('username').classList.add('error');
            document.getElementById('password').classList.add('error');
        }
    } catch {
        showError('Connection error. Try again.');
    } finally {
        submitBtn.classList.remove('loading');
    }
});

function showError(msg) {
    errorText.textContent = msg;
    errorMsg.classList.add('show');
}
function pickQuote(){
    const quote       = document.querySelector('.quote-text'); 
    const author       = document.querySelector('#author'); 
    const chessQuotes = [
  {
    quote: "When you see a good move, look for a better one.",
    author: "- Emanuel Lasker"
  },
  {
    quote: "The hardest game to win is a won game.",
    author: "- Emanuel Lasker"
  },
  {
    quote: "Without error there can be no brilliancy.",
    author: "- Emanuel Lasker"
  },
  {
    quote: "Tactics flow from a superior position.",
    author: "- Bobby Fischer"
  },
  {
    quote: "I don't believe in psychology. I believe in good moves.",
    author: "- Bobby Fischer"
  },
  {
    quote: "Chess demands total concentration.",
    author: "- Bobby Fischer"
  },
  {
    quote: "The blunders are all there on the board, waiting to be made.",
    author: "- Savielly Tartakower"
  },
  {
    quote: "The winner of the game is the player who makes the next-to-last mistake.",
    author: "- Savielly Tartakower"
  },
  {
    quote: "Tactics is knowing what to do when there is something to do. Strategy is knowing what to do when there is nothing to do.",
    author: "- Savielly Tartakower"
  },
  {
    quote: "Pawns are the soul of chess.",
    author: "- Philidor"
  },
  {
    quote: "A sacrifice is best refuted by accepting it.",
    author: "- Wilhelm Steinitz"
  },
  {
    quote: "Attackers may sometimes regret bad moves, but it is much worse to forever regret an opportunity you allowed to pass.",
    author: "- Garry Kasparov"
  },
  {
    quote: "Chess is life.",
    author: "- Bobby Fischer"
  },
  {
    quote: "Chess is everything: art, science, and sport.",
    author: "- Anatoly Karpov"
  },
  {
    quote: "Chess, like love, like music, has the power to make men happy.",
    author: "- Siegbert Tarrasch"
  },
  {
    quote: "The passed pawn is a criminal which should be kept under lock and key.",
    author: "- Aron Nimzowitsch"
  },
  {
    quote: "Even a poor plan is better than no plan at all.",
    author: "- Mikhail Chigorin"
  },
  {
    quote: "Of course, errors are not good for a chess game, but errors are unavoidable.",
    author: "- Mikhail Tal"
  },
  {
    quote: "You must take your opponent into a deep dark forest where 2+2=5.",
    author: "- Mikhail Tal"
  },
  {
    quote: "Blunders rarely travel alone.",
    author: "- Anatoly Karpov"
  }
];
    let n = Math.floor(Math.random() * 20);
    quote.innerHTML  = chessQuotes[n].quote;
    author.innerHTML = chessQuotes[n].author;
}
</script>

</body>
</html>