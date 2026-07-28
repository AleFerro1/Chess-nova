<?php
require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

session_start();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . '/app/db/connection.php';
require_once __DIR__ . '/app/controllers/HomeController.php';
require_once __DIR__ . '/app/controllers/GameController.php';
require_once __DIR__ . '/app/services/ChessServices.php';
require_once __DIR__ . '/app/controllers/LoginController.php';
require_once __DIR__ . '/app/controllers/SignInController.php';
require_once __DIR__ . '/app/controllers/MatchmakingController.php';
require_once __DIR__ . '/app/controllers/ProfileController.php';
require_once __DIR__ . '/app/controllers/EditProfileController.php';
require_once __DIR__ . '/app/controllers/VerifyEmailController.php';
require_once __DIR__ . '/app/controllers/TimecontrolController.php';
require_once __DIR__ . '/app/controllers/LeaderboardController.php';

use App\Controllers\HomeController;
use App\Controllers\GameController;
use App\Controllers\LoginController;
use App\Controllers\SignInController;
use App\Services\ChessServices;
use App\Controllers\MatchmakingController;
use App\Controllers\ProfileController;
use App\Controllers\EditProfileController;
use App\Controllers\VerifyEmailController;
use app\controllers\TimecontrolController;
use App\Controllers\LeaderboardController;

function checkCsrf(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

    $token = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'CSRF token non valido']);
        exit;
    }
}

// routing base
$url = $_GET['url'] ?? 'login';

$publicRoutes = ['login', 'loginExe', 'signin', 'signinexe', 'verify'];

if(!in_array($url, $publicRoutes)){
    if(!isset($_SESSION['username'])){
        header("Location: /login");
        exit;
    }
}

switch ($url) {
    case 'timecontrol':{
        $tipo = $_GET['tipo'] ?? 1;
        $timecontrol = new TimecontrolController($db);
        echo $timecontrol->printTimecontrol($_SESSION['username'], $tipo);
        break;
    }
    case 'login': {
        $verify = $_GET['verify'] ?? 0;
        $login = new LoginController($db);
        echo $login->printLogin($verify);
        break;
    }
    case 'loginExe':{
        $login = new LoginController($db);
        if($_SERVER['REQUEST_METHOD'] === 'POST'){
            header('Content-Type: application/json');

            $username = $_POST['username'];
            $password = $_POST['password'];

            $result = $login->login($username, $password);

            if($result == 'success'){
                $_SESSION['username'] = $username;
                echo json_encode(["success" => "success"]);
                exit;
            }
            if($result == 'not verified'){
                echo json_encode(["success" => "not_verified"]);
                exit;
            }
            if($result == 'credenziali errate'){
                echo json_encode(["success" => "error"]);
                exit;
            }
        }
        break;
    }
    case 'signin': {
        $signin = new SignInController($db);
        echo $signin->printSignIn();
        break;
    }
    case 'signinexe':{
        $signin = new SignInController($db);
        if($_SERVER['REQUEST_METHOD'] === 'POST'){
            header('Content-Type: application/json');
            
            $username = $_POST['username'];
            $password = $_POST['password'];
            $email    = $_POST['email'];
            switch ($signin->signInUtente($username, $password, $email)){
                case 'username':{
                    echo json_encode(["success" => false, "username" => true]);
                    exit;
                }
                case 'email': {
                    echo json_encode(["success" => false, "email" => true]);
                    exit;
                }
                case 'password': {
                    echo json_encode(["success" => false, "password" => true]);
                    exit;
                }
                case 'success': {
                    echo json_encode(["success" => true]);
                    exit;
                }
                case 'error': {
                    echo json_encode(["success" => false]);
                    exit;
                }
            }
        }
        break;
    }

    case 'verify': {
        $token = $_GET['token'] ?? null;
        $verifyEmail = new VerifyEmailController($db);
        echo $verifyEmail->printVerifyEmail($token);
        break;
    }
    
    case 'searchMatch': {
        header('Content-Type: application/json');
        checkCsrf();
       
        if (!isset($_SESSION['username'])) {
            echo json_encode(["success" => false]);
            exit;
        }

        $timecontrol = $_POST['timecontrol'] ?? 'rapid-10';
        $tipo        = $_POST['tipo']; 
        

        $matchmaking = new MatchmakingController($db);
        $board       = new GameController($db);
        $username    = $_SESSION['username'];

        $matchmaking->cleanStale();

        $id_partita = $board->getActiveMatchId($username);

        if ($id_partita) {
            echo json_encode([
                "success" => true,
                "id_partita" => $id_partita
            ]);
            exit;
        }

        if ($matchmaking->isWaiting($username)) {
            echo json_encode(["success" => false, "waiting" => true]);
            exit;
        }
        $matchmaking->putInMatchmaking($username, $timecontrol, $tipo);

        $opponent = $matchmaking->findOpponent($username, $timecontrol, $tipo);

        if ($opponent) {
            $id_partita = $board->createMatch(time(), $timecontrol, $tipo);
            $_SESSION['id_partita'] = $id_partita;
            $colore1 = random_int(0, 1) ? 'bianco' : 'nero';
            $colore2 = $colore1 === 'bianco' ? 'nero' : 'bianco';

            $matchmaking->assignPlayersToMatch(
                $id_partita,
                $username,
                $opponent['username_utente'],
                $colore1,
                $colore2
            );

            $matchmaking->removeFromMatchmaking($username);
            $matchmaking->removeFromMatchmaking($opponent['username_utente']);

            echo json_encode(["success" => true, "id_partita" => $id_partita]);
            exit;
        }

        echo json_encode(["success" => false, "waiting" => true]);
        exit;
    }

    case 'checkMatch': {
        header('Content-Type: application/json');

        if (!isset($_SESSION['username'])) {
            echo json_encode(["success" => false]);
            exit;
        }

        $board = new GameController($db);
        $id_partita = $board->getActiveMatchId($_SESSION['username']);
        $_SESSION['id_partita'] = $id_partita;
        error_log("INDEX - session_id: " . session_id());
error_log("INDEX - username: " . ($_SESSION['username'] ?? 'NULL'));
error_log("INDEX - id_partita: " . ($_SESSION['id_partita'] ?? 'NULL'));
        if ($id_partita) {
            echo json_encode([
                "success" => true,
                "id_partita" => $id_partita
            ]);
            exit;
        }

        echo json_encode([
            "success" => false,
            "waiting" => true
        ]);
        exit;
    }
    
    case 'leaveMatchmaking': {
        checkCsrf();
        if (isset($_SESSION['username'])) {
            $matchmaking = new MatchmakingController($db);
            $matchmaking->removeIfWaiting($_SESSION['username']);
        }
        http_response_code(204);
        exit;
    }
    case 'createMatch':{
        $board = new GameController($db);
        $board->createMatch(time());
        break;
    }

    case 'resign': {
        header('Content-Type: application/json');
        checkCsrf();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(["success" => false, "error" => "metodo non consentito"]);
            exit;
        }

        $id = $_POST['id'] ?? null;
        if (!$id) {
            echo json_encode(["success" => false, "error" => "id mancante"]);
            exit;
        }

        $board = new GameController($db);

        $playerColor = $board->getPlayerColorForGame((int)$id, $_SESSION['username']);
        if ($playerColor === false) {
            echo json_encode(["success" => false, "error" => "non partecipi a questa partita"]);
            exit;
        }
        if (isset($color) && $playerColor !== $color) { // solo per timeoutGame
            echo json_encode(["success" => false, "error" => "colore non corrispondente"]);
            exit;
        }

        $board->reisgnFunction($id, $_SESSION['username']);

        $winner = ($color === 'bianco') ? 'nero' : 'bianco';
        $board->updateWinner($winner, $id);

        echo json_encode([
            "success" => true,
            "id_partita" => $id
        ]);
        exit;
    }

    case 'timeoutGame': {
        header('Content-Type: application/json');
        checkCsrf();

        if (!isset($_SESSION['username'])) {
            echo json_encode(["success" => false, "error" => "not logged"]);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(["success" => false, "error" => "metodo non consentito"]);
            exit;
        }

        $board = new GameController($db);
        $id    = $_POST['id'] ?? null;
        $color = $_POST['color'] ?? null;

        if (!$id || !in_array($color, ['bianco', 'nero'])) {
            echo json_encode(["success" => false, "error" => "parametri mancanti"]);
            exit;
        }

        $playerColor = $board->getPlayerColorForGame((int) $id, $_SESSION['username']);
        if ($playerColor !== $color) {
            http_response_code(403);
            echo json_encode(["success" => false, "error" => "colore non corrispondente"]);
            exit;
        }

        $winner = ($color === 'bianco') ? 'nero' : 'bianco';
        $board->updateWinner($winner, $id);
        $game = $board->stateMatch($id);
        $board->updateMatch($id, $game['fen'], $game['notation'], 'Timeout');

        echo json_encode(["success" => true]);
        exit;
    }

    case 'board':{
        $service = new ChessServices();
        
        $board = new GameController($db);
        $game = $board->stateMatch($_GET['id'] ?? null);

        $coloreGiocatore = $board->getPlayerColorForGame((int) ($game['id_partita'] ?? 0), $_SESSION['username']);
        if (!$coloreGiocatore) {
            http_response_code(403);
            echo "Accesso negato";
            exit;
        }
        
        $boardFittizia = $service->createBoard($game['fen']);
        
        if ($_SERVER['REQUEST_METHOD'] == "POST") {
            header('Content-Type: application/json');
            checkCsrf();

            $piece = $_POST["piece"];
            $from  = $_POST["from"];
            $to    = $_POST["to"];

            $notation = $piece . $from . $to;

            if (!$board->canPlayerMakeMove($game['id_partita'], $_SESSION['username'], $piece)) {
                echo json_encode(["success" => false, "error" => "non è il tuo pezzo"]);
                exit;
            }

            $newFen = $service->isValidMove($game['fen'], $piece, $from, $to);

            if ($newFen === false) {
                echo json_encode(["success" => false]);
                exit;
            }

            if (is_array($newFen) && isset($newFen['promozione'])) {
                $_SESSION['pending_promotion'][(string) $game['id_partita']] = [
                    'fen_base' => $newFen['fen_base'],
                    'from'     => $from,
                    'to'       => $to,
                    'piece'    => $piece,
                    'username' => $_SESSION['username'],
                ];

                echo json_encode([
                    "success"    => true,
                    "promozione" => true,
                    "fen_base"   => $newFen['fen_base']
                ]);
                exit;
            }

            // ───── SCACCO (o scacco matto) ─────
            if (is_array($newFen) && isset($newFen['check'])) {
                $actualFen = $newFen['fen'];
                $newBoard  = $service->createBoard($actualFen);
                $opponent  = $service->getTurn($actualFen);
                $checkmate = !$service->hasLegalMoves($newBoard, $opponent);

                $oldTurn = $service->getTurn($game['fen']);
                $adesso  = time();
                $tempo_passato = $adesso - $game['tempo_ultima_mossa'];

                if ($oldTurn === 'w') {
                    $nuovoTempoBianco = $game['tempo_bianco'] - $tempo_passato;
                    $board->updateTime($game['id_partita'], $nuovoTempoBianco, 'w');
                    $tempo = ["tempo" => $board->getTime($game['id_partita'], 'w'), "colore" => 'w'];
                } else {
                    $nuovoTempoNero = $game['tempo_nero'] - $tempo_passato;
                    $board->updateTime($game['id_partita'], $nuovoTempoNero, 'b');
                    $tempo = ["tempo" => $board->getTime($game['id_partita'], 'b'), "colore" => 'b'];
                }

                $lastMove = $service->lastMove($notation);
                $turn     = $service->getTurn($actualFen);

                if ($checkmate) {
                    $winner = ($opponent === 'w') ? 'nero' : 'bianco';
                    $board->updateWinner($winner, $game['id_partita']);
                    $board->updateMatch($game['id_partita'], $actualFen, $notation, 'Checkmate');

                    echo json_encode([
                        "success"   => true,
                        "fen"       => $actualFen,
                        "board"     => $newBoard,
                        "checkmate" => true,
                        "winner"    => $winner,
                        "tempo"     => $tempo,
                        "notation"  => $lastMove
                    ]);
                    exit;
                }

                $board->updateMatch($game['id_partita'], $actualFen, $notation, 'in corso');

                echo json_encode([
                    "success"      => true,
                    "fen"          => $actualFen,
                    "board"        => $newBoard,
                    "check"        => true,
                    "checkmate"    => false,
                    "enoughPieces" => true,
                    "tempo"        => $tempo,
                    "notation"     => $lastMove,
                    "turn"         => $turn
                ]);
                exit;
            }

            // ───── MOSSA NORMALE ─────
            $newBoard     = $service->createBoard($newFen);
            $opponent     = $service->getTurn($newFen);

            $stalemate    = !$service->isKingInCheck($newBoard, $opponent)
                        && !$service->hasLegalMoves($newBoard, $opponent);

            $enoughPieces = $service->hasEnoughPieces($newFen);
            $fiftyMoves   = $service->isFiftyMoveRule($newFen);
            $lastMove     = $service->lastMove($notation);
            $turno        = $service->getTurn($game['fen']);

            $tmp = $board->getFullMatch($game['id_partita']);
            $repetition = $service->repeatedMoves($tmp['moves'], $newFen);

            if ($stalemate) {
                $stato = 'Stalemate';
            } elseif (!$enoughPieces) {
                $stato = 'Insufficient material';
            } elseif ($repetition) {
                $stato = 'Threefold repetition';
            } elseif ($fiftyMoves) {
                $stato = '50 moves';
            } else {
                $stato = 'in corso';
            }

            $possibleOutcomes = ['Stalemate', 'Insufficient material', 'Threefold repetition', '50 moves'];
            if (in_array($stato, $possibleOutcomes)) {
                $board->updateDraw($game['id_partita']);
            }

            $adesso = time();
            $tempo_passato = $adesso - $game['tempo_ultima_mossa'];

            if ($turno === 'w') {
                $nuovoTempoBianco = $game['tempo_bianco'] - $tempo_passato;
                $board->updateTime($game['id_partita'], $nuovoTempoBianco, $turno);
                $tempo = ["tempo" => $board->getTime($game['id_partita'], $turno), "colore" => $turno];
            } else {
                $nuovoTempoNero = $game['tempo_nero'] - $tempo_passato;
                $board->updateTime($game['id_partita'], $nuovoTempoNero, $turno);
                $tempo = ["tempo" => $board->getTime($game['id_partita'], $turno), "colore" => $turno];
            }

            $board->updateMatch($game['id_partita'], $newFen, $notation, $stato);
            $fullMatch = $board->getFullMatch($game['id_partita']);

            echo json_encode([
                "success"      => true,
                "fen"          => $newFen,
                "board"        => $newBoard,
                "stalemate"    => $stalemate,
                "enoughPieces" => $enoughPieces,
                "repetition"   => $repetition,
                "fiftyMoves"   => $fiftyMoves,
                "notation"     => $lastMove,
                "tempo"        => $tempo,
                "storico"      => $fullMatch
            ]);
            exit;

        } else {
            echo $board->printBoard(
                $boardFittizia,
                $coloreGiocatore,
                $game['tempo_bianco'],
                $game['tempo_nero'],
                $game['tempo_ultima_mossa'],
                $_SESSION['username'],
                $game['id_partita'],
                $game['tipo_partita'],
                $game['fen']
            );
        }

        break;
    }

    case 'promuovi':{
        $service = new ChessServices();
        $board   = new GameController($db);
        $id      = $_GET['id'] ?? null;
        $game    = $board->stateMatch($id);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');
            checkCsrf();

            $tipo       = $_POST['tipo'] ?? 'scacchi';   // 'scacchi' o 'dama'
            $promoPiece = $_POST['promo'] ?? null;

            // Nella dama la promozione è automatica → forziamo 'q'
            if ($tipo === 'dama') {
                $promoPiece = 'q';
            }

            if (!in_array($promoPiece, ['q', 'r', 'b', 'n'], true)) {
                echo json_encode(["success" => false, "error" => "pezzo di promozione non valido"]);
                exit;
            }

            $pending = $_SESSION['pending_promotion'][(string) $id] ?? null;

            if (!$pending || $pending['username'] !== $_SESSION['username']) {
                echo json_encode(["success" => false, "error" => "nessuna promozione in sospeso"]);
                exit;
            }

            // Verifica che il tipo di gioco corrisponda
            if (($pending['tipo'] ?? 'scacchi') !== $tipo) {
                unset($_SESSION['pending_promotion'][(string) $id]);
                echo json_encode(["success" => false, "error" => "tipo partita non corrispondente"]);
                exit;
            }

            if (!$board->canPlayerMakeMove($id, $_SESSION['username'], $pending['piece'], $tipo)) {
                unset($_SESSION['pending_promotion'][(string) $id]);
                echo json_encode(["success" => false, "error" => "non è il tuo pezzo"]);
                exit;
            }

            $result = $service->isValidMove(
                $game['fen'],
                $pending['piece'],
                $pending['from'],
                $pending['to'],
                $tipo
            );

            if ($result === false || !is_array($result) || !isset($result['promozione'])) {
                unset($_SESSION['pending_promotion'][(string) $id]);
                echo json_encode(["success" => false, "error" => "stato promozione non più valido"]);
                exit;
            }

            $fenBase = $result['fen_base'];

            $opponentTurn = $service->getTurn($fenBase);
            $moverColor   = ($opponentTurn === 'w') ? 'b' : 'w';

            // Pezzo finale
            if ($tipo === 'dama') {
                $finalPiece = ($moverColor === 'w') ? 'W' : 'B';
                $notation   = $pending['to'] . '=' . $finalPiece;
            } else {
                $finalPiece = ($moverColor === 'w') ? strtoupper($promoPiece) : strtolower($promoPiece);
                $notation   = $pending['to'] . '=' . $finalPiece;
            }

            $boardArr = $service->createBoard($fenBase);
            [$row, $col] = $service->squareToCoord($pending['to']);

            $expectedPiece = ($tipo === 'dama')
                ? (($moverColor === 'w') ? 'w' : 'b')
                : (($moverColor === 'w') ? 'P' : 'p');

            if (($boardArr[$row][$col] ?? null) !== $expectedPiece) {
                unset($_SESSION['pending_promotion'][(string) $id]);
                echo json_encode(["success" => false, "error" => "stato promozione incoerente"]);
                exit;
            }

            $boardArr[$row][$col] = $finalPiece;

            $parts    = explode(' ', trim($fenBase));
            $parts[0] = $service->createFEN($boardArr);
            $finalFen = implode(' ', $parts);

            $newBoard = $boardArr;

            // Controllo fine partita
            if ($tipo === 'dama') {
                $hasMoves = $service->hasLegalMovesDama($newBoard, $opponentTurn);
                $check    = false;
                $checkmate = false;
                $stalemate = false;

                if (!$hasMoves) {
                    $winnerColor = ($moverColor === 'w') ? 'bianco' : 'nero';
                    $stato = 'Checkmate';
                    $board->updateWinner($winnerColor, $id);
                } else {
                    $stato = 'in corso';
                }
            } else {
                $check     = $service->isKingInCheck($newBoard, $opponentTurn);
                $checkmate = false;
                $stalemate = false;

                if ($check) {
                    $checkmate = !$service->hasLegalMoves($newBoard, $opponentTurn);
                } else {
                    $stalemate = !$service->hasLegalMoves($newBoard, $opponentTurn);
                }

                $enoughPieces = $service->hasEnoughPieces($finalFen);
                $fiftyMoves   = $service->isFiftyMoveRule($finalFen);
                $tmp          = $board->getFullMatch((int) $id);
                $repetition   = $service->repeatedMoves($tmp['moves'] ?? [], $finalFen);

                if ($checkmate) {
                    $stato = 'Checkmate';
                } elseif ($stalemate) {
                    $stato = 'Stalemate';
                } elseif (!$enoughPieces) {
                    $stato = 'Insufficient material';
                } elseif ($repetition) {
                    $stato = 'Threefold repetition';
                } elseif ($fiftyMoves) {
                    $stato = '50 moves';
                } else {
                    $stato = 'in corso';
                }

                if ($checkmate) {
                    $winnerColor = ($moverColor === 'w') ? 'bianco' : 'nero';
                    $board->updateWinner($winnerColor, $id);
                } elseif (in_array($stato, ['Stalemate', 'Insufficient material', 'Threefold repetition', '50 moves'], true)) {
                    $board->updateDraw((int) $id);
                }
            }

            $board->updateMatch($game['id_partita'], $finalFen, $notation, $stato);
            unset($_SESSION['pending_promotion'][(string) $id]);

            $response = [
                "success"   => true,
                "fen"       => $finalFen,
                "board"     => $newBoard,
            ];

            if ($tipo === 'dama') {
                $response['checkmate'] = isset($stato) && $stato === 'Checkmate';
                $response['winner']    = $response['checkmate'] ? (($moverColor === 'w') ? 'bianco' : 'nero') : null;
            } else {
                $response['check']     = $check;
                $response['checkmate'] = $checkmate;
                $response['stalemate'] = $stalemate;
            }

            echo json_encode($response);
        }
        break;
    }

    case 'legalMoves':{
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');
            checkCsrf();
        
            $service      = new ChessServices();
            $board        = new GameController($db);
            
            $game         = $board->stateMatch($_GET['id'] ?? null);
            $tipo_partita = $game['tipo_partita'];
            
            $piece        = $_POST['piece'];
            $from         = $_POST['from'];
            
            if (!$board->canPlayerMakeMove($game['id_partita'], $_SESSION['username'], $piece, $tipo_partita)) {
                echo json_encode(["success" => false, "error" => "non è il tuo pezzo"]);
                exit;
            }

            $moves = $service->getLegalMoves($game['fen'], $piece, $from, $tipo_partita);
            
            echo json_encode([
                "success" => true,
                "moves"   => $moves 
            ]);
        }
        break;
    }

    case 'dama': {
        $service = new ChessServices();
        $board = new GameController($db);
        $game = $board->stateMatch($_GET['id'] ?? null);

        $coloreGiocatore = $board->getPlayerColorForGame((int) ($game['id_partita'] ?? 0), $_SESSION['username']);
        if (!$coloreGiocatore) {
            http_response_code(403);
            echo "Accesso negato";
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            header('Content-Type: application/json');
            checkCsrf();

            $piece = $_POST["piece"];
            $from  = $_POST["from"];
            $to    = $_POST["to"];
            $notation = $piece . $from . $to;

            if (!$board->canPlayerMakeMove($game['id_partita'], $_SESSION['username'], $piece, 'dama')) {
                echo json_encode(["success" => false, "error" => "non è il tuo pezzo"]);
                exit;
            }

            $newFen = $service->isValidMove($game['fen'], $piece, $from, $to, 'dama');
            if ($newFen === false) {
                echo json_encode(["success" => false]);
                exit;
            }

            // Gestione promozione
            if (is_array($newFen) && isset($newFen['promozione'])) {
                $_SESSION['pending_promotion'][(string) $game['id_partita']] = [
                    'fen_base' => $newFen['fen_base'],
                    'from'     => $from,
                    'to'       => $to,
                    'piece'    => $piece,
                    'username' => $_SESSION['username'],
                    'tipo'     => 'dama',
                ];

                echo json_encode([
                    "success"    => true,
                    "promozione" => true,
                    "fen_base"   => $newFen['fen_base']
                ]);
                exit;
            }

            // Aggiorna il database con la nuova posizione
            $board->updateMatch($game['id_partita'], $newFen, $notation, 'in corso');

            $newBoard = $service->createBoard($newFen);
            $turn     = $service->getTurn($newFen);

            // Aggiornamento tempo
            $oldTurn = $service->getTurn($game['fen']);
            $adesso = time();
            $tempo_passato = $adesso - $game['tempo_ultima_mossa'];

            if ($oldTurn === 'w') {
                $nuovoTempoBianco = $game['tempo_bianco'] - $tempo_passato;
                $board->updateTime($game['id_partita'], $nuovoTempoBianco, 'w');
            } else {
                $nuovoTempoNero = $game['tempo_nero'] - $tempo_passato;
                $board->updateTime($game['id_partita'], $nuovoTempoNero, 'b');
            }

            // Controllo fine partita
            $opponentTurn = ($turn === 'w') ? 'b' : 'w';
            $hasMoves = $service->hasLegalMovesDama($newBoard, $turn);
            if (!$hasMoves) {
                $winnerColor = ($opponentTurn === 'w') ? 'nero' : 'bianco';
                $board->updateWinner($winnerColor, $game['id_partita']);
                $board->updateMatch($game['id_partita'], $newFen, $notation, 'Checkmate');
                
                echo json_encode([
                    "success"   => true,
                    "fen"       => $newFen,
                    "board"     => $newBoard,
                    "turn"      => $turn,
                    "checkmate" => true,
                    "winner"    => $winnerColor,
                    "gameOver"  => true   
                ]);
                exit;
            }

            $fullMatch = $board->getFullMatch($game['id_partita']);

            $tempo = [
                "tempo"  => $board->getTime($game['id_partita'], $oldTurn),
                "colore" => $oldTurn
            ];

            $lastMoveStruct = $service->lastMove($notation);

            echo json_encode([
                "success"  => true,
                "fen"      => $newFen,
                "board"    => $newBoard,
                "turn"     => $turn,
                "storico"  => $fullMatch,
                "notation" => $lastMoveStruct,
                "tempo"    => $tempo 
            ]);
            exit;
        } else {
            
            $boardFittizia = $service->createBoard($game['fen']);
            echo $board->printBoard(
                $boardFittizia,
                $coloreGiocatore,
                $game['tempo_bianco'],
                $game['tempo_nero'],
                $game['tempo_ultima_mossa'],
                $_SESSION['username'],
                $game['id_partita'],
                $game['tipo_partita'],
                $game['fen']
            );
        }
        break;
    }

    case 'profile' :{
        $profile = new ProfileController($db);
        echo $profile->printProfile($_SESSION['username']);
        break;
    }

    case 'editProfile': {
    
        $editProfile = new EditProfileController($db);
    
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            checkCsrf();

            $username     = trim($_POST['username']        ?? '');
            $email        = trim($_POST['email']           ?? '');
            $bio          = trim($_POST['bio']             ?? '');
            $oldPassword  = $_POST['current_password']     ?? '';
            $newPassword  = $_POST['new_password']         ?? '';
            $country      = $_POST['country']              ?? '';
            $removeAvatar = isset($_POST['remove_avatar']) && $_POST['remove_avatar'] === '1';
    
            $avatarFile = (!empty($_FILES['avatar']['name']))
                        ? $_FILES['avatar']
                        : null;
    
            $result = $editProfile->updateProfile(
                $_SESSION['username'],
                $username,
                $email,
                $bio,
                $oldPassword,
                $newPassword,
                $country,
                $avatarFile,
                $removeAvatar
            );
    
            switch ($result) {
                case 'ok':
                    $_SESSION['success']  = "Profilo aggiornato con successo";
                    $_SESSION['username'] = $username;
                    break;
                case 'username':
                    $_SESSION['username_error']       = "Offensive or not valid username";
                    break;
                case 'email':
                    $_SESSION['email_error']          = "Invalid email";
                    break;
                case 'bio':
                    $_SESSION['bio_error']            = "Bio contains offensive words";
                    break;
                case 'errorePassword':
                    $_SESSION['oldPassword_error']    = "Incorrect password";
                    break;
                case 'erroreLunghezza':
                    $_SESSION['password_lenghtError'] = "The new password must have at least 8 characters";
                    break;
                case 'erroreNumeri':
                    $_SESSION['password_numberError'] = "The new password must contain at least 1 number";
                    break;
                case 'erroreAvatar':
                    $_SESSION['avatar_error']         = "Invalid image (max 2 MB, formats: jpg, png, gif, webp)";
                    break;
            }
        }
    
        echo $editProfile->printEditProfile($_SESSION['username']);
    
        
        unset(
            $_SESSION['username_error'],
            $_SESSION['bio_error'],
            $_SESSION['email_error'],
            $_SESSION['oldPassword_error'],
            $_SESSION['password_lenghtError'],
            $_SESSION['password_numberError'],
            $_SESSION['avatar_error'],
            $_SESSION['success']
        );
    
        break;
    }
    
    case 'logout': {
        $_SESSION = [];
    
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
    
        session_destroy();
    
        header('Location: /login');
        exit;
    }

    case 'heartbeat': {
        checkCsrf();
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['username'])) {
            $home = new HomeController($db);
            $home->heartbeat($_SESSION['username']);
        }
        http_response_code(204);
        exit;
    }
    
    case 'leaderboard': {
        $leaderboard = new LeaderboardController($db);
        echo $leaderboard->printLeaderboard();
        break;
    }

    case 'home':
    default:
        $home = new HomeController($db);
        echo $home->printHome($_SESSION['username']);
        break;
}