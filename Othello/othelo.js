/*
CMPE 2550 - Web Applications
Date: Sept. 18, 2025
Author: Jonathan Le
Purpose: Javascript for DOM manipulations of Othello. Logic handled in gameFlow.php.
*/
console.log("js has successfully connected!");

// Tracks the previous board state so RenderBoard can detect
// newly placed vs flipped pieces and apply the right animation.
let previousGrid = null;

$(document).ready(function () {
    $("#submit").click(ConnectToServer);
});

/* Summary: ConnectToServer() prepares player name data and initiates an AJAX
 *          call to verify names before starting a game.
 * Parameters: none
 * Returns: nothing
 */
function ConnectToServer() {
    $(".gameboard").html("");   // clear board on New Game
    previousGrid = null;        // reset animation tracking

    var postData = {};
    postData["state"]   = "check";
    postData["player1"] = $(".player1").val();
    postData["player2"] = $(".player2").val();
    CallAJAX('gameFlow.php', 'post', postData, 'json', VerifyNames, AjaxError);
}

/* Summary: VerifyNames() handles the server response for name validation.
 *          Alerts on error, otherwise generates the board and starts the game.
 * Parameters: returnedData, statusMessage, ajaxRequest
 * Returns: nothing
 */
function VerifyNames(returnedData, statusMessage, ajaxRequest) {
    if (returnedData.errors != "")
        $(".status-text").text(returnedData.errors + " must input a name!");

    if (returnedData.errors == "") {
        GenerateBoard(8, 8);
        CallAJAX('gameFlow.php', 'post', { "state": "start" }, 'json', StartGame, AjaxError);
    }
}

/* Summary: CallAJAX() is a generic wrapper for jQuery AJAX calls.
 * Parameters: url, method, data, dataType, success callback, error callback
 * Returns: nothing
 */
function CallAJAX(url, method, data, dataType, success, error) {
    $.ajax({
        url:      url,
        method:   method,
        data:     data,
        dataType: dataType,
        success:  success,
        error:    error
    });
}

/* Summary: AjaxError() logs details of a failed AJAX request.
 * Parameters: ajaxRequest, statusMessage, errorThrownMessage
 * Returns: nothing
 */
function AjaxError(ajaxRequest, statusMessage, errorThrownMessage) {
    console.log(ajaxRequest);
    console.log(statusMessage);
    console.log(errorThrownMessage);
}

/* Summary: GenerateBoard() builds an 8×8 grid of clickable board cells
 *          inside .gameboard.
 * Parameters: width, height
 * Returns: nothing
 */
function GenerateBoard(width, height) {
    for (let y = 0; y < width; y++) {
        for (let x = 0; x < height; x++) {
            let $boardPiece = $(`<div class='board' id=${x}${y} name='board'></div>`);
            $(".gameboard").append($boardPiece);
        }
    }
}

/* Summary: StartGame() renders the initial board, locks name inputs,
 *          and binds click handlers to all board cells.
 * Parameters: gameData (AJAX response), statusMessage, ajaxRequest
 * Returns: nothing
 */
function StartGame(gameData, statusMessage, ajaxRequest) {
    console.log("StartGame()");
    console.log(gameData);

    RenderBoard(gameData.gameGrid, gameData.playable);
    $(".status-text").text(`${gameData["player1"]} starts as Black!`);

    // Lock names during play
    $(".player1").prop("readonly", true);
    $(".player2").prop("readonly", true);

    // Show initial scores (4 starting tiles split evenly)
    $(".player1Tiles").text(`${gameData["player1"]} — 2`);
    $(".player2Tiles").text(`${gameData["player2"]} — 2`);

    // Bind click handler via event delegation on each cell
    $(".board").click(function () {
        console.log("board click: " + $(this).attr("id"));
        CallAJAX(
            'gameFlow.php', 'post',
            // Skips if beyond bounds of board (i.e. 9, 9) used for skip turn action
            SendPlayData($(this).attr("id"), gameData["playerTurn"], gameData["state"]),
            'json', UpdateGame, AjaxError
        );
    });
}

/* Summary: UpdateGame() re-renders the board and status after each move.
 *          Handles play, retry, skip, and end states.
 * Parameters: gameData (AJAX response), statusMessage, ajaxRequest
 * Returns: nothing
 */
function UpdateGame(gameData, statusMessage, ajaxRequest) {
    console.log("UpdateGame()");
    console.log(gameData);

    // Determine colour labels for current and opposing player
    let color    = (gameData.playerTurn == gameData.first) ? "Black" : "White";
    let oppColor = (color === "Black") ? "White" : "Black";

    // Update score display
    $(".player1Tiles").text(`${gameData["player1"]} — ${gameData.player1Score}`);
    $(".player2Tiles").text(`${gameData["player2"]} — ${gameData.player2Score}`);

    RenderBoard(gameData.gameGrid, gameData.playable);

    // End-game states
    if (gameData.state == "end" || gameData.state == "tie")
        EndGame(gameData.state, gameData["winner"]);

    if (gameData["state"] == "play")
        $(".status-text").text(`${gameData.playerTurn}'s turn as ${color}`);

    if (gameData["state"] == "retry")
        $(".status-text").text(`${gameData.playerTurn} (${color}) — invalid move, try again`);

    if (gameData["state"] == "skip") {
        let oppPlayer = (gameData.playerTurn == gameData.player1)
            ? gameData.player2
            : gameData.player1;
        $(".status-text").text(
            `${gameData.playerTurn} (${color}) has no moves — ${oppPlayer} (${oppColor}) continues`
        );
        // Brief pause so the player can read the skip message
        setTimeout(() => {
            CallAJAX(
                'gameFlow.php', 'post',
                SendPlayData("99", gameData["playerTurn"], gameData["state"]),
                'json', UpdateGame, AjaxError
            );
        }, 2000);
    }
}

/* Summary: RenderBoard() updates every cell to reflect the current gameGrid.
 *          Uses CSS classes (.piece.black / .piece.white) instead of HTML
 *          entities, and applies place-in / flip-in animations by comparing
 *          against previousGrid.
 *
 *          Piece value reference (from PHP enum Tile):
 *            9679 = Black (●)
 *            9675 = White (○)
 *            ""   = empty
 *
 * Parameters: gameGrid (2-D array), playable (array of [x,y] pairs)
 * Returns: nothing
 */
function RenderBoard(gameGrid, playable) {
    for (let x = 0; x < gameGrid.length; x++) {
        for (let y = 0; y < gameGrid[x].length; y++) {
            const cell = $(`.board#${x}${y}`);
            const curr = gameGrid[x][y];
            const prev = previousGrid ? previousGrid[x][y] : null;

            cell.html("");

            if (curr !== "") {
                const colorClass = (curr == 9679) ? "black" : "white";
                let animClass = "";

                if (previousGrid === null) {
                    // First render — drop the 4 starting pieces in
                    animClass = "place-in";
                } else if (prev === "" || prev === null) {
                    // Cell was empty, now has a piece — newly placed
                    animClass = "place-in";
                } else if (prev !== curr) {
                    // Cell changed colour — piece was flipped
                    animClass = "flip-in";
                }
                // If prev === curr, piece is unchanged — no animation

                cell.html(`<div class="piece ${colorClass} ${animClass}"></div>`);
            }
        }
    }

    // Render playable-move dots on empty cells only
    for (let i = 0; i < playable.length; i++) {
        const [px, py] = playable[i];
        const cell = $(`.board#${px}${py}`);
        if (cell.html() === "") {
            cell.html('<div class="playable-dot"></div>');
        }
    }

    // Deep-copy so next render can diff against current state
    previousGrid = JSON.parse(JSON.stringify(gameGrid));
}

/* Summary: SendPlayData() packages a board-click into a data object for AJAX.
 * Parameters: position (id string e.g. "34"), turn (player name), state
 * Returns: object with x, y, playerTurn, state
 */
function SendPlayData(position, turn, state) {
    return {
        "x":          position.charAt(0),
        "y":          position.charAt(1),
        "playerTurn": turn,
        "state":      state
    };
}

/* Summary: EndGame() disables the board, unlocks name inputs, and shows
 *          the final result message.
 * Parameters: gameState ("end" | "tie"), winner (player name or "na")
 * Returns: nothing
 */
function EndGame(gameState, winner) {
    $(".board").off("click");               // stop accepting moves
    $(".player1").prop("readonly", false);  // allow name editing for rematch
    $(".player2").prop("readonly", false);

    if (gameState == "tie")
        $(".status-text").text("It's a tie — well played!");
    else
        $(".status-text").text(`${winner} wins! 🏆`);
}