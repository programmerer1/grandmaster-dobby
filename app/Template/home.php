<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chess vs Dobby</title>
    <link href="https://unpkg.com/@chrisoakman/chessboardjs@1.0.0/dist/chessboard-1.0.0.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://unpkg.com/@chrisoakman/chessboardjs@1.0.0/dist/chessboard-1.0.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/chess.js/0.12.0/chess.min.js"></script>
    <style>
        /* Общие стили */
        body {
            font-family: 'Roboto', sans-serif;
            background: linear-gradient(to bottom, #f0f4f8, #d9e2ec);
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            color: #333;
        }

        /* Контейнер */
        .container {
            text-align: center;
            padding: 20px;
            max-width: 600px;
            width: 90%;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        /* Шахматная доска */
        #board {
            width: 500px;
            max-width: 100%;
            margin: 0 auto 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
            border: 2px solid #4a4a4a;
        }

        /* Кнопки */
        .buttons {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 20px;
        }

        button {
            padding: 10px 20px;
            font-size: 16px;
            font-weight: 700;
            color: #fff;
            background: #007bff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s, transform 0.1s;
        }

        button:hover {
            background: #0056b3;
        }

        button:active {
            transform: scale(0.98);
        }

        #retryMove {
            background: #ff6b6b;
        }

        #retryMove:hover {
            background: #d90429;
        }

        /* Статус и история */
        .status,
        .history {
            margin: 10px 0;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.1);
        }

        .status p,
        .history p {
            margin: 0;
            font-size: 18px;
        }

        .status span {
            font-weight: 700;
            color: #007bff;
        }

        .history {
            max-height: 100px;
            overflow-y: auto;
        }

        /* Адаптивность */
        @media (max-width: 600px) {
            #board {
                width: 90vw;
            }

            .container {
                padding: 10px;
            }

            button {
                padding: 8px 16px;
                font-size: 14px;
            }

            .status p,
            .history p {
                font-size: 16px;
            }
        }

        .dobby img {
            max-width: 200px;
            margin: 0 auto;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="dobby">
            <img src="/img/dobby.png" alt="dobby">
            <h1>Think you’ve got what it takes? Challenge Grandmaster Dobby and prove your skills on the board!</h1>
        </div>
        <div id="board"></div>
        <div class="buttons">
            <button onclick="startNewGame()">New Game</button>
            <button id="retryMove" style="display: none;" onclick="retryAIMove()">Try Again</button>
        </div>
        <div class="status">
            <p>Status: <span id="status"></span></p>
        </div>
        <div class="history">
            <p>Move History: <span id="moveHistory"></span></p>
        </div>
    </div>

    <script>
        // Инициализация
        const boardConfig = {
            draggable: true,
            position: 'start',
            onDrop: onDrop,
            pieceTheme: 'https://chessboardjs.com/img/chesspieces/wikipedia/{piece}.png',
        };
        const board = Chessboard('board', boardConfig);
        const chess = new Chess();
        const statusEl = document.getElementById('status');
        const historyEl = document.getElementById('moveHistory');
        const retryButton = document.getElementById('retryMove');

        // Локальное хранилище
        const GAME_STORAGE_KEY = 'chess_game_state';
        let gameState = JSON.parse(localStorage.getItem(GAME_STORAGE_KEY)) || {
            history: [],
            fen: 'start',
            playerColor: null,
            awaitingAIMove: false,
            lastInvalidAIMove: null,
        };
        let gameHistory = gameState.history;
        let playerColor = gameState.playerColor;
        let lastPlayerMoveUCI = '';
        let awaitingAIMove = gameState.awaitingAIMove;
        let lastInvalidAIMove = gameState.lastInvalidAIMove;
        let lastLegalMoves = [];

        // Получение валидных ходов в UCI-формате
        function getLegalMovesUCI() {
            const moves = chess.moves({
                verbose: true
            });
            return moves.map(move => move.from + move.to + (move.promotion || ''));
        }

        // Сохранение состояния игры
        function saveGameState() {
            gameState = {
                history: gameHistory,
                fen: chess.fen(),
                playerColor,
                awaitingAIMove,
                lastInvalidAIMove,
            };
            localStorage.setItem(GAME_STORAGE_KEY, JSON.stringify(gameState));
        }

        // Ограничение ходов игрока по цвету
        function onDrop(source, target) {
            if (chess.turn() !== playerColor) {
                statusEl.textContent = `Your color: ${playerColor === 'w' ? 'White' : 'Black'}. Dobby's turn.`;
                return 'snapback';
            }

            const move = chess.move({
                from: source,
                to: target,
                promotion: 'q'
            });
            if (move === null) {
                statusEl.textContent = 'Invalid move';
                return 'snapback';
            }

            lastPlayerMoveUCI = source + target + (move.promotion ? move.promotion : '');
            awaitingAIMove = true;
            lastInvalidAIMove = null;
            lastLegalMoves = getLegalMovesUCI();
            saveMove(move.san);
            board.position(chess.fen());
            saveGameState();
            updateStatus();

            // Не отправляем запрос, если игра завершена
            if (chess.game_over()) {
                awaitingAIMove = false;
                saveGameState();
                return;
            }

            console.log('Player move:', {
                fen: chess.fen(),
                move: lastPlayerMoveUCI,
                legalMoves: lastLegalMoves
            });
            sendMoveToServer(chess.fen(), lastPlayerMoveUCI);
        }

        // Отправка хода на сервер
        async function sendMoveToServer(fen, move) {
            try {
                retryButton.style.display = 'none';
                lastLegalMoves = getLegalMovesUCI();
                console.log('Sending to server:', {
                    fen,
                    move,
                    legalMoves: lastLegalMoves
                });
                const response = await fetch('/api', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        fen,
                        move,
                        legalMoves: lastLegalMoves
                    }),
                });

                if (!response.ok) throw new Error(`Server error: ${response.status}`);

                const data = await response.json();
                console.log('Server response:', data);
                if (!data.answer || !data.answer.move) {
                    awaitingAIMove = false;
                    saveGameState();
                    statusEl.textContent = data.answer?.message || 'Error: Server response lacks move';
                    return;
                }

                const aiMove = data.answer.move;
                if (!lastLegalMoves.includes(aiMove)) {
                    console.error('Dobby move not in legal moves:', aiMove, 'Legal moves:', lastLegalMoves);
                    awaitingAIMove = true;
                    lastInvalidAIMove = aiMove;
                    saveGameState();
                    statusEl.textContent = `Error: Invalid Dobby move (${aiMove})`;
                    retryButton.style.display = 'inline';
                    return;
                }

                const moveObj = chess.move(aiMove, {
                    sloppy: true
                });
                if (moveObj) {
                    awaitingAIMove = false;
                    lastInvalidAIMove = null;
                    saveMove(moveObj.san);
                    board.position(chess.fen());
                    saveGameState();
                    updateStatus();
                } else {
                    console.error('Invalid Dobby move:', aiMove, 'FEN:', fen, 'Legal moves:', lastLegalMoves);
                    awaitingAIMove = true;
                    lastInvalidAIMove = aiMove;
                    saveGameState();
                    statusEl.textContent = `Error: Invalid Dobby move (${aiMove})`;
                    retryButton.style.display = 'inline';
                }
            } catch (error) {
                console.error('Error sending move:', error, 'FEN:', fen);
                awaitingAIMove = false;
                saveGameState();
                statusEl.textContent = 'Server connection error';
            }
        }

        // Повторный запрос хода ИИ
        function retryAIMove() {
            console.log('Retrying Dobby move:', {
                fen: chess.fen(),
                move: lastPlayerMoveUCI,
                legalMoves: lastLegalMoves
            });
            sendMoveToServer(chess.fen(), lastPlayerMoveUCI);
        }

        // Сохранение хода
        function saveMove(move) {
            gameHistory.push(move);
            saveGameState();
            updateMoveHistory();
        }

        // Обновление истории ходов
        function updateMoveHistory() {
            historyEl.textContent = gameHistory.join(', ');
        }

        // Обновление статуса игры
        function updateStatus() {
            let status = '';
            if (chess.game_over()) {
                awaitingAIMove = false;
                saveGameState();
                if (chess.in_checkmate()) {
                    if (chess.turn() === playerColor) {
                        status = 'Checkmate! Dobby wins!';
                    } else {
                        status = 'Checkmate! Congratulations, you win!';
                    }
                } else if (chess.in_draw()) {
                    status = 'Draw!';
                } else if (chess.in_stalemate()) {
                    status = 'Stalemate!';
                }
            } else {
                status = `${chess.turn() === 'w' ? 'White' : 'Black'}'s turn`;
                if (chess.turn() === playerColor) {
                    status += ' (your turn)';
                } else if (awaitingAIMove) {
                    status += lastInvalidAIMove ?
                        ` (Dobby error: invalid move ${lastInvalidAIMove})` :
                        ' (Dobby\'s turn)';
                } else {
                    status += ' (Dobby\'s turn)';
                }
            }
            statusEl.textContent = status;
        }

        // Начало новой игры
        function startNewGame() {
            chess.reset();
            board.position('start');
            gameHistory = [];
            playerColor = Math.random() < 0.5 ? 'w' : 'b';
            lastPlayerMoveUCI = '';
            awaitingAIMove = playerColor === 'b';
            lastInvalidAIMove = null;
            lastLegalMoves = [];
            saveGameState();
            updateMoveHistory();
            updateStatus();
            if (playerColor === 'b' && !chess.game_over()) {
                console.log('Dobby moves first as White:', {
                    fen: chess.fen(),
                    move: '',
                    legalMoves: getLegalMovesUCI()
                });
                sendMoveToServer(chess.fen(), '');
            }
        }

        // Восстановление игры при загрузке
        document.addEventListener('DOMContentLoaded', () => {
            if (gameState.fen && gameState.fen !== 'start') {
                try {
                    chess.load(gameState.fen);
                    board.position(gameState.fen);
                    console.log('Восстановлен FEN:', gameState.fen);
                } catch (error) {
                    console.error('Ошибка загрузки FEN:', error);
                    startNewGame();
                }
            }
            if (!playerColor) {
                playerColor = Math.random() < 0.5 ? 'w' : 'b';
                awaitingAIMove = playerColor === 'b';
                saveGameState();
            }
            updateMoveHistory();
            updateStatus();
            if (awaitingAIMove && !chess.game_over()) {
                if (lastInvalidAIMove) {
                    statusEl.textContent = `Error: Invalid Dobby move (${lastInvalidAIMove})`;
                    retryButton.style.display = 'inline';
                }
                console.log('Dobby moves on load:', {
                    fen: chess.fen(),
                    move: lastPlayerMoveUCI || '',
                    legalMoves: getLegalMovesUCI()
                });
                sendMoveToServer(chess.fen(), lastPlayerMoveUCI || '');
            }
        });
    </script>
</body>

</html>