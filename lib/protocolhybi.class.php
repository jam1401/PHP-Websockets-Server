<?php
  require_once "wsprotocol.interface.php";
  require_once "wsexceptions.class.php";
  
  /**
   * Implements version 10 of the current HyBi protocol
   */
  class ProtocolHyBi implements WSProtocol {
  	private $socket;
	private $state = 0;
	private $frameFin = false;
	private $frameR1 = false;
	private $frameR2 = false;
	private $frameR3 = false;
	private $frameOp = 0;
	private $frameMasked = false;
	private $frameMaskOffset = 0;
	private $frameLength = 0;
	private $bufferPos = 0;
	private $buffer = '';
	private $length = 0;
	private $lastPing;
	private $themessage ='';
	
	
	/**
	 * Sets the socket to be used by the class
	 */
  	function setSocket($socket) {
  		$this->socket = $socket;
  	}
	
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
	function read() {
		
		// Read a block of data
		$bytes = @socket_recv($this->socket, $buffer, 2048, 0);
		if($bytes == 0) {
			WsDisconnect($this->socket);
			logToFile($this->socket."Client closed DISCONNECTED!");
			throw new WSClientClosedException();
		}
		
		// Append to data read to the class buffer
		$this->buffer .= $buffer;
		$length = $this->length + strlen($buffer);
		
		// Now parse the frame
		while($length > 0) {
			$lastPos = $this->bufferPos;
			$result = $this->parse($length);
			if(isset($result['done'])) {
				if($result['done'] === false) {
					logToFile($this->socket."Parser Error");
					$out['size'] = -1;
					$out['frame'] = "";
					$out['binary'] = false;
					return $out;
				} else if ($result['done'] === true) {
					$this->length = $bytes - $this->bufferPos;
					$this->buffer = substr($this->buffer, $this->bufferPos, $this->length);
					$this->bufferPos = 0;
					$length = 0; // stop parsing
				}
			}
		}
		return $result;
	}

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
	function send($data) {
		$databuffer = array();
		$rawBytesSend = $data['size'] + 2;
		$packet;
		$sendlength = $data['size'];
		
		
		if($sendlength > 65535) {
			// 64bit
			array_pad($databuffer, 10, 0);
			$databuffer[1] = 127;
			$lo = $sendlength | 0;
			$hi = ($sendlength - $lo) / 4294967296;
			
			$databuffer[2] = ($hi >> 24) & 255;
			$databuffer[3] = ($hi >> 16) & 255;
			$databuffer[4] = ($hi >> 8) & 255;
			$databuffer[5] =  $hi & 255;
			
			$databuffer[6] = ($lo >> 24) & 255;
			$databuffer[7] = ($lo >> 16) & 255;
			$databuffer[8] = ($lo >> 8) & 255;
			$databuffer[9] =  $lo & 255;
			
			$rawBytesSend += 8;
		} else if ($sendlength > 125) {
			// 16 bit
			array_pad($databuffer, 4, 0);
			$databuffer[1] = 126;
			$databuffer[2] = ($sendlength >> 8) & 255;
			$databuffer[3] = $sendlength & 255;
			
			$rawBytesSend += 2;
		} else {
			array_pad($databuffer, 2, 0);
			$databuffer[1] = $sendlength;
		}
		
		// Set op and find
		$databuffer[0] = (128 + ($data['binary'] ? 2 : 1));
		$packet = pack('c', $databuffer[0]);
		// Clear masking bit
		//$databuffer[1] &= ~128;
		
	
		// write out the packet header
		for($i=1; $i < count($databuffer); $i++) {
			//$packet .= $databuffer[$i];
			$packet .= pack('c', $databuffer[$i]);
		}
		
		// write out the packet data
		for($i=0; $i < $data['size']; $i++) {
			$packet .= $data['frame'][$i];
		}
		
		$len = strlen($packet);
		$offset = 0;
		while($offset < $len) {
			$bytes_sent = socket_write($this->socket,substr($packet, $offset), $len - $offset);
			$offset += $bytes_sent;
		}
		
		return $offset;
	}

	/**
	 * Send a ping to the client
	 */
	function ping() {
		$this->lastPing = time();
		$pingval = "";
		
		$pingval = pack('C', 0x89);
		$pingval .= pack('C', 0x03);
		$pingval .= "Foo";
		socket_write($socket,$pingval, strlen($pingval));
	}
	
	/**
	 * Sends a server initiated close to the client
	 */
	function close() {
		$this->buffer = '';
		$this->bufferPos = 0;
		$close = pack('C', 0x88);
		socket_write($this->socket,$close, strlen($close));
		WsDisconnect($this->socket);
	}
	
	
	/**
	 * Internal function for parsing the data framing
	 */
	private function parse($length) {
		// figure out where we are in the buffer
		$bytes = $length - $this->bufferPos;
		$this->themessage ='';
		$result = array();
		
		// Just starting up, need to read the frame header
		if ($this->state === 0 && $bytes >= 1) {
			$byte = ord($this->buffer[$this->bufferPos++]);
			$this->frameFin = ($byte & 1) === 1;
			$this->frameOp = $byte & 15;
			$byte &= 240;
			
			
			// Reserved Frame check
			if(($byte & 2) === 2 || ($byte & 4) === 4 || ($byte & 8) === 8) {
				// Not a good frame so bail
				$this->state = -1;
			} else if ($this->frameOp === 8) { // closing frame
				// This is a close frame
				$this->state = 0;
				throw new WSClientClosedException();
			} else if ($this->frameOp === 10) { // Ping frame
				// The client sent us a ping
				$this->state = 1;
			} else if ($this->frameOp !== 1 && $this->frameOp !== 2
							&& $this->frameOp !== 9) { // Unused Ops
				// The client is using unknown options so bail
				$this->state = -1;		
			} else {
				// Probably we have some data
				$this->state = 1;
			}
		} else if($this->state === 1 && $bytes >=1) {
			// Processing the packet
			$byte = ord($this->buffer[$this->bufferPos++]);
			
			// Clients are supposed to mask the data they send so checking here
			$this->frameMasked = $this->frameOp !== 10 ? (($byte & 1) === 1 || true) : false;
			$this->frameLength = $byte & 127;
			
			
			if($this->frameLength <= 125) {
				// short frame just pass it on
				$this->state = $this->frameMasked ? 4 : 5;
			} else if ($this->frameLength === 126) {
				// The length field is 16 bits
				$this->frameLength = 0;
				$this->state = 2;
			} else if ($this->frameLength === 127) {
				// The length field is 64 bits
				$this->frameLength = 0;
				$this->state = 3;
			} else {
				$this->state = -1;
			}
		} else if($this->state === 2 && $bytes >=2) { // Read 16 bit length
			// Need to do a bit of math
			$this->frameLength = $this->buffer[$this->bufferPos + 1] + ($this->buffer[$this->bufferPos] << 8);
			$this->bufferPos += 2;
			$this->state = $this->frameMasked ? 4 : 5;
			
		} else if($this->state === 3 && $bytes >= 8) {
			// Need to do a lot of math
			// Read 64 Bit Length
			$hi = ($this->buffer[$this->bufferPos] << 24)
					+ ($this->buffer[$this->bufferPos + 1] << 16)
					+ ($this->buffer[$this->bufferPos + 2] << 8 )
					+ $this->buffer[$this->bufferPos + 3];
					
			$lo = ($this->buffer[$this->bufferPos + 4] << 24)
					+ ($this->buffer[$this->bufferPos + 5] << 16)
					+ ($this->buffer[$this->bufferPos + 6] << 8 )
					+ $this->buffer[$this->bufferPos + 7];
					
			$this->frameLength = ($hi * 4294967296) + $lo;
			$this->bufferPos += 8;
			$this->state = $this->frameMasked ? 4: 5;
		} else if ($this->state === 4 && $bytes >= 4) {
			// Read the mask
			$this->frameMaskOffset = $this->bufferPos;
			$this->bufferPos += 4;
			$this->state = 5;
		} else if ($this->state === 5 && $bytes >= $this->frameLength) {
			// check if this is binary data
			$result['binary'] = $this->frameOp === 2;
			if($this->frameLength > 0) {
				if($this->frameMasked) {
					$i = 0;
					while($i < $this->frameLength) {
						// Un-mask the data
						$chr = $this->buffer[$this->bufferPos + $i] ^ $this->buffer[$this->frameMaskOffset + ($i % 4)];
						$this->buffer[$this->bufferPos + $i] = $chr;
						$i++;
					}
				}
				
				$this->themessage .= substr($this->buffer, $this->bufferPos, $this->frameLength);
			} else {
				// There is no data in this frame
				$this->themessage .= '';
				$this->frameLength  += 0;
			}
			
			// reset the state and advance the bufferPos
			$this->state = 0;
			$this->bufferPos += $this->frameLength;
			
			// check for ping
			if($this->frameOp === 9) {
				// Send a pong
				$result['size'] = $this->frameLength;
				$result['frame'] = $this->themessage;
				
				$this->send($result);
				$result['size'] = 0;
				$result['frame'] = "";
				$result['buffer'] = "";
			} else if($this->frameOp !== 10) {
				// its a message
				$result['size'] = $this->frameLength;
				$result['frame'] = $this->themessage;
				
			}
			$result['done'] = true;
			return $result;
		} else {
			// No data received even though there was supposed to be some
			$result['done'] = false;
			return $result;
		}
		
		if ($this->state === -1) {
			// There has been a problem with the data we received so bail.
			logToFile($this->socket."Problem with parsing the data received");
			$result['done'] = false;
			$result['size'] = -1;
			return $result;
		}
		
	}

	/**
	 * utility function
	 */
	function strToHex($string)
	{
	    $hex='';
	    for ($i=0; $i < strlen($string); $i++)
	    {
	        $hex .= dechex(ord($string[$i]));
	    }
	    return $hex;
	}
  }
?>