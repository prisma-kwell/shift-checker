<?php
	/**
	 * @author Jan
	 * @link https://github.com/lepetitjan/shift-checker
	 * @license https://github.com/lepetitjan/shift-checker/blob/master/LICENSE
	 */

/* 	__________________________

	      GENERAL CONFIG
	__________________________ */

// You NEED to edit this value before running the script...
	$homeDir		= "/home/".get_current_user()."/";		// Full path to your home folder	

// You may leave the settings below as they are...
	$date			= date("Y-m-d H:i:s");				// Current date
	$pathtoapp		= $homeDir."shift/";				// Full path to your shift installation	
	$baseDir		= dirname(__FILE__)."/";			// Folder which contains THIS file
	$lockfile		= $baseDir."checkdelegate.lock";		// Name of our lock file
	$database		= $baseDir."check_fork.sqlite3";		// Database name to use
	$table 			= "forks";					// Table name to use
	$msg 			= "Failed to find common block with";		// Message that is printed when forked
	$shiftlog 		= $pathtoapp."logs/shift.log";			// Needs to be a FULL path, so not ~/shift
	$linestoread		= 30;						// How many lines to read from the end of $shiftlog
	$max_count 		= 3;						// How may times $msg may occur
	$okayMsg 		= "√";						// 'Okay' message from shift_manager.bash

// Always-Forge settings
	$consensusEnable		= false;				// Enable consensus check? Be sure to check $nodes first..
	$apiHost 		= "http://127.0.0.1:9405";
	$nodes			= array(
		"http://127.0.0.1:9405",
		"http://testnode1.shiftnrg.org:9405" // Add your backup node here...the one mentioned is just for show!
	);
	$threshold 		= 80; 						// Percentage of consensus threshold
	if(is_file($pathtoapp."config.json")){
		$getConfig  = json_decode(file_get_contents($pathtoapp."config.json"), true);
        $secret 	= $getConfig['forging']['secret'];
    }else{ 
    	$secret 	= ""; 
    	echo $date." - Config.json file not found. Failed to get your secret.\n";
    }

// Snapshot settings
	$snapshotDir		= $homeDir."shift-snapshot/";			// Base folder of shift-snapshot
	$createsnapshot		= true;						// Do you want to create daily snapshots?
	$max_snapshots		= 3;						// How many snapshots to preserve? (in days)

// Log file rotation
	$logfile 		= $baseDir."logs/checkdelegate.log";		// The location of your log file (see section crontab on Github)
	$max_logfiles		= 3;						// How many log files to preserve? (in days)  
	$logsize 		= 5242880;					// Max file size, default is 5 MB

// Telegram Bot
	$telegramId 		= ""; 						// Your Telegram ID
	$telegramApiKey 	= ""; 						// Your Telegram API key 
	$telegramEnable 	= false; 					// Change to true to enable Telegram Bot
	$telegramSendMessage 	= "https://api.telegram.org/bot".$telegramApiKey."/sendMessage"; // Full URL to post message
?>
