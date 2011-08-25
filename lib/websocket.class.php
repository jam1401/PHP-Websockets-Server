<?php

require_once 'wsuser.class.php';
require_once 'handshaker.interface.php';
require_once "wsutil.php";
require_once 'handshakehybi.class.php';
require_once 'handshake76.class.php';
require_once 'handshake75.class.php';
require_once "wsexceptions.class.php";
require_once "appfactory.class.php";


/**
 * WebSocket implements the basic websocket protocol handling initial handshaking and also
 * dispatching requests up to the clients bound to the socket.
 */
class WebSocket {
	private $user;
	private $log;
	private $wsapp;
	
	/**
	 * Default constructor 
	 * $user is the WSUser object associated with the request
	 * $log is the path for the log file
	 */
	function WebSocket(WsUser $user, $log) {
		$this->user = $user;
	}
	
	/**
	 * Entry point for all client requests. This function
	 * determines if handshaking has been done and if not selects the
	 * specific handshaking protocol and invokes it.
	 * 
	 * If handshaking has been done this function dispatches the request
	 * to the service bound to the request associated with the user object
	 */
	function handleRequest($socket) {
		// Check the handshake required
		if(!$this->user->handshakeDone()) {
			logToFile($socket."Performing the handshake\n");
			$bytes = @socket_recv($socket, $buffer, 2048, 0);
			if($bytes == 0) {
					WsDisconnect($socket);
					logToFile($socket." DISCONNECTED!");
					return;
			}
			$headers = WsParseHeaders2($buffer);
			if(count($headers) == 0 || !isset($headers['Upgrade'])) {
				// Not good send back an error status
				$this->sendFatalErrorResponse();
				return;
			} else {
				if(strtolower($headers['Upgrade']) != 'websocket')
					$this->sendFatalErrorResponse();
				// now get the handshaker for this request
				$hs = $this->getHandshaker($headers);
				$hs->dohandshake($this->user, $headers);
				logToFile($socket."Handshake Done\n");
				return;
			}
		}
		$appID = $this->user->appId();
		if($appID === "_ECHO_") {
			try {
				$protocol = $this->user->protocol();
				if(isset($protocol)) {
					$protocol->setSocket($socket);
					$result = $protocol->read();
					$bytesRead = $result['size'];
					
					if($bytesRead !== -1 && $bytesRead !== -2) {
						$protocol->send($result);
					} else {
						// badness must close
						$protocol->close();
						//WsDisconnect($socket);
						return;
					}
				} else {
					$this->sendFatalErrorResponse();
					return;
				}
			}
			catch (WSClientClosedException $e) {
				return;
			}
		} else {
			// Load the application class and send the message
			try {
				AppFactory::autoload($appID);
				$this->wsapp = AppFactory::create($appID);
				$protocol = $this->user->protocol();
				if(isset($protocol)) {
					$protocol->setSocket($socket);
					$this->wsapp->setProtocol($protocol);
					$result = $protocol->read();
					$bytesRead = $result['size'];
					if($bytesRead !== -1 && $bytesRead !== -2) {
						$this->wsapp->onMessage($result);
						/* if($resp['size'] != 0) {
							logToFile($socket."Calling send on APP\n");
							$protocol->send($resp);
						} */
					} else {
						$this->wsapp->onError();
						$protocol->close();
						WsDisconnect($socket);
						return;
					}
				} else {
					$this->sendFatalErrorResponse();
				}
			}
			catch (WSClientClosedException $e) {
				if(isset($this->wsapp))
					$this->wsapp->onClose();
				return;
			}
			catch (WSAppNotInstalled $e) {
				//$this->sendFatalErrorResponse();
				WsDisconnect($socket);
				return;
			}
		}
		return;
	}
	
	/**
	 * Takes the appropriate action to close the connection down
	 */
	private function sendFatalErrorResponse() {
		// Just close the socket if in handhake mode
		if(!$this->user->handshakeDone()) {
			WsDisconnect($this->user->socket());
			return;
		} else {
			//send a status code and then close
		}
	}
	
	/**
	 * Looks at the headers to determine which handshaker to 
	 * use
	 * $headers are the headers in the request
	 */
	private function getHandshaker($headers) {
		// Lets check which handshaker we need
		if(isset($headers['Sec-WebSocket-Version'])) {
			if($headers['Sec-WebSocket-Version'] === '8') {
				// This is the HyBI handshaker
				return new HandshakeHyBi();
			}
			// Not a version we support
			$this->sendFatalErrorResponse();
		} else if(isset($headers['Sec-WebSocket-Key1']) && isset($headers['Sec-WebSocket-Key2'])) {
			// Draft 76
			return new Handshake76();
		}
		// Must be draft 75
		
		return new Handshake75();
	}
	
}
?>