Websocket protocols can be placed in a directory named after the protocol.
For example this file exists in a protocol directory for the Bar protocol.
There must also exist a PHP class file named;

	protocol.class.php 
	
and in this directory you will find;

	Bar.class.php
	
For which is the entry class for the Bar protocol. This class will must implement
the application interface specified in ../lib/wsapp.interface.php

The bar protocol may be specified by the client by passing the protocol name into the
WebSocket constructor for example:

Javascript

	socket = new WebSocket(host, "Bar");
	

The Bar.class.php file implements a simple a echo service and as such acts as an example