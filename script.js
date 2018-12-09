var currentTurn = 'black';
var inAnimation = false;
//create a two dimensional array
var grid = new Array(7);
for(x = 0; x < 7; x++){
    grid[x] = new Array(6);    
}

/*FUNCTIONS FOR ONLINE*/

var userID;
var partnerID;
var playerColor;
var gameID;
var isOnline = false;
var myTurn;
var isDone = false;

$(window).on("beforeunload", function() {
	//syncronous request to make server is updated that the user quit
	$.ajax({
		url: 'api.php',
		method: 'POST',
		data: 'action=remove&userid='+userID,
		async: false
	});
});

var countdownTimer; // global var so it can be turned off from anywhere
function startTimer(seconds) {
	$('#timeBox').css({'color':'black'}); //reset the color of the countdown
	clearInterval(countdownTimer); 
    countdownTimer = setInterval(function() {
        $('#timeBox').text('0:' + ((seconds < 10)?'0':'') + seconds); //change counter number with leading zeroes
		if(seconds>0) seconds--; //subtract 1 from seconds
		if(seconds<5 && myTurn) { 
			//play a beep sound and change the color to red if under 5 seconds left
			document.getElementById('beep').play(); 
			$('#timeBox').css({'color':'darkred'});
		}
		if(seconds==0) {
			clearInterval(countdownTimer);
			if(myTurn) {
				//if it was the user's turn, let them know they failed to make a move within the time
				$('#modalText').text('You timed out.');
				$('#rematchBtn').hide(); //no option to rematch on time out
				$('#finishedModal').show();
			}
		}
    }, 1000); // 1 second
}

//transition from main menu to online menu
function showOnlineMenu() {
	$('#mainBox').css({'width':'275px','height':'105px'});
	$('#mainMenu').hide(0);
	$('#onlineMenu').delay(200).show(0); //delay is used to wait for animation
}

//transition from online menu to main menu
function onlineToMain() {
	$('#mainBox').css({'width':'500px','height':'200px'});
	$('#onlineMenu').delay(100).hide(0); 
	$('#mainMenu').delay(200).show(0).delay(200); //delay is used to wait for animation
}

//when typed in the name input field in the online menu
function O_nameInputChanged() {
	//if the input is between 3 and 12 characters, enable the start button
	if($('#pNameInput').val().trim().length > 2 && $('#pNameInput').val().trim().length < 12) {
		$('#startOnline').addClass('startButton').removeClass('startButtonDisabled');	
		$("#startOnline").unbind("click"); //this is to ensure the button is not binded multiple times
		$("#startOnline").on("click",function(){startOnline()}).one();
	} else {
		$('#startOnline').addClass('startButtonDisabled').removeClass('startButton');
		$("#startOnline").unbind("click");
	}
}

//when the start button is clicked in the online menu
function startOnline() {
	$('#startOnline').addClass('startButtonDisabled').removeClass('startButton');
	$("#startOnline").unbind("click");
	//add the user to the database 
	$.ajax({
		url: 'api.php',
		method: 'POST',
		data: 'action=insert&name='+$('#pNameInput').val(),
		success: function(data) {
			//if name is invalid
			if(data == '-1') {
				//allow user to re try the input
				$('#startOnline').addClass('startButton').removeClass('startButtonDisabled');
				$("#startOnline").unbind("click");
				$("#startOnline").on("click",function(){startOnline()}).one();	
				return;
			} 
			//if name is valid, get the userID and look for an opponent
			userID = data;
			$("#findingModal").show();
			findPlayer();
		}
	});
}

function findPlayer() {
	//send a request to the server to find an opponent
	$.ajax({
		url: 'api.php', 
		method: 'POST',
		data: 'action=find&userid='+userID,
		success: function(data) {
			if(data=='') return;
			split = data.split(',');
			//set game data
			partnerID = split[0];
			partnerName = split[1];
			playerColor = split[2];
			gameID = split[3];
			//start the game
			startOnlineGame();
		},
		error: function(data) {	
			//if timed out, send another request
			findPlayer();
		} 
	});
}

//if user presses cancel button
function cancelFinding() {
	//send request to server to make sure that the user doesn't show up for other clients
	$.ajax({
		url: 'api.php',
		method: 'POST',
		data: 'action=remove&userid='+userID,
		success: function(data) {	
			//go back to the online menu
			$("#findingModal").hide();
			$("#startOnline").on("click",function(){startOnline()}).one();
			$('#startOnline').addClass('startButton').removeClass('startButtonDisabled');
			$('#pNameInput').prop('disabled',false);
		}
	});
}

function startOnlineGame() {
	isOnline = true; //flag that it is an online game
	//transition into the game screen
	$('#mainBox').css({'width':'540px','height':'540px'});
	$('#timeBox').show();
	$("#findingModal").hide();
	$('#onlineMenu').hide(0);
	$('#gameArea').delay(300).show(0); //delay is used to wait for animation
	$('#'+playerColor+'Name').text($('#pNameInput').val()); 
	startTimer(30); //start the countdown
	//figure out whose turn it is
	if(playerColor=='black') { 
		$('#redName').text(partnerName); 
		myTurn = true;
	}
	else if(playerColor=='red') {
		$('#blackName').text(partnerName);
		myTurn = false;
		$('.col').css('cursor','default');
		waitForMove();
	}
}

function sendMove(colID) {
	//send request to server with the column the move was made in
	$.ajax({
		url: 'api.php',
		method: 'POST',
		data: 'action=move&gameid='+gameID+'&userid='+userID+'&col='+colID,
		success: function(data) {	
			//if the game isn't over, wait for a move from the opponent
			if(!isDone) waitForMove();
		}
	});
}

function waitForMove() {
	startTimer(30); //start the 30 second timer
	//send request to server and wait for response
	$.ajax({
		url: 'api.php',
		method: 'POST',
		data: 'action=wait&gameid='+gameID+'&userid='+userID,
		success: function(data) {	
			//if the opponent timed out
			if(data == "timeout") {
				clearInterval(countdownTimer);
				$('#modalText').text(partnerName + ' timed out.');
				$('#rematchBtn').hide();
				$('#finishedModal').show();
			}
			//if the opponent quit
			else if(data == "quit") {
				clearInterval(countdownTimer);
				$('#modalText').text(partnerName + ' quit.');
				$('#rematchBtn').hide();
				$('#finishedModal').show();
			}
			else {
				startTimer(30); //start the timer for the user's move
				col = data;
				col++;
				//run the animation as if the user clicked on that column
				onColClick(col, true);	
			}			
		}
	});
}

//when the rematch button is pressed
function requestRematch() {
	if(isOnline) {
		//make sure button can't be clicked twice
		$('#rematchBtn').addClass('startButtonDisabled').removeClass('startButton');
		$('#rematchBtn').unbind("click");
		//let the server know this player wants a rematch
		$.ajax({
			url: 'api.php',
			method: 'POST',
			data: 'action=rematch&userid='+userID,
			success: function(data) {	
				if(data=="no") $('#rematchBtn').text("Player left."); 
				//if the other player also selected rematch
				if(data=="yes") {
					//restart the game
					isDone = false;
					reMatch();
					if(!myTurn) waitForMove(); //if other player's turn, wait for their move
					else startTimer(30); //otherwise start the timer
					//reset the rematch button
					$('#rematchBtn').addClass('startButton').removeClass('startButtonDisabled');
					$("#rematchBtn").on("click",function(){requestRematch()}).one();	
				}
			}
		});
	} else reMatch(); //if it is a local game
}

/********************/

//go from the main menu to menu to start local game
function showLocalMenu() {
	$('#mainBox').css({'width':'300px','height':'170px'});
	$('#mainMenu').hide(0);
	$('#localMenu').delay(200).show(0); //delay is used to wait for animation
}

//go from menu to start local game to main menu
function localToMain() {
	$('#mainBox').css({'width':'500px','height':'200px'});
	$('#localMenu').delay(100).hide(0); 
	$('#mainMenu').delay(200).show(0).delay(200);//delay is used to wait for animation
}

//go from menu to start local game to actual game
function startLocalGame() {
	$('#mainBox').css({'width':'540px','height':'540px'});
	$('#localMenu').hide(0);
	$('#gameArea').delay(300).show(0); //delay is used to wait for animation
	$('#blackName').text($('#p1NameInput').val()); 
	$('#redName').text($('#p2NameInput').val());
}

//detect when name boxes are typed in
function nameInputChanged() {
	//if both fields have something written in them, enable the button
	if($('#p1NameInput').val().length > 0 && $('#p2NameInput').val().length > 0) {
		$('#startLocal').addClass('startButton').removeClass('startButtonDisabled');
		$("#startLocal").on("click",function(){startLocalGame()});
	} else {
		$('#startLocal').addClass('startButtonDisabled').removeClass('startButton');
		$("#startLocal").off("click");
	}
}

//show the chip placer when a column is hovered over
function onColHover(colId) {
	if(!(isOnline && !myTurn)) {
		offset = 55 + (68.5 * (colId-1));
		$('#chipPlacer').css({'left':offset});
		if(!inAnimation) $('#fallingChip').css({'left':offset}); //move the hidden chip image that is used for the animation
		if(!inAnimation) $('#chipPlacer').show();
	}
}

function offColHover() {
	$('#chipPlacer').hide();
}

//when a column is clicked
function onColClick(colId, opponentMove = false) {
	if(!(isOnline && !myTurn && !opponentMove)) { //nothing should happen if the user clicks on a column when it is not their turn
		if(inAnimation) return; //do nothing if there is currently an animation going on
		colId = colId-1; //start at 0
		for(i=5; i>=0; i--) {
			if(grid[colId][i] == null) {
				grid[colId][i] = currentTurn; //set grid value to the color chip
				var row = i;
				break; 
			}
			if(i==0) return; //if row is full, do nothing
		}
		inAnimation = true; //flag that an animation is now in progress
		$('#'+(colId+1)+'_'+(row+1)).attr('src','images/'+currentTurn+'circle.png'); //change color of chip in grid
		topPos = 63 + 68 * row; //figure out how far the animation-chip has to fall
		transTime = 600 - (80*(6-row)); //figure out how long is should take to fall
		offset = 55 + (68.5 * (colId)); 
		$('#fallingChip').css({'visibility':'visible','left':offset});
		$('#chipPlacer').hide(); //hide the chip placer to give the illusion that it is falling
		setTimeout(function(){ $('#fallingChip').css({'transition': transTime+'ms linear','top':topPos}) }, 10); //execute the animation
		//after the animation is done
		setTimeout(function(){
			$('#fallingChip').css({'visibility':'hidden','top':'4px','transition':'none'}); //hide the animation-chip
			$('#'+(colId+1)+'_'+(row+1)).css('visibility', 'visible'); //show the chip that should be in the grid at that position
			if(!isOnline) $('#chipPlacer').show();
			if(isOnline && myTurn) sendMove(colId); //send the move to the server
			if(!checkWin(colId, row)) { //check if someone has won
				if(!checkDraw()) { //if not, check if it was a draw
					changeTurn(); //if not, change the turn like regular
				} else finishGame("draw"); 
			} else finishGame("win"); 
			inAnimation = false;
			document.getElementById('chipDrop').play(); //play sound effect
		}, transTime);
	}
}

//check if every element in the grid is null
function checkDraw() {
    for(i = 0; i < 7; i++){
        for(j = 0; j < 6; j++){
            if(grid[i][j] == null) 
                return false;
        }
    }
    return true;
}

//show popup box with result
function finishGame(type) {
	clearInterval(countdownTimer);
	isDone = true;
	if(type=="win")
		if(!isOnline) $('#modalText').text($('#'+currentTurn+'Name').text() + ' wins!');
		else {
			if(myTurn) $('#modalText').text('You win!');
			else $('#modalText').text('You lose.');
		}
	
	if(type=="draw") 
		$('#modalText').text('It is a draw!');
	
	$('#finishedModal').show();
}

function changeTurn() {
	//if it is an online game, user should not be able to click on the columns when it is not their turn
	if(isOnline) { 
		myTurn = !myTurn;
		if (myTurn) $('.col').css('cursor','pointer');
		else $('.col').css('cursor','default');
	}
	if (currentTurn=='black') {
		$('#blackName').addClass('disabledNameText').removeClass('currentNameText');
		$('#redName').addClass('currentNameText').removeClass('disabledNameText');
		$('#blackChipImg').addClass('disabledImg');
		$('#redChipImg').removeClass('disabledImg');
		currentTurn = 'red';
	}
	else {
		$('#redName').addClass('disabledNameText').removeClass('currentNameText');
		$('#blackName').addClass('currentNameText').removeClass('disabledNameText');
		$('#redChipImg').addClass('disabledImg');
		$('#blackChipImg').removeClass('disabledImg');
		$('#blackChipPlacer').show();
		$('#redChipPlacer').hide();
		currentTurn = 'black';
	}
	$('#chipPlacer').attr('src','images/'+currentTurn+'circle.png');
	$('#fallingChip').attr('src','images/'+currentTurn+'circle.png');
}

//play again function
function reMatch() {
	$('#finishedModal').hide();
	clearBoard();
	changeTurn();
}

//sets all chips visibility to hidden
function clearBoard() {
	document.getElementById('gridClear').play();
	for(x = 0; x < 7; x++){
		for(y = 0; y < 6; y++) {
			grid[x][y] = null;
			$('#'+(x+1)+'_'+(y+1)).css('visibility', 'hidden');
		}
	}
}

//plays an animation and then refreshes the page
function exitToMenu() {
	$('#finishedModal').hide();
	$('#mainBox').css({'width':'500px','height':'200px'});
	$('#gameArea').hide();
	$('#mainMenu').delay(200).show(0);
	setTimeout(function(){ location.reload(true); }, 500);
}

//function to check if an x,y coordinated exists in an array
function isVisited(x, y, visited) {
    var pointStr = [x, y].join(); //join array elements into a string
    return visited.some(function (e) {
        return e.join() == pointStr;
    });
}

//function to check if there are 4 in a row
function checkWin(x, y) {
	var count = [1,1,1,1]; //4 possible directions
    function countNeighbors(x, y, color, visited, direction) {

        visited.push([x, y]); //add coordinate to visited array

        //for each neighbour
        for (var i = -1; i < 2; i++) {
            for (var j = -1; j < 2; j++) {
				if(j==0 && i==0) continue; //ignore if is itself
				if (y+j > 5 || x+i > 6 || y+j < 0 || x+i < 0) continue; //ignore if not valid coordinate
				if (isVisited(x+i, y+j, visited)) continue; //ignore if already visited
				if (grid[x+i][y+j] !== color) continue; //ignore if wrong color or null
		
				if(Math.abs(j) == Math.abs(i)) { //if x and y offsets are equal to eachother, it is diagonal
					if(i*j > 0) d=0; //if their product is positive it is down-right
					if(i*j < 0) d=1; //if their product is negative is is up-right
				}
				else if(i != 0) d=2; //if x offset exists but y doesn't, it is horizontal
				else if(j != 0) d=3; //if y offset exists but x doesn't, it is vertical
				//if chip is in the correct direction, or if there is no direction yet
				if(direction==d || direction<0) { 
					count[d]++; //increase count for that direction
					countNeighbors(x+i, y+j, color, visited, d); //find neighbours in that direction
				}
            }
        }
    }
    countNeighbors(x, y, grid[x][y], [], -1);
	return count.some(e => e>=4); //if any of the directions have 4, return true
}
