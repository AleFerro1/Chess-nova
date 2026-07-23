<?php
namespace app\services\pieces;
interface PieceInterface{
    public function isValidMove($board, $from, $to, $turn, $piece) ;
}