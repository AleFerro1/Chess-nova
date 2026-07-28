<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="<?= $_SESSION['csrf_token'] ?>">
<title>Edit Profile - ChessNova</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="icon" type="image/png" sizes="64x64" href="./images/favicon.png">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
    --red: rgba(255, 0, 0, 0.85);
    --red:      rgba(255, 0, 0, 0.85);
    --red-dim:  rgba(242, 37, 37, 0.52);
    --red-glow: rgba(240, 1, 1, 0.22);
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
    position: fixed;
    top: -180px; left: 50%;
    transform: translateX(-50%);
    width: 800px; height: 500px;
    background: radial-gradient(ellipse, rgba(201, 93, 93, 0.07) 0%, transparent 65%);
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
    color: var(--red); letter-spacing: 0;
    text-decoration: none;
    display: flex; align-items: center; gap: 10px;
}
.logo-icon {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    
}

.logo-icon img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

nav { display: flex; align-items: center; gap: 8px; }
.nav-link {
    text-decoration: none; color: var(--text-muted);
    font-size: 0.88rem; font-weight: 500;
    padding: 8px 14px; border-radius: 10px;
    transition: color .2s, background .2s;
}
.nav-link:hover { color: var(--text); background: var(--bg-surface); }
.nav-logout {
    text-decoration: none;
    font-size: 0.88rem; font-weight: 600;
    color: #ff8d8d; padding: 8px 16px;
    border-radius: 10px;
    border: 1px solid rgba(255,100,100,0.18);
    background: rgba(255,60,60,0.06);
    transition: background .2s;
}
.nav-logout:hover { background: rgba(255,60,60,0.12); }

/* ── PAGE ── */
.page {
    position: relative; z-index: 1;
    max-width: 860px;
    margin: 0 auto;
    padding: 52px 32px 80px;
}

/* ── PAGE HEADER ── */
.breadcrumb {
    display: flex; align-items: center; gap: 8px;
    font-size: 0.78rem; color: var(--text-muted);
    margin-bottom: 16px;
}
.breadcrumb a { color: var(--text-muted); text-decoration: none; transition: color .15s; }
.breadcrumb a:hover { color: var(--red); }
.breadcrumb-sep { opacity: 0.4; }

.page-title {
    font-family: var(--font-head);
    font-size: 1.9rem; font-weight: 700;
    letter-spacing: -0.4px; margin-bottom: 6px;
}
.page-sub {
    font-size: 0.9rem; color: var(--text-muted);
    line-height: 1.6; margin-bottom: 36px;
}

/* banners */
.banner {
    display: flex; align-items: center; gap: 12px;
    padding: 13px 18px; border-radius: 13px;
    font-size: 0.88rem; font-weight: 600;
    margin-bottom: 24px;
    animation: fadeIn .2s ease;
}
@keyframes fadeIn { from { opacity:0; transform:translateY(-5px); } to { opacity:1; transform:translateY(0); } }
.banner-success {
    background: rgba(201, 93, 93, 0.1);
    border: 1px solid rgba(201, 93, 93, 0.28);
    color: #e47a7a;
}

/* ── FORM CARD ── */
.form-card {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: 24px;
    overflow: hidden;
    box-shadow: 0 30px 60px rgba(0,0,0,0.5);
}

/* sections inside card */
.form-section {
    padding: 28px 32px;
    border-bottom: 1px solid var(--border);
}
.form-section:last-child { border-bottom: none; }

.section-label {
    font-size: 0.7rem; font-weight: 600;
    letter-spacing: 0.1em; text-transform: uppercase;
    color: var(--text-muted);
    margin-bottom: 20px;
    display: flex; align-items: center; gap: 10px;
}
.section-label::after {
    content: ''; flex: 1; height: 1px; background: var(--border);
}

/* ── AVATAR ── */
.avatar-row {
    display: flex; align-items: center; gap: 22px; flex-wrap: wrap;
}
.avatar-preview {
    width: 82px; height: 82px; border-radius: 50%;
    overflow: hidden; flex-shrink: 0;
    border: 2px solid rgba(201, 93, 93, 0.3);
    box-shadow: 0 0 20px rgba(201, 93, 93, 0.18);
}
.avatar-preview img { width: 100%; height: 100%; object-fit: cover; display: block; }

.avatar-actions { display: flex; flex-direction: column; gap: 9px; }
.avatar-btn {
    display: inline-flex; align-items: center; gap: 7px;
    padding: 9px 16px; border: none; border-radius: 10px;
    font-family: var(--font-body); font-size: 0.83rem; font-weight: 600;
    cursor: pointer; transition: transform .15s, opacity .15s;
    white-space: nowrap;
}
.avatar-btn:hover { transform: translateY(-1px); }
.btn-upload { background: linear-gradient(145deg, var(--red), var(--red-dim)); color: #0d1a06; }
.btn-remove { background: rgba(255,60,60,0.1); color: #ff8d8d; border: 1px solid rgba(255,60,60,0.18); }
.avatar-hint { font-size: 0.73rem; color: var(--text-muted); line-height: 1.5; }

/* ── GRID ── */
.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
.form-group { display: flex; flex-direction: column; gap: 8px; }
.form-group.full { grid-column: 1 / -1; }

label {
    font-size: 0.8rem; font-weight: 600;
    color: rgba(255,255,255,0.72);
    display: flex; align-items: center; gap: 6px;
}
label .hint { font-weight: 400; color: var(--text-muted); }

input[type="text"],
input[type="email"],
input[type="password"],
textarea {
    width: 100%; padding: 12px 15px;
    border: 1px solid var(--border); border-radius: 11px;
    background: rgba(255,255,255,0.04);
    color: var(--text); font-family: var(--font-body); font-size: 0.92rem;
    outline: none; transition: border-color .2s, box-shadow .2s, background .2s;
}
input::placeholder, textarea::placeholder { color: var(--text-muted); }
input:focus, textarea:focus {
    border-color: var(--red);
    background: rgba(201, 93, 93, 0.04);
    box-shadow: 0 0 0 3px rgba(201, 93, 93, 0.1);
}
input.error {
    border-color: rgba(255,60,60,0.5) !important;
    box-shadow: 0 0 0 3px rgba(255,60,60,0.08) !important;
}
textarea { resize: vertical; min-height: 105px; }

/* errors */
.field-error {
    display: flex; align-items: center; gap: 6px;
    font-size: 0.78rem; font-weight: 500; color: #ff7070;
    background: rgba(255,60,60,0.07);
    border: 1px solid rgba(255,60,60,0.18);
    border-radius: 8px; padding: 6px 11px;
    animation: errorPop .18s ease-out;
}
@keyframes errorPop { from { opacity:0; transform:translateY(-5px); } to { opacity:1; transform:translateY(0); } }

/* ── DROPDOWN ── */
.dropdown { position: relative; }
.dropdown-selected {
    display: flex; align-items: center;
    padding: 12px 15px; border: 1px solid var(--border); border-radius: 11px;
    background: rgba(255,255,255,0.04); color: var(--text);
    font-size: 0.92rem; cursor: pointer; user-select: none;
    transition: border-color .2s, box-shadow .2s;
}
.dropdown-selected:hover,
.dropdown-selected.open {
    border-color: var(--red);
    box-shadow: 0 0 0 3px rgba(201, 93, 93, 0.1);
}
.dropdown-arrow { margin-left: auto; font-size: 0.65rem; opacity: 0.45; transition: transform .2s; }
.dropdown-selected.open .dropdown-arrow { transform: rotate(180deg); }

.dropdown-menu {
    position: absolute; top: calc(100% + 5px); left: 0; width: 100%;
    background: #0d1018; border: 1px solid var(--border); border-radius: 13px;
    display: none; flex-direction: column;
    box-shadow: 0 16px 40px rgba(0,0,0,0.6); z-index: 1000; overflow: hidden;
}
.dropdown-menu.open { display: flex; }
.dropdown-search {
    padding: 11px 14px; border: none; border-bottom: 1px solid var(--border);
    background: transparent; color: var(--text);
    font-family: var(--font-body); font-size: 0.87rem; outline: none;
}
.dropdown-search::placeholder { color: var(--text-muted); }
.dropdown-list { max-height: 195px; overflow-y: auto; }
.dropdown-item {
    padding: 10px 14px; font-size: 0.87rem;
    cursor: pointer; color: var(--text); transition: background .12s;
}
.dropdown-item:hover { background: rgba(201, 93, 93, 0.1); color: var(--red); }
.dropdown-list::-webkit-scrollbar { width: 5px; }
.dropdown-list::-webkit-scrollbar-thumb { background: rgba(201, 93, 93, 0.3); border-radius: 10px; }

/* ── ACTIONS ── */
.form-actions {
    display: flex; justify-content: flex-end; gap: 12px;
    padding: 24px 32px;
    
    
}
.btn {
    display: inline-flex; align-items: center; gap: 7px;
    padding: 12px 22px; border: none; border-radius: 11px;
    font-family: var(--font-body); font-size: 0.88rem; font-weight: 600;
    cursor: pointer; transition: transform .18s, box-shadow .18s;
    text-decoration: none;
}
.btn:hover { transform: translateY(-2px); }
.btn-cancel {
    background: var(--bg-surface); color: var(--text-muted);
    border: 1px solid var(--border);
}
.btn-cancel:hover { background: rgba(255,255,255,0.07); color: var(--text); }
.btn-save {
    background: linear-gradient(145deg, var(--red), var(--red-dim));
    color: #1a0606;
    box-shadow: 0 7px 20px rgba(201, 93, 93, 0.26);
}
.btn-save:hover { box-shadow: 0 10px 26px rgba(201, 93, 93, 0.4); }

/* ── RESPONSIVE ── */
@media (max-width: 700px) {
    header { padding: 0 20px; }
    .page { padding: 30px 14px 60px; }
    .form-section { padding: 22px 18px; }
    .form-actions { padding: 18px; flex-direction: column-reverse; }
    .form-grid { grid-template-columns: 1fr; }
    .btn { width: 100%; justify-content: center; }
    .avatar-row { flex-direction: column; align-items: flex-start; }
}
</style>
</head>
<body>

<div class="ambient"></div>

<!-- HEADER -->
<header>
    <a class="logo" href="./home">
        <div class="logo-icon">
            <img src="./images/favicon.png" alt="ChessFeller logo">
        </div>
        ChessNova
    </a>
    <nav>
        <a class="nav-link" href="./home">Home</a>
        <a class="nav-link" href="./profile">Profile</a>
        <a class="nav-logout" href="./logout">Logout</a>
    </nav>
</header>

<!-- PAGE -->
<div class="page">

    <div class="breadcrumb">
        <a href="./home">Home</a>
        <span class="breadcrumb-sep">›</span>
        <a href="./profile">Profile</a>
        <span class="breadcrumb-sep">›</span>
        <span>Edit</span>
    </div>

    <h1 class="page-title">Edit profile</h1>
    <p class="page-sub">Update your personal info and customize your account.</p>

    <?php if (!empty($_SESSION['success'])): ?>
        <div class="banner banner-success">
            ✓ &nbsp;<?= htmlspecialchars($_SESSION['success']) ?>
        </div>
    <?php endif; ?>

    <!-- FORM -->
    <form method="POST" action="./editProfile" enctype="multipart/form-data">
        <input type="hidden" name="_csrf" value="<?= $_SESSION['csrf_token'] ?>">
        <div class="form-card">

            <!-- AVATAR -->
            <div class="form-section">
                <div class="section-label">Avatar</div>
                <?php
                    $avatarSrc = !empty($profile['avatar'])
                        ? '/public/' . htmlspecialchars($profile['avatar'])
                        : '/public/assets/img/redking.jpg';
                ?>
                <div class="avatar-row">
                    <div class="avatar-preview">
                        <img id="avatarPreview" src="<?= $avatarSrc ?>" alt="Avatar">
                    </div>
                    <div class="avatar-actions">
                        <button type="button" class="avatar-btn btn-upload" id="changeAvatarBtn">
                            ↑ &nbsp;Upload picture
                        </button>
                        <button type="button" class="avatar-btn btn-remove" id="removeAvatarBtn">
                            ✕ &nbsp;Remove avatar
                        </button>
                        <p class="avatar-hint">JPG, PNG, GIF, WEBP · max 2 MB</p>
                    </div>
                    <input type="file" id="avatarInput" name="avatar"
                           accept="image/jpeg,image/png,image/gif,image/webp" hidden>
                    <input type="hidden" name="remove_avatar" id="removeAvatarFlag" value="0">
                </div>
                <?php if (!empty($_SESSION['avatar_error'])): ?>
                    <div class="field-error" style="margin-top:14px;">
                        ⚠ <?= htmlspecialchars($_SESSION['avatar_error']) ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- DATI ACCOUNT -->
            <div class="form-section">
                <div class="section-label">Account information</div>
                <div class="form-grid">

                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="username"
                               value="<?= htmlspecialchars($profile['username_utente']) ?>"
                               <?php if (!empty($_SESSION['username_error'])): ?>class="error"<?php endif; ?>>
                        <?php if (!empty($_SESSION['username_error'])): ?>
                            <div class="field-error">⚠ <?= htmlspecialchars($_SESSION['username_error']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email"
                               value="<?= htmlspecialchars($profile['email']) ?>"
                               <?php if (!empty($_SESSION['email_error'])): ?>class="error"<?php endif; ?>>
                        <?php if (!empty($_SESSION['email_error'])): ?>
                            <div class="field-error">⚠ <?= htmlspecialchars($_SESSION['email_error']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group full">
                        <label>Bio</label>
                        <textarea name="bio" placeholder="About yourself…"
                        <?php if (!empty($_SESSION['bio_error'])): ?>class="error"<?php endif; ?>
                        ><?= htmlspecialchars(trim($profile['biografia'] ?? '')) ?>
                        </textarea>
                        <?php if (!empty($_SESSION['bio_error'])): ?>
                            <div class="field-error">⚠ <?= htmlspecialchars($_SESSION['bio_error']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label>Country</label>
                        <div class="dropdown" id="countryDropdown">
                            <div class="dropdown-selected" id="selectedCountry">
                                Choose country
                                <span class="dropdown-arrow">▼</span>
                            </div>
                            <div class="dropdown-menu" id="dropdownMenu">
                                <input type="text" class="dropdown-search"
                                       id="dropdownSearch" placeholder="Search country…">
                                <div class="dropdown-list" id="dropdownList"></div>
                            </div>
                            <input type="hidden" name="country" id="countryInput">
                        </div>
                    </div>

                </div>
            </div>

            <!-- PASSWORD -->
            <div class="form-section">
                <div class="section-label">Change password</div>
                <div class="form-grid">

                    <div class="form-group">
                        <label>
                            Current password
                            <span class="hint">(only if you want to change it)</span>
                        </label>
                        <input type="password" name="current_password" placeholder="••••••••"
                               <?php if (!empty($_SESSION['oldPassword_error'])): ?>class="error"<?php endif; ?>>
                        <?php if (!empty($_SESSION['oldPassword_error'])): ?>
                            <div class="field-error">⚠ <?= htmlspecialchars($_SESSION['oldPassword_error']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label>New password</label>
                        <input type="password" name="new_password"
                               placeholder="At least 8 characters + a number"
                               <?php if (!empty($_SESSION['password_numberError']) || !empty($_SESSION['password_lenghtError'])): ?>class="error"<?php endif; ?>>
                        <?php if (!empty($_SESSION['password_lenghtError'])): ?>
                            <div class="field-error">⚠ <?= htmlspecialchars($_SESSION['password_lenghtError']) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($_SESSION['password_numberError'])): ?>
                            <div class="field-error">⚠ <?= htmlspecialchars($_SESSION['password_numberError']) ?></div>
                        <?php endif; ?>
                    </div>

                </div>
            </div>

        </div><!-- /form-card -->

        <!-- AZIONI -->
        <div class="form-actions">
            <button type="button" class="btn btn-cancel"
                    onclick="window.location.href='profile'">
                Undo
            </button>
            <button type="submit" class="btn btn-save">
                ✓ &nbsp;Save
            </button>
        </div>

    </form>
</div>

<script>
/* ── AVATAR ── */
const changeBtn   = document.getElementById('changeAvatarBtn');
const removeBtn   = document.getElementById('removeAvatarBtn');
const avatarInput = document.getElementById('avatarInput');
const preview     = document.getElementById('avatarPreview');
const removeFlag  = document.getElementById('removeAvatarFlag');

const DEFAULT_AVATAR = '/public/assets/img/redking.jpg';
const savedAvatar    = "<?= !empty($profile['avatar']) ? '/public/' . htmlspecialchars($profile['avatar']) : '' ?>";

changeBtn.addEventListener('click', () => avatarInput.click());

avatarInput.addEventListener('change', function () {
    const file = this.files[0];
    if (!file) return;
    removeFlag.value = '0';
    removeBtn.innerHTML = '✕ &nbsp;Rimuovi avatar';
    const reader = new FileReader();
    reader.onload = e => { preview.src = e.target.result; };
    reader.readAsDataURL(file);
});

removeBtn.addEventListener('click', () => {
    if (removeFlag.value === '1') {
        removeFlag.value = '0';
        removeBtn.innerHTML = '✕ &nbsp;Rimuovi avatar';
        preview.src = savedAvatar || DEFAULT_AVATAR;
    } else {
        removeFlag.value = '1';
        removeBtn.innerHTML = '↩ &nbsp;Annulla rimozione';
        avatarInput.value = '';
        preview.src = DEFAULT_AVATAR;
    }
});

/* ── DROPDOWN PAESE ── */
const countries    = <?= json_encode($countries) ?>;
const savedCountry = "<?= htmlspecialchars($profile['country'] ?? '') ?>";

const selEl    = document.getElementById('selectedCountry');
const menuEl   = document.getElementById('dropdownMenu');
const listEl   = document.getElementById('dropdownList');
const searchEl = document.getElementById('dropdownSearch');
const inputEl  = document.getElementById('countryInput');

let open = false;

function setOpen(val) {
    open = val;
    menuEl.classList.toggle('open', open);
    selEl.classList.toggle('open', open);
    if (open) { searchEl.focus(); searchEl.value = ''; renderList(); }
}

function renderList(filter = '') {
    listEl.innerHTML = '';
    Object.entries(countries)
        .filter(([, name]) => name.toLowerCase().includes(filter.toLowerCase()))
        .forEach(([code, name]) => {
            const div = document.createElement('div');
            div.className = 'dropdown-item';
            div.textContent = name;
            div.addEventListener('click', () => {
                // aggiorna solo il testo, preserva la freccia
                selEl.firstChild.textContent = name + '\u00a0';
                inputEl.value = code;
                setOpen(false);
            });
            listEl.appendChild(div);
        });
}

renderList();
selEl.addEventListener('click', () => setOpen(!open));
searchEl.addEventListener('input', e => renderList(e.target.value));
document.addEventListener('click', e => {
    if (!document.getElementById('countryDropdown').contains(e.target)) setOpen(false);
});

if (savedCountry && countries[savedCountry]) {
    selEl.firstChild.textContent = countries[savedCountry] + '\u00a0';
    inputEl.value = savedCountry;
}
</script>

</body>
</html>