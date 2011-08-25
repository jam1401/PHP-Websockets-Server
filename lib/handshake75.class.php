<?php
require_once 'wsuser.class.php';
require_once 'handshaker.interface.php';

/**
 * A class to implement version 75 of the handshake
 */
class Handshake75 implements Handshaker {
	/**
	 * Perform the handshake
	 * $user - The user/client that requests the websocket connection
	 * $headers - an array containing the HTTP headers sent
	 */
	function dohandshake(WsUser $user,$headers) {
		
		$origin = $headers['Origin'];
		$host = $headers['Host'];
		$status = $headers['status'];
		$statusFields = explode(' ', $status);
		$resource = $statusFields[1];
		if(isset($headers['Sec-WebSocket-Protocol'])) {
			$app = $headers['Sec-WebSocket-Protocol'];
			if(is_array($appI)) {
			// need to process the Array and find the first match
			}
		} else
			$app = getAppID($resource);
		$user->setAppID($app);
		
		$upgrade  = "HTTP/1.1 101 WebSocket Protocol Handshake\r\n" .
	              "Upgrade: WebSocket\r\n" .
	              "Connection: Upgrade\r\n" .
	              "Sec-WebSocket-Protocol: ". $app . "\r\n" .
	              "Sec-WebSocket-Origin: " . $origin . "\r\n" .
	              "Sec-WebSocket-Location: ws://" . $host . $statusFields[1] . "\r\n" .
	              "\r\n" .
	              "\r\n";
				  
	   	
		socket_write($user->socket(),$upgrade,strlen($upgrade));
		$user->setHandshakeDone();
		;
		$user->setProtocol(new Protocol76());
		
		return;
	}
}
?>