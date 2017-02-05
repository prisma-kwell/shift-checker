<?php
	/**
	 * @author Jan
	 * @link https://github.com/lepetitjan/shift-checker
	 * @license https://github.com/lepetitjan/shift-checker/blob/master/LICENSE
	 */

/* GENERAL SETTINGS
____________________ */

	// You NEED to edit this value before running the script...
	$homeDir			= "/home/lepetitjan/";				// Full path to your home folder	

	// You may leave the settings below as they are...
	$date				= date("Y-m-d H:i:s");				// Current date
	$pathtoapp			= $homeDir."shift/";				// Full path to your shift installation	
	$baseDir			= dirname(__FILE__)."/";			// Folder which contains THIS file
	$lockfile			= $baseDir."checkdelegate.lock";	// Name of our lock file
	$database			= $baseDir."check_fork.sqlite3";	// Database name to use
	$table 				= "forks";							// Table name to use
	
	// Let's fetch some values from the Shift config.json
    $getConfig      	= json_decode(file_get_contents($pathtoapp."config.json"), true);
    if(is_file($pathtoapp."config.json")){
        $getVersion 	= $getConfig['version'];
        $getLogFile 	= $getConfig['logFileName'];
    }else{ echo $date." - Config file not found! Version check failed.\n"; }

	$msg 				= "Failed to find common block with";	// Message that is printed when forked
	$shiftlog 			= $pathtoapp.$getLogFile;				// Needs to be a FULL path, so not ~/shift
	$linestoread		= 50;									// How many lines to read from the end of $shiftlog
	$max_count 			= 3;									// How may times $msg may occur
	
	if($getVersion >= "6.1.1"){
		$okayMsg = "√";											// 'Okay' message from shift_manager.bash
	}else{
		$okayMsg = "OK";
	}

	// Snapshot settings
	$snapshotDir		= $homeDir."shift-snapshot/";	// Base folder of shift-snapshot
	$createsnapshot		= true;							// Do you want to create daily snapshots?
	$max_snapshots		= 3;							// How many snapshots to preserve? (in days)

	// Log file rotation
	$logfile 			= $baseDir."logs/checkdelegate.log";	// The location of your log file (see section crontab on Github)
	$max_logfiles		= 3;									// How many log files to preserve? (in days)  
	$logsize 			= 10485760;								// Max file size, default is 10 MB

	// Telegram Bot
	$telegramId 		= ""; 		// Your Telegram ID
	$telegramApiKey 	= ""; 		// Your Telegram API key 
	$telegramEnable 	= false; 	// Change to true to enable Telegram Bot
	$telegramSendMessage= "https://api.telegram.org/bot".$telegramApiKey."/sendMessage"; // Full URL to post message

	// Check delegate balance
	$checkBalance		= false;							// Enable (true) or disable (false) the balance checker
	$nodeAddress 		= "http://127.0.0.1:9305";			// Address of your node to check your balance and send transactions omitting final /
	$myDelegate 		= "";								// SHIFT address of your delegate
	$firstPass			= "";								// Passphrase/Secret of your delegate
	$secondPass			= "";								// Your second passphrase if you have it..otherwise leave blank!
	$myPrivateWallet	= "";								// SHIFT address of your private wallet
	$maxBalance			= 10;								// Max of balance to keep in your delegate wallet
	$explorer			= "https://explorer.shiftnrg.org/";	// URL of explorer to use

	// Donations
	$donate				= true;								// Only works if $checkBalance is enabled
	$donateAddress		= "6440419179119733364S";			// Donation address
	$donateAmount		= 10;								// Donation amount

/* PREREQUISITES
____________________ */

require('functions.php');

// Let's start the output with a line for the log file
echo "___________________________________________________\n";


/* LOCK FILE
____________________ */

// Check if lock file exists
if (file_exists($baseDir.$lockfile)) {

	// Check age of lock file and touch it if older than 10 minutes
	if((time()-filectime($baseDir.$lockfile)) >= 600){
	
		echo $date." - [ LOCKFILE ] Lock file is older than 10 minutes. Going to touch it and continue..\n";
		
		if (!touch($lockfile)){
		  exit("[ LOCKFILE ] Error touching $baseDir.$lockfile\n");
		}

	// If file is younger than 10 minutes, exit!
	}else{
		exit("[ LOCKFILE ] A previous job is still running...\n");
	}

}

/* CHECK STATUS
____________________ */

echo $date." - [ STATUS ] Let's check if our delegate is still running...\n";

// Check status with shift_manager.bash. Use PHP's ob_ function to create an output buffer
	ob_start();
   	$check_status = passthru("cd $pathtoapp && bash shift_manager.bash status | cut -z -b1-3");
	$check_output = ob_get_contents();
	ob_end_clean();

// If status is not OK...
   if(strpos($check_output, $okayMsg) === false){
   	echo $date." - Okay message: ".$okayMsg."\n";
	// Echo something to our log file
   		echo $date." - [ STATUS ] Delegate not running/healthy. Let me restart it for you...\n";
   			if($telegramEnable === true){
   				$msg = "Delegate ".gethostname()." not running/healthy. I will restart it for you...";
   				passthru("curl -s -d 'chat_id=$telegramId&text=$msg' $telegramSendMessage > /dev/null");
   			}
   		echo $date." - [ STATUS ] Stopping all forever processes...\n";
   			passthru("forever stopall");
   		echo $date." - [ STATUS ] Starting Shift forever proces...\n";
   			passthru("cd $pathtoapp && forever start app.js");
   
   }else{
   
   		echo $date." - [ STATUS ] Delegate is still running...\n";
   
   }


/* CHECK IF FORKED
____________________ */

echo $date." - [ FORKING ] Going to check for forked status now...\n";

// Set the database to save our counts to
    $db = new SQLite3($database) or die("[ FORKING ] Unable to open database");
 
// Create table if not exists
    $db->exec("CREATE TABLE IF NOT EXISTS $table (
                    id INTEGER PRIMARY KEY,  
                    counter INTEGER,
                    time INTEGER)");

// Let's check if any rows exists in our table
    $check_exists = $db->query("SELECT count(*) AS count FROM $table");
    $row_exists   = $check_exists->fetchArray();
    $numExists    = $row_exists['count'];

    // If no rows exist in our table, add one
    	if($numExists < 1){
        	
        	// Echo something to our log file
        	echo $date." - [ FORKING ] No rows exist in our table to update the counter...Adding a row for you.\n";
        	
        	$insert = "INSERT INTO $table (counter, time) VALUES ('0', time())";
        	$db->exec($insert) or die("[ FORKING ] Failed to add row!");
      	
      	}

// Tail shift.log
	$last = tailCustom($shiftlog, $linestoread);

// Count how many times the fork message appears in the tail
	$count = substr_count($last, $msg);

// Get counter value from our database
    $check_count 	= $db->query("SELECT * FROM $table LIMIT 1");
    $row          	= $check_count->fetchArray();
    $counter      	= $row['counter'];

// If counter + current count is greater than $max_count, take action...
    if (($counter + $count) >= $max_count) {

        echo $date." - [ FORKING ] Hit max_count. I am going to restore from a snapshot.\n";
        	if($telegramEnable === true){
   				$msg = "Hit max_count on ".gethostname().". I am going to restore from a snapshot.";
   				passthru("curl -s -d 'chat_id=$telegramId&text=$msg' $telegramSendMessage > /dev/null");
   			}

       	passthru("cd $pathtoapp && forever stop app.js");
       	passthru("cd $snapshotDir && echo y | ./shift-snapshot.sh restore");
       	passthru("cd $pathtoapp && forever start app.js");

        echo $date." - [ FORKING ] Finally, I will reset the counter for you...\n";

        $query = "UPDATE $table SET counter='0', time=time()";
    	$db->exec($query) or die("[ FORKING ] Unable to set counter to 0!");

// If counter + current count is not greater than $max_count, add current count to our database...
      } else {

	    $query = "UPDATE $table SET counter=counter+$count, time=time()";
    	$db->exec($query) or die("[ FORKING ] Unable to plus the counter!");

    	echo $date." - [ FORKING ] Counter ($counter) + current count ($count) is not sufficient to restore from snapshot. Need: $max_count \n";

    	// Check snapshot setting
    	if($createsnapshot === false){
    		echo $date." - [ SNAPSHOT ] Snapshot setting is disabled.\n";
    	}

    	// If counter + current count equals 0 AND option $createsnapshot is true, create a new snapshot
    	if(($counter + $count) == 0 && $createsnapshot === true){
    		
    		echo $date." - [ SNAPSHOT ] It's safe to create a daily snapshot and the setting is enabled.\n";
    		echo $date." - [ SNAPSHOT ] Let's check if a snapshot was already created today...\n";
    		
    		$snapshots = glob($snapshotDir.'snapshot/shift_db'.date("d-m-Y").'*.snapshot.tar');
			if (!empty($snapshots)) {
			
			    echo $date." - [ SNAPSHOT ] A snapshot for today already exists:\n";
			    foreach($snapshots as $snapshot){
			    	echo ">>> ".$snapshot."\n";
			    }
			    
			    echo $date." - [ SNAPSHOT ] Going to remove snapshots older than $max_snapshots days...\n";
			    	$files = glob($snapshotDir.'snapshot/shift_db*.snapshot.tar');
				  	foreach($files as $file){
				    	if(is_file($file)){
				      		if(time() - filemtime($file) >= 60 * 60 * 24 * $max_snapshots){
				        		if(unlink($file)){
				        			echo $date." - [ SNAPSHOT ] Deleted snapshot $file\n";
				        		}
				      		}
				    	}
				  	}

			    echo $date." - [ SNAPSHOT ] Done!\n";
			
			}else{

				echo $date." - [ SNAPSHOT ] No snapshot exists for today, I will create one for you now!\n";
					
				ob_start();
				$create = passthru("cd $snapshotDir && ./shift-snapshot.sh create");
				$check_createoutput = ob_get_contents();
				ob_end_clean();

				// If buffer contains "OK snapshot created successfully"
				if(strpos($check_createoutput, 'OK snapshot created successfully') !== false){
				
			   		echo $date." - [ SNAPSHOT ] Done!\n";
					
					if($telegramEnable === true){
		   				$msg = "Created daily snapshot on ".gethostname().".";
		   				passthru("curl -s -d 'chat_id=$telegramId&text=$msg' $telegramSendMessage > /dev/null");
		   			}

				}

			}

    	}

      }

// CHECK BALANCE AND SEND IF > $maxBalance
      if($checkBalance === true){
      	echo $date." - [ BALANCE ] Transfer balance option is ENABLED.\n";

      	$myBalance = getBalance($nodeAddress, $myDelegate);
      	if($myBalance > $maxBalance){
      	  if(!empty($myPrivateWallet) && !empty($firstPass)){
      		echo $date." - [ BALANCE ] Balance ($myBalance) exceeds max ($maxBalance). I will transfer ".floor(($myBalance -1) * 100000000)." to $myPrivateWallet.\n";
      		
      		// Donate?
      		if($donate === true){
      			ob_start();
	      		if(!empty($secondPass)){
	      			$sendDonation = passthru("curl -s -k -H 'Content-Type: application/json' -X PUT -d '{\"secret\":\"$firstPass\",\"secondSecret\":\"$secondPass\",\"amount\":".($donateAmount * 100000000).",\"recipientId\":\"$donateAddress\"}' $nodeAddress/api/transactions");
	      		}else{
	      			$sendDonation = passthru("curl -s -k -H 'Content-Type: application/json' -X PUT -d '{\"secret\":\"$firstPass\",\"amount\":".($donateAmount * 100000000).",\"recipientId\":\"$donateAddress\"}' $nodeAddress/api/transactions");
	      		}
				$sendDonation_output = ob_get_contents();
				ob_end_clean();
      		
				if(strpos($sendDonation_output, '"success":true') !== false){
					$array = json_decode($sendDonation_output, true);
					echo $date." - [ DONATION ] Thank you for your donation! Transaction ID: ".$array['transactionId']."\n";
				}
      		}

      		ob_start();
      		if(!empty($secondPass)){
      			$transfer = passthru("curl -s -k -H 'Content-Type: application/json' -X PUT -d '{\"secret\":\"$firstPass\",\"secondSecret\":\"$secondPass\",\"amount\":".floor(($myBalance -1) * 100000000).",\"recipientId\":\"$myPrivateWallet\"}' $nodeAddress/api/transactions");
      		}else{
      			$transfer = passthru("curl -s -k -H 'Content-Type: application/json' -X PUT -d '{\"secret\":\"$firstPass\",\"amount\":".floor(($myBalance -1) * 100000000).",\"recipientId\":\"$myPrivateWallet\"}' $nodeAddress/api/transactions");
      		}
			$transfer_output = ob_get_contents();
			ob_end_clean();

			if(strpos($transfer_output, '"success":true') !== false){
				$array = json_decode($transfer_output, true);
				echo $date." - [ BALANCE ] Transfer completed! Transaction ID: ".$array['transactionId']."\n";

				if($telegramEnable === true){
	   				$msg = gethostname()." - Max balance reached. I transferred ".floor(($myBalance -1) * 100000000)." to $myPrivateWallet.\n
	   				Transaction ID: ".$explorer."tx/".$array['transactionId'];
	   				passthru("curl -s -d 'chat_id=$telegramId&text=$msg' $telegramSendMessage > /dev/null");
	   			}
			}else{
				echo $transfer_output."\n";
				echo $date." - [ BALANCE ] Transfer failed..\n";
			}
		  }else{
		  	echo $date." - [ BALANCE ] Error! You did not enter a private address or forgot your passphrase.\n";
		  }
      	}else{
      		echo $date." - [ BALANCE ] Balance has not reached it's max yet.\n";
      	}
      }else{
      	echo $date." - [ BALANCE ] Transfer balance option is DISABLED.\n";
      }

// Cleaning up your log file(s)
      echo $date." - [ LOGFILES ] Performing log rotation and cleanup...\n";
      rotateLog($logfile, $max_logfiles, $logsize);
