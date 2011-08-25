This directory contains the implementation of the PHP Websockets Server. 

The main class is implemented in websocket.class.php which implements the main
body of the service and is the first point of call for any client interaction.

Handshakers.
Because WebSockets is an evoling protocol there are a number of versions. One of the 
key differences between versions is the way handshaking is done. The server has been 
implemented to support the independant evolution of the standard with the evolution of
user clients. The server will automatically choose the correct handshake based on the client
request. 

The file handshaker.interface.php defines an abstract class that all handshake implementations
must implement. Implementations of the Handshaker interface are have names that start with handshake.
Currently this implementation supports clients that use draft 75/76 and the new Hybi specification

Protocols
The other difference between the Websocket protocol versions is how the data is encoded and
framed. The file wsprotocol.interface.php defines the Protocol interface which must be supported
by all protocols. Currently this package supports the base/draft76 and Hybi protocols.

Finally the wsapp.interface.php file defines the WSApp interface which all application level (protocol)
must implement. This interface provides the basic entry points for OnMessage, OnClose and OnError
as defined by the WebSocket protocol.

