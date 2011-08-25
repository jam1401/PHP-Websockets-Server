<?php
require_once 'wsuser.class.php';
require_once 'handshaker.interface.php';
require_once 'hybitranscoder.class.php';
require_once "protocolhybi.class.php";

/**
 * Performs the HyBi handshake 
 */
class HandshakeHyBi implements Handshaker {
	
	/**
	 * Perform the HyBi Handshake operation
	 * 
	 * $user - The user/client that is trying to establish connection
	 * $headers - An array of headers sent in the Websocket connect request
	 */
	function dohandshake(WsUser $user,$headers) {
		// Get the key sent from the client
		$strkey1 = $headers['Sec-WebSocket-Key'];
		// Append the Magic ID
		$keyPlusMagic = $strkey1 . "258EAFA5-E914-47DA-95CA-C5AB0DC85B11";
		
		// Get the raw sha1 then encode it
		$shaAcceptKey = sha1($keyPlusMagic, true);
		$socketAccept = base64_encode($shaAcceptKey);
	
		// Grab the rest of the headers needed
		if(isset($headers['Origin']))
			$origin = $headers['Origin'];
		if(isset($headers["Sec-WebSocket-Origin"]))
			$origin = $headers["Sec-WebSocket-Origin"];
		$host = $headers['Host'];
		$status = $headers['status'];
		$statusFields = explode(' ', $status);
		$resource = $statusFields[1];
		if(isset($headers['Sec-WebSocket-Protocol'])) {
			$app = $headers['Sec-WebSocket-Protocol'];
			if(is_array($app)) {
				// @TODO - should find the first matching APP
				// Just use the first specified for now
				$app = $app[0];
			}
		} else
			$app = getAppID($resource);
		$user->setAppID($app);
		
		if(isset($headers['Sec-WebSocket-Extensions'])) {
			$exts = explode(',', $headers['Sec-WebSocket-Extensions']);
		}
		
		
	
		// Now create the upgrade response
		$upgrade  = "HTTP/1.1 101 Switching Protocols\r\n" .
	              "Upgrade: WebSocket\r\n" .
	              "Connection: Upgrade\r\n" .
	              "Sec-WebSocket-Version: 8\r\n";
		if(isset($origin)) {
			$upgrade .= "Sec-WebSocket-Origin: " . $origin . "\r\n";
		}		  
        if(isset($headers['Sec-WebSocket-Protocol'])) {
        	$upgrade = $upgrade."Sec-WebSocket-Protocol: ". $app . "\r\n";
        }
		
		
		if(isset($headers['Sec-WebSocket-Extensions'])) {
			//@TODO - need to process the Extensions and figure out what is supported
			//$upgrade = $upgrade."Sec-WebSocket-Extensions: ". $exts[0] . "\r\n";
		}
	    $upgrade = $upgrade."Sec-WebSocket-Accept: " . $socketAccept . "\r\n" .
	              "\r\n";
				  
		
		//socket_write($user->socket(),$upgrade.chr(0),strlen($upgrade.chr(0)));
		socket_write($user->socket(),$upgrade,strlen($upgrade));
		$user->setHandshakeDone();
		$user->setProtocol(new ProtocolHyBi());
		return;
	}
}
?>