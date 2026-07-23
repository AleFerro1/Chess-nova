<?php
namespace app\services\pieces;
require_once __DIR__ . '/PieceInterface.php';
use app\services\pieces\PieceInterface;

class PawnServices {
    function __construct(){

    }
    public function isValidMove($board, $from, $to, $turn, $piece, $enPassant = null): bool|string
    {
        if ($piece === null) return false;

        $fromCol  = ord($from[0]) - ord('a');
        $fromRank = 8 - (int)$from[1];

        $toCol  = ord($to[0]) - ord('a');
        $toRank = 8 - (int)$to[1];

        $rowDiff = $toRank - $fromRank;
        $colDiff = abs($toCol - $fromCol);

        // Direzione e passi validi per colore
        $validSteps = ($turn === 'w') ? [-1, -2] : [1, 2];
        if (!in_array($rowDiff, $validSteps, true)) return false;
        if ($colDiff > 1) return false;

        // Doppio passo
        if (abs($rowDiff) === 2) {
            if ($piece === 'P' && $fromRank !== 6) return false;
            if ($piece === 'p' && $fromRank !== 1) return false;
            if ($colDiff !== 0) return false;

            // Casella intermedia libera
            $middleRank = (int)(($fromRank + $toRank) / 2);
            if ($board[$middleRank][$fromCol] !== null) return false;
        }

        // Movimento verticale: casella di arrivo libera
        if ($colDiff === 0) {
            if ($board[$toRank][$toCol] !== null) return false;
        }

        // Cattura diagonale
        if ($colDiff === 1) {
            $target = $board[$toRank][$toCol];
            
            // Cattura normale
            if ($target !== null) {
                $isWhiteTarget = ctype_upper($target);
                if ($turn === 'w' && $isWhiteTarget)  return false;
                if ($turn === 'b' && !$isWhiteTarget) return false;
                
            // En passant — la casella di arrivo è vuota ma c'è un pedone affianco
            } elseif ($enPassant !== null && $to === $enPassant) {
                // valido — verrà rimosso il pedone in ChessServices
            } else {
                return false;
            }
        }

        // Promozione
        if ($turn === 'w' && $toRank === 0) return 'promozione';
        if ($turn === 'b' && $toRank === 7) return 'promozione';



        return true;
    }
}