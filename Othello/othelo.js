/*
CMPE 2550 - Web Applications
Date: Sept. 18, 2025 
Author: Jonathan Le
Purpose: Javascript for DOM manipulations of Tic Tac Toe. Logic handled in a separate page.  
*/
console.log("js has successfully connected!");

$(document).ready(function()
{
    $("#submit").click(ConnectToServer);  // Binding the click event handler to the function that will initiate 
                                     // our AJAX call
});
/* Summary: StartClick() prepares data on the state of the game, player1 and player2 names,
 *          then initates an ajax call to the server 
 * Parameters: No arguments
 * Returns: Returns nothing
*/
// Preparing for and initiating an AJAX call to the server
function ConnectToServer() 
{
    $(".gameboard").html(""); // resets the board if the 'New Game' button is clicked
    var postData = {};              // preparing data object to be used with the AJAX call to the server
    postData["state"] = "check";   // intended to be used to limit activity on the server to intended program flow
    postData["player1"] = $(".player1").val();
    postData["player2"] = $(".player2").val();
    console.log(postData);
    CallAJAX('gameFlow.php', 'post', postData, 'json', VerifyNames, Error);
}

/* Summary: StartSuccess() On successful AJAX interaction with the server, any returned data from the server will be returned to this function
 * Parameters: takes 3 arguments - 
 *      1. data returned from the server 
 *      2. the status of the ajax call 
 *      3. the ajax request 
 * Returns: Returns nothing
*/
function VerifyNames(returnedData, statusMessage, ajaxRequest)
{
    console.log(returnedData);
    console.log(returnedData.errors);   // Always good to see what data has been returned.  After we verify we are getting back 
                                        // what was expected, we may ignore the server and focus on client side data processing.
                                        // Check for this data in the console of your inspect tools.
    // notify user to input name if not inputted - gameFlow.php determined game not started yet
    if(returnedData.errors != "") 
        window.alert(returnedData.errors + " must input a name!");

    if(returnedData.errors == ""){
        GenerateBoard(8, 8);
        CallAJAX('gameFlow.php', 'post', {"state": "start"}, 'json', StartGame, Error);
    }
}

/* Summary: CallAJAX() sends an ajax call to the a specified location. A generic ajax call function
 * Parameters: takes 6 arguments - 
 *      1. url - location for the ajax call
 *      2. method - http method 
 *      3. data - data to send to the server
 *      4. dataType - method of encoding information
 *      5. success - callback function for a successful request
 *      6. error - callback function for an unsuccessfull request
 * Returns: Returns nothing
*/
function CallAJAX(url, method, data, dataType, success, error)
{
    var ajaxOptions = {};
    ajaxOptions['url'] = url;
    ajaxOptions['method'] = method;
    ajaxOptions['data'] = data;
    ajaxOptions['dataType'] = dataType;
    ajaxOptions['success'] = success;
    ajaxOptions['error'] = error;

    $.ajax(ajaxOptions);
}

/* Summary: Error() is a shared function for failed AJAX requests
 * Parameters: takes 3 arguments
 *      1. the ajax request
 *      2. status message
 *      3. error message
 * Returns: Returns nothing
*/
function Error(ajaxRequest, statusMessage, errorThrownMessage)
{
    console.log(ajaxRequest);
    console.log(statusMessage);
    console.log(errorThrownMessage);
}
/* Summary: GenerateBoard() will iterate 8x8 times to create a gameboard
 * Parameters: takes 0 arguments
 * Returns: Returns nothing
*/
function GenerateBoard(width, height){
    for(let y = 0; y < width; y++){
        for(let x = 0; x < height; x++){
            let $boardPiece = $(`<div class='board' id=${x}${y} name='board'></div>`);
            $boardPiece.attr("readonly", "readonly");
            $(".gameboard").append($boardPiece);
        }
    }
}

/* Summary:
 * StartGame() sets the event delegation is used and a click handler is applied on the parent container
 * Parameters: takes 1 argument - The returned ajax object to access the game state and the board 
 * Returns: Returns nothing
*/
function StartGame(gameData, statusMessage, ajaxRequest){
    console.log("StartGame()");
    console.log(gameData);
    RenderBoard(gameData.gameGrid, gameData.playable);

    $("h3").text(`${gameData["player1"]} starts as black!`); // update status
    // prevent name change mid game
    $(".player1").prop("readonly", true); 
    $(".player2").prop("readonly", true);

    $(".player1Tiles").text(`${gameData["player1"]} - 0`);
    $(".player2Tiles").text(`${gameData["player2"]} - 0`);
    // event delegation to the parent container
    $(".board").click(function(){
        console.log("board click");
        console.log(SendPlayData($(this).attr("id"), gameData["playerTurn"], gameData["state"]));
        CallAJAX('gameFlow.php', 'post', SendPlayData($(this).attr("id"), gameData["playerTurn"], gameData["state"]), 'json', UpdateGame, Error);
    });
}
/* Summary:
 * UpdateGame() will render the game status and the gameboard to the user
 * Will end the game if the game state has changed
 * Parameters:
 * takes 3 arguments
 * 1. The returned ajax object
 * 2. status message
 * 3. ajax request
 * Returns:
 * Returns nothing
*/
function UpdateGame(gameData, statusMessage, ajaxRequest){
    console.log("UpdateGame()");
    console.log(gameData);
    let color = (gameData.playerTurn == gameData.first) ? "black" : "white";
    let oppColor = (color == "black") ? "white" : "black";

    $(".player1Tiles").text(`${gameData["player1"]} - ${gameData.player1Score}`);
    $(".player2Tiles").text(`${gameData["player2"]} - ${gameData.player2Score}`);
    RenderBoard(gameData.gameGrid, gameData.playable);

    // when the game state has changed, end the match
    if(gameData.state == "end" || gameData.state == "tie")
        EndGame(gameData.state, gameData["winner"]);

    if(gameData["state"] == "play")
        $("h3").text(`${gameData.playerTurn}'s turn as ${color}...`);
    // update the player's turn
    if(gameData["state"] == "retry") // prompt to try again if illegal move made
        $("h3").text(`${gameData.playerTurn} (${color}) made an illegal move, try again...`);
    if(gameData["state"] == "skip"){
        let oppPlayer = (gameData.PlayerTurn == gameData.player1) ? gameData.player2 : gameData.player1; 
        $("h3").text(`${gameData.playerTurn}'s (${color}) turn skipped, ${oppPlayer}'s (${oppColor}) turn...`);
        // timeout the skip ajax call for player to see the skip message
        setTimeout(()=>{CallAJAX('gameFlow.php', 'post', SendPlayData("99", gameData["playerTurn"], gameData["state"]), 'json', UpdateGame, Error);}, 2000)
    }
}

/* Summary:
 * RenderBoard() renders the contents of the board
 * Parameters:
 * takes 1 argument
 * 1. board array from the returned ajax object
 * Returns:
 * Returns nothing
*/
function RenderBoard(gameGrid, playable){
    console.log(playable);

    // rendering board pieces
    for(let x = 0; x < gameGrid.length; x++){
        for(let y = 0; y < gameGrid[x].length; y++){
            $(`.board#${x}${y}`).html(""); // clearing board
            if(gameGrid[x][y] != "")
                $(`.board#${x}${y}`).html(`&#${gameGrid[x][y]};`); // display the mark to the corresponding grid position
        }
    }
    // rendering playable spots
    for(let i = 0; i < playable.length; i++){
        if(gameGrid[playable[i][0]][playable[i][1]] == "")
            $(`.board#${playable[i][0]}${playable[i][1]}`).html(`&#183;`); // display the mark to the corresponding grid position
    }
}
/* Summary:
 * SendPlayData() is the callback for the ajax call on a board piece click
 * Parameters:
 * takes 2 arguments
 * 1. the position of the board that was clicked
 * 2. the turn of the player that just played
 * Returns:
 * Object containing the index of the board piece clicked and the turn of the player that played it
*/
function SendPlayData(position, turn, state){
    return {
        "x": position.charAt(0), 
        "y": position.charAt(1),
        "playerTurn": turn,
        "state": state
    };
}
/* Summary:
 * EndGame() changed the game state to stop the game on the server-side
 * Accounts for a tie and win condition
 * Parameters:
 * takes 1 argument
 * 1. returned data object from the ajax call
 * Returns:
 * Returns nothing
*/
function EndGame(gameState, winner){
    $(".board").off("click"); // turn off click handler
    $(".player1").prop("readonly", false); // allow users to change name if they'd like
    $(".player2").prop("readonly", false);

    // tie result
    if(gameState == "tie")
        $("h3").text("Tie match!");
    // win result
    else
        $("h3").text(`Winner is: ${winner}!!`);

}