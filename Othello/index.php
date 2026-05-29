<?php
  session_start();    // starts a new session if there is not one already for the connecting client,
                      // but if there is an existing session, connects the client back to it.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./styles.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="./othelo.js"></script>
    <title>Othello — Jonathan Le</title>
</head>
<body>
    <header>OTHELLO</header>
    <main>

        <!-- ── Control Panel ── -->
        <div class="player-input">

            <!-- Game status / prompt line -->
            <p class="status-text">Enter your names to begin</p>

            <!-- Score cards (disc icon + name — score) -->
            <div class="scores">
                <div class="score-card">
                    <span class="score-disc black-disc"></span>
                    <h4 class="player1Tiles">Player 1</h4>
                </div>
                <div class="score-card">
                    <span class="score-disc white-disc"></span>
                    <h4 class="player2Tiles">Player 2</h4>
                </div>
            </div>

            <!-- Name inputs -->
            <div class="input-row">
                <input type="text" placeholder="Player 1 name" name="player1" class="player1">
                <input type="text" placeholder="Player 2 name" name="player2" class="player2">
            </div>

            <!-- Action buttons -->
            <div class="button-row">
                <button class="start" id="submit">New Game</button>
                <form action="index.php" method="post" style="display:inline; margin:0;">
                    <button type="submit" name="quit" value="quit" id="id">Quit Game</button>
                </form>
            </div>

        </div>

        <!-- ── Board (wooden frame wraps the green grid) ── -->
        <div class="board-frame">
            <div class="gameboard container"></div>
        </div>

    </main>
    <footer>
        <p>&copy; 2025 Jonathan Le &nbsp;|&nbsp;
        <script>document.write("Last Modified: " + document.lastModified)</script></p>
    </footer>
</body>
</html>