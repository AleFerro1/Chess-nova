function renderBoard(board) {
        const scacchiera = document.getElementById("scacchiera");
        if (!scacchiera || !board) return;

        scacchiera.innerHTML = "";

        const isWhitePerspective = (window.playerColor === 'bianco');

        for (let i = 0; i < 8; i++) {
            for (let j = 0; j < 8; j++) {

                const row = isWhitePerspective ? i : 7 - i;
                const col = isWhitePerspective ? j : 7 - j;

                const square = document.createElement("div");

                square.className =
                    (row + col) % 2 === 0 ? "casellaBianca" : "casellaNera";

                square.dataset.row = row;
                square.dataset.col = col;

                if (lastMove) {
                    if (
                        (row === lastMove.from[0] && col === lastMove.from[1]) ||
                        (row === lastMove.to[0] && col === lastMove.to[1])
                    ) {
                        square.classList.add(
                            square.classList.contains("casellaBianca") ?
                            "lastMoveBianca" :
                            "lastMoveNera"
                        );
                    }
                }

                if (legalSquares.some(m => m[0] === row && m[1] === col)) {
                    square.classList.add(
                        board[row][col] !== null ? "legalCapture" : "legalMove"
                    );
                }

                const piece = board[row][col];
                if (piece !== null) {
                    const img = document.createElement("img");

                    const pieceImages = {
                        'p': 'DarkPawn',
                        'n': 'DarkKnight',
                        'b': 'DarkBishop',
                        'r': 'DarkRook',
                        'q': 'DarkQueen',
                        'k': 'DarkKing',
                        'P': 'LightPawn',
                        'N': 'LightKnight',
                        'B': 'LightBishop',
                        'R': 'LightRook',
                        'Q': 'LightQueen',
                        'K': 'LightKing'
                    };

                    img.src = `/images/${pieceImages[piece]}.webp`;
                    img.className = "pedina";
                    square.appendChild(img);
                }

                square.addEventListener("click", () => {
                    handleClick(row, col, board, square);
                });

                scacchiera.appendChild(square);
            }
        }
    }
