<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Email Verification - ChessNova</title>

<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">

<style>
:root {
    --bg: #080b10;
    --card: rgba(14,17,26,0.97);
    --text: #ffffff;
    --muted: rgba(255,255,255,0.6);
    --red: rgba(255, 0, 0, 0.85);
    --border: rgba(255,255,255,0.08);
    --font-head: 'Syne', sans-serif;
    --font-body: 'DM Sans', sans-serif;
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: var(--font-body);
    background: var(--bg);
    color: var(--text);
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.card {
    width: 100%;
    max-width: 420px;
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: 20px;
    padding: 40px;
    text-align: center;
    box-shadow: 0 20px 60px rgba(0,0,0,0.5);
}

h1 {
    font-family: var(--font-head);
    font-size: 1.6rem;
    margin-bottom: 10px;
}

p {
    color: var(--muted);
    font-size: 0.95rem;
    line-height: 1.5;
}

.icon {
    font-size: 50px;
    margin-bottom: 20px;
}

.success {
    color: #4ade80;
}

.error {
    color: #ff4d4d;
}

.btn {
    display: inline-block;
    margin-top: 25px;
    padding: 12px 18px;
    border-radius: 12px;
    text-decoration: none;
    font-weight: 600;
    font-size: 0.9rem;
    background: var(--red);
    color: #fff;
    transition: 0.2s;
}

.btn:hover {
    opacity: 0.85;
}
</style>
</head>

<body>

<div class="card">

<?php if (isset($success) && $success): ?>

    <div class="icon success">?</div>
    <h1>Email verified</h1>
    <p>Your account has been activated successfully.<br>You can now log in to ChessNova.</p>

    <a class="btn" href="/login">Go to Login</a>

<?php else: ?>

    <div class="icon error">?</div>
    <h1>Link not valid</h1>
    <p>The token has expired or is not valid.<br>Request a new verification email.</p>

    <a class="btn" href="/signin">Sign Up Again</a>

<?php endif; ?>

</div>

</body>
</html>