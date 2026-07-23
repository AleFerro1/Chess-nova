<?php
namespace app\services\pieces;
require_once __DIR__ . '/PieceInterface.php';
use app\services\pieces\PieceInterface;

class KingServices implements PieceInterface
{
    public function isValidMove($board, $from, $to, $turn, $piece, string $fen = '', $chess = null): bool
    {
        if ($piece === null) return false;

        $fromCol  = ord($from[0]) - ord('a');
        $fromRank = 8 - (int)$from[1];
        $toCol    = ord($to[0])   - ord('a');
        $toRank   = 8 - (int)$to[1];

        $rowDiff = abs($toRank - $fromRank);
        $colDiff = abs($toCol  - $fromCol);

        // Arrocco
        if ($rowDiff === 0 && $colDiff === 2) {
            if ($chess === null) return false;
            return $this->isCastlingValid($board, $from, $to, $turn, $fen, $chess);
        }

        // Mossa normale
        if ($rowDiff > 1 || $colDiff > 1) return false;
        if ($rowDiff === 0 && $colDiff === 0) return false;

        $target = $board[$toRank][$toCol];
        if ($target !== null) {
            $isWhiteTarget = ctype_upper($target);
            if ($turn === 'w' && $isWhiteTarget)  return false;
            if ($turn === 'b' && !$isWhiteTarget) return false;
        }
        $opponent = ($turn === 'w') ? 'b' : 'w';

        return true;
    }

    private function isCastlingValid($board, $from, $to, $turn, string $fen, $chess): bool
    {
        $parts    = explode(' ', trim($fen));
        $castling = $parts[2] ?? '-';

        $isKingside = ord($to[0]) > ord($from[0]);
        $rank       = $from[1];

        if ($turn === 'w') {
            if ($isKingside  && !str_contains($castling, 'K')) return false;
            if (!$isKingside && !str_contains($castling, 'Q')) return false;
            if ($rank !== '1') return false;
        } else {
            if ($isKingside  && !str_contains($castling, 'k')) return false;
            if (!$isKingside && !str_contains($castling, 'q')) return false;
            if ($rank !== '8') return false;
        }

        $fromCol  = ord($from[0]) - ord('a');
        $fromRank = 8 - (int)$from[1];
        $opponent = ($turn === 'w') ? 'b' : 'w';

        if ($isKingside) {
            if ($board[$fromRank][$fromCol + 1] !== null) return false;
            if ($board[$fromRank][$fromCol + 2] !== null) return false;

            if ($chess->isSquareAttacked($board, $fromRank, $fromCol,     $opponent)) return false;
            if ($chess->isSquareAttacked($board, $fromRank, $fromCol + 1, $opponent)) return false;
            if ($chess->isSquareAttacked($board, $fromRank, $fromCol + 2, $opponent)) return false;
        } else {
            if ($board[$fromRank][$fromCol - 1] !== null) return false;
            if ($board[$fromRank][$fromCol - 2] !== null) return false;
            if ($board[$fromRank][$fromCol - 3] !== null) return false;

            if ($chess->isSquareAttacked($board, $fromRank, $fromCol,     $opponent)) return false;
            if ($chess->isSquareAttacked($board, $fromRank, $fromCol - 1, $opponent)) return false;
            if ($chess->isSquareAttacked($board, $fromRank, $fromCol - 2, $opponent)) return false;
        }

        return true;
    }
}