#!/php -q
<?php

require_once "lib/wsutil.php";
require_once "lib/websocket.class.php";
require_once "lib/wsuser.class.php";

$log = './websockets.log';
$port = '8080';
$address = "127.0.0.1";

	error_reporting(E_ALL);
	set_time_limit(0);
	ob_implicit_flush();

	$master = GetSocket($address, $port, $log);
	$sockets = array($master);
	$users = array();

	// The main process
	while(true) {
		$changed = $sockets;
		socket_select($changed, $write = NULL, $except = NULL, NULL);
		foreach($changed as $socket) {
			if($socket == $master) {
				$client = socket_accept($master);
				if($client < 0) { logToFile("socket_accept() failed");
					continue ;
				} else {
					WsConnect($client, $sockets, $users);
					logToFile($client." CONNECTED\n");
				}
			} else {
				logToFile($client.": Processing request\n");
				$user = WsGetUserBySocket($socket, $users);
				$ws = new WebSocket($user, $log);
				$ws->handleRequest($socket);
			}
		}
	}

?>