<?php
echo "[ LOGFILES ] \n";
echo "\t\t\tPerforming log rotation and cleanup...\n";
rotateLog($logfile, $max_logfiles, $logsize);

// Remove lock file
if(!unlink($lockfile)){
echo "[ LOCKFILE ] Unable to remove lock file!\n";
}