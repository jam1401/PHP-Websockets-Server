<?php
  require_once "wsprotocol.interface.php";
  require_once "wsexceptions.class.php";
  
  class Protocol76 implements WSProtocol {
  	private $socket;
	private $state = 0;
	private $frame = "";
	private $framelength = 0;
	
  	function setSocket($socket) {
  		$this->socket = $socket;
  	}
	
	function read() {
		$messageLength = 0;
		$out = array();
		
		$bytes = @socket_recv($this->socket, $buffer, 2048, 0);
		if($bytes == 0) {
			echo "Nothing in this request\n";
			WsDisconnect($this->socket);
			logToFile($this->socket." DISCONNECTED!");
			throw new WSClientClosedException();
		}
		
		for($i=0, $l = $bytes; $i < $l ; $i++) {
			$b = $buffer[$i];
			if ($this->state === 0) {
				if(ord($b & 0x80) === 128) {
					$this->state = 2;
				} else {
					$this->state = 1;
				}
			} else if ($this->state === 1) {
				if(ord($b) === 255) {
					$this->state = 0;
					$out['size'] = $this->framelength;
					$out['binary'] = false;
					$out['frame'] = $this->frame;
					$this->framelength = 0;
					$this->frame = "";
					return $out;
				} else {
					$this->frame .= $b;
					$this->framelength++;
				}
				
			} else if ($this->state === 2) {
				echo "Oops looks like I got a close\n";
				if (ord($b) === 0) {
					throw new WSClientClosedException();
				}		
			}
		}
		// Got a bad request so terminate it
		WsDisconnect($this->socket);
		$out['size'] = -1;
		throw new WSClientClosedException();
	}
	function send($data) {
		$rawBytesSend = $data['size'] + 2;
		$packet = pack('C', 0x00);
		
		if($data['size'] !== 0) {
			for($i = 0; $i < $data['size']; $i++) {
				$packet .= $data['frame'][$i];
			}
		}
		$packet .= pack('C', 0xff);
		$bytesSent = socket_write($this->socket, $packet, strlen($packet));
		return $bytesSent;
	}
	
	function close() {
		$data = array();
		$data['size'] = 0;
		$this->send($data);
	}
	
  }
?>