<?php




require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

/*session_save_path(__DIR__ . '/tmp');
if (!is_dir(__DIR__ . '/tmp')) {
    mkdir(__DIR__ . '/tmp', 0777, true);
}*/ 
session_start();


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

        // già in una partita ATTIVA
        $id_partita = $board->getActiveMatchId($username);

        if ($id_partita) {
            echo json_encode([
                "success" => true,
                "id_partita" => $id_partita
            ]);
            exit;
        }

        // già in attesa
        if ($matchmaking->isWaiting($username)) {
            echo json_encode(["success" => false, "waiting" => true]);
            exit;
        }
        // prima volta: metti in coda
        $matchmaking->putInMatchmaking($username, $timecontrol, $tipo);

        // prova a trovare un avversario
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

        $id = $_GET['id'] ?? null;

        $board = new GameController($db);
        $board->reisgnFunction($id, $_SESSION['username']);
        $color = $board->getPlayerColor($_SESSION['username']);
        $winner = null;
        if($color == 'bianco'){
            $winner = 'nero';
        }
        if($color == 'nero') $winner = 'bianco';

        $board->updateWinner($winner, $id);

        echo json_encode([
            "success" => true,
            "id_partita" => $id
        ]);
        exit;
    }

    /*case 'checkTime' : {
        $id_partita = $_GET['id'] ?? null;
        $board = new GameController($db);

        $game = $board->getFullMatch($id_partita);
        $fen = $game['game']['fen'];
        $turn = explode(' ', $fen)[1];
        $turn == 'bianco'
        $board->getTime($id_partita, $turn);
    }*/
    case 'timeoutGame': {
        header('Content-Type: application/json');
        if (!isset($_SESSION['username'])) {
            echo json_encode(["success" => false, "error" => "not logged"]);
            exit;
        }
        $board = new GameController($db);
        $id    = $_GET['id'] ?? null;
        $color = $_GET['color'] ?? null;  // 'bianco' o 'nero', passato dal client

        if (!$id || !in_array($color, ['bianco', 'nero'])) {
            echo json_encode(["success" => false, "error" => "parametri mancanti"]);
            exit;
        }

        // Verifica che il giocatore sia effettivamente di quel colore nella partita
        $playerColor = $board->getPlayerColor($_SESSION['username']);
        if ($playerColor !== $color) {
            echo json_encode(["success" => false, "error" => "colore non corrispondente"]);
            exit;
        }

        // Determina il vincitore opposto
        $winner = ($color === 'bianco') ? 'nero' : 'bianco';
        $board->updateWinner($winner, $id);
        $board->updateMatch($id, null, null, 'Timeout');  // aggiorna lo stato (fen e notation non cambiano)

        echo json_encode(["success" => true]);
        exit;
    }

    case 'board':{
    $service = new ChessServices();
    
    $board = new GameController($db);
    $game = $board->stateMatch($_GET['id'] ?? null);
    
    $boardFittizia = $service->createBoard($game['fen']);
    
    if ($_SERVER['REQUEST_METHOD'] == "POST") {
        header('Content-Type: application/json');

        $piece = $_POST["piece"];
        $from  = $_POST["from"];
        $to    = $_POST["to"];

        $notation = $piece . $from . $to;

        if (!$board->canPlayerMakeMove($game['id_partita'], $_SESSION['username'], $piece)) {
            echo json_encode(["success" => false, "error" => "non è il tuo pezzo"]);
            exit;
        }

        $newFen = $service->isValidMove($game['fen'], $piece, $from, $to);

        if ($newFen === false) { //mossa non valida
            echo json_encode(["success" => false]);
            exit;
        }

        // Promozionechied al client che pezzo vuole
        if (is_array($newFen) && isset($newFen['promozione'])) {
            echo json_encode([
                "success"    => true,
                "promozione" => true,
                "fen_base"   => $newFen['fen_base']
            ]);
            exit;
        }

        if (is_array($newFen) && isset($newFen['check'])) { //check 
            $actualFen = $newFen['fen'];
            $board->updateMatch($game['id_partita'], $actualFen, $notation, 'in corso');
            $newBoard  = $service->createBoard($actualFen);
            $opponent  = $service->getTurn($actualFen);
            $checkmate = !$service->hasLegalMoves($newBoard, $opponent);

            if ($checkmate) {
                $winner = ($opponent === 'w') ? 'nero' : 'bianco';
                $board->updateWinner($winner, $game['id_partita']);
                $board->updateMatch($game['id_partita'], $actualFen, $notation, 'Checkmate');

                echo json_encode([
                    "success" => true,
                    "fen" => $actualFen,
                    "board" => $newBoard,
                    "checkmate" => true,
                    "winner" => $winner
                ]);
                exit;
            }

            echo json_encode([
                "success"      => true,
                "fen"          => $actualFen,
                "board"        => $newBoard,
                "check"        => true,
                "checkmate"    => $checkmate,
                "enoughPieces" => true 
            ]);
            exit;
        }

        // Mossa nrmale
        $newBoard     = $service->createBoard($newFen);
        $opponent     = $service->getTurn($newFen);

        $stalemate    = !$service->isKingInCheck($newBoard, $opponent)
                    && !$service->hasLegalMoves($newBoard, $opponent);

        $enoughPieces = $service->hasEnoughPieces($newFen);
        $fiftyMoves   = $service->isFiftyMoveRule($newFen);
        $lastMove = $service->lastMove($notation);
        $turno = $service->getTurn($game['fen']);

        // passa $newFen che è la posizione appena raggiunta
        $tmp = $board->getFullMatch($game['id_partita']);
        $repetition = $service->repeatedMoves($tmp['moves'], $newFen);
        if ($stalemate) {
            $stato = 'Stalemate';
        } elseif (!$enoughPieces) {
            $stato = 'Insufficent material';
        } elseif ($repetition) {
            $stato = 'Threefold repetition';
        } elseif ($fiftyMoves) {
            $stato = '50 moves';
        } else {
            $stato = 'in corso';
        }

        $possibleOutcomes = ['Stalemate', 'Insufficent material', 'Threefold repetition', '50 moves'];

        if(in_array($stato, $possibleOutcomes)){
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
            "notation" => $lastMove,
            "tempo" => $tempo,
            "storico" => $fullMatch
        ]);
        exit;
    } else {
        $coloreGiocatore = $board->getPlayerColor($_SESSION['username']);
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
    $board = new GameController($db);
    $game = $board->stateMatch($_GET['id'] ?? null);
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json');

        $fenBase    = $_POST['fen_base'];
        $promoPiece = $_POST['promo'];
        $to         = $_POST['to'];
        $turn       = $_POST['turn'];

        $finalPiece = ($turn === 'w') ? strtoupper($promoPiece) : strtolower($promoPiece);

        $notation = $to . "=" . $finalPiece;

        
        $boardArr = $service->createBoard($fenBase);
        [$row, $col] = $service->squareToCoord($to);
        $boardArr[$row][$col] = $finalPiece;

     
        $parts    = explode(' ', trim($fenBase));
        $parts[0] = $service->createFEN($boardArr);
        $finalFen = implode(' ', $parts);

        $opponent = $service->getTurn($finalFen);
        $newBoard = $boardArr;

        // Scaccomatto dopo promozione
        $check     = $service->isKingInCheck($newBoard, $opponent);
        $checkmate = false;
        $stalemate = false;

        if ($check) {
            $checkmate = !$service->hasLegalMoves($newBoard, $opponent);
        } else {
            $stalemate = !$service->hasLegalMoves($newBoard, $opponent);
        }

        if($stalemate) $stato = 'Stalemate';
        if(!$enoughPieces) $stato = 'Insufficent material';
        if($repetition) $stato = 'Threefold repetition';
        if($fiftymoves) $stato = '50 moves';
        if (!$stalemate && $enoughPieces && !$repetition && !$fiftyMoves) $stato = 'in corso';

        $board->updateMatch($game['id_partita'], $finalFen, $notation , $stato);
        

        echo json_encode([
            "success"   => true,
            "fen"       => $finalFen,
            "board"     => $newBoard,
            "check"     => $check,
            "checkmate" => $checkmate,
            "stalemate" => $stalemate
        ]);
    }
    break;
    }
    case 'legalMoves':{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json');
        
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
        
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            header('Content-Type: application/json');

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

            // Aggiorna il database con la nuova posizione
            $board->updateMatch($game['id_partita'], $newFen, $notation, 'in corso');

            $newBoard = $service->createBoard($newFen);
            $turn     = $service->getTurn($newFen); // chi deve muovere adesso

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

            // Controllo fine
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

            echo json_encode([
                "success"  => true,
                "fen"      => $newFen,
                "board"    => $newBoard,
                "turn"     => $turn,
                "storico"  => $fullMatch,
                "notation" => $notation,
                "tempo"    => $tempo 
            ]);
            exit;
        } else {
            
            $boardFittizia = $service->createBoard($game['fen']);
            $coloreGiocatore = $board->getPlayerColor($_SESSION['username']);
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
    
            $username     = trim($_POST['username']        ?? '');
            $email        = trim($_POST['email']           ?? '');
            $bio          = trim($_POST['bio']             ?? '');
            $oldPassword  = $_POST['current_password']     ?? '';
            $newPassword  = $_POST['new_password']         ?? '';
            $country      = $_POST['country']              ?? '';
            $removeAvatar = isset($_POST['remove_avatar']) && $_POST['remove_avatar'] === '1';
    
            /* file avatar (null se non caricato) */
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
        /* invalida la sessione in modo sicuro */
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
