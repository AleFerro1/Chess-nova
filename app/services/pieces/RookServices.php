<?php
namespace app\services\pieces;
require_once __DIR__ . '/PieceInterface.php';
use app\services\pieces\PieceInterface;

class RookServices implements PieceInterface
{
    public function isValidMove($board, $from, $to, $turn, $piece): bool
    {
        if ($piece === null) return false;

        $fromCol  = ord($from[0]) - ord('a');
        $fromRank = 8 - (int)$from[1];

        $toCol  = ord($to[0]) - ord('a');
        $toRank = 8 - (int)$to[1];

        
         if ($fromRank !== $toRank && $fromCol !== $toCol) return false;

        // Movimento verticale
        if($toRank === $fromRank){
            $direzioneOrizzontale = ($toCol  > $fromCol) ? 1 : -1;
            $direzioneVerticale = 0;
            $col = $fromCol  + $direzioneOrizzontale;
            while($col !== $toCol){
                if ($board[$fromRank][$col] !== null) return false;
                $col += $direzioneOrizzontale;
            }
        }
        else{ // movimento orizzontale
            $direzioneVerticale = ($toRank > $fromRank) ? 1 : -1;
            $direzioneOrizzontale = 0;
            $row = $fromRank  + $direzioneVerticale;
            while($row !== $toRank){
                if ($board[$row][$fromCol] !== null) return false;
                $row += $direzioneVerticale;
            }
        }
        // La casella di arrivo non deve contenere un pezzo alleato
        $target = $board[$toRank][$toCol];
        if ($target !== null) {
            $isWhiteTarget = ctype_upper($target);
            if ($turn === 'w' && $isWhiteTarget)  return false;
            if ($turn === 'b' && !$isWhiteTarget) return false;
        }

        return true;
    }
}