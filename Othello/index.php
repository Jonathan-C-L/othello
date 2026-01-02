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
    <title>Othello - Jonathan Le</title>
</head>
<body>
    <header>Othello</header>
    <main>
      <div class="player-input container">
          <h3>Enter your names below:</h3>
          <div class="scores"><h4 class="player1Tiles"></h4><h4 class="player2Tiles"></h4></div>
          <div>
            <input type="text" placeholder="Player one name here!" name="player1" class="player1">
            <input type="text" placeholder="Player two name here!" name="player2" class="player2">
          </div>
          <div>
            <button class="start" id="submit" name="action" value="start">New Game</button>
          </div>
        <form action="index.php" method="post">
            <button type="submit" name="quit" value="quit" id="id">Quit Game</button>
        </form>
      </div>
        <div class="gameboard container"></div>
    </main>
    <footer>
      <h4>&copy; Copyright 2025 by Jonathan Le</h4>
      <script>document.write("Last Modified: " + document.lastModified)</script>
    </footer>
  </body>
</html>