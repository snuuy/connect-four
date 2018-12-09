<?php
set_time_limit(33); //page times out in 30 seconds
require '../../connecti.php'; //handles MySQL connection to database
$action = $_POST['action'];

//if action is to insert
if($action == 'insert') {
	if(ctype_alnum(str_replace(' ', '', $_POST['name'])) && strlen(trim($_POST['name'])) > 2 && strlen(trim($_POST['name'])) < 12) { //make sure name is valid
		$userID = uniqid('u'); //generate a unique id for the user
		$con->query('INSERT INTO four_users VALUES (null, "'.$userID.'", "'.$_POST['name'].'", null, "'.$_SERVER['REMOTE_ADDR'].'", 0, UNIX_TIMESTAMP(), null)');
		die($userID);
	} else die('-1');
}

//if action is to remove
if($action == 'remove') {
	if(ctype_alnum($_POST['userid'])) {
		$con->query('UPDATE four_users SET status=4 WHERE userid="'.$_POST['userid'].'"'); //set status to 4 so no other client tries to pair with them
	}
}

//if action is to find 
if($action == 'find') {
	if(ctype_alnum($_POST['userid'])) { //make sure userid is valid
		$con->query('UPDATE four_users SET status=1,time=UNIX_TIMESTAMP() WHERE userID="'.$_POST['userid'].'"');//change users status and update timestamp
		while(true) {
			//get user info
			$q = $con->query('SELECT * FROM four_users WHERE userID="'.$_POST['userid'].'"');
			$userInfo = $q->fetch_assoc();
			//if the user has found a partner (if another client has changed the user's status)
			if($userInfo['status']==2) {
				$q = $con->query('SELECT name FROM four_users WHERE id='.$userInfo['partnerID']);	
				die($userInfo['partnerID'] . "," . $q->fetch_assoc()['name'] . ",red,".$userInfo['gameID']);
			}
			if($userInfo['status']==4) die(); //if user has quit
		
			$q = $con->query('SELECT * FROM four_users WHERE status=1 AND id !='.$userInfo['id'].' AND UNIX_TIMESTAMP()-time < 30');
			//if another user looking for a partner is found
			if($q->num_rows>0) {
				//get partner's info
				$partnerInfo = $q->fetch_assoc();
				//create empty 2d array for the grid
				$emptyGrid = array(); 
				for($i=0; $i<7; $i++){
					$emptyGrid[$i] = array();
					for($j=0; $j<6; $j++){
						$emptyGrid[$i][$j] = null;
					}
				}
				//convert to a json string to store in the mysql table
				$chips = json_encode($emptyGrid);
				
				//create a new game with an empty board
				$con->query('INSERT INTO four_games VALUES (null, '.$userInfo['id'].', '.$partnerInfo['id'].', "'.$chips.'", '.$userInfo['id'].', UNIX_TIMESTAMP(), null)');
				$gameid = $con->insert_id;
				//update statuses, partners, and gameids for both users
				$con->query('UPDATE four_users SET status=2,partnerID='.$partnerInfo['id'].', gameID='.$gameid. ' WHERE id='.$userInfo['id']);
				$con->query('UPDATE four_users SET status=2,partnerID='.$userInfo['id'].', gameID='.$gameid.' WHERE id='.$partnerInfo['id']);

				die($partnerInfo['id'] . "," . $partnerInfo['name'] . ",black," . $gameid); //output partner id, partner's name, and game id
			}
			usleep(300000); //sleep to prevent 100% cpu
		}
	}
}

//if a move is made
if($action == 'move') {
	if(ctype_alnum($_POST['userid']) && is_numeric($_POST['gameid']) && is_numeric($_POST['col'])) { //make sure all post data is valid
		//get id from userid
		$q = $con->query('SELECT * FROM four_users WHERE userID="'.$_POST['userid'].'"');
		$userInfo = $q->fetch_assoc();
		
		$q = $con->query('SELECT * FROM four_games WHERE id='.$_POST['gameid'].' AND currentTurn='.$userInfo['id']);
		if($q->num_rows == 0) die();
		
		$gameInfo = $q->fetch_assoc();
		//turn the string for the grid into an array 
		
		$grid = json_decode($gameInfo['chips']);
		//make sure the move is played in a valid slot
		for($i=5; $i>=0; $i--) {
			if($grid[$_POST['col']][$i] == null) {
				$row = $i;
				$grid[$_POST['col']][$i] = intval($userInfo['id']); //update grid with new move
				$jsonGrid = json_encode($grid); //convert array to string
				$con->query('UPDATE four_games SET chips="'.addslashes($jsonGrid).'", currentTurn='.$userInfo['partnerID'].', lastMove=UNIX_TIMESTAMP(), moveCol='.$_POST['col'].' WHERE id='.$_POST['gameid']);
				break;
			}
			if($i==0) die(); //if the slot is full
		}
	}
}

//if action is to wait for a move from the opponent
if($action == 'wait') {
	if(ctype_alnum($_POST['userid']) && is_numeric($_POST['gameid'])) {
		//get id from userid
		$q = $con->query('SELECT * FROM four_users WHERE userID="'.$_POST['userid'].'"');
		$userInfo = $q->fetch_assoc();
		
		//make sure game exists and it is not the users turn
		$q = $con->query('SELECT * FROM four_games WHERE id='.$_POST['gameid'].' AND currentTurn='.$userInfo['partnerID']);
		if($q->num_rows == 0) die();
		
		while(true) {
			//get game info
			$q = $con->query('SELECT * FROM four_games WHERE id='.$_POST['gameid']);
			$gameInfo = $q->fetch_assoc();
			//if the opponent has made a move, output the slot they chose
			if($gameInfo['currentTurn'] == $userInfo['id']) die($gameInfo['moveCol']);
			
			//if the opponent has exceeded 30 seconds
			if(time() - $gameInfo['lastMove'] > 31) die("timeout");
			
			//if the opponent has left the game
			$q = $con->query('SELECT * FROM four_users WHERE partnerID='.$userInfo['id']);
			$partnerStatus = $q->fetch_assoc()['status'];
			if($partnerStatus == 4) die("quit");
			
			usleep(100000); //sleep to avoid 100% cpu
		}
	}
}

if($action == 'rematch') {
	if(ctype_alnum($_POST['userid'])) { //make sure userid is valid
		//update user's status indicating they have requested a rematch
		$q = $con->query('UPDATE four_users SET status=3 WHERE userID="'.$_POST['userid'].'"');
		//get users info
		$q = $con->query('SELECT * FROM four_users WHERE userID="'.$_POST['userid'].'"');
		$userInfo = $q->fetch_assoc();
		while(true) {
			//get opponents info
			$q = $con->query('SELECT * FROM four_users WHERE id='.$userInfo['partnerID']);
			$partnerInfo = $q->fetch_assoc();
			//if the opponent has left the game
			if($partnerInfo['status'] == 4) die("no");
			//if the opponent has also requested a rematch
			if($partnerInfo['status'] == 3) {
				//generate an empty grid
				$emptyGrid = array(); 
				for($i=0; $i<7; $i++){
					$emptyGrid[$i] = array();
					for($j=0; $j<6; $j++){
						$emptyGrid[$i][$j] = null;
					}
				}
				//convert to a json string to store in the mysql table
				$chips = json_encode($emptyGrid);
				//update last move time and grid in the database
				$con->query('UPDATE four_games SET lastMove=UNIX_TIMESTAMP(), chips="'.$chips.'" WHERE id='.$userInfo['gameID']);
				$con->query('UPDATE four_users SET status=2 WHERE id='.$userInfo['partnerID']);
				die("yes");
			}
			usleep(10000);
		}
	}
}

/* I created these functions but ended up doing the win-checking client side so they were not needed */
$count = array(1,1,1,1);

function checkWin($x, $y, $grid) {
	global $count;
	countNeighbours($x, $y, $grid[$x][$y], array(), -1, $grid);
	foreach($count as $c) {
				echo $c;
		if($c>=4) return true;
	}
	return false;
}

function checkDraw($grid) {
	for($i=0; $i<7; $i++){
		for($j=0; $j<6; $j++){
			if($grid[$i][$j] == null) return false;
		}
	}	
	return true;
}

function isVisited($x, $y, $visited) {
    foreach ($visited as $point) {
		if($point[0] == $x && $point[1] == $y) {
			echo 'true';
			return true;
		}
    }
	echo 'false';
	return false;
}

function countNeighbours($x, $y, $color, $visited, $direction, $grid) {
	global $count;
	array_push($visited, array($x,$y));
	for($i=-1; $i<2; $i++) {
		for($j=-1; $j<2; $j++) {
			if($j==0 && $i==0) continue;
			if($x+$i > 6 || $y+$j > 5 || $x+$i < 0 || $y+$j < 0) continue;
			if($grid[$x+$i][$y+$j] !== $color) continue;
			if(isVisited($x+$i, $y+$j, $visited)) continue;
			
			if(abs($i) == abs($j)) {
				if($i*$j > 0) $d = 0;
				if($i*$j < 0) $d = 1;
			} 
			elseif($i != 0) $d = 2;
			elseif($j != 0) $d = 3;
			
			if($direction==$d || $direction<0) {
				$count[$d]++;
				countNeighbours($x+$i, $y+$j, $color, $visited, $d, $grid);
			}
			
		}
	}
}	
?>