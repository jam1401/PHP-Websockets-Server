This is the main readme file for the PHP Websockets server packge. Each directory has it's own
Readme to provide further documentation.

The PHP Websockets server provides a complete implementation of a websockets server and a simple 
framework for application protocols. This directory contains the framework used to run the server 
some test files.

There are two files in this directory which end with the .changethis extension. They are scripts 
can be used to run the server on different platforms.

WSDaemon.changethis

This is Linux daemon script allowing the server to be configured under init.d. Just make the modifications
for your installation, save the modified file withou the changethis extension. Make sure the file
is executable then start, stop and restart the daemon in the normal Linux manner.

WSdaemon.php.changethis

This can be used to run the daemon on either windows for linux from the commandline. Make the
modifications as directed in the file, save it without the changethis extension and then run using 
the following command

php -q WSdaemon.php