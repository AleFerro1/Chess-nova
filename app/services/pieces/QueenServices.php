<?php
namespace app\services\pieces;
require_once __DIR__ . '/PieceInterface.php';
use app\services\pieces\PieceInterface;

class QueenServices implements PieceInterface
{
    public function isValidMove($board, $from, $to, $turn, $piece): bool
    {
        if ($piece === null) return false;

        $fromCol  = ord($from[0]) - ord('a');
        $fromRank = 8 - (int)$from[1];
        $toCol    = ord($to[0])   - ord('a');
        $toRank   = 8 - (int)$to[1];

        $rowDiff = abs($toRank - $fromRank);
        $colDiff = abs($toCol  - $fromCol);

        $isDiagonal   = ($rowDiff === $colDiff && $rowDiff > 0);
        $isHorizontal = ($fromRank === $toRank && $colDiff > 0);
        $isVertical   = ($fromCol  === $toCol  && $rowDiff > 0);

        // La regina si muove solo in diagonale, orizzontale o verticale
        if (!$isDiagonal && !$isHorizontal && !$isVertical) return false;

        if ($isDiagonal) {
            $stepRow = ($toRank > $fromRank) ? 1 : -1;
            $stepCol = ($toCol  > $fromCol)  ? 1 : -1;
            $row = $fromRank + $stepRow;
            $col = $fromCol  + $stepCol;

            while ($row !== $toRank) {
                if ($board[$row][$col] !== null) return false;
                $row += $stepRow;
                $col += $stepCol;
            }
        } elseif ($isHorizontal) {
            $stepCol = ($toCol > $fromCol) ? 1 : -1;
            $col = $fromCol + $stepCol;

            while ($col !== $toCol) {
                if ($board[$fromRank][$col] !== null) return false;
                $col += $stepCol;
            }
        } else { // verticale
            $stepRow = ($toRank > $fromRank) ? 1 : -1;
            $row = $fromRank + $stepRow;

            while ($row !== $toRank) {
                if ($board[$row][$fromCol] !== null) return false;
                $row += $stepRow;
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