<?php

echo "[ IPFS & DAPP Status ]\n";

if($ipfsEnable === true){

//check if ipfs check enabled
echo "\t\t\tLet's check if ipfs still running...      ";

// Check status with shift_manager.bash. Use PHP's ob_ function to create an output buffer
	ob_start();
  $check_status = passthru("cd $pathtoapp && bash shift_manager.bash check_ipfs | tail -n +2 | cut -c6");
	$check_output = ob_get_contents();
	ob_end_clean();

// If status is not OK...
  if(strpos($check_output, "n") === false){
   		
	  // Echo something to our log file
   	echo "\t\t\tIpfs not running. Let me restart it for you...\n";
   	
   		passthru("cd $pathtoapp && bash shift_manager.bash start_ipfs >/dev/null");
   
  }else{
  	echo "\t\t\tipfs is still running...\n";
  }

}
// check dapp
if($dappEnable === true){
// Check status api call. Use PHP's ob_ function to create an output buffer
	ob_start();
  $check_status = passthru("curl -s -k -X GET http://localhost:9405/api/dapps/launched | cut -b30");
	$check_output = ob_get_contents();
	ob_end_clean();

// If status is not OK...
  if(strpos($check_output, "3") === false){
   		
	  // Echo something to our log file
   	echo "\t\t\tPhantom is not running. Let me restart it for you...\n";
   	echo "sorry I dont know how yet";
   		//passthru("cd $pathtoapp && bash shift_manager.bash start_ipfs >/dev/null");
   
  }else{
  	echo "\t\t\tPhantom is still running...\n";
  }

}







