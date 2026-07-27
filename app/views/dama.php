<?php
session_start();

// ═══════════════════════════════════════════════
// 1. Controlli di accesso e validazione variabili
// ═══════════════════════════════════════════════
if (!isset($_SESSION['username'], $_SESSION['id_partita'])) {
    die('Accesso negato.');
}
$id_partita = $_SESSION['id_partita']; // MAI fidarsi del client!

// Recupero dati partita (questi dovrebbero essere già stati estratti dal DB)
$required = ['board', 'turn', 'fen', 'tempo_bianco', 'tempo_nero',
             'tempo_ultima_mossa', 'coloreGiocatore', 'username',
             'nomeBianco', 'nomeNero', 'elo_bianco', 'elo_nero', 'timecontrol'];
foreach ($required as $var) {
    if (!isset($$var)) $$var = null; // In produzione: lanciare eccezione
}

// Escape per output HTML
$coloreGiocatore = htmlspecialchars($coloreGiocatore);
$username        = htmlspecialchars($username);
$nomeBianco      = htmlspecialchars($nomeBianco);
$nomeNero        = htmlspecialchars($nomeNero);

// Funzione helper per avatar
function getAvatar($data, $default = '/public/assets/img/redking.jpg') {
    return !empty($data['avatar'])
        ? '/public/' . htmlspecialchars($data['avatar'])
        : $default;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/styles/scacchiera.css">
    <link rel="icon" type="image/png" sizes="64x64" href="/images/favicon.png">
    <title>Italian Checkers - ChessNova</title>
</head>
<body>
    <div class="layout">
        <div class="left">
            <?php
                // ─── Variabili per evitare duplicazione ───
                $isBianco = ($coloreGiocatore === 'bianco');
                $topColor  = $isBianco ? 'nero'   : 'bianco';
                $botColor  = $isBianco ? 'bianco' : 'nero';
                $topName   = $isBianco ? $nomeNero  : $nomeBianco;
                $botName   = $isBianco ? $nomeBianco: $nomeNero;
                $topElo    = $isBianco ? $elo_nero   : $elo_bianco;
                $botElo    = $isBianco ? $elo_bianco : $elo_nero;
            ?>
            <!-- ─── Avversario (sopra) ─── -->
            <div class="top-bar">
                <div class="player-info player-<?= $topColor ?>">
                    <div class="player-left">
                        <div class="player-avatar">
                            <img src="<?= getAvatar($topElo) ?>" alt="Avatar">
                        </div>
                        <div class="player-meta">
                            <div class="player-name">
                                <?php if (!empty($topElo['country'])): ?>
                                    <img class="flag" src="https://flagcdn.com/w20/<?= strtolower(htmlspecialchars($topElo['country'])) ?>.png" alt="Flag">
                                <?php endif; ?>
                                <?= htmlspecialchars($topName) ?>
                            </div>
                            <div class="player-rank">
                                Elo <?= htmlspecialchars($topElo[$timecontrol] ?? '---') ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="timer<?= ucfirst($topColor) ?>" class="timer timer-<?= $topColor ?>">10:00</div>
            </div>

            <!-- Scacchiera -->
            <div id="scacchiera" class="scacchiera"></div>
            <button id="resignBtn" class="resign-btn">Resign</button>

            <!-- ─── Giocatore locale (sotto) ─── -->
            <div class="bottom-bar">
                <div class="player-info player-<?= $botColor ?>">
                    <div class="player-left">
                        <div class="player-avatar">
                            <img src="<?= getAvatar($botElo) ?>" alt="Avatar">
                        </div>
                        <div class="player-meta">
                            <div class="player-name">
                                <?php if (!empty($botElo['country'])): ?>
                                    <img class="flag" src="https://flagcdn.com/w20/<?= strtolower(htmlspecialchars($botElo['country'])) ?>.png" alt="Flag">
                                <?php endif; ?>
                                <?= htmlspecialchars($botName) ?>
                            </div>
                            <div class="player-rank">
                                Elo <?= htmlspecialchars($botElo[$timecontrol] ?? '---') ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="timer<?= ucfirst($botColor) ?>" class="timer timer-<?= $botColor ?>">10:00</div>
            </div>
        </div>
    </div>

    <!-- End screen -->
    <div id="endScreen" class="end-screen hidden">
        <div class="end-box">
            <h1 id="endTitle">Game ended</h1>
            <p id="endReason">Result</p>
            <div class="end-buttons">
                <button id="esci">Home</button>
            </div>
        </div>
    </div>

    <!-- Audio precaricato -->
    <audio id="endSound" src="/sounds/game-end.mp3" preload="auto"></audio>

    <script>
        // ═══════════════════════════════════════════
        // 2. Passaggio sicuro variabili PHP → JS
        // ═══════════════════════════════════════════
        window.board        = <?= json_encode($board) ?>;
        window.turn         = <?= json_encode($turn) ?>;
        window.playerColor  = <?= json_encode($coloreGiocatore) ?>;
        window.fen          = <?= json_encode($fen) ?>;
        window.tempoBianco  = <?= (int)$tempo_bianco ?>;
        window.tempoNero    = <?= (int)$tempo_nero ?>;
        window.lastMoveTime = <?= (int)$tempo_ultima_mossa ?>;
        window.username     = <?= json_encode($username) ?>;
        window.gameId       = <?= json_encode($id_partita) ?>;  // Ora dal server

        // ═══════════════════════════════════════════
        // 3. WebSocket con riconnessione automatica
        // ═══════════════════════════════════════════
        let ws;
        let reconnectAttempts = 0;
        const maxReconnectDelay = 30000;

        function connectWebSocket() {
            if (ws && ws.readyState !== WebSocket.CLOSED) return;
            ws = new WebSocket(`wss://chessnova.win/wss/`);

            ws.onopen = () => {
                reconnectAttempts = 0;
                ws.send(JSON.stringify({
                    type:    'join',
                    game_id: window.gameId,
                    username: window.username
                }));
            };

            ws.onmessage = (event) => {
                const data = JSON.parse(event.data);
                switch (data.type) {
                    case 'opponent_move':
                        currentFen   = data.fen;
                        window.board = fenToBoard(data.fen);
                        currentTurn  = data.turn;

                        if (data.timers) {
                            // Correzione: struttura piatta
                            tempoBianco = parseInt(data.timers.bianco);
                            tempoNero   = parseInt(data.timers.nero);
                        }

                        avviaTimer();
                        aggiornaVisualTimer();

                        if (data.notation) {
                            lastMove = { from: data.notation.from, to: data.notation.to };
                        }

                        document.getElementById('scacchiera').innerHTML = '';
                        renderBoard(window.board);
                        // Rimossa chiamata a updateRecord inesistente
                        break;

                    case 'game_over':
                        handleGameOver(data);
                        break;
                }
            };

            ws.onerror = (e) => console.error('WebSocket error:', e);

            ws.onclose = () => {
                console.log('WebSocket chiuso, riconnessione...');
                const delay = Math.min(1000 * Math.pow(2, reconnectAttempts), maxReconnectDelay);
                reconnectAttempts++;
                setTimeout(connectWebSocket, delay);
            };
        }

        connectWebSocket();

        // ═══════════════════════════════════════════
        // 4. Stato globale
        // ═══════════════════════════════════════════
        let selected = null;
        let currentTurn = window.turn || 'w';
        let lastMove = null;
        let legalSquares = [];
        let tempoBianco = window.tempoBianco || 600;
        let tempoNero = window.tempoNero || 600;
        let intervalTimer = null;
        let currentFen = window.fen;
        let gameOver = false;

        // ═══════════════════════════════════════════
        // 5. Funzioni di supporto
        // ═══════════════════════════════════════════
        function fenToBoard(fen) {
            const board = Array.from({ length: 8 }, () => Array(8).fill(null));
            const rows = fen.split(" ")[0].split("/");
            for (let i = 0; i < 8; i++) {
                let col = 0;
                for (let char of rows[i]) {
                    if (isNaN(char)) {
                        board[i][col] = char;
                        col++;
                    } else {
                        col += parseInt(char);
                    }
                }
            }
            return board;
        }

        function toSquare(i, j) {
            const files = ["a","b","c","d","e","f","g","h"];
            return files[j] + (8 - i);
        }

        function formatTime(seconds) {
            const mins = Math.floor(Math.max(0, seconds) / 60);
            const secs = Math.floor(Math.max(0, seconds) % 60);
            return `${mins}:${secs.toString().padStart(2, '0')}`;
        }

        function aggiornaVisualTimer() {
            document.getElementById('timerBianco').textContent = formatTime(tempoBianco);
            document.getElementById('timerNero').textContent = formatTime(tempoNero);
            document.getElementById('timerBianco').classList.toggle('attivo', currentTurn === 'w');
            document.getElementById('timerNero').classList.toggle('attivo', currentTurn === 'b');
        }

        function avviaTimer() {
            if (intervalTimer) clearInterval(intervalTimer);
            intervalTimer = setInterval(() => {
                if (currentTurn === 'w') {
                    tempoBianco--;
                    if (tempoBianco <= 0) {
                        tempoBianco = 0;
                        clearInterval(intervalTimer);
                        document.getElementById('timerBianco').classList.add('scaduto');
                        new Audio('/sounds/game-end.mp3').play();

                        // --- TIMOUT BIANCO: vince il nero ---
                        broadcastGameOver('timeout', 'nero');
                        endGame('Timeout', 'Black wins!');
                    }
                } else {
                    tempoNero--;
                    if (tempoNero <= 0) {
                        tempoNero = 0;
                        clearInterval(intervalTimer);
                        document.getElementById('timerNero').classList.add('scaduto');
                        new Audio('/sounds/game-end.mp3').play();

                        // --- TIMEOUT NERO: vince il bianco ---
                        broadcastGameOver('timeout', 'bianco');
                        endGame('Timeout', 'White wins!');
                    }
                }
                aggiornaVisualTimer();
            }, 1000);
        }

        function initTimerFromServer() {
            const now = Math.floor(Date.now() / 1000);
            const elapsed = now - window.lastMoveTime;
            if (window.turn === 'w') {
                tempoBianco = Math.max(0, window.tempoBianco - elapsed);
            } else {
                tempoNero = Math.max(0, window.tempoNero - elapsed);
            }
            avviaTimer();
            aggiornaVisualTimer();
        }

        // ═══════════════════════════════════════════
        // 6. Rendering scacchiera
        // ═══════════════════════════════════════════
        function renderBoard(board) {
            const scacchiera = document.getElementById("scacchiera");
            if (!scacchiera || !board) return;
            scacchiera.innerHTML = "";
            const isWhitePerspective = (window.playerColor === 'bianco');

            for (let i = 0; i < 8; i++) {
                for (let j = 0; j < 8; j++) {
                    const row = isWhitePerspective ? i : 7 - i;
                    const col = isWhitePerspective ? j : 7 - j;
                    const square = document.createElement("div");
                    square.className = (row + col) % 2 === 0 ? "casellaBianca" : "casellaNera";
                    square.dataset.row = row;
                    square.dataset.col = col;

                    if (legalSquares.some(m => m[0] === row && m[1] === col)) {
                        square.classList.add(board[row][col] !== null ? "legalCapture" : "legalMove");
                    }

                    const piece = board[row][col];
                    if (piece !== null) {
                        const img = document.createElement("img");
                        const pieceImages = {
                            'b': 'damaPiccolaNera',
                            'w': 'damaPiccolaBianca',
                            'B': 'damaNeraPromossa',
                            'W': 'damaBiancaPromossa'
                        };
                        img.src = `/images/${pieceImages[piece]}.png`;
                        img.className = "pedina";
                        square.appendChild(img);
                    }
                    square.addEventListener("click", () => handleClick(row, col, board, square));
                    scacchiera.appendChild(square);
                }
            }
        }

        // ═══════════════════════════════════════════
        // 7. Gestione click e mosse
        // ═══════════════════════════════════════════
        function handleClick(i, j, board, square) {
            if (gameOver) return;
            if (!selected) {
                if (board[i][j] === null) return;
                const piece = board[i][j];
                const isWhitePiece = (piece === 'w' || piece === 'W');
                if (currentTurn === 'w' && !isWhitePiece) return;
                if (currentTurn === 'b' && isWhitePiece) return;
                selectPiece(i, j, piece);
            } else {
                const isWhitePiece = board[i][j] !== null && (board[i][j] === 'W' || board[i][j] === 'w');
                const isMyPiece = board[i][j] !== null &&
                    ((currentTurn === 'w' && isWhitePiece) || (currentTurn === 'b' && !isWhitePiece));

                if (isMyPiece) {
                    selectPiece(i, j, board[i][j]);
                    return;
                }

                const savedSelected = { ...selected };
                const turnBeforeMove = currentTurn;
                selected.element.classList.remove("selected");
                selected = null;
                legalSquares = [];

                fetch("/dama?id=" + window.gameId, {
                    method: "POST",
                    credentials: "same-origin",
                    body: new URLSearchParams({
                        piece: savedSelected.piece,
                        from: toSquare(savedSelected.i, savedSelected.j),
                        to: toSquare(i, j)
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.promozione) {
                        showPromoMenu(data.fen_base, toSquare(i, j), turnBeforeMove);
                        return;
                    }
                    console.log(data.tempo.tempo);
                    if (data.success) {
                        if (data.tempo) {
                            //const nuovoTempo = parseInt(data.tempo.tempo.tempo_bianco);
                            if (data.tempo.colore === 'w') {
                                tempoBianco = parseInt(data.tempo.tempo.tempo_bianco);
                            } else {
                                tempoNero = parseInt(data.tempo.tempo.tempo_nero);
                            }
                            avviaTimer();
                            aggiornaVisualTimer();
                        }
                        currentFen = data.fen;
                        window.board = data.board;
                        currentTurn = data.turn;
                        document.getElementById("scacchiera").innerHTML = '';
                        renderBoard(window.board);

                        ws.send(JSON.stringify({
                            type:     'move',
                            game_id:  window.gameId,
                            fen:      data.fen,
                            turn:     currentTurn,
                            moves:    data.storico?.moves ?? [],
                            timers:   { bianco: tempoBianco, nero: tempoNero },
                            notation: data.notation ?? null,
                        }));

                        if (data.checkmate) {
                            endGame('Checkmate', data.winner === 'bianco' ? 'White wins!' : 'Black wins!');
                            broadcastGameOver('checkmate', data.winner);
                            return;
                        }
                        if (data.notation && data.notation.from && data.notation.to) {
                            lastMove = { from: data.notation.from, to: data.notation.to };
                        }
                    } else {
                        selectPiece(savedSelected.i, savedSelected.j, savedSelected.piece);
                    }
                });
            }
        }

        function selectPiece(i, j, piece) {
            if (selected) selected.element.classList.remove("selected");
            legalSquares = [];
            document.getElementById("scacchiera").innerHTML = '';
            renderBoard(window.board);

            const element = document.querySelector(
                `.casellaBianca[data-row="${i}"][data-col="${j}"], .casellaNera[data-row="${i}"][data-col="${j}"]`
            );
            if (element) element.classList.add("selected");
            selected = { i, j, piece, element };

            fetch("/legal-moves?id=" + window.gameId, {
                method: "POST",
                credentials: "same-origin",
                body: new URLSearchParams({ piece, from: toSquare(i, j) })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    legalSquares = data.moves;
                    document.getElementById("scacchiera").innerHTML = '';
                    renderBoard(window.board);
                    const newElement = document.querySelector(
                        `.casellaBianca[data-row="${i}"][data-col="${j}"], .casellaNera[data-row="${i}"][data-col="${j}"]`
                    );
                    if (newElement) {
                        selected.element = newElement;
                        selected.element.classList.add("selected");
                    }
                }
            });
        }

        // ═══════════════════════════════════════════
        // 8. Promozione
        // ═══════════════════════════════════════════
        function showPromoMenu(fenBase, to, turn) {
            fetch("/promuovi?id=" + window.gameId, {
                method: "POST",
                credentials: "same-origin",
                body: new URLSearchParams({ fen_base: fenBase, promo: 'q', to: to, turn: turn })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    currentFen = data.fen;
                    window.board = data.board;
                    currentTurn = data.turn !== undefined ? data.turn : (currentTurn === 'w' ? 'b' : 'w');
                    document.getElementById("scacchiera").innerHTML = '';
                    renderBoard(window.board);

                    ws.send(JSON.stringify({
                        type:     'move',
                        game_id:  window.gameId,
                        fen:      data.fen,
                        turn:     currentTurn,
                        moves:    data.storico?.moves ?? [],
                        timers:   { bianco: tempoBianco, nero: tempoNero },
                        notation: data.notation ?? null,
                    }));

                    if (data.checkmate) endGame("Checkmate", data.winner === "bianco" ? "White wins" : "Black wins");
                    else if (data.stalemate) endGame("Draw", "Stalemate");
                }
            });
        }

        // ═══════════════════════════════════════════
        // 9. Fine partita
        // ═══════════════════════════════════════════
        function endGame(title, reason) {
            if (gameOver) return;
            gameOver = true;
            clearInterval(intervalTimer);
            intervalTimer = null;
            showEndScreen(title, reason);
        }

        function showEndScreen(title, reason) {
            document.getElementById("endTitle").textContent  = title;
            document.getElementById("endReason").textContent = reason;
            document.getElementById("endScreen").classList.remove("hidden");
        }

        function broadcastGameOver(reason, winner = null) {
            ws.send(JSON.stringify({
                type:    'game_over',
                game_id: window.gameId,
                reason,
                winner,
            }));
        }

        function handleGameOver(data) {
            const messages = {
                'checkmate':     ['Checkmate',        data.winner === 'bianco' ? 'White wins!' : 'Black wins!'],
                'stalemate':     ['Draw',             'Stalemate'],
                'resign_nero':   ['Black resigned',   'White wins!'],
                'resign_bianco': ['White resigned',   'Black wins!'],
                'timeout':       ['Timeout',          data.winner === 'bianco' ? 'White wins!' : 'Black wins!'],
                'threefold':     ['Draw',             'Threefold repetition'],
                'fifty_moves':   ['Draw',             '50 moves rule'],
                'insufficient':  ['Draw',             'Insufficient material'],
            };
            const [title, reason] = messages[data.reason] ?? ['Game over', ''];
            endGame(title, reason);
            playEndSound();
        }

        // ═══════════════════════════════════════════
        // 10. Audio sicuro
        // ═══════════════════════════════════════════
        function playEndSound() {
            const audio = document.getElementById('endSound');
            if (audio) {
                audio.currentTime = 0;
                audio.play().catch(e => console.log('Audio play blocked:', e));
            }
        }

        // ═══════════════════════════════════════════
        // 11. Abbandono
        // ═══════════════════════════════════════════
        document.querySelector("#resignBtn").addEventListener("click", function () {
            if (gameOver) return;
            fetch("/resign?id=" + window.gameId, { method: "POST", credentials: "same-origin" })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const reason = window.playerColor === 'bianco' ? 'resign_bianco' : 'resign_nero';
                    broadcastGameOver(reason);
                    endGame(
                        window.playerColor === 'bianco' ? 'White resigned' : 'Black resigned',
                        window.playerColor === 'bianco' ? 'Black wins!'    : 'White wins!'
                    );
                }
            });
        });

        // ═══════════════════════════════════════════
        // 12. Pulsante Home
        // ═══════════════════════════════════════════
        document.querySelector("#esci").addEventListener("click", function () {
            window.location.href = "/home";
        });

        // ═══════════════════════════════════════════
        // 13. Avvio
        // ═══════════════════════════════════════════
        renderBoard(window.board);
        initTimerFromServer();
    </script>
</body>
</html>