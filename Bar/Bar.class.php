<?php

/*
 * Sample Application that implements a simple Echo but shows
 * how to add services through a simple class definition
 */
require_once "./lib/wsapp.interface.php";
    class Bar implements WSApp {
    	 public static $app_name = 'Bar';
		 
		 private $protocol;
		 
		function onMessage($msg) {
			$size = $msg['size'];
			$data = $msg['frame'];
			echo "In foo onMessage and we been sent ".$data."\n";
		    return $msg;
		}
		function onClose() {
			echo "closing connection \n";
		}
		function onError($err) {
			
		}
		function setProtocol(WSProtocol $protocol) {
			$this->protocol = $protocol;
		}
    }
?>