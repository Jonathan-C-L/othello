<?php
// MUST be done before die(); - will not recognize otherwise

// globals
enum Tile: int{
    case Black = 9679; // backed case for black
    case White = 9675; // backed case for white
    // pure cases do not have a assignment - can only be all pure or all backed
}
// globals
$black = Tile::Black->value;
$white = Tile::White->value;
// x/y-Checks are the coordinates for the recursive checks in each direction
$xChecks = array(-1, 1, 0, 0, 1, 1, -1, -1);
$yChecks = array(0, 0, 1, -1, 1, -1, 1, -1);

session_start(); // start session, or connect to session if already started 

error_log(json_encode($_POST), destination: "./");                   

$clean = CleanData(); // clean all input data
$output = array();  // create an array to be used to hold all data to be returned to the client.

error_log(json_encode($clean), destination: "./");  // Make sure the data is still as we expect

if(isset($clean["state"])){
    if ($clean["state"] == "check"){
        $output["errors"] = VerifyPlayerNames();
    }
    if ($clean["state"] == "start"){
        $output = StartGame();                                    
    }
    if ($clean["state"] == "play" || $clean["state"] == "retry" || $clean["state"] == "skip"){
        $_SESSION["state"] = "play";
        $output = SendGameData();
    } 
}
if(isset($clean["quit"]) && $clean["quit"] == "quit"){
    session_unset(); // deletes all of the current variables related to the session
    session_destroy(); // completely eliminates all data related to the session, including variables
}

error_log(json_encode($output), destination: "./");  
echo json_encode($output);  // Package and send data to the client. Only do this a single time per AJAX request.
die();  // Stop execution of the PHP page here.  
        
// function to clean inputted data
/* CleanData() takes the $_POST info and will strip any tags and verify escape characters
 *      'cleaning' the intake data and ensure nothing is out of place or wrong with it
 * No parameters
 * Returns the cleaned data with key=>value pairs with the tags stripped and escape characters verified
 */
function CleanData(){
    $cleanData = array();
    foreach($_POST as $key => $value){
        $cleanData[trim((strip_tags($key)))] = trim((strip_tags($value)));
    }
    return $cleanData;
}

// A sample showing use of the $_SESSION super global.  Remember that it is an associative array and therefore 
// may store ver complex data constructs.
// The $_SESSION may be used directly in program operations, but because of the level of indexing complexity
// that may arise in some programs, it is wise to extract $_SESSION information out into local variables for use. 
function StartGame(){
    global $clean, $black;

    $_SESSION["statusMessage"] = "We are starting our storage of game data";
    $_SESSION["gameGrid"] = CreateGameGrid(8, 8);
    $_SESSION["playerTurn"] = $_SESSION["first"]; // if a new player turn hasn't been sent from the client side, use the default session value
    $_SESSION["state"] = "play"; // allows game logic to be run

    AvailablePlays($black);
    // array that holds the info to send to the client
    return array(
        "playable" => $_SESSION["playable"],
        "gameGrid" => $_SESSION["gameGrid"],
        "player1" => $_SESSION["player1"],
        "player2" => $_SESSION["player2"],
        "first" => $_SESSION["first"],
        "second" => $_SESSION["second"],
        "playerTurn" => $_SESSION["playerTurn"], // tracks player turn
        "state" => $_SESSION["state"]
    );
}
/**
 * CreateGameGrid() creates a 8x8 2D array, representing the othello gameboard.
 * This will also randomly assign the player to start randomly and prepopulate the starting play pieces.
 */
function CreateGameGrid($width, $height){
    global $black, $white;
    RandomizeStart(); // randomizes player to start

    // creating gameboard
    $board = array();
    for($x = 0; $x < $width; $x++){
        $board[$x] = array();
        for($y = 0; $y < $height; $y++){
            $board[$x][$y] = "";
        }
    }
    // preset pieces at the start of the match
    $board[3][3] = $white;
    $board[4][3] = $black;
    $board[3][4] = $black;
    $board[4][4] = $white;

    return $board;
}
/* Summary:
 * VerifyPlayerNames() will add an error message if the names are empty
 * If both names are inputted, the game state will be changed and the players will be assigned accordingly
 * Parameters: No arguments
 * Returns: Returns any error messages regarding player input
*/
function VerifyPlayerNames(){
    global $clean;

    $nameError = "";
    // check if names are inputted - give prompt to if name missing for either/both
    if($clean["player1"] == "")
        $nameError = "Player 1";
    if($clean["player2"] == "")
        $nameError = "Player 2";
    if($clean["player1"] == "" && $clean["player2"] == "")
        $nameError = "Both players";
    if($clean["player1"] != "" && $clean["player2"] != ""){
        $_SESSION["player1"] = $clean["player1"];
        $_SESSION["player2"] = $clean["player2"];
    }

    return $nameError;
}
/* Summary:
 * RandomizeStart() randomize who starts to their move first
 * Parameters: No arguments
 * Returns: Returns a randomized array of players
*/
function RandomizeStart(){
    $players = [$_SESSION["player1"], $_SESSION["player2"]];
    shuffle($players);

    $_SESSION["first"] = $players[0];
    $_SESSION["second"] = $players[1];
}
/* Summary:
 * NextTurn() will swap the player based on who the current player is
 * Parameters: No arguments 
 * Returns: Returns nothing
*/
function NextTurn(){
    $_SESSION["playerTurn"] = ($_SESSION["playerTurn"] == $_SESSION["first"]) ? $_SESSION["second"] : $_SESSION["first"];
}
/* Summary:
 * CheckValidPlay() checks if the position played is open for play
 * Ignores the move and doesn't shift if a taken position is played
 * Parameters: No arguments
 * Returns: Returns nothing
*/
function CheckValidPlay(){
    global $clean, $black, $white, $xChecks, $yChecks;

    $gameGrid = $_SESSION["gameGrid"]; // using local variable to make working with grid simplier
    $x = $clean["x"];
    $y = $clean["y"];

    $validPlay = 0; // flag variable to check if a valid move was made

    $playable = $_SESSION["playable"];
    // change only happens when there is an empty spot clicked
    if($gameGrid[$x][$y] != ""){ // check current gameboard state
        // position information from the ajax call from interacting with the board
        $_SESSION["state"] = "retry";
    }

    for($i = 0; $i < count($playable); $i++){
            if($x == $playable[$i][0] && $y == $playable[$i][1])
                $validPlay++;
        }
    if( $validPlay == 0) // if there was no match, then retry move
        $_SESSION["state"] = "retry";
    
    if($_SESSION["state"] == "play"){
        $gameGrid[$x][$y] = ($_SESSION["playerTurn"] == $_SESSION["first"]) ? ($black) : ($white); // change state of board depending on player turn
        $_SESSION["gameGrid"] = $gameGrid; // put the recently worked on grid into the session to store

        FlipTiles($x, $y); // recursive function for flipping tiles
    }
}
/**
 * FlipTiles() recursively checks a specified direction and flips the tiles that are eligible to be flipped
 * @param $x the current x position
 * @param $y the current y position
 */
function FlipTiles($x, $y){
    global $black, $white, $xChecks, $yChecks;
    $gameGrid = $_SESSION["gameGrid"];

    $tileChange = ($gameGrid[$x][$y] == $black) ? ($white) : ($black);

    // check each direction once the board position has been verified and commited to the session
    for($i = 0; $i < count($xChecks); $i++){
        RecursiveFill($x + $xChecks[$i], $y + $yChecks[$i], $xChecks[$i], $yChecks[$i], $gameGrid[$x][$y], $tileChange);
    }
}
/**
 * Flippable() recursively checks a specified direction if the tiles adjacent to it meets the requirements to be a flippable tile
 * @param $x the current x position
 * @param $y the current y position
 * @param $dirX the x direction to continue to check recursively
 * @param $dirY the y direction to continue to check recursively
 * @param $ownTile the current tile color
 * @param $otherTile the opposite tile color
 * @return bool true or false based on exit and recursion conditions
 */
function Flippable($x, $y, $dirX, $dirY, $ownTile, $otherTile){
    $gameGrid = $_SESSION["gameGrid"];

    if($gameGrid[$x][$y] == "")
        return false;
    if($x < 0 || $x > count($gameGrid) || $y < 0 || $y > count($gameGrid[$x]))
        return false;
    if($gameGrid[$x][$y] == $ownTile)
        return true;
    if(Flippable($x + $dirX, $y + $dirY, $dirX, $dirY, $ownTile, $otherTile))
        return true;
    return false;
}
/** 
 * AvailablePlays() will recursively check every position that is eligible to be a played piece and will store
 * the positions of the valid play spots in $_SESSION
 * @param $currentTile is the current play tile color
 */
function AvailablePlays($currentTile){
    global $black, $white, $xChecks, $yChecks;

    $gameGrid = $_SESSION["gameGrid"];
    $oppositeTile = ($currentTile == $black) ? $white : $black;

    $emptyX = array();
    $emptyY = array();
    // find emtpy areas of the opposite color and store the positions
    for($x = 1; $x < count($gameGrid) - 1; $x++){
        for($y = 1; $y < count($gameGrid[$x]) - 1; $y++){
            if( $gameGrid[$x][$y] == $oppositeTile && ($gameGrid[$x + 1][$y] == "" || $gameGrid[$x - 1][$y] == "" || $gameGrid[$x][$y + 1] == "" || $gameGrid[$x][$y - 1] == "" || 
                $gameGrid[$x + 1][$y + 1] == "" || $gameGrid[$x + 1][$y - 1] == "" || $gameGrid[$x - 1][$y + 1] == "" || $gameGrid[$x - 1][$y - 1] == "")){
                $emptyX[] = $x; // add x position
                $emptyY[] = $y; // add y position
            }
        }   
    }

    $possiblePlays = 0; // keeps track of the num of playable spots
    $playable = array(); // track the playable positions
    $indicators = array();

    // check the positions that could potentially be playable
    if(count($emptyX) > 0 && count($emptyY) > 0){
        for($x = 0; $x < count($emptyX); $x++){
            for($i = 0; $i < count($xChecks); $i++){
                if(Flippable($emptyX[$x] + $xChecks[$i], $emptyY[$x] + $yChecks[$i], $xChecks[$i], $yChecks[$i], $currentTile, $oppositeTile)){
                    $playable[] = array($emptyX[$x], $emptyY[$x]);
                    $indicators[] = array(($emptyX[$x] + ($xChecks[$i] * -1)), ($emptyY[$x] + ($yChecks[$i] * -1)));
                    $possiblePlays++;
                }
            }
        }
    }
    $_SESSION["gameGrid"] = $gameGrid;
    $_SESSION["playable"] = $indicators;
    // check for skips - no playable areas
    if($possiblePlays > 0){
        $_SESSION["skips"] = 0;
    }
    else{ 
        $_SESSION["state"] = "skip";
        $_SESSION["skips"]++; // increment this because 2 skips in a row means no plays available in match
    }
}
/**
 * Summary of RecursiveFill - recursively checks a specific direction and flips tiles of other colors
 * only if the tile of the same color is found at the end of the recursion
 * @param mixed $x initial x coordinate
 * @param mixed $y initial y coordinate
 * @param mixed $dirX direction x
 * @param mixed $dirY direction y
 * @param mixed $ownTile current color check
 * @param mixed $otherTile opposite color check
 * @return bool true or false for escape or continue conditions
 */
function RecursiveFill($x, $y, $dirX, $dirY, $ownTile, $otherTile){
    $gameGrid = $_SESSION["gameGrid"];

    // exit conditions
    if($gameGrid[$x][$y] == "")
        return false;
    if($x < 0 || $x > count($_SESSION["gameGrid"]) || $y < 0 || $y > count($_SESSION["gameGrid"][$x]))
        return false;
    if($gameGrid[$x][$y] == $ownTile){
        return true;
    }
    if(RecursiveFill($x + $dirX, $y + $dirY, $dirX, $dirY, $ownTile, $otherTile)){
        // if this not done, the last tile doesn't flip
        // A potential fix for this would be to global the $gameGrid variable; the recursion needs to track the changes
        // however, $gameGrid = $_SESSION["gameGrid"] only gets the state of the board prior to change, hence the requirement
        // for the line below 
        $_SESSION["gameGrid"][$x][$y] = $ownTile; // update change in session 
        return true;
    }
    return false;
}
/* Summary:
 * CheckResults() checks for tie and win conditions
 * Parameters: No arguments
 * Returns: Returns nothing
*/
function CheckResults(){
    global $black, $white;

    $gameGrid = $_SESSION["gameGrid"];

    // max plays is 64
    // tie check - if both players have 32
    $tilesPlayed = 0;
    $player1Tiles = 0;
    $player2Tiles = 0;
    $p1Tiles = ($_SESSION["player1"] == $_SESSION["first"]) ? ($black) : ($white);
    $p2Tiles = ($_SESSION["player2"] == $_SESSION["first"]) ? ($black) : ($white);
    
    // count player tiles for score
    for($x = 0; $x < count($gameGrid); $x++){
        for($y = 0; $y < count($gameGrid[$x]); $y++){
            if($gameGrid[$x][$y] != "") // check tiles played
                $tilesPlayed++;
            if($gameGrid[$x][$y] == $p1Tiles) // player 1 points
                $player1Tiles++;
            if($gameGrid[$x][$y] == $p2Tiles) // player 2 points
                $player2Tiles++;
        }
    }
    // store points in session
    $_SESSION["player1Score"] = $player1Tiles;
    $_SESSION["player2Score"] = $player2Tiles;
    $_SESSION["gameGrid"] = $gameGrid;
    $_SESSION["winner"] = "na";

    if($_SESSION["skips"] >= 2){ // 2 skips occurs back to back
        $_SESSION["state"] = "end";
        if($player1Tiles > $player2Tiles) // player 1 has more tiles, p1 win
            $_SESSION["winner"] = $_SESSION["player1"];
        if($player1Tiles < $player2Tiles) // player 2 has more tiles, p2 wins
            $_SESSION["winner"] = $_SESSION["player2"];
        if($player1Tiles == $player2Tiles) // both players have the same, tied
            $_SESSION["state"] = "tie";
    }
    if($tilesPlayed == 64){ // no more moves
        $_SESSION["state"] = "end"; // state of tie will end the game process (can only play when state = start)
        if($player1Tiles > $player2Tiles) // player 1 has more tiles, p1 win
            $_SESSION["winner"] = $_SESSION["player1"];
        if($player1Tiles < $player2Tiles) // player 2 has more tiles, p2 wins
            $_SESSION["winner"] = $_SESSION["player2"];
        if($player1Tiles == $player2Tiles) // both players have the same, tied
            $_SESSION["state"] = "tie";
    }
}

/* Summary:
 * SendGameData() sends back game data back to the client
 * Parameters: No arguments
 * Returns: Returns a object with the game grid, player1 info, player2 info, player turn, and the game state info
*/
function SendGameData(){
    global $clean, $black, $white;

    if($clean["x"] != 9 && $clean["y"] != 9){
        CheckValidPlay(); // check if the player placement is valid
    }
    CheckResults(); // check if any end-game conditions met (lose, tie, win)
    if($_SESSION["state"] == "play"){
        NextTurn();
        $currentTile = ($_SESSION["playerTurn"] == $_SESSION["first"]) ? $black : $white;
        AvailablePlays($currentTile);
    }

    return array(
        "player1Score" => $_SESSION["player1Score"],
        "player2Score" => $_SESSION["player2Score"],
        "playable" => $_SESSION["playable"],
        "winner" => $_SESSION["winner"],
        "gameGrid" => $_SESSION["gameGrid"], // keeps track of the board
        "player1" => $_SESSION["player1"], // store player1 name
        "player2" => $_SESSION["player2"], // store player2 name
        "skips" => $_SESSION["skips"],
        "first" => $_SESSION["first"],
        "second" => $_SESSION["second"],
        "playerTurn" => $_SESSION["playerTurn"], // tracks player turn
        "state" => $_SESSION["state"] // tracks state of game (stop, play, win, retry, and tie)
    );
}