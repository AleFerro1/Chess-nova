<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= $_SESSION['csrf_token'] ?>">
    <link rel="stylesheet" href="/styles/scacchiera.css">
    <link rel="icon" type="image/png" sizes="64x64" href="/images/favicon.png">
    <title>Chess - ChessNova</title>
</head>

<body>
    <div class="layout">

        <!-- colonna sinistra -->
        <div class="left">

            <!-- INFO NERO -->
            <div class="top-bar">

                <div class="player-info player-nero">

                    <div class="player-left">
                        <?php
                            $avatarSrc = !empty($elo_nero['avatar'])
                                ? '/public/' . htmlspecialchars($elo_nero['avatar'])
                                : '/public/assets/img/redking.jpg';
                        ?>
                        <div class="player-avatar">
                            <img src="<?= $avatarSrc ?>">
                        </div>

                        <div class="player-meta">
                            <div class="player-name">

                                <?php if (!empty($elo_nero['country'])): ?>
                                    <img class="flag"
                                        src="https://flagcdn.com/w20/<?= strtolower($elo_nero['country']) ?>.png">
                                <?php endif; ?>

                                <?= $nomeNero ?>
                            </div>

                            <div class="player-rank">
                                Elo <?= $elo_nero[$timecontrol] ?? '---' ?>
                            </div>
                        </div>

                    </div>

                </div>

                <div id="timerNero" class="timer timer-nero">
                    10:00
                </div>

            </div>

            <div id="scacchiera" class="scacchiera"></div>

            <button id="resignBtn" class="resign-btn">Resign</button>

            <!-- INFO BIANCO -->
            <div class="bottom-bar">

                <div class="player-info player-bianco">

                    <div class="player-left">

                        <?php
                            $avatarSrc = !empty($elo_bianco['avatar'])
                                ? '/public/' . htmlspecialchars($elo_bianco['avatar'])
                                : '/public/assets/img/redking.jpg';
                        ?>
                        <div class="player-avatar">
                            <img src="<?= $avatarSrc ?>">
                        </div>

                        <div class="player-meta">
                            <div class="player-name">

                                <?php if (!empty($elo_bianco['country'])): ?>
                                    <img class="flag"
                                        src="https://flagcdn.com/w20/<?= strtolower($elo_bianco['country']) ?>.png">
                                <?php endif; ?>

                                <?= $nomeBianco ?>
                            </div>

                            <div class="player-rank">
                                Elo <?= $elo_bianco[$timecontrol] ?? '---' ?>
                            </div>
                        </div>

                    </div>

                </div>

                <div id="timerBianco" class="timer timer-bianco">
                    10:00
                </div>

            </div>

        </div>

        <!-- pannello mosse -->
        <div class="moves-panel">
            <div class="moves-title">Moves record</div>
            <div id="movesList" class="moves-list"></div>
        </div>

    </div>

    <div id="endScreen" class="end-screen hidden">
        <div class="end-box">
            <h1 id="endTitle">Game ended</h1>
            <p id="endReason">Result</p>

            <div class="end-buttons">
                <button id="esci">Home</button>
            </div>
        </div>
    </div>

    <script>
        console.log("Board.php caricato");
        window.board        = <?= json_encode($board) ?>;
        window.turn         = <?= json_encode($turn) ?>;
        window.playerColor  = <?= json_encode($coloreGiocatore) ?>;
        window.fen          = <?= json_encode($fen) ?>;
        window.tempoBianco  = <?= (int)$tempo_bianco ?>;
        window.tempoNero    = <?= (int)$tempo_nero ?>;
        window.lastMoveTime = <?= (int)$tempo_ultima_mossa ?>;
        window.username     = <?= json_encode($username) ?>;

        if (window.playerColor === 'nero') {
            const left = document.querySelector('.left');
            const topBar = document.querySelector('.top-bar');
            const bottomBar = document.querySelector('.bottom-bar');
            const scacchiera = document.getElementById('scacchiera');
            const resignBtn = document.getElementById('resignBtn');

            left.innerHTML = '';
            left.appendChild(bottomBar);
            left.appendChild(scacchiera);
            left.appendChild(resignBtn);
            left.appendChild(topBar);
        }
    </script>
    <script src="/js/game.js?"></script>

</body>
</html>