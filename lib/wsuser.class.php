<?php

require_once 'wsuser.class.php';
require_once 'handshaker.interface.php';
require_once "wsprotocol.interface.php";

class WsUser {
	
	private $id;
	private $socket;
	private $handshake_done = false;
	private $transcoder;
	private $appId;
	private $protocol;
	
	/**
	 * Class Constructor for the WsUser Object
	 * 
	 */
	function WsUser() {
		$this->id = uniqid();
	}
	
	function id() {
		return $this->id;
	}
	
	function setSocket($socket) {
		$this->socket = $socket;
	}
	
	function socket() {
		return $this->socket;
	}
	
	function setHandshakeDone() {
		$this->handshake_done = true;
	}
	
	function handshakeDone() {
		return $this->handshake_done;
	}
	
	function setTranscoder(MessageTranscoder $transcoder) {
		$this->transcoder = $transcoder;
	}
	
	function transcoder() {
		return $this->transcoder;
	}
	
	function setProtocol(WSProtocol $protocol) {
		$this->protocol = $protocol;
	}
	
	function protocol() {
		return $this->protocol;
	}
	
	function setAppID($app) {
		$this->appId = $app;
	}
	
	function appId() {
		return $this->appId;
	}
	
}
?>