# shift-checker
This script checks the status of your Shiftnrg Delegate by using shift_manager.bash.<br>
When status is not "OK" it will restart your delegate.
 
This script will also check whether your node has forked or not.<br>
When forked, it will stop Shift, restore to previous snapshot, and start Shift again.
  
There are some echo lines in this file.<br>
When you redirect output to a log file in your crontab, these lines will show up. <br>
See section Example crontab for more information.

Be sure to run this script after:
* You have installed shift-snapshot
* You have created a snapshot with shift-snapshot

## Prerequisites
Be sure that your php.ini allows passthru(). It's default that it does though, so just check if this script is not working.
```
apt install php php-cli php-mbstring php-sqlite3
```
* shift-snapshot.sh: https://github.com/mrgrshift/shift-snapshot

## Installation
```
git clone https://github.com/lepetitjan/shift-checker.git
```
* Change settings inside checkdelegate.php to match your needs
* Edit your crontab with the example below

## Example crontab
```
* * * * * php ~/shift-checker/checkdelegate.php >> ~/shift-checker/checkdelegate.log 2>&1
```

## Contact 
* Twitter: [@lepetitjan](https://twitter.com/lepetitjan) 
* Shiftnrg Slack: https://shiftnrg.slack.com/team/jan 

## Contributors
Seatrips (create snapshot when status is okay)
* Twitter: [@seatrips<br>](https://twitter.com/seatrips)
* Shiftnrg Slack: https://shiftnrg.slack.com/team/seatrips

Mrgr (Shift snapshots)
* Shiftnrg Slack: https://shiftnrg.slack.com/team/mrgr
* Github: https://github.com/mrgrshift
