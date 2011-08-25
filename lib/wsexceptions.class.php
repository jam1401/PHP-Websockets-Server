<?php

class WSClientClosedException extends Exception {
	public function errorMessage() {
		return 'Client has closed connection';
	}
}

class WSAppNotInstalled extends Exception {
}


?>