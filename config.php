<?php
	/**
	 * @author Jan
	 * @link https://github.com/lepetitjan/shift-checker
	 * @license https://github.com/lepetitjan/shift-checker/blob/master/LICENSE
	 */

/*  GENERAL CONFIG
__________________________ */

// You should have installed Shift-Checker as normal user, so the line below should work by default.
// However, if you installed as root (please don't..) change the path below to $homeDir = "/root/";
    $homeDir        = "/home/".get_current_user()."/";

// You may leave the settings below as they are...
	$date		= date("Y-m-d H:i:s");			// Current date
	$pathtoapp	= $homeDir."shift/";			// Full path to your shift installation	
	$baseDir	= dirname(__FILE__)."/";		// Folder which contains THIS file
	$lockfile	= $baseDir."checkdelegate.lock";	// Name of our lock file
	$database	= $baseDir."check_fork.sqlite3";	// Database name to use
	$table 		= "forks";				// Table name to use
	$msg 		= "\"cause\":3";			// Message that is printed when forked
	$shiftlog 	= $pathtoapp."logs/shift.log";		// Needs to be a FULL path, so not ~/shift
	$linestoread	= 30;					// How many lines to read from the end of $shiftlog
	$max_count 	= 3;					// How may times $msg may occur
	$okayMsg 	= "√";					// 'Okay' message from shift_manager.bash

// ssh tunnel settings
        //make sure you move rsa.pvt generated by bash to home folder on master (sftp)
	//use "sudo chmod 400 rsa.pvt" to make permissions right
        $tunnelEnable= false;                                  //enable ssh tunnel
	$remotePort= 9405;                                     //port on slave server shift runs
	$localPort= 9410;                                      //port on this server you want
        $tunnelIP= "207.255.246.97";                           //ip address of slave server
	$sshPort= 22;                                          //port ssh uses (i would change it)
// Consensus settings
	//if your running tunnel on master set them both to 127.0.0.1
	//if your running tunnel set slave and master ip to the real ip
	//only run tunnel on master slave doesnt send your secret (i think)
	$consensusEnable= false;                                // Enable consensus check? Be sure to check $nodes first..
	$master         = true;                                 // Is this your master node? True/False
	$masternode     = "http://127.0.0.1";                   // Master node
	$masterport     = 9405;                                 // Master port
	$slavenode      = "http://127.0.0.1";                   // Slave node
	$slaveport      = $localPort;                           // Slave port
	$threshold      = 50;                                   // Percentage of consensus threshold
	$apiHost        = "http://127.0.0.1:".$masterport;	// Used to calculate $publicKey by $secret. Use the server your currently on so your secret stays at this server
	$secret         = array("");                            // Add your secrets here. If you want to forge multiple, add extra to the array. 

// Snapshot settings
	$snapshotDir	= $homeDir."shift-snapshot/";		// Base folder of shift-snapshot
	$createsnapshot	= true;					// Do you want to create daily snapshots?
	$max_snapshots	= 3;					// How many snapshots to preserve? (in days)

// Log file rotation
	$logfile 	= $baseDir."logs/checkdelegate.log";	// The location of your log file (see section crontab on Github)
	$max_logfiles	= 3;					// How many log files to preserve? (in days)  
	$logsize 	= 5242880;				// Max file size, default is 5 MB
//IPFS & DAPP
	$ipfsEnable     = false;                                //will start ipfs

	$dappEnable     = false;                                //will start a dapp for you
	$dappID         = ;                                     //id of your dapp
	$massPass       = ;                                     //dapp enable password

// Telegram Bot
	$telegramId 	= ""; 					// Your Telegram ID
	$telegramApiKey = ""; 					// Your Telegram API key 
	$telegramEnable = false;				// Change to true to enable Telegram Bot
	$telegramSendMessage 	= "https://api.telegram.org/bot".$telegramApiKey."/sendMessage"; // Full URL to post message
?>
