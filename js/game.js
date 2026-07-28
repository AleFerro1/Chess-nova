let selected = null;
let currentTurn = window.turn ? window.turn : 'w';
let lastMove = null;
let legalSquares = [];
let tempoBianco = window.tempoBianco ? parseInt(window.tempoBianco) : 600;
let tempoNero = window.tempoNero ? parseInt(window.tempoNero) : 600;
let intervalTimer = null;
let replayMoves = [];
let replayIndex = -1;
let currentFen = window.fen;
let perspective = window.playerColor;
let gameOver = false;

// --- WebSocket -----------------------------------------------
const gameId = sessionStorage.getItem("id_partita");
const ws = new WebSocket(`wss://chessnova.win/wss/`);

ws.onopen = () => { // Invia un messaggio al server WebSocket per unirsi alla partita
    ws.send(JSON.stringify({
        type:     'join',
        game_id:  gameId,
        username: window.username,
    }));
};

ws.onmessage = (event) => { // Gestisce i messaggi ricevuti dal server WebSocket
    const data = JSON.parse(event.data);

    switch (data.type) {

        case 'opponent_move':
            currentFen   = data.fen;
            window.board = fenToBoard(data.fen);
            currentTurn  = data.turn;

            if (data.timers) {
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

            if (data.moves) updateRecord({ moves: data.moves });

            
            break;

        case 'game_over':
            handleGameOver(data);
            break;
    }
};

ws.onerror = (e) => console.error('WebSocket error:', e);
ws.onclose = () => console.log('WebSocket chiuso');

// --- Broadcast game over -------------------------------------
function broadcastGameOver(reason, winner = null) {
    ws.send(JSON.stringify({
        type:    'game_over',
        game_id: gameId,
        reason,
        winner,
    }));
}

// --- Gestisce game_over ricevuto via WS ----------------------
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
    new Audio('/sounds/game-end.mp3').play();
}

renderBoard(window.board);
initTimerFromServer();

// --- Timer ---------------------------------------------------

function avviaTimer() {
    console.log("Tempo bianco scaduto" + gameId);
    if (intervalTimer) clearInterval(intervalTimer);
    intervalTimer = setInterval(() => {
        if (gameOver) return;
        if (currentTurn === 'w') {
            tempoBianco--;
            if (tempoBianco <= 0) {
                console.log("Tempo bianco scaduto" + gameId);
                tempoBianco = 0;
                clearInterval(intervalTimer);
                document.getElementById('timerBianco').classList.add('scaduto');
                // 1. Aggiorna il database
                fetch(`/timeoutGame?id=${gameId}&color=bianco`, {
                    method: 'POST',
                    credentials: 'same-origin'
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        // 2. Notifica l'avversario via WebSocket
                        broadcastGameOver('timeout', (window.playerColor === 'bianco' ? 'nero' : 'bianco'));
                        // 3. Mostra schermata
                        endGame('Timeout', window.playerColor === 'bianco' ? 'Black wins!' : 'White wins!');
                        playEndSound();
                    } else {
                        console.error("Errore aggiornamento timeout:", data.error);
                    }
                });
            }
        } else {
            tempoNero--;
            console.log("Tempo nero scaduto");
            if (tempoNero <= 0) {
                tempoNero = 0;
                clearInterval(intervalTimer);
                document.getElementById('timerNero').classList.add('scaduto');
                fetch(`/timeoutGame?id=${gameId}&color=nero`, {
                    method: 'POST',
                    credentials: 'same-origin'
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        broadcastGameOver('timeout', (window.playerColor === 'bianco' ? 'nero' : 'bianco'));
                        endGame('Timeout', window.playerColor === 'bianco' ? 'Black wins!' : 'White wins!');
                        playEndSound();
                    }
                });
            }
        }
        aggiornaVisualTimer();
    }, 1000);
}

function aggiornaVisualTimer() {
    document.getElementById('timerBianco').textContent = formatTime(tempoBianco);
    document.getElementById('timerNero').textContent   = formatTime(tempoNero);
    document.getElementById('timerBianco').classList.toggle('attivo', currentTurn === 'w');
    document.getElementById('timerNero').classList.toggle('attivo',   currentTurn === 'b');
}

function sincronizzaTimer(data) {
    if (data.tempo) {
        if (data.tempo.colore === 'w') {
            tempoBianco = parseInt(data.tempo.tempo.tempo_bianco);
        } else {
            tempoNero = parseInt(data.tempo.tempo.tempo_nero);
        }
    }
    avviaTimer();
    aggiornaVisualTimer();
}

function initTimerFromServer() {
    const now     = Math.floor(Date.now() / 1000);
    const elapsed = now - window.lastMoveTime;

    if (window.turn === 'w') {
        tempoBianco = window.tempoBianco - elapsed;
    } else {
        tempoNero = window.tempoNero - elapsed;
    }

    avviaTimer();
    aggiornaVisualTimer();
}

function formatTime(secondi) {
    const m = Math.floor(secondi / 60).toString().padStart(2, '0');
    const s = (secondi % 60).toString().padStart(2, '0');
    return `${m}:${s}`;
}

// --- Render --------------------------------------------------

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

            if (lastMove) {
                if (
                    (row === lastMove.from[0] && col === lastMove.from[1]) ||
                    (row === lastMove.to[0]   && col === lastMove.to[1])
                ) {
                    square.classList.add(
                        square.classList.contains("casellaBianca") ? "lastMoveBianca" : "lastMoveNera"
                    );
                }
            }

            if (legalSquares.some(m => m[0] === row && m[1] === col)) {
                square.classList.add(board[row][col] !== null ? "legalCapture" : "legalMove");
            }

            const piece = board[row][col];
            if (piece !== null) {
                const img = document.createElement("img");
                const pieceImages = {
                    'p':'DarkPawn','n':'DarkKnight','b':'DarkBishop','r':'DarkRook','q':'DarkQueen','k':'DarkKing',
                    'P':'LightPawn','N':'LightKnight','B':'LightBishop','R':'LightRook','Q':'LightQueen','K':'LightKing'
                };
                img.src       = `/images/${pieceImages[piece]}.webp`;
                img.className = "pedina";
                square.appendChild(img);
            }

            square.addEventListener("click", () => handleClick(row, col, board, square));
            scacchiera.appendChild(square);
        }
    }
}

// --- Click ---------------------------------------------------

function handleClick(i, j, board, square) {
    if (gameOver) return;
    if (!selected) {
        if (board[i][j] === null) return;
        const isWhitePiece = board[i][j] === board[i][j].toUpperCase();
        if (currentTurn === 'w' && !isWhitePiece) return;
        if (currentTurn === 'b' && isWhitePiece)  return;
        selectPiece(i, j, board[i][j]);
    } else {
        const isWhitePiece = board[i][j] !== null && board[i][j] === board[i][j].toUpperCase();
        const isMyPiece    = board[i][j] !== null &&
            ((currentTurn === 'w' && isWhitePiece) || (currentTurn === 'b' && !isWhitePiece));

        if (isMyPiece) {
            selectPiece(i, j, board[i][j]);
            return;
        }

        const savedSelected  = { ...selected };
        const turnBeforeMove = currentTurn;
        selected.element.classList.remove("selected");
        selected     = null;
        legalSquares = [];

        fetch("/board?id=" + gameId, {
            method:      "POST",
            credentials: "same-origin",
            body: new URLSearchParams({
                piece: savedSelected.piece,
                from:  toSquare(savedSelected.i, savedSelected.j),
                to:    toSquare(i, j)
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.promozione) {
                showPromoMenu(data.fen_base, toSquare(i, j), turnBeforeMove);
                return;
            }

            if (!data.success) {
                selectPiece(savedSelected.i, savedSelected.j, savedSelected.piece);
                return;
            }

            currentFen   = data.fen;
            window.board = data.board;
            currentTurn  = data.turn ?? (currentTurn === 'w' ? 'b' : 'w');

            sincronizzaTimer(data);

            if (data.notation) {
                lastMove = { from: data.notation.from, to: data.notation.to };
            }

            document.getElementById('scacchiera').innerHTML = '';
            renderBoard(window.board);

            if (data.storico) updateRecord(data.storico);

            // -- Notifica avversario via WebSocket --
            ws.send(JSON.stringify({
                type: 'move',
                from: toSquare(savedSelected.i, savedSelected.j),
                to: toSquare(i, j),
                piece: savedSelected.piece,
            }));

            // -- Game over --
            if (data.checkmate) {
                endGame('Checkmate', data.winner === 'bianco' ? 'White wins!' : 'Black wins!');
                broadcastGameOver('checkmate', data.winner);
                return;
            }
            if (data.stalemate)              { endGame('Draw', 'Stalemate');            broadcastGameOver('stalemate');    return; }
            if (data.fiftyMoves)             { endGame('Draw', '50 moves rule');         broadcastGameOver('fifty_moves');  return; }
            if (data.repetition)             { endGame('Draw', 'Threefold repetition');  broadcastGameOver('threefold');    return; }
            if (data.enoughPieces === false) { endGame('Draw', 'Insufficient material'); broadcastGameOver('insufficient'); return; }

            if (data.check && !data.checkmate) new Audio('/sounds/move-check.mp3').play();
        });
    }
}

// --- Resign --------------------------------------------------

document.querySelector("#resignBtn").addEventListener("click", function () {
    fetch("/resign?id=" + gameId, { method: "POST", credentials: "same-origin" })
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

// --- Selezione -----------------------------------------------

function selectPiece(i, j, piece) {
    if (selected) selected.element.classList.remove("selected");
    legalSquares = [];

    document.getElementById('scacchiera').innerHTML = '';
    renderBoard(window.board);

    const element = document.querySelector(
        `.casellaBianca[data-row="${i}"][data-col="${j}"], .casellaNera[data-row="${i}"][data-col="${j}"]`
    );
    if (element) element.classList.add("selected");
    selected = { i, j, piece, element };

    fetch("/legal-moves?id=" + gameId, {
        method:      "POST",
        credentials: "same-origin",
        body: new URLSearchParams({ piece, from: toSquare(i, j) })
    })
    .then(res => res.json())
    .then(data => {
        if (!data.success) return;
        legalSquares = data.moves;

        document.getElementById('scacchiera').innerHTML = '';
        renderBoard(window.board);

        const newElement = document.querySelector(
            `.casellaBianca[data-row="${i}"][data-col="${j}"], .casellaNera[data-row="${i}"][data-col="${j}"]`
        );
        if (newElement) {
            selected.element = newElement;
            selected.element.classList.add("selected");
        }
    });
}

// --- Utilità -------------------------------------------------

function toSquare(i, j) {
    const files = ["a","b","c","d","e","f","g","h"];
    return files[j] + (8 - i);
}

function fenToBoard(fen) {
    const board = Array.from({ length: 8 }, () => Array(8).fill(null));
    const rows  = fen.split(" ")[0].split("/");

    for (let i = 0; i < 8; i++) {
        let col = 0;
        for (let char of rows[i]) {
            if (isNaN(char)) { board[i][col] = char; col++; }
            else             { col += parseInt(char); }
        }
    }
    return board;
}

// --- Promozione ----------------------------------------------

function showPromoMenu(fenBase, to, turn) {
    const pieces = ['q','r','b','n'];
    const menu   = document.createElement('div');
    menu.id = 'promoMenu';
    menu.style.cssText = 'position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);background:#fff;padding:20px;border:2px solid #333;z-index:999;display:flex;gap:10px;';

    pieces.forEach(p => {
        const btn   = document.createElement('button');
        const label = { q:'♛', r:'♜', b:'♝', n:'♞' };
        btn.textContent = label[p];
        btn.style.fontSize = '2rem';
        btn.onclick = () => {
            document.body.removeChild(menu);
            fetch("/promuovi?id=" + gameId, {
                method:      "POST",
                credentials: "same-origin",
                body: new URLSearchParams({ fen_base: fenBase, promo: p, to, turn })
            })
            .then(res => res.json())
            .then(data => {
                if (!data.success) return;
                sincronizzaTimer(data);
                window.board = data.board;
                currentTurn  = currentTurn === 'w' ? 'b' : 'w';
                lastMove     = data.notation ? { from: data.notation.from, to: data.notation.to } : lastMove;

                document.getElementById('scacchiera').innerHTML = '';
                renderBoard(window.board);

                ws.send(JSON.stringify({
                    type:     'move',
                    game_id:  gameId,
                    fen:      data.fen,
                    turn:     currentTurn,
                    moves:    [],
                    timers:   { bianco: tempoBianco, nero: tempoNero },
                    notation: data.notation ?? null,
                }));

                if (data.checkmate || data.stalemate) {
                    const reason = data.checkmate ? 'checkmate' : 'stalemate';
                    broadcastGameOver(reason, data.winner ?? null);
                    clearInterval(intervalTimer);
                    new Audio('/sounds/game-end.mp3').play();
                }
                if (data.check && !data.checkmate) new Audio('/sounds/move-check.mp3').play();
            });
        };
        menu.appendChild(btn);
    });

    document.body.appendChild(menu);
}

// --- Storico -------------------------------------------------

function updateRecord(record) {
    replayMoves = record.moves || [];
    const currentStorico = document.getElementById("movesList");
    if (!currentStorico) return;
    currentStorico.innerHTML = "";

    for (let i = 0; i < replayMoves.length; i += 2) {
        const moveNumber = Math.floor(i / 2) + 1;
        const whiteMove  = replayMoves[i];
        const blackMove  = replayMoves[i + 1];

        const row = document.createElement("div");
        row.classList.add("move");
        row.dataset.whiteIndex = i;
        row.dataset.blackIndex = i + 1;
        row.innerHTML = `
            <span>${moveNumber}. </span>
            <span class="white">${whiteMove ? whiteMove.notazione : ""}</span>
            <span class="black">${blackMove ? blackMove.notazione : ""}</span>
        `;

        row.querySelector(".white").addEventListener("click", (e) => {
            e.stopPropagation();
            if (!whiteMove) return;
            replayIndex = i;
            renderBoard(fenToBoard(whiteMove.fen));
            highlightReplayMove();
        });

        if (blackMove) {
            row.querySelector(".black").addEventListener("click", (e) => {
                e.stopPropagation();
                replayIndex = i + 1;
                renderBoard(fenToBoard(blackMove.fen));
                highlightReplayMove();
            });
        }

        currentStorico.appendChild(row);
    }
}

function highlightReplayMove() {
    const rows = document.querySelectorAll("#movesList .move");
    rows.forEach(row => row.classList.remove("active"));
    const rowIndex = Math.floor(replayIndex / 2);
    if (rows[rowIndex]) {
        rows[rowIndex].classList.add("active");
        rows[rowIndex].scrollIntoView({ block: "nearest" });
    }
}

// --- Finale --------------------------------------------------

function showEndScreen(title, reason) {
    document.getElementById("endTitle").textContent  = title;
    document.getElementById("endReason").textContent = reason;
    document.getElementById("endScreen").classList.remove("hidden");
}

function endGame(title, reason) {
    if (gameOver) return;
    gameOver = true;
    clearInterval(intervalTimer);
    intervalTimer = null;
    showEndScreen(title, reason);
}

document.querySelector("#esci").addEventListener("click", () => {
    window.location.href = "/home";
});