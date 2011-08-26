<?php
    interface WSApp {
    	/**
		 * Set the set the Websocket protocol class
		 * over which this application is being called
		 * This is needed for the application to send data back 
		 * to the client
		 */
    	function setProtocol(WSProtocol $protocol);
		/**
		 * Called whenever there is a message from a client
		 * $msg is an array with the following structure
	 	 *  Array {
	 	 * 		'size': The size in bytes of the data frame
	 	 * 		'frame': A buffer containing the data received
	 	 * 		'binary': boolean indicator true is frame is binary false if frame is utf8
	 	 *  }
		 */
    	function onMessage($msg);
		/**
		 * Called when the client closes connection
		 */
		function onClose();
		/**
		 * Called in the event of an error.
		 */
		function onError($err);
    }
?>