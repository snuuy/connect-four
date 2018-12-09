<html>
	<head>
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script><!--jquery library-->
		<script type="text/javascript" src="script.js"></script>
		<link rel="stylesheet" href="style.css">
	</head>
	<body>
		<audio id="chipDrop"><source src="sounds/drop.mp3" type="audio/mp3"></audio><!--audio file to play when chip is dropped-->
		<audio id="gridClear"><source src="sounds/clear.mp3" type="audio/mp3"></audio><!--audio file to play when grid is cleared-->
		<audio id="beep"><source src="sounds/beep.mp3" type="audio/mp3"></audio><!--audio file to play when countdown is counting-->
		<div id="mainBox">
			<div id="mainMenu">
				<div id="titleArea">
					<img id="titleIcon" src="images/icon.png">
					<span id="titleText"><font color="#c94848">Connect</font> <font color="#c94848">Four</font></span>
				</div>
				<div id="buttonsArea">
					<!--play buttons-->
					<div id="localButton" onClick="showLocalMenu()" class="playButton">Play Local</div>
					<div id="onlineButton" onClick="showOnlineMenu()" class="playButton">Play Online</div>
				</div>
			</div>
			<div id="localMenu"><!--menu to start local game-->
				<img src="images/blackchip.png" class="playerColor"><input type="text" onKeyUp="nameInputChanged()" maxlength="12" placeholder="Player One" id="p1NameInput" class="nameInput"><br>
				<img src="images/redchip.png" class="playerColor"><input type="text" onKeyUp="nameInputChanged()" maxlength="12" placeholder="Player Two" id="p2NameInput" class="nameInput">
				<div class="startButtonDisabled" id="startLocal">Start Game</div>
				<div class="backButton" onClick="localToMain()">Back</div>
			</div>
			<div id="onlineMenu"><!--menu to start online game-->
				<img src="images/whitechip.png" class="playerColor"><input type="text" onKeyUp="O_nameInputChanged()" maxlength="12" placeholder="Name" id="pNameInput" class="nameInput"><br>
				<div class="startButtonDisabled" id="startOnline">Find Game</div>
				<div class="backButton" onClick="onlineToMain()">Back</div>
			</div>
			<div class="modal" id="findingModal">
				<div style="width: 240px; height: 150px;" class="modalBox">
					<p><b>Finding opponent</b></p>
					<div id="fountainG">
						<div id="fountainG_1" class="fountainG"></div>
						<div id="fountainG_2" class="fountainG"></div>
						<div id="fountainG_3" class="fountainG"></div>
						<div id="fountainG_4" class="fountainG"></div>
						<div id="fountainG_5" class="fountainG"></div>
						<div id="fountainG_6" class="fountainG"></div>
						<div id="fountainG_7" class="fountainG"></div>
						<div id="fountainG_8" class="fountainG"></div>
					</div><br>
					<div class="backButton" onClick="cancelFinding()">Cancel</div>
				</div>
			</div>
			<div id="gameArea"><!--create the grid with image presets for all of the chips-->
				<img src="images/grid.png" id="gridImg">
				<img id="chipPlacer" class="chipPlacer" src="images/blackcircle.png"><!--chip that stays on top of the board and moves with the cursor-->
				<img id="fallingChip" class="chipPlacer" src="images/blackcircle.png"><!--hidden chip that is used for the animation-->
				<div class="colPositioning">
					<!--chips have ids based on their x and y coordinates-->
					<div class="col" id="col1" onClick="onColClick(1)" onmouseover="onColHover(1)" onmouseout="offColHover()">
						<img src="images/redcircle.png" class="chip" id="1_1">
						<img src="images/redcircle.png" class="chip" id="1_2">
						<img src="images/redcircle.png" class="chip" id="1_3">
						<img src="images/redcircle.png" class="chip" id="1_4">
						<img src="images/redcircle.png" class="chip" id="1_5">
						<img src="images/redcircle.png" class="chip" id="1_6">
					</div>
					<div class="col" id="col2" onClick="onColClick(2)" onmouseover="onColHover(2)" onmouseout="offColHover()">
						<img src="images/redcircle.png" class="chip" id="2_1">
						<img src="images/redcircle.png" class="chip" id="2_2">
						<img src="images/redcircle.png" class="chip" id="2_3">
						<img src="images/redcircle.png" class="chip" id="2_4">
						<img src="images/redcircle.png" class="chip" id="2_5">
						<img src="images/redcircle.png" class="chip" id="2_6">
					</div>
					<div class="col" id="col3" onClick="onColClick(3)" onmouseover="onColHover(3)" onmouseout="offColHover()">
						<img src="images/redcircle.png" class="chip" id="3_1">
						<img src="images/redcircle.png" class="chip" id="3_2">
						<img src="images/redcircle.png" class="chip" id="3_3">
						<img src="images/redcircle.png" class="chip" id="3_4">
						<img src="images/redcircle.png" class="chip" id="3_5">
						<img src="images/redcircle.png" class="chip" id="3_6">
					</div>
					<div class="col" id="col4" onClick="onColClick(4)" onmouseover="onColHover(4)" onmouseout="offColHover()">
						<img src="images/redcircle.png" class="chip" id="4_1">
						<img src="images/redcircle.png" class="chip" id="4_2">
						<img src="images/redcircle.png" class="chip" id="4_3">
						<img src="images/redcircle.png" class="chip" id="4_4">
						<img src="images/redcircle.png" class="chip" id="4_5">
						<img src="images/redcircle.png" class="chip" id="4_6">
					</div>
					<div class="col" id="col5" onClick="onColClick(5)" onmouseover="onColHover(5)" onmouseout="offColHover()">
						<img src="images/redcircle.png" class="chip" id="5_1">
						<img src="images/redcircle.png" class="chip" id="5_2">
						<img src="images/redcircle.png" class="chip" id="5_3">
						<img src="images/redcircle.png" class="chip" id="5_4">
						<img src="images/redcircle.png" class="chip" id="5_5">
						<img src="images/redcircle.png" class="chip" id="5_6">
					</div>
					<div class="col" id="col6" onClick="onColClick(6)" onmouseover="onColHover(6)" onmouseout="offColHover()">
						<img src="images/redcircle.png" class="chip" id="6_1">
						<img src="images/redcircle.png" class="chip" id="6_2">
						<img src="images/redcircle.png" class="chip" id="6_3">
						<img src="images/redcircle.png" class="chip" id="6_4">
						<img src="images/redcircle.png" class="chip" id="6_5">
						<img src="images/redcircle.png" class="chip" id="6_6">
					</div>
					<div class="col" id="col7" onClick="onColClick(7)" onmouseover="onColHover(7)" onmouseout="offColHover()">
						<img src="images/redcircle.png" class="chip" id="7_1">
						<img src="images/redcircle.png" class="chip" id="7_2">
						<img src="images/redcircle.png" class="chip" id="7_3">
						<img src="images/redcircle.png" class="chip" id="7_4">
						<img src="images/redcircle.png" class="chip" id="7_5">
						<img src="images/redcircle.png" class="chip" id="7_6">
					</div>
				</div>
				<div id="playerNamesArea">
					<img src="images/blackchip.png" id="blackChipImg" class="playerColor" style="max-width:50px;margin-top:-3px;"><span id="blackName" class="currentNameText">Player One</span>
					<img src="images/redchip.png" id="redChipImg" class="disabledImg playerColor" style="max-width:50px;margin-top:-3px;"><span id="redName" class="disabledNameText">Player Two</span>
					<div class="exitButton" onClick="exitToMenu()">Exit</div>
					<br><br>
				</div>
				<div id="timeBox">
				0:30
				</div>
			</div>
		</div>
		<!-- div for the dialog box for when the game is finished -->
		<div class="modal" id="finishedModal"> 
			<div class="modalBox">
				<div id="modalText" class="modalText"></div>
				<!-- buttons -->
				<div class="rematchButton startButton" id="rematchBtn" onClick="requestRematch()">Rematch</div>
				<div class="quitButton startButton" onClick="exitToMenu()">Main Menu</div>
			</div>
		</div>
	</body>
</html>