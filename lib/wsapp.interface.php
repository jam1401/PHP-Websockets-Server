<?php
require_once "messagetranscoder.interface.php";
    interface WSApp {
    	function setProtocol(WSProtocol $protocol);
    	function onMessage($msg);
		function onClose();
		function onError($err);
    }
?>