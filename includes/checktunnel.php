<?php

echo "[ Tunnel ]\n";
if($tunnelEnable === true){

$ip = "http://127.0.0.1"; //IP or web addy

echo("checking tunnel....   "); 
$find = array("http://","https://");
      $up = ping(str_replace($find,"",$masternode), $localPort); 
//Do this if it is open
      if($up){
	echo( "Tunnel ok\n" );
	
}
else{
//Do this if it is closed
	echo( "Restarting Tunnel\n" );

	passthru("ssh -f -N -q -$sshPort  -i ~/rsa.pvt -L $localPort:localhost:$remotePort slave@$tunnelIP >> /dev/null");
}
	
}


