# shift-checker
Checks the status of your Shiftnrg delegate

## Prerequisites
	- apt install php php-cli php-mbstring php-sqlite3
	- Be sure that your php.ini allows passthru(). It's default that it does though, so just check if this script is not working.

## Example crontab
```
	* * * * * php /scripts/checkdelegate.php >> /scripts/checkdelegate.log 2>&1
```