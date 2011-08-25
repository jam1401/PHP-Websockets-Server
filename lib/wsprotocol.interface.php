<?php

/**
 * Interface that must be implemented by every protocol handler
 */
interface WSProtocol {
	/**
	 * setSocket enables the procotol to cache the socket on which the request is being made
	 * $socket - this is the socket on which a request has been received
	 */
	function setSocket($socket);
	
	/**
	 * Reads a packet of data from the websocket and parses it into the following structure
	 * 
	 * Array {
	 * 		'size': The size in bytes of the data frame
	 * 		'frame': A buffer containing the data received
	 * 		'binary': boolean indicator true is frame is binary false if frame is utf8
	 * }
	 * 
	 * which must be returned if there is an error then the size field should be set to -1;
	 */
	function read();
	/**
	 * Sends a packet of data to a client over the websocket.
	 * 
	 * 	$data is an array with the following structure
	 * Array {
	 * 		'size': The size in bytes of the data frame
	 * 		'frame': A buffer containing the data received
	 * 		'binary': boolean indicator true is frame is binary false if frame is utf8
	 * }
	 * 
	 * 
	 */
	function send($data);
	/**
	 * Sends a server initiated close to the client
	 */
	function close();
}
?>