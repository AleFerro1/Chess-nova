```php
<?php

// Variabili fornite dal controller:
// $board, $turn, $coloreGiocatore, $fen,
// $tempo_bianco, $tempo_nero, $tempo_ultima_mossa,
// $username, $id_partita, $nomeNero, $nomeBianco,
// $elo_nero, $elo_bianco, $timecontrol, $tipo_partita

function getAvatarDama($data, $default = '/public/assets/img/redking.jpg')
{
    return !empty($data['avatar'])
        ? '/public/' . htmlspecialchars($data['avatar'], ENT_QUOTES, 'UTF-8')
        : $default;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <meta
        name="csrf-token"
        content="<?= htmlspecialchars(
            $_SESSION['csrf_token'] ?? '',
            ENT_QUOTES,
            'UTF-8'
        ) ?>"
    >

    <link
        rel="stylesheet"
        href="/styles/scacchiera.css"
    >

    <link
        rel="icon"
        type="image/png"
        sizes="64x64"
        href="/images/favicon.png"
    >

    <title>Italian Checkers - ChessNova</title>

</head>

<body>

<div class="layout">

    <div class="left">

        <?php

        $isBianco = ($coloreGiocatore === 'bianco');

        $topColor = $isBianco ? 'nero' : 'bianco';
        $botColor = $isBianco ? 'bianco' : 'nero';

        $topName = $isBianco ? $nomeNero : $nomeBianco;
        $botName = $isBianco ? $nomeBianco : $nomeNero;

        $topElo = $isBianco ? $elo_nero : $elo_bianco;
        $botElo = $isBianco ? $elo_bianco : $elo_nero;

        ?>

        <!-- Avversario -->

        <div class="top-bar">

            <div class="player-info player-<?= htmlspecialchars(
                $topColor,
                ENT_QUOTES,
                'UTF-8'
            ) ?>">

                <div class="player-left">

                    <div class="player-avatar">

                        <img
                            src="<?= getAvatarDama($topElo) ?>"
                            alt="Avatar"
                        >

                    </div>

                    <div class="player-meta">

                        <div class="player-name">

                            <?php if (!empty($topElo['country'])): ?>

                                <img
                                    class="flag"
                                    src="https://flagcdn.com/w20/<?= strtolower(
                                        htmlspecialchars(
                                            $topElo['country'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        )
                                    ) ?>.png"
                                    alt="Flag"
                                >

                            <?php endif; ?>

                            <?= htmlspecialchars(
                                $topName,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>

                        </div>

                        <div class="player-rank">

                            Elo
                            <?= htmlspecialchars(
                                $topElo[$timecontrol] ?? '---',
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>

                        </div>

                    </div>

                </div>

            </div>

            <div
                id="timer<?= ucfirst($topColor) ?>"
                class="timer timer-<?= htmlspecialchars(
                    $topColor,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
            >
                10:00
            </div>

        </div>


        <!-- Scacchiera -->

        <div
            id="scacchiera"
            class="scacchiera"
        ></div>


        <button
            id="resignBtn"
            class="resign-btn"
            type="button"
        >
            Resign
        </button>


        <!-- Giocatore locale -->

        <div class="bottom-bar">

            <div class="player-info player-<?= htmlspecialchars(
                $botColor,
                ENT_QUOTES,
                'UTF-8'
            ) ?>">

                <div class="player-left">

                    <div class="player-avatar">

                        <img
                            src="<?= getAvatarDama($botElo) ?>"
                            alt="Avatar"
                        >

                    </div>

                    <div class="player-meta">

                        <div class="player-name">

                            <?php if (!empty($botElo['country'])): ?>

                                <img
                                    class="flag"
                                    src="https://flagcdn.com/w20/<?= strtolower(
                                        htmlspecialchars(
                                            $botElo['country'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        )
                                    ) ?>.png"
                                    alt="Flag"
                                >

                            <?php endif; ?>

                            <?= htmlspecialchars(
                                $botName,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>

                        </div>

                        <div class="player-rank">

                            Elo
                            <?= htmlspecialchars(
                                $botElo[$timecontrol] ?? '---',
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>

                        </div>

                    </div>

                </div>

            </div>

            <div
                id="timer<?= ucfirst($botColor) ?>"
                class="timer timer-<?= htmlspecialchars(
                    $botColor,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
            >
                10:00
            </div>

        </div>

    </div>

</div>


<!-- End screen -->

<div
    id="endScreen"
    class="end-screen hidden"
>

    <div class="end-box">

        <h1 id="endTitle">
            Game ended
        </h1>

        <p id="endReason">
            Result
        </p>

        <div class="end-buttons">

            <button
                id="esci"
                type="button"
            >
                Home
            </button>

        </div>

    </div>

</div>


<audio
    id="endSound"
    src="/sounds/game-end.mp3"
    preload="auto"
></audio>


<script>

/* ═══════════════════════════════════════════
   Stato iniziale fornito dal server
   ═══════════════════════════════════════════ */

window.board = <?= json_encode(
    $board,
    JSON_UNESCAPED_UNICODE |
    JSON_UNESCAPED_SLASHES
) ?>;

window.turn = <?= json_encode($turn) ?>;

window.playerColor = <?= json_encode($coloreGiocatore) ?>;

window.fen = <?= json_encode($fen) ?>;

window.tempoBianco = <?= (int)$tempo_bianco ?>;

window.tempoNero = <?= (int)$tempo_nero ?>;

window.lastMoveTime = <?= (int)$tempo_ultima_mossa ?>;

window.gameId = <?= json_encode(
    (int)$id_partita
) ?>;


/*
 * Il tipo di partita è una proprietà della pagina,
 * NON viene usato come informazione attendibile dal server.
 */
window.tipoPartita = 'dama';


/* ═══════════════════════════════════════════
   CSRF
   ═══════════════════════════════════════════ */

const csrfToken =
    document.querySelector(
        'meta[name="csrf-token"]'
    )?.content ?? '';


/* ═══════════════════════════════════════════
   Stato globale
   ═══════════════════════════════════════════ */

let ws = null;

let reconnectAttempts = 0;

const maxReconnectDelay = 30000;

let selected = null;

let currentTurn = window.turn || 'w';

let lastMove = null;

let legalSquares = [];

let tempoBianco = Number(window.tempoBianco) || 600;

let tempoNero = Number(window.tempoNero) || 600;

let intervalTimer = null;

let currentFen = window.fen;

let gameOver = false;


/* ═══════════════════════════════════════════
   Utility
   ═══════════════════════════════════════════ */

function isValidFen(fen)
{
    if (typeof fen !== 'string') {
        return false;
    }

    if (fen.length < 15 || fen.length > 200) {
        return false;
    }

    return true;
}


function fenToBoard(fen)
{
    if (!isValidFen(fen)) {
        return null;
    }

    const board = Array.from(
        { length: 8 },
        () => Array(8).fill(null)
    );

    const parts = fen.trim().split(/\s+/);

    if (!parts[0]) {
        return null;
    }

    const rows = parts[0].split('/');

    if (rows.length !== 8) {
        return null;
    }

    for (let i = 0; i < 8; i++) {

        let col = 0;

        for (const char of rows[i]) {

            if (/[1-8]/.test(char)) {

                col += parseInt(char, 10);

            } else {

                if (
                    !['w', 'b', 'W', 'B'].includes(char) ||
                    col >= 8
                ) {
                    return null;
                }

                board[i][col] = char;

                col++;
            }
        }

        if (col !== 8) {
            return null;
        }
    }

    return board;
}


function toSquare(i, j)
{
    const files = [
        'a',
        'b',
        'c',
        'd',
        'e',
        'f',
        'g',
        'h'
    ];

    if (
        !Number.isInteger(i) ||
        !Number.isInteger(j) ||
        i < 0 ||
        i > 7 ||
        j < 0 ||
        j > 7
    ) {
        return null;
    }

    return files[j] + (8 - i);
}


function formatTime(seconds)
{
    const safeSeconds = Math.max(
        0,
        Number(seconds) || 0
    );

    const mins = Math.floor(
        safeSeconds / 60
    );

    const secs = Math.floor(
        safeSeconds % 60
    );

    return `${mins}:${secs
        .toString()
        .padStart(2, '0')}`;
}


function aggiornaVisualTimer()
{
    const timerBianco =
        document.getElementById('timerBianco');

    const timerNero =
        document.getElementById('timerNero');

    if (!timerBianco || !timerNero) {
        return;
    }

    timerBianco.textContent =
        formatTime(tempoBianco);

    timerNero.textContent =
        formatTime(tempoNero);

    timerBianco.classList.toggle(
        'attivo',
        currentTurn === 'w'
    );

    timerNero.classList.toggle(
        'attivo',
        currentTurn === 'b'
    );
}


/* ═══════════════════════════════════════════
   WebSocket
   ═══════════════════════════════════════════ */

function connectWebSocket()
{
    if (
        ws &&
        (
            ws.readyState === WebSocket.OPEN ||
            ws.readyState === WebSocket.CONNECTING
        )
    ) {
        return;
    }

    ws = new WebSocket(
        'wss://chessnova.win/wss/'
    );


    ws.onopen = function()
    {
        reconnectAttempts = 0;

        /*
         * NON inviamo username.
         *
         * NON inviamo FEN.
         *
         * NON inviamo turno.
         *
         * NON inviamo colore.
         *
         * Il server deve identificare l'utente tramite
         * la PHP session associata alla connessione.
         */

        ws.send(JSON.stringify({
            type: 'join',
            game_id: Number(window.gameId)
        }));
    };


    ws.onmessage = function(event)
    {
        let data;

        try {

            data = JSON.parse(event.data);

        } catch (error) {

            console.error(
                'Messaggio WebSocket non valido'
            );

            return;
        }


        if (!data || typeof data.type !== 'string') {
            return;
        }


        switch (data.type) {


            /* ═══════════════════════════════════
               Aggiornamento stato partita
               ═══════════════════════════════════ */

            case 'game_update':

            case 'opponent_move':

                if (!isValidFen(data.fen)) {
                    return;
                }


                const newBoard =
                    fenToBoard(data.fen);

                if (!newBoard) {
                    return;
                }


                currentFen = data.fen;

                window.fen = data.fen;

                window.board = newBoard;


                if (
                    data.turn === 'w' ||
                    data.turn === 'b'
                ) {
                    currentTurn = data.turn;
                }


                if (
                    data.timers &&
                    Number.isFinite(
                        Number(data.timers.bianco)
                    ) &&
                    Number.isFinite(
                        Number(data.timers.nero)
                    )
                ) {

                    tempoBianco =
                        Math.max(
                            0,
                            Number(data.timers.bianco)
                        );

                    tempoNero =
                        Math.max(
                            0,
                            Number(data.timers.nero)
                        );
                }


                /*
                 * lastMove deve essere già validato
                 * e generato dal server.
                 */

                if (
                    data.lastMove &&
                    Array.isArray(data.lastMove.from) &&
                    Array.isArray(data.lastMove.to) &&
                    data.lastMove.from.length === 2 &&
                    data.lastMove.to.length === 2
                ) {

                    lastMove = {
                        from: [
                            Number(data.lastMove.from[0]),
                            Number(data.lastMove.from[1])
                        ],
                        to: [
                            Number(data.lastMove.to[0]),
                            Number(data.lastMove.to[1])
                        ]
                    };

                } else {

                    lastMove = null;
                }


                selected = null;

                legalSquares = [];


                const scacchiera =
                    document.getElementById(
                        'scacchiera'
                    );

                if (scacchiera) {

                    scacchiera.innerHTML = '';

                    renderBoard(window.board);
                }


                avviaTimer();

                aggiornaVisualTimer();

                break;


            /* ═══════════════════════════════════
               Fine partita
               ═══════════════════════════════════ */

            case 'game_over':

                if (
                    data.reason &&
                    typeof data.reason === 'string'
                ) {
                    handleGameOver(data);
                }

                break;


            /* ═══════════════════════════════════
               Errore
               ═══════════════════════════════════ */

            case 'error':

                console.error(
                    'WebSocket:',
                    data.message || 'Errore'
                );

                break;
        }
    };


    ws.onerror = function(error)
    {
        console.error(
            'WebSocket error:',
            error
        );
    };


    ws.onclose = function()
    {
        ws = null;

        const delay = Math.min(
            1000 * Math.pow(
                2,
                reconnectAttempts
            ),
            maxReconnectDelay
        );

        reconnectAttempts++;

        setTimeout(
            connectWebSocket,
            delay
        );
    };
}


connectWebSocket();


/* ═══════════════════════════════════════════
   Timer
   ═══════════════════════════════════════════ */

function avviaTimer()
{
    if (intervalTimer) {
        clearInterval(intervalTimer);
    }

    intervalTimer = setInterval(
        function()
        {
            if (gameOver) {
                return;
            }


            if (currentTurn === 'w') {

                tempoBianco--;


                if (tempoBianco <= 0) {

                    tempoBianco = 0;

                    clearInterval(
                        intervalTimer
                    );

                    document
                        .getElementById('timerBianco')
                        ?.classList.add('scaduto');


                    fetch('/timeoutGame', {

                        method: 'POST',

                        credentials: 'same-origin',

                        headers: {
                            'X-CSRF-Token': csrfToken
                        },

                        body: new URLSearchParams({
                            id: window.gameId,
                            color: 'bianco'
                        })

                    })
                    .then(res => res.json())

                    .then(data => {

                        if (data.success) {

                            endGame(
                                'Timeout',
                                window.playerColor === 'bianco'
                                    ? 'Black wins!'
                                    : 'White wins!'
                            );

                            playEndSound();

                            requestServerSync();
                        }
                    })

                    .catch(error => {
                        console.error(
                            'Timeout error:',
                            error
                        );
                    });
                }

            } else {

                tempoNero--;


                if (tempoNero <= 0) {

                    tempoNero = 0;

                    clearInterval(
                        intervalTimer
                    );

                    document
                        .getElementById('timerNero')
                        ?.classList.add('scaduto');


                    fetch('/timeoutGame', {

                        method: 'POST',

                        credentials: 'same-origin',

                        headers: {
                            'X-CSRF-Token': csrfToken
                        },

                        body: new URLSearchParams({
                            id: window.gameId,
                            color: 'nero'
                        })

                    })
                    .then(res => res.json())

                    .then(data => {

                        if (data.success) {

                            endGame(
                                'Timeout',
                                window.playerColor === 'bianco'
                                    ? 'Black wins!'
                                    : 'White wins!'
                            );

                            playEndSound();

                            requestServerSync();
                        }
                    })

                    .catch(error => {
                        console.error(
                            'Timeout error:',
                            error
                        );
                    });
                }
            }


            aggiornaVisualTimer();

        },
        1000
    );
}


function initTimerFromServer()
{
    const now =
        Math.floor(Date.now() / 1000);

    const elapsed =
        Math.max(
            0,
            now - Number(window.lastMoveTime || 0)
        );


    if (window.turn === 'w') {

        tempoBianco = Math.max(
            0,
            Number(window.tempoBianco) - elapsed
        );

    } else {

        tempoNero = Math.max(
            0,
            Number(window.tempoNero) - elapsed
        );
    }


    avviaTimer();

    aggiornaVisualTimer();
}


/* ═══════════════════════════════════════════
   Rendering dama
   ═══════════════════════════════════════════ */

function renderBoard(board)
{
    const scacchiera =
        document.getElementById(
            'scacchiera'
        );

    if (!scacchiera || !board) {
        return;
    }


    scacchiera.innerHTML = '';


    const isWhitePerspective =
        window.playerColor === 'bianco';


    for (let i = 0; i < 8; i++) {

        for (let j = 0; j < 8; j++) {

            const row =
                isWhitePerspective
                    ? i
                    : 7 - i;

            const col =
                isWhitePerspective
                    ? j
                    : 7 - j;


            const square =
                document.createElement('div');


            square.className =
                (row + col) % 2 === 0
                    ? 'casellaBianca'
                    : 'casellaNera';


            square.dataset.row = row;

            square.dataset.col = col;


            /* Ultima mossa */

            if (
                lastMove &&
                Array.isArray(lastMove.from) &&
                Array.isArray(lastMove.to)
            ) {

                const isFrom =
                    row === lastMove.from[0] &&
                    col === lastMove.from[1];

                const isTo =
                    row === lastMove.to[0] &&
                    col === lastMove.to[1];


                if (isFrom || isTo) {

                    square.classList.add(
                        square.classList.contains(
                            'casellaBianca'
                        )
                            ? 'lastMoveBianca'
                            : 'lastMoveNera'
                    );
                }
            }


            /* Mosse legali */

            if (
                legalSquares.some(
                    move =>
                        Array.isArray(move) &&
                        move[0] === row &&
                        move[1] === col
                )
            ) {

                square.classList.add(
                    board[row][col] !== null
                        ? 'legalCapture'
                        : 'legalMove'
                );
            }


            /* Pedina */

            const piece =
                board[row][col];


            if (piece !== null) {

                const pieceImages = {

                    'b': 'damaPiccolaNera',

                    'w': 'damaPiccolaBianca',

                    'B': 'damaNeraPromossa',

                    'W': 'damaBiancaPromossa'
                };


                const imageName =
                    pieceImages[piece];


                if (imageName) {

                    const img =
                        document.createElement('img');


                    img.src =
                        `/images/${imageName}.png`;


                    img.className =
                        'pedina';


                    img.alt =
                        piece === 'w' || piece === 'W'
                            ? 'Pedina bianca'
                            : 'Pedina nera';


                    img.draggable = false;


                    square.appendChild(img);
                }
            }


            square.addEventListener(
                'click',
                function()
                {
                    handleClick(
                        row,
                        col,
                        board,
                        square
                    );
                }
            );


            scacchiera.appendChild(square);
        }
    }
}


/* ═══════════════════════════════════════════
   Click / movimento
   ═══════════════════════════════════════════ */

function handleClick(i, j, board, square)
{
    if (gameOver) {
        return;
    }


    /*
     * Se non è selezionata una pedina,
     * selezioniamo quella cliccata.
     */

    if (!selected) {

        if (board[i][j] === null) {
            return;
        }


        const piece =
            board[i][j];


        const isWhitePiece =
            piece === 'w' ||
            piece === 'W';


        if (
            currentTurn === 'w' &&
            !isWhitePiece
        ) {
            return;
        }


        if (
            currentTurn === 'b' &&
            isWhitePiece
        ) {
            return;
        }


        selectPiece(
            i,
            j,
            piece
        );

        return;
    }


    /*
     * Se clicchiamo un'altra nostra pedina,
     * cambiamo selezione.
     */

    const targetPiece =
        board[i][j];


    const isWhiteTarget =
        targetPiece !== null &&
        (
            targetPiece === 'w' ||
            targetPiece === 'W'
        );


    const isMyPiece =
        targetPiece !== null &&
        (
            (
                currentTurn === 'w' &&
                isWhiteTarget
            )
            ||
            (
                currentTurn === 'b' &&
                !isWhiteTarget
            )
        );


    if (isMyPiece) {

        selectPiece(
            i,
            j,
            targetPiece
        );

        return;
    }


    /*
     * Salviamo la selezione prima di
     * cancellarla.
     */

    const savedSelected = {
        i: selected.i,
        j: selected.j,
        piece: selected.piece
    };


    if (selected.element) {
        selected.element.classList.remove(
            'selected'
        );
    }


    selected = null;

    legalSquares = [];


    const from =
        toSquare(
            savedSelected.i,
            savedSelected.j
        );

    const to =
        toSquare(
            i,
            j
        );


    if (!from || !to) {
        return;
    }


    /*
     * La mossa viene sempre validata
     * dal server.
     *
     * Il client NON decide se è valida.
     */

    fetch(
        '/dama?id=' +
        encodeURIComponent(
            window.gameId
        ),
        {
            method: 'POST',

            credentials: 'same-origin',

            headers: {
                'X-CSRF-Token': csrfToken
            },

            body: new URLSearchParams({
                piece: savedSelected.piece,
                from: from,
                to: to
            })
        }
    )

    .then(async response => {

        let data;

        try {
            data = await response.json();
        } catch (error) {
            throw new Error(
                'Risposta server non valida'
            );
        }


        if (!response.ok) {
            throw new Error(
                data.message ||
                'Mossa rifiutata dal server'
            );
        }


        return data;
    })

    .then(data => {

        /*
         * Promozione.
         */

        if (data.promozione) {

            fetch(
                '/promuovi?id=' +
                encodeURIComponent(
                    window.gameId
                ),
                {
                    method: 'POST',

                    credentials: 'same-origin',

                    headers: {
                        'X-CSRF-Token': csrfToken
                    },

                    body: new URLSearchParams({

                        fen_base:
                            data.fen_base,

                        /*
                         * La scelta della promozione
                         * deve essere eventualmente
                         * validata dal server.
                         */
                        promo: 'q',

                        to: to,

                        /*
                         * Il server deve ignorare questi
                         * valori come autorità.
                         */
                        turn: currentTurn,

                        tipo: 'dama'
                    })
                }
            )

            .then(async response => {

                let promoData;

                try {
                    promoData =
                        await response.json();
                } catch (error) {

                    throw new Error(
                        'Risposta promozione non valida'
                    );
                }


                if (!response.ok) {

                    throw new Error(
                        promoData.message ||
                        'Promozione rifiutata'
                    );
                }


                return promoData;
            })

            .then(promoData => {

                if (!promoData.success) {

                    selectPiece(
                        savedSelected.i,
                        savedSelected.j,
                        savedSelected.piece
                    );

                    return;
                }


                applyMoveUpdate(
                    promoData,
                    savedSelected,
                    i,
                    j
                );
            })

            .catch(error => {

                console.error(
                    'Errore promozione:',
                    error
                );


                selectPiece(
                    savedSelected.i,
                    savedSelected.j,
                    savedSelected.piece
                );
            });


            return;
        }


        /*
         * Mossa rifiutata.
         */

        if (!data.success) {

            selectPiece(
                savedSelected.i,
                savedSelected.j,
                savedSelected.piece
            );

            return;
        }


        /*
         * Mossa accettata dal server.
         */

        applyMoveUpdate(
            data,
            savedSelected,
            i,
            j
        );
    })

    .catch(error => {

        console.error(
            'Errore mossa:',
            error
        );


        /*
         * Ripristiniamo la selezione
         * se la richiesta fallisce.
         */

        selectPiece(
            savedSelected.i,
            savedSelected.j,
            savedSelected.piece
        );
    });
}


/* ═══════════════════════════════════════════
   Applicazione stato restituito dal server
   ═══════════════════════════════════════════ */

function applyMoveUpdate(
    data,
    savedSelected,
    targetI,
    targetJ
)
{
    if (
        !data ||
        !data.success ||
        !isValidFen(data.fen)
    ) {
        return;
    }


    const newBoard =
        Array.isArray(data.board)
            ? data.board
            : fenToBoard(data.fen);


    if (!newBoard) {
        return;
    }


    currentFen = data.fen;

    window.fen = data.fen;

    window.board = newBoard;


    /*
     * Il turno restituito dal server
     * ha priorità.
     */

    if (
        data.turn === 'w' ||
        data.turn === 'b'
    ) {

        currentTurn =
            data.turn;

    } else {

        currentTurn =
            currentTurn === 'w'
                ? 'b'
                : 'w';
    }


    /*
     * Evidenziamo la mossa usando
     * coordinate LOCALI già note.
     *
     * Non ci fidiamo di notation
     * proveniente dal browser.
     */

    if (
        savedSelected &&
        Number.isInteger(savedSelected.i) &&
        Number.isInteger(savedSelected.j) &&
        Number.isInteger(targetI) &&
        Number.isInteger(targetJ)
    ) {

        lastMove = {

            from: [
                savedSelected.i,
                savedSelected.j
            ],

            to: [
                targetI,
                targetJ
            ]
        };

    } else {

        lastMove = null;
    }


    /*
     * Timer restituiti dal server.
     */

    if (
        data.tempo &&
        data.tempo.tempo
    ) {

        if (
            data.tempo.colore === 'w' &&
            Number.isFinite(
                Number(
                    data.tempo.tempo.tempo_bianco
                )
            )
        ) {

            tempoBianco =
                Number(
                    data.tempo.tempo.tempo_bianco
                );

        } else if (
            data.tempo.colore === 'b' &&
            Number.isFinite(
                Number(
                    data.tempo.tempo.tempo_nero
                )
            )
        ) {

            tempoNero =
                Number(
                    data.tempo.tempo.tempo_nero
                );
        }
    }


    selected = null;

    legalSquares = [];


    const scacchiera =
        document.getElementById(
            'scacchiera'
        );


    if (scacchiera) {

        scacchiera.innerHTML = '';

        renderBoard(
            window.board
        );
    }


    avviaTimer();

    aggiornaVisualTimer();


    /*
     * ═══════════════════════════════════════
     * IMPORTANTE
     * ═══════════════════════════════════════
     *
     * Non inviamo:
     *
     * - FEN
     * - board
     * - turno
     * - colore
     * - pezzo
     * - winner
     * - timer
     *
     * al WebSocket.
     *
     * Il server deve recuperare lo stato
     * autentico direttamente dal database.
     */

    requestServerSync();


    /*
     * Fine partita.
     *
     * Anche qui il risultato visualizzato
     * localmente viene solamente mostrato:
     * il server deve essere l'autorità.
     */

    if (
        data.checkmate ||
        data.gameOver
    ) {

        endGame(
            'Game over',
            data.winner === 'bianco'
                ? 'White wins!'
                : data.winner === 'nero'
                    ? 'Black wins!'
                    : 'Game ended'
        );

        requestServerSync();
    }


    if (data.stalemate) {

        endGame(
            'Draw',
            'Stalemate'
        );

        requestServerSync();
    }
}


/* ═══════════════════════════════════════════
   Sincronizzazione WebSocket
   ═══════════════════════════════════════════ */

function requestServerSync()
{
    if (
        !ws ||
        ws.readyState !== WebSocket.OPEN
    ) {
        return;
    }


    /*
     * L'unica informazione inviata è:
     *
     * "La partita autenticata di questa
     * connessione è cambiata."
     *
     * websocket.php deve usare il game_id
     * associato alla connessione dopo il join,
     * non quello eventualmente manipolato
     * dal client.
     */

    ws.send(JSON.stringify({
        type: 'sync'
    }));
}


/* ═══════════════════════════════════════════
   Selezione pedina
   ═══════════════════════════════════════════ */

function selectPiece(i, j, piece)
{
    if (selected && selected.element) {

        selected.element.classList.remove(
            'selected'
        );
    }


    legalSquares = [];


    const scacchiera =
        document.getElementById(
            'scacchiera'
        );


    if (scacchiera) {

        scacchiera.innerHTML = '';

        renderBoard(
            window.board
        );
    }


    const element =
        document.querySelector(
            `.casellaBianca[data-row="${i}"][data-col="${j}"],
             .casellaNera[data-row="${i}"][data-col="${j}"]`
        );


    if (element) {

        element.classList.add(
            'selected'
        );
    }


    selected = {
        i: i,
        j: j,
        piece: piece,
        element: element
    };


    /*
     * Le mosse legali vengono sempre richieste
     * al server.
     */

    fetch(
        '/legal-moves?id=' +
        encodeURIComponent(
            window.gameId
        ),
        {
            method: 'POST',

            credentials: 'same-origin',

            headers: {
                'X-CSRF-Token': csrfToken
            },

            body: new URLSearchParams({
                piece: piece,
                from: toSquare(i, j)
            })
        }
    )

    .then(async response => {

        let data;

        try {
            data = await response.json();
        } catch (error) {
            throw new Error(
                'Risposta server non valida'
            );
        }


        if (!response.ok) {
            throw new Error(
                data.message ||
                'Impossibile recuperare le mosse'
            );
        }


        return data;
    })

    .then(data => {

        if (!data.success) {
            return;
        }


        if (!Array.isArray(data.moves)) {
            return;
        }


        legalSquares =
            data.moves;


        if (scacchiera) {

            scacchiera.innerHTML = '';

            renderBoard(
                window.board
            );
        }


        const newElement =
            document.querySelector(
                `.casellaBianca[data-row="${i}"][data-col="${j}"],
                 .casellaNera[data-row="${i}"][data-col="${j}"]`
            );


        if (newElement) {

            selected.element =
                newElement;

            selected.element.classList.add(
                'selected'
            );
        }
    })

    .catch(error => {

        console.error(
            'Errore legal moves:',
            error
        );
    });
}


/* ═══════════════════════════════════════════
   Fine partita
   ═══════════════════════════════════════════ */

function endGame(title, reason)
{
    if (gameOver) {
        return;
    }


    gameOver = true;


    if (intervalTimer) {

        clearInterval(
            intervalTimer
        );

        intervalTimer = null;
    }


    showEndScreen(
        title,
        reason
    );
}


function showEndScreen(title, reason)
{
    const titleElement =
        document.getElementById(
            'endTitle'
        );

    const reasonElement =
        document.getElementById(
            'endReason'
        );

    const endScreen =
        document.getElementById(
            'endScreen'
        );


    if (titleElement) {
        titleElement.textContent =
            title;
    }


    if (reasonElement) {
        reasonElement.textContent =
            reason;
    }


    if (endScreen) {
        endScreen.classList.remove(
            'hidden'
        );
    }
}


/*
 * Manteniamo questa funzione per compatibilità
 * con eventuale codice già presente.
 *
 * NON invia più winner/reason al server.
 */
function broadcastGameOver()
{
    requestServerSync();
}


function handleGameOver(data)
{
    const messages = {

        'checkmate': [
            'Checkmate',
            data.winner === 'bianco'
                ? 'White wins!'
                : 'Black wins!'
        ],

        'stalemate': [
            'Draw',
            'Stalemate'
        ],

        'resign_nero': [
            'Black resigned',
            'White wins!'
        ],

        'resign_bianco': [
            'White resigned',
            'Black wins!'
        ],

        'timeout': [
            'Timeout',
            data.winner === 'bianco'
                ? 'White wins!'
                : 'Black wins!'
        ],

        'threefold': [
            'Draw',
            'Threefold repetition'
        ],

        'fifty_moves': [
            'Draw',
            '50 moves rule'
        ],

        'insufficient': [
            'Draw',
            'Insufficient material'
        ]
    };


    const result =
        messages[data.reason] ??
        ['Game over', ''];


    endGame(
        result[0],
        result[1]
    );


    playEndSound();
}


function playEndSound()
{
    const audio =
        document.getElementById(
            'endSound'
        );


    if (!audio) {
        return;
    }


    audio.currentTime = 0;


    audio
        .play()
        .catch(
            error =>
                console.log(
                    'Audio play blocked:',
                    error
                )
        );
}


/* ═══════════════════════════════════════════
   Resa
   ═══════════════════════════════════════════ */

const resignButton =
    document.getElementById(
        'resignBtn'
    );


if (resignButton) {

    resignButton.addEventListener(
        'click',
        function()
        {
            if (gameOver) {
                return;
            }


            /*
             * Il server deve verificare:
             *
             * - sessione autenticata
             * - appartenenza alla partita
             * - partita ancora attiva
             * - colore reale dell'utente
             *
             * L'id inviato dal browser NON è sufficiente.
             */

            fetch(
                '/resign',
                {
                    method: 'POST',

                    credentials: 'same-origin',

                    headers: {
                        'X-CSRF-Token': csrfToken
                    },

                    body: new URLSearchParams({
                        id: window.gameId
                    })
                }
            )

            .then(async response => {

                let data;

                try {
                    data = await response.json();
                } catch (error) {

                    throw new Error(
                        'Risposta server non valida'
                    );
                }


                if (!response.ok) {

                    throw new Error(
                        data.message ||
                        'Resa rifiutata'
                    );
                }


                return data;
            })

            .then(data => {

                if (!data.success) {
                    return;
                }


                const localReason =
                    window.playerColor === 'bianco'
                        ? 'White resigned'
                        : 'Black resigned';


                const localWinner =
                    window.playerColor === 'bianco'
                        ? 'Black wins!'
                        : 'White wins!';


                /*
                 * Il server ha già registrato
                 * la resa.
                 *
                 * Il WebSocket recupererà lo stato
                 * reale dal DB.
                 */

                requestServerSync();


                endGame(
                    localReason,
                    localWinner
                );


                playEndSound();
            })

            .catch(error => {

                console.error(
                    'Errore resa:',
                    error
                );
            });
        }
    );
}


/* ═══════════════════════════════════════════
   Home
   ═══════════════════════════════════════════ */

const esciButton =
    document.getElementById(
        'esci'
    );


if (esciButton) {

    esciButton.addEventListener(
        'click',
        function()
        {
            window.location.href =
                '/home';
        }
    );
}


/* ═══════════════════════════════════════════
   Avvio
   ═══════════════════════════════════════════ */

renderBoard(
    window.board
);

initTimerFromServer();

</script>

</body>
</html>
```
