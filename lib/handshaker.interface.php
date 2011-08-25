<?php

require_once 'wsuser.class.php';

/**
 * Abstract interface to be used by all Hanadshake implementations
 */
interface Handshaker {
	/**
	 * Perform a Websocket Handshake
	 * 
	 * $user - the user object associated with this request. 
	 * $headers - array of the headers sent by the user for purposes of performing the handshake
	 */
	function dohandshake(WsUser $user,$headers);
}
?>