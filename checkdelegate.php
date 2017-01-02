<?php
	/**
	 * @author Jan
	 * @link https://github.com/lepetitjan/shift-checker
	 * @license https://github.com/lepetitjan/shift-checker/blob/master/LICENSE
	 */

/* GENERAL SETTINGS
____________________ */

	$date			= date("Y-m-d H:i:s");				// Current date
	$baseDir		= dirname(__FILE__)."/";			// Folder which contains THIS file
	$lockfile		= "checkdelegate.lock";				// Name of our lock file
	$database		= "check_fork.sqlite3";				// Database name to use
	$table 			= "forks";					// Table name to use
	$msg 			= "Failed to find common block with";		// Message that is printed when forked
	$pathtoapp		= "/home/lepetitjan/shift/";			// Path to your Shift installation
	$logfile 		= $pathtoapp."logs/shift.log";			// Needs to be a FULL path, so not ~/shift
	$linestoread		= 50;						// How many lines to read from the end of $logfile
	$max_count 		= 10;						// How may times $msg may occur

	// Snapshot settings
	$createsnapshot		= true;						// Do you want to create daily snapshots?
	$max_snapshots		= 3;						// How many snapshots to preserve? (in days)

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
	
		echo $date." - Lock file age is older than 10 minutes. Going to touch it and continue with the script.\n";
		
		if (!touch($lockfile)){
		  exit("Error touching $baseDir.$lockfile\n");
		}

	// If file is younger than 10 minutes, exit!
	}else{
		exit("A previous job is still running...\n");
	}

}

/* CHECK STATUS
____________________ */

echo $date." - Let's check if our delegate is still running...\n";

// Check status with shift_manager.bash. Use PHP's ob_ function to create an output buffer
	ob_start();
   	$check_status = passthru("cd $pathtoapp && bash shift_manager.bash status | cut -z -b1-3");
	$check_output = ob_get_contents();
	ob_end_clean();

// If status is not OK...
   if(strpos($check_output, 'OK') === false){
   		
	// Echo something to our log file
   		echo $date." - Delegate not running/healthy. Let me restart it for you...\n";
   		echo "Stopping all forever processes...\n";
   			passthru("forever stopall");
   		echo "Starting Shift forever proces...\n";
   			passthru("cd $pathtoapp && forever start app.js");
   
   }else{
   
   		echo $date." - Delegate is still running...\n";
   
   }


/* CHECK IF FORKED
____________________ */

echo $date." - Going to check for forked status now...\n";

// Set the database to save our counts to
    $db = new SQLite3($baseDir.$database) or die('Unable to open database');
 
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
        	echo $date." - No rows exist in our table to update the counter...Adding a row for you.\n";
        	
        	$insert = "INSERT INTO $table (counter, time) VALUES ('0', time())";
        	$db->exec($insert) or die('Failed to add row!');
      	
      	}

// Tail shift.log
	$last = tailCustom($logfile, $linestoread);

// Count how many times the fork message appears in the tail
	$count = substr_count($last, $msg);

// Get counter value from our database
    $check_count 	= $db->query("SELECT * FROM $table LIMIT 1");
    $row          	= $check_count->fetchArray();
    $counter      	= $row['counter'];

// If counter + current count is greater than $max_count, take action...
    if (($counter + $count) >= $max_count) {

        echo $date." - Hit max_count. I am going to restore from a snapshot.\n";

       	passthru("cd $pathtoapp && forever stop app.js");
       	passthru("cd $pathtoapp && echo y | ./shift-snapshot.sh restore");
       	passthru("cd $pathtoapp && forever start app.js");

        echo $date." - Finally, I will reset the counter for you...\n";

        $query = "UPDATE $table SET counter='0', time=time()";
    	$db->exec($query) or die('Unable to set counter to 0!');

// If counter + current count is not greater than $max_count, add current count to our database...
      } else {

	    $query = "UPDATE $table SET counter=counter+$count, time=time()";
    	$db->exec($query) or die('Unable to plus the counter!');

    	echo $date." - Counter ($counter) + current count ($count) is not sufficient to restore from snapshot. Need: $max_count \n";

    	// Check snapshot setting
    	if($createsnapshot === false){
    		echo $date." - Snapshot setting is disabled.\n";
    	}

    	// If counter + current count equals 0 AND option $createsnapshot is true, create a new snapshot
    	if(($counter + $count) == 0 && $createsnapshot === true){
    		
    		echo $date." - It's safe to create a daily snapshot and the setting is enabled.\n";
    		echo $date." - Let's check if a snapshot was already created today...\n";
    		
    		$snapshots = glob($pathtoapp.'snapshot/shift_db'.date("d-m-Y").'*.snapshot.tar');
			if (!empty($snapshots)) {
			
			    echo $date." - A snapshot for today already exists:\n";
			    	print_r($snapshots)."\n";
			    
			    echo $date." - Going to remove snapshots older than $max_snapshots days...\n";
			    	$files = glob($pathtoapp.'snapshot/shift_db*.snapshot.tar');
				  	foreach($files as $file){
				    	if(is_file($file)){
				      		if(time() - filemtime($file) >= 60 * 60 * 24 * $max_snapshots){
				        		if(unlink($file)){
				        			echo $date." - Deleted snapshot $file\n";
				        		}
				      		}
				    	}
				  	}

			    echo $date." - Done!\n";
			
			}else{

				echo $date." - No snapshot exists for today, I will create one for you now!\n";
				passthru("cd $pathtoapp && ./shift-snapshot.sh create");
				echo $date." - Done!\n";

			}

    	}

      }
