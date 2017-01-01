<?php
	/**
	 * This script checks the status of your Shiftnrg Delegate by using shift_manager.bash
	 * When status is not "OK" it will (re)start your delegate.
	 * 
	 * This script will also check whether your node has forked or not. 
	 * When forked, it will stop Shift, restore to previous snapshot, and start Shift again.
	 * Of course this requires shift-snapshot.sh:
	 * 		https://github.com/mrgrshift/shift-snapshot
	 *  
	 * There are some echo lines in this file. 
	 * When you redirect output to a log file in your crontab, these lines will show up. 
	 * See section Example crontab for more information.
	 * 
	 * Extra note: 
	 * Be sure to run this script after:
	 * 	- You have installed shift-snapshot
	 * 	- You have created a snapshot with shift-snapshot
	 * 
	 * Contact me on Twitter: @lepetitjan 
	 * or Shiftnrg Slack: https://shiftnrg.slack.com/team/jan
	 * 
	 * @author Jan
	 * @link https://github.com/jeeweevee/shift-checker
	 * @license https://github.com/jeeweevee/shift-checker/blob/master/LICENSE
	 */

/* GENERAL SETTINGS
____________________ */

	$date			= date("Y-m-d H:i:s");					// Current date
	$baseDir		= getcwd()."/";							// Folder which contains THIS file
	$database		= "check_fork.sqlite3";					// Database name to use
	$table 			= "forks";								// Table name to use
	$msg 			= "Failed to find common block with";	// Message that is printed when forked
	$pathtoapp		= "/home/azureuser/shift/";				// Path to your Shift installation
	$logfile 		= $pathtoapp."logs/shift.log";			// Needs to be a FULL path, so not ~/shift
	$linestoread	= 50;									// How many lines to read from the end of $logfile
	$max_count 		= 10;									// How may times $msg may occur


/* PREREQUISITES
____________________ */

require('functions.php');


/* CHECK STATUS
____________________ */

// Check status with shift_manager.bash
   $check_status = passthru("cd $pathtoapp && bash shift_manager.bash status | cut -z -b1-3");

// If status is not OK...
   if($check_status != "OK"){
   		
	// Echo something to our log file
   		echo $date." - Delegate not running/healthy. Let me restart it for you...";
   		passthru("forever stopall && cd $pathtoapp && forever start app.js");
   
   }else{
   
   		echo $date." - Still running...";
   
   }


/* CHECK IF FORKED
____________________ */

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
        	echo $date." - No rows exist...Adding!\n";
        	
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

      }
