<?php

require_once 'wsuser.class.php';
/**
 * Creates and returns the socket on which the server will listen
 * $address is the address at which the server is listening
 * $port is the port at which the server is listening
 * $log is the log file reference.
 */
function GetSocket($address, $port, $log){
  
  $master=socket_create(AF_INET, SOCK_STREAM, SOL_TCP)     or die("socket_create() failed");
  socket_set_option($master, SOL_SOCKET, SO_REUSEADDR, 1)  or die("socket_option() failed");
  socket_bind($master, $address, $port)                    or die("socket_bind() failed");
  socket_listen($master,20)                                or die("socket_listen() failed");
  date_default_timezone_set('America/Los_Angeles');
  logToFile("Server Started : ".date('Y-m-d H:i:s')."\n");
  logToFile("Master socket  : ".$master."\n");
  logToFile("Listening on   : ".$address." port ".$port."\n\n");
  return $master;
}

/**
 * Simple log function which appends a message to a log file
 * $log is the file path for the log file
 * $msg is the message to be added to the log file
 */
function logToFile($msg) {
	global $log;
	
	file_put_contents($log, $msg, FILE_APPEND);
}

function WsConnect($socket){
  global $sockets, $users;
  $user = new WsUser();
  $user->setSocket($socket);
  array_push($users,$user);
  array_push($sockets,$socket);
}

function WsDisconnect($socket){
  global $sockets, $users;
  logToFile("Disconnecting ".$socket." \n\n");
  $found=null;
  $n=count($users);
  for($i=0;$i<$n;$i++){
    if($users[$i]->socket()==$socket){ $found=$i; break; }
  }
  if(!is_null($found)){ array_splice($users,$found,1); }
  $index = array_search($socket,$sockets);
  socket_close($socket);
  if($index>=0){ array_splice($sockets,$index,1); }
}

function WsGetUserBySocket($socket){
  global $users;
  $found=null;
  foreach($users as $user){
    if($user->socket()==$socket){ $found=$user; break; }
  }
  return $found;
}

function WsParseHeaders( $header )
{
    $retVal = array();
    $fields = explode("\r\n", preg_replace('/\x0D\x0A[\x09\x20]+/', ' ', $header));
    foreach( $fields as $field ) {
        if( preg_match('/([^:]+): (.+)/m', $field, $match) ) {
            $match[1] = preg_replace('/(?<=^|[\x09\x20\x2D])./e', 'strtoupper("\0")', strtolower(trim($match[1])));
            if( isset($retVal[$match[1]]) ) {
                $retVal[$match[1]] = array($retVal[$match[1]], $match[2]);
            } else {
                $retVal[$match[1]] = trim($match[2]);
            }
        }
    }
    return $retVal;
}

function WsParseHeaders2($headers=false){
    if($headers === false){
        return false;
        }
	$statusDone = false;
    $headers = str_replace("\r","",$headers);
    $headers = explode("\n",$headers);
    foreach($headers as $value){
        $header = explode(": ",$value);
		if(count($header) == 1) {
        // if($header[0] && !$header[1]){
        	if(!$statusDone) {
            	$headerdata['status'] = $header[0];
				$statusDone = true;
			} else {
				$headerdata['body'] = $header[0];
				//return $headerdata;
			}
        }
        elseif($header[0] && $header[1]){
            $headerdata[$header[0]] = $header[1];
            }
        }
    return $headerdata;
}

function getAppID($resource) {
	$fields = explode("?", $resource);
	if(count($fields) === 2)
		return $fields[1];
	return '_ECHO_';
	
}
?>