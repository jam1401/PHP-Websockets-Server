<?php
/*
 * Sample Application that implements a simple Echo but shows
 * how to add services through a simple class definition
 */
require_once "./lib/wsapp.interface.php";
    class Foo implements WSApp {
    	 public static $app_name = 'foo';
		 
		 private $protocol;
		 
		function onMessage($msg) {
			$size = $msg['size'];
			$data = $msg['frame'];
			echo "In Foo Class onMessage: just received ".$data." \n";
			// Just Echo it back
		   	$this->protocol->send($msg);
		}
		function onClose() {
			echo "In Foo Class onClose: closing connection \n";
		}
		function onError($err) {
			echo "In Foo Class onError: closing connection \n";
		}
		
		function setProtocol(WSProtocol $protocol) {
			$this->protocol = $protocol;
		}
    }
?>