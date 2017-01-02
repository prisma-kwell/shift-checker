# shift-checker
Checks the status of your Shiftnrg delegate

This script checks the status of your Shiftnrg Delegate by using shift_manager.bash
When status is not "OK" it will (re)start your delegate.
 
This script will also check whether your node has forked or not. 
When forked, it will stop Shift, restore to previous snapshot, and start Shift again.
Of course this requires shift-snapshot.sh:
	https://github.com/mrgrshift/shift-snapshot
  
There are some echo lines in this file. 
When you redirect output to a log file in your crontab, these lines will show up. 
See section Example crontab for more information.

Be sure to run this script after:
* You have installed shift-snapshot
* You have created a snapshot with shift-snapshot

## Prerequisites
Be sure that your php.ini allows passthru(). It's default that it does though, so just check if this script is not working.
```
apt install php php-cli php-mbstring php-sqlite3
```

## Example crontab
```
* * * * * php /scripts/checkdelegate.php >> /scripts/checkdelegate.log 2>&1
```

## Contact 
* Twitter: @lepetitjan 
* Shiftnrg Slack: https://shiftnrg.slack.com/team/jan 
