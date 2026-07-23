<?php
namespace app\services;
require_once __DIR__ . '/pieces/PawnServices.php';
require_once __DIR__ . '/pieces/KnightServices.php';
require_once __DIR__ . '/pieces/BishopServices.php';
require_once __DIR__ . '/pieces/RookServices.php';
require_once __DIR__ . '/pieces/QueenServices.php';
require_once __DIR__ . '/pieces/KingServices.php';
require_once __DIR__ . '/pieces/DamaPiccolaService.php';
use app\services\pieces\DamaPiccolaService;
use app\services\pieces\PawnServices;
use app\services\pieces\KnightServices;
use app\services\pieces\BishopServices;
use app\services\pieces\RookServices;
use app\services\pieces\QueenServices;
use app\services\pieces\KingServices;
class ChessServices{
    private $board;
    function __construct($scacchiera = null)
    {
        if ($scacchiera === null) {
            $scacchiera = [
                ['r','n','b','q','k','b','n','r'],
                ['p','p','p','p','p','p','p','p'],
                [null,null,null,null,null,null,null,null],
                [null,null,null,null,null,null,null,null],
                [null,null,null,null,null,null,null,null],
                [null,null,null,null,null,null,null,null],
                ['P','P','P','P','P','P','P','P'],
                ['R','N','B','Q','K','B','N','R'],
            ];
        }
    
        $this->board = $scacchiera;
    }
    public function isValidMove(string $fen, string $piece, string $from, string $to, $tipo = 'scacchi') {
        $this->createBoard($fen);
        $move = $from . $to;
        if (!$this->isRightTurn($move, $fen, $tipo)) return false;
        if (!$this->isPieceThere($move)) return false;
        if($tipo == 'scacchi'){
            switch (strtolower($piece)) {
                case 'p':
                    $pawn = new PawnServices();
                    $enPassant = $this->getEnPassant($fen);
                    $result = $pawn->isValidMove($this->board, $from, $to, $this->getTurn($fen), $piece, $enPassant);
                    if (!$result) return false;
                    

                    // Applica la mossa sul board
                    [$fromRow, $fromCol] = $this->squareToCoord($from);
                    [$toRow, $toCol]     = $this->squareToCoord($to);
                    $this->board[$toRow][$toCol]     = $this->board[$fromRow][$fromCol];
                    $this->board[$fromRow][$fromCol] = null;

                    if ($enPassant !== null && $to === $enPassant) {
                        if ($this->getTurn($fen) === 'w') {
                            $this->board[$toRow + 1][$toCol] = null; // pedone nero è sotto
                        } else {
                            $this->board[$toRow - 1][$toCol] = null; // pedone bianco è sopra
                        }
                    }

                    
                    $opponent = ($this->getTurn($fen) === 'w') ? 'b' : 'w';

                    if ($this->isKingInCheck($this->board, $this->getTurn($fen))) return false;

                    if ($this->isKingInCheck($this->board, $opponent)) {
                        return ['check' => true, 'fen' => $this->buildFullFEN($this->board, $fen, $from, $to, true)];
                    }

                    if ($result === 'promozione') {
                        
                        return ['promozione' => true, 'fen_base' => $this->buildFullFEN($this->board, $fen, $from, $to, true)];
                    }

                    return $this->buildFullFEN($this->board, $fen, $from, $to, true);
                case 'n': {
                    $knight = new KnightServices();
                    $result = $knight->isValidMove($this->board, $from, $to, $this->getTurn($fen), $piece);
                    if (!$result) return false;

                    [$fromRow, $fromCol] = $this->squareToCoord($from);
                    [$toRow,   $toCol]   = $this->squareToCoord($to);
                    $wasCapture = $this->board[$toRow][$toCol] !== null; 

                    $this->board[$toRow][$toCol]     = $this->board[$fromRow][$fromCol];
                    $this->board[$fromRow][$fromCol] = null;

                    if ($this->isKingInCheck($this->board, $this->getTurn($fen))) return false;

                    $opponent = ($this->getTurn($fen) === 'w') ? 'b' : 'w';
                    if ($this->isKingInCheck($this->board, $opponent)) {
                        return ['check' => true, 'fen' => $this->buildFullFEN($this->board, $fen, $from, $to, $wasCapture)];
                    }

                    return $this->buildFullFEN($this->board, $fen, $from, $to, $wasCapture);
                }
                case 'b': {
                    $bishop = new BishopServices();
                    $result = $bishop->isValidMove($this->board, $from, $to, $this->getTurn($fen), $piece);
                    if (!$result) return false;

                    [$fromRow, $fromCol] = $this->squareToCoord($from);
                    [$toRow,   $toCol]   = $this->squareToCoord($to);
                    $wasCapture = $this->board[$toRow][$toCol] !== null;

                    $this->board[$toRow][$toCol]     = $this->board[$fromRow][$fromCol];
                    $this->board[$fromRow][$fromCol] = null;

                    if ($this->isKingInCheck($this->board, $this->getTurn($fen))) return false;

                    $opponent = ($this->getTurn($fen) === 'w') ? 'b' : 'w';
                    if ($this->isKingInCheck($this->board, $opponent)) {
                        return ['check' => true, 'fen' => $this->buildFullFEN($this->board, $fen, $from, $to, $wasCapture)];
                    }

                    return $this->buildFullFEN($this->board, $fen, $from, $to, $wasCapture);
                }
                case 'r': {
                    $rook = new RookServices();
                    $result = $rook->isValidMove($this->board, $from, $to, $this->getTurn($fen), $piece);
                    if (!$result) return false;

                    [$fromRow, $fromCol] = $this->squareToCoord($from);
                    [$toRow,   $toCol]   = $this->squareToCoord($to);
                    $wasCapture = $this->board[$toRow][$toCol] !== null;

                    $this->board[$toRow][$toCol]     = $this->board[$fromRow][$fromCol];
                    $this->board[$fromRow][$fromCol] = null;

                    if ($this->isKingInCheck($this->board, $this->getTurn($fen))) return false;

                    $opponent = ($this->getTurn($fen) === 'w') ? 'b' : 'w';
                    if ($this->isKingInCheck($this->board, $opponent)) {
                        return ['check' => true, 'fen' => $this->buildFullFEN($this->board, $fen, $from, $to, $wasCapture)];
                    }

                    return $this->buildFullFEN($this->board, $fen, $from, $to, $wasCapture);
                }
                case 'q': {
                    $queen = new QueenServices();
                    $result = $queen->isValidMove($this->board, $from, $to, $this->getTurn($fen), $piece);
                    if (!$result) return false;

                    [$fromRow, $fromCol] = $this->squareToCoord($from);
                    [$toRow,   $toCol]   = $this->squareToCoord($to);
                    $wasCapture = $this->board[$toRow][$toCol] !== null;

                    $this->board[$toRow][$toCol]     = $this->board[$fromRow][$fromCol];
                    $this->board[$fromRow][$fromCol] = null;

                    if ($this->isKingInCheck($this->board, $this->getTurn($fen))) return false;

                    $opponent = ($this->getTurn($fen) === 'w') ? 'b' : 'w';
                    if ($this->isKingInCheck($this->board, $opponent)) {
                        return ['check' => true, 'fen' => $this->buildFullFEN($this->board, $fen, $from, $to, $wasCapture)];
                    }

                    return $this->buildFullFEN($this->board, $fen, $from, $to, $wasCapture);
                }
                case 'k': {
                    $king = new KingServices();
                    $result = $king->isValidMove($this->board, $from, $to, $this->getTurn($fen), $piece, $fen, $this);
                    if (!$result) return false;

                    [$fromRow, $fromCol] = $this->squareToCoord($from);
                    [$toRow,   $toCol]   = $this->squareToCoord($to);
                    $wasCapture = $this->board[$toRow][$toCol] !== null;

                    $this->board[$toRow][$toCol]     = $this->board[$fromRow][$fromCol];
                    $this->board[$fromRow][$fromCol] = null;

                    if (abs($toCol - $fromCol) === 2) {
                        if ($toCol === 6) {
                            $this->board[$fromRow][5] = $this->board[$fromRow][7];
                            $this->board[$fromRow][7] = null;
                        } elseif ($toCol === 2) {
                            $this->board[$fromRow][3] = $this->board[$fromRow][0];
                            $this->board[$fromRow][0] = null;
                        }
                    }

                    if ($this->isKingInCheck($this->board, $this->getTurn($fen))) return false;

                    return $this->buildFullFEN($this->board, $fen, $from, $to, $wasCapture);
                }
            }
        }
        else {
            $dama = new DamaPiccolaService();
            $result = $dama->isValidMove($this->board, $from, $to, $this->getTurn($fen), $piece);
            if (!$result) return false;

            [$fromRow, $fromCol] = $this->squareToCoord($from);
            [$toRow,   $toCol]   = $this->squareToCoord($to);

          
            $this->board[$toRow][$toCol]     = $this->board[$fromRow][$fromCol];
            $this->board[$fromRow][$fromCol] = null;

            
            if (is_array($result) && isset($result['captured'])) {
                foreach ($result['captured'] as [$capturedRow, $capturedCol]) {
                    $this->board[$capturedRow][$capturedCol] = null;
                }
            }

            // promozione
            if ($this->board[$toRow][$toCol] === 'w' && $toRow === 0) {
                $this->board[$toRow][$toCol] = 'W';
            }
            if ($this->board[$toRow][$toCol] === 'b' && $toRow === 7) {
                $this->board[$toRow][$toCol] = 'B';
            }

            $newturn = ($this->getTurn($fen) === 'w') ? 'b' : 'w';
            return $this->createFEN($this->board, $newturn);
        }
        
        return false;
    }

public function applyMove(string $fen, string $from, string $to, string $piece): array
{
    $this->createBoard($fen);

    [$fromRow, $fromCol] = $this->squareToCoord($from);
    [$toRow,   $toCol]   = $this->squareToCoord($to);

    $movingPiece = $this->board[$fromRow][$fromCol];

    
    $this->board[$toRow][$toCol]     = $movingPiece;
    $this->board[$fromRow][$fromCol] = null;

    // Arrocco: spostatorre
    if (strtolower($movingPiece) === 'k' && abs($toCol - $fromCol) === 2) {
        if ($toCol === 6) {
            
            $this->board[$fromRow][5] = $this->board[$fromRow][7];
            $this->board[$fromRow][7] = null;
        } elseif ($toCol === 2) {
            
            $this->board[$fromRow][3] = $this->board[$fromRow][0];
            $this->board[$fromRow][0] = null;
        }
    }

    return $this->board;
}

    public function createBoard($fen) {
        $parts = explode(' ', trim($fen));
        $rows = explode('/', $parts[0]);

        $this->board = [];

        foreach ($rows as $i => $row) {
            $this->board[$i] = [];
            $j = 0;

            for ($k = 0; $k < strlen($row); $k++) {
                $char = $row[$k];

                if (ctype_digit($char)) {
                    $empty = (int)$char;

                    for ($n = 0; $n < $empty; $n++) {
                        $this->board[$i][$j++] = null;
                    }
                } else {
                    $this->board[$i][$j++] = $char;
                }
            }
        }
        return $this->board;
    }

    public function notationCheck($move){ //Controlla se la notazione della mossa è grammaticalmente corretta
        if(!preg_match('/^[a-h][1-8][a-h][1-8][qrbn]?$/' , $move)){
            return false;
        }
        else return true;
    }

    public function isPieceThere($move){ // Controlla se un pezzo è presente in una casella
        $from = substr($move, 0, 2);
        [$row, $col] = $this->squareToCoord($from);
        if($this->board[$row][$col] === null){
            return false;
        }
        else return true; 
    }

    function squareToCoord($square) {
        $file = $square[0]; 
        $rank = $square[1]; 

        $col = ord($file) - ord('a'); 
        $row = 8 - (int)$rank;        

        return [$row, $col];
    }

    public function isRightTurn($move, $fen, $tipo = 'scacchi'): bool {
        $tmp = explode(' ', $fen);
        $turn = $tmp[1]; 

        $from = substr($move, 0, 2);
        [$row, $col] = $this->squareToCoord($from);

        $piece = $this->board[$row][$col];

        if ($piece === null) {
            return false;
        }

        if ($tipo === 'dama') {
            // Dama: pedoni 'w'/'b', dame 'W'/'B'
            $isWhite = ($piece === 'w' || $piece === 'W');
        } else {
            $isWhite = ctype_upper($piece);
        }

        if ($turn === 'w' && !$isWhite) return false;
        if ($turn === 'b' && $isWhite)  return false;

        return true;
    }
    public function getTurn($fen){
        $parts = explode(' ', trim($fen));
        return $parts[1];
    }

    public function createFEN($board, $turn = null)
{
    $fen = '';

    for ($row = 0; $row < 8; $row++) {

        $empty = 0;

        for ($col = 0; $col < 8; $col++) {

            $piece = $board[$row][$col];

            if ($piece === null) {
                $empty++;
            } else {
                if ($empty > 0) {
                    $fen .= $empty;
                    $empty = 0;
                }
                $fen .= $piece;
            }
        }

        // se finisce la riga con caselle vuote
        if ($empty > 0) {
            $fen .= $empty;
        }

        // separatore righe
        if ($row < 7) {
            $fen .= '/';
        }
    }
    if($turn !== null){
        $fen .= ' ' . $turn;
    }

    return $fen;
}
public function buildFullFEN(array $board, string $oldFen, string $from, string $to, bool $resetHalfMove = false): string
{
    $parts    = explode(' ', trim($oldFen));
    $turn     = $parts[1];
    $castling = $parts[2] ?? '-';
    $halfMove = isset($parts[4]) ? (int)$parts[4] : 0;
    $fullMove = isset($parts[5])
        ? ($turn === 'b' ? (int)$parts[5] + 1 : $parts[5])
        : '1';

    // Aggiorna castling
    if ($from === 'e1') $castling = str_replace(['K', 'Q'], '', $castling);
    if ($from === 'e8') $castling = str_replace(['k', 'q'], '', $castling);
    if ($from === 'h1') $castling = str_replace('K', '', $castling);
    if ($from === 'a1') $castling = str_replace('Q', '', $castling);
    if ($from === 'h8') $castling = str_replace('k', '', $castling);
    if ($from === 'a8') $castling = str_replace('q', '', $castling);
    if ($castling === '') $castling = '-';

    // Calcola en passant
    $enPassant = '-';
    [$fromRow, $fromCol] = $this->squareToCoord($from);
    [$toRow,   $toCol]   = $this->squareToCoord($to);
    $movedPiece = $board[$toRow][$toCol];

    if (strtolower($movedPiece) === 'p' && abs($toRow - $fromRow) === 2) {
        $epRow     = (int)(($fromRow + $toRow) / 2);
        $enPassant = $this->coordToSquare($epRow, $fromCol);
    }

    // Halfmove: azzera se pedone mosso o cattura, altrimenti incrementa
    if ($resetHalfMove) {
        $halfMove = 0;
    } else {
        $halfMove++;
    }

    $newTurn        = ($turn === 'w') ? 'b' : 'w';
    $piecePlacement = $this->createFEN($board);

    return "$piecePlacement $newTurn $castling $enPassant $halfMove $fullMove";
}
    public function isSquareAttacked(array $board, int $row, int $col, string $byTurn): bool
{
    // Controlla se la casella ($row, $col) è attaccata da qualsiasi pezzo del colore $byTurn

    for ($r = 0; $r < 8; $r++) {
        for ($c = 0; $c < 8; $c++) {
            $piece = $board[$r][$c];
            if ($piece === null) continue;

            // Considera solo i pezzi del colore attaccante
            $isWhite = ctype_upper($piece);
            if ($byTurn === 'w' && !$isWhite) continue;
            if ($byTurn === 'b' && $isWhite)  continue;

            $fromSquare = $this->coordToSquare($r, $c);
            $toSquare   = $this->coordToSquare($row, $col);

            $attacks = match(strtolower($piece)) {
                'p' => $this->pawnAttacks($board, $fromSquare, $toSquare, $byTurn, $piece),
                'n' => (new KnightServices())->isValidMove($board, $fromSquare, $toSquare, $byTurn, $piece),
                'b' => (new BishopServices())->isValidMove($board, $fromSquare, $toSquare, $byTurn, $piece),
                'r' => (new RookServices())->isValidMove($board, $fromSquare, $toSquare, $byTurn, $piece),
                'q' => (new QueenServices())->isValidMove($board, $fromSquare, $toSquare, $byTurn, $piece),
                'k' => $this->kingAttacks($row, $col, $r, $c), // re non usa isValidMove per evitare ricorsione
                default => false
            };

            if ($attacks) return true;
        }
    }

    return false;
}

// Il pedone attacca in diagonale — diverso dal suo movimento normale
private function pawnAttacks(array $board, string $from, string $to, string $turn, string $piece): bool
{
    $fromCol  = ord($from[0]) - ord('a');
    $fromRank = 8 - (int)$from[1];
    $toCol    = ord($to[0])   - ord('a');
    $toRank   = 8 - (int)$to[1];

    $colDiff = abs($toCol - $fromCol);
    $rowDiff = $toRank - $fromRank;

    // Il pedone attacca solo le diagonali avanti di una casella
    if ($colDiff !== 1) return false;
    if ($turn === 'w' && $rowDiff !== -1) return false;
    if ($turn === 'b' && $rowDiff !== 1)  return false;

    return true;
}

// Controlla se il re attacca una casella (senza chiamare isValidMove)
private function kingAttacks(int $toRow, int $toCol, int $fromRow, int $fromCol): bool
{
    $rowDiff = abs($toRow - $fromRow);
    $colDiff = abs($toCol - $fromCol);

    return $rowDiff <= 1 && $colDiff <= 1 && !($rowDiff === 0 && $colDiff === 0);
}

// Metodo inverso di squareToCoord
public function coordToSquare(int $row, int $col): string
{
    $file = chr(ord('a') + $col);
    $rank = (string)(8 - $row);
    return $file . $rank;
}

public function isKingInCheck(array $board, string $turn): bool
{
    // Trova la posizione del re
    $kingPiece = ($turn === 'w') ? 'K' : 'k';
    $kingRow = -1;
    $kingCol = -1;

    for ($r = 0; $r < 8; $r++) {
        for ($c = 0; $c < 8; $c++) {
            if ($board[$r][$c] === $kingPiece) {
                $kingRow = $r;
                $kingCol = $c;
                break 2;
            }
        }
    }

    if ($kingRow === -1) return false; // re non trovato
    
    $opponent = ($turn === 'w') ? 'b' : 'w';
    return $this->isSquareAttacked($board, $kingRow, $kingCol, $opponent);
}
public function hasLegalMoves(array $board, string $turn): bool
{
    for ($r = 0; $r < 8; $r++) {
        for ($c = 0; $c < 8; $c++) {
            $piece = $board[$r][$c];
            if ($piece === null) continue;

            // Solo i pezzi delturno corrente
            $isWhite = ctype_upper($piece);
            if ($turn === 'w' && !$isWhite) continue;
            if ($turn === 'b' && $isWhite)  continue;

            $from = $this->coordToSquare($r, $c);

            // Prova tutte le caselle di destinazione
            for ($tr = 0; $tr < 8; $tr++) {
                for ($tc = 0; $tc < 8; $tc++) {
                    $to = $this->coordToSquare($tr, $tc);
                    if ($from === $to) continue;

                    // Simula la mossa
                    $testBoard = $board;
                    $testBoard[$tr][$tc] = $piece;
                    $testBoard[$r][$c]   = null;

                    // Valida con il service del pezzo
                    $valid = false;
                    switch (strtolower($piece)) {
                        case 'p':
                            $result = (new PawnServices())->isValidMove($board, $from, $to, $turn, $piece);
                            $valid = $result !== false;
                            break;
                        case 'n':
                            $valid = (new KnightServices())->isValidMove($board, $from, $to, $turn, $piece);
                            break;
                        case 'b':
                            $valid = (new BishopServices())->isValidMove($board, $from, $to, $turn, $piece);
                            break;
                        case 'r':
                            $valid = (new RookServices())->isValidMove($board, $from, $to, $turn, $piece);
                            break;
                        case 'q':
                            $valid = (new QueenServices())->isValidMove($board, $from, $to, $turn, $piece);
                            break;
                        case 'k':
                            $valid = (new KingServices())->isValidMove($board, $from, $to, $turn, $piece);
                            break;
                    }

                    if (!$valid) continue;

                    
                    if (!$this->isKingInCheck($testBoard, $turn)) {
                        return true; // esiste almeno una mossa legale
                    }
                }
            }
        }
    }

    return false; // nessuna mossa legale
}
public function getEnPassant(string $fen): ?string
{
    $parts = explode(' ', trim($fen));
    $ep    = $parts[3] ?? '-';
    return $ep === '-' ? null : $ep;
}
public function hasEnoughPieces(string $fen): bool
{
    $board = $this->createBoard($fen);

    $white = ['p' => 0, 'r' => 0, 'q' => 0, 'b' => 0, 'n' => 0];
    $black = ['p' => 0, 'r' => 0, 'q' => 0, 'b' => 0, 'n' => 0];

    for ($i = 0; $i < 8; $i++) {
        for ($j = 0; $j < 8; $j++) {
            $piece = $board[$i][$j];
            if ($piece === null || strtolower($piece) === 'k') continue;

            $isWhite = ctype_upper($piece);
            $type    = strtolower($piece);

            if ($isWhite) {
                $white[$type]++;
            } else {
                $black[$type]++;
            }
        }
    }

    
    foreach (['w' => $white, 'b' => $black] as $color => $pieces) {
        if ($pieces['p'] > 0) return true;
        if ($pieces['r'] > 0) return true;
        if ($pieces['q'] > 0) return true;

        // Due alfieri
        if ($pieces['b'] >= 2) return true;

        // Alfiere + cavallo
        if ($pieces['b'] >= 1 && $pieces['n'] >= 1) return true;

        // Due cavalli (tecnicamente non forzato ma convenzionalmente sufficiente)
        if ($pieces['n'] >= 2) return true;
    }

    // Solo re vs re, o re+alfiere vs re, o re+cavallo vs re
    return false;
}
public function repeatedMoves(array $moves, string $currentFen): bool
{
    $fenCount = [];

    // posizione iniziale vera
    $startKey = $this->getPositionKey('rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1');
    $fenCount[$startKey] = 1;

    foreach ($moves as $move) {
        if (!isset($move['fen'])) continue;

        $key = $this->getPositionKey($move['fen']);
        $fenCount[$key] = ($fenCount[$key] ?? 0) + 1;

        if ($fenCount[$key] >= 3) return true;
    }

    $currentKey = $this->getPositionKey($currentFen);
    $fenCount[$currentKey] = ($fenCount[$currentKey] ?? 0) + 1;

    return $fenCount[$currentKey] >= 3;
}
public function getPositionKey(string $fen): string
{
    $parts = explode(' ', trim($fen));
    // Solo pezzi + turno, ignora arrocco ed en passant
    return $parts[0] . ' ' . ($parts[1] ?? 'w');
}

public function isFiftyMoveRule(string $fen){
    $parts    = explode(' ', trim($fen));
    $halfMove = isset($parts[4]) ? (int)$parts[4] : 0;
    return $halfMove >= 100;
}

public function lastMove($notation){
    $service = new ChessServices();
    $fromSquare = $service->squareToCoord($notation[1] . $notation[2]);
    $toSquare = $service->squareToCoord($notation[3] . $notation[4]);
    return ['from' => $fromSquare, 'to' => $toSquare];
}
public function getLegalMoves(string $fen, string $piece, string $from, $tipo = 'scacchi'): array
{
    $this->createBoard($fen);
    $turn  = $this->getTurn($fen);
    $moves = [];

    for ($r = 0; $r < 8; $r++) {
        for ($c = 0; $c < 8; $c++) {
            $to = $this->coordToSquare($r, $c);
            if ($to === $from) continue;

            // Salva board originale
            $originalBoard = $this->board;

            $valid = false;
            if($tipo == 'scacchi'){
                switch (strtolower($piece)) {
                    case 'p':
                        $enPassant = $this->getEnPassant($fen);
                        $result    = (new PawnServices())->isValidMove($this->board, $from, $to, $turn, $piece, $enPassant);
                        $valid     = $result !== false;
                        break;
                    case 'n':
                        $valid = (new KnightServices())->isValidMove($this->board, $from, $to, $turn, $piece);
                        break;
                    case 'b':
                        $valid = (new BishopServices())->isValidMove($this->board, $from, $to, $turn, $piece);
                        break;
                    case 'r':
                        $valid = (new RookServices())->isValidMove($this->board, $from, $to, $turn, $piece);
                        break;
                    case 'q':
                        $valid = (new QueenServices())->isValidMove($this->board, $from, $to, $turn, $piece);
                        break;
                    case 'k':
                        $valid = (new KingServices())->isValidMove($this->board, $from, $to, $turn, $piece, $fen, $this);
                        break;
                }
            
               
                if (!$valid) continue;

                // Simula la mossa e verifica che il re non sia in scacco
                [$fromRow, $fromCol] = $this->squareToCoord($from);
                $testBoard           = $this->board;
                $testBoard[$r][$c]   = $testBoard[$fromRow][$fromCol];
                $testBoard[$fromRow][$fromCol] = null;

                if (!$this->isKingInCheck($testBoard, $turn)) {
                    $moves[] = [$r, $c]; // indici array
                }

                // Ripristina il board originale
                $this->board = $originalBoard;
            }
            else {
                $damaService = new DamaPiccolaService();
                $result = $damaService->isValidMove($this->board, $from, $to, $turn, $piece);
                $valid = $result !== false;

                if ($valid) {
                    $moves[] = [$r, $c]; 
                }
            }
        }
    }

    return $moves;
}
public function hasLegalMovesDama(array $board, string $turn): bool
{
    $damaService = new DamaPiccolaService();

    // prima controlla se ci sono catture obbligatorie
    $captures = $damaService->getAllCaptures($board, $turn);
    if (!empty($captures)) return true;

    // altrimenti cerca almeno una mossa normale
    $dir  = ($turn === 'w') ? -1 : 1;
    $dirs = [[$dir, -1], [$dir, 1]]; // diagonali in avanti

    for ($r = 0; $r < 8; $r++) {
        for ($c = 0; $c < 8; $c++) {
            $piece = $board[$r][$c];
            if ($piece === null) continue;
            if (strtolower($piece) !== $turn) continue;

            $isKing = ($piece === 'W' || $piece === 'B');
            $moveDirs = $isKing
                ? [[-1,-1],[-1,1],[1,-1],[1,1]] // dama: tutte le direzioni
                : $dirs;                          // pedina: solo avanti

            foreach ($moveDirs as [$dr, $dc]) {
                $tr = $r + $dr;
                $tc = $c + $dc;
                if ($tr < 0 || $tr > 7 || $tc < 0 || $tc > 7) continue;
                if ($board[$tr][$tc] === null) return true;
            }
        }
    }

    return false;
}
}