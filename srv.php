#!/usr/bin/php
<?php


function sig_handler($signo) {
}

pcntl_signal ( SIGTERM, "sig_handler" );
pcntl_signal ( SIGINT, "sig_handler" );


define ('SOCKET_PATH', "/tmp/srv.socket");
$socket_pool = array();


$server_socket = stream_socket_server("unix://" . SOCKET_PATH, $errno, $errstr);
if ($server_socket === FALSE) {
    echo "Unable to create socket at " . SOCKET_PATH . ": $errstr ($errno)\n";
    exit(1);
}

$socket_pool[] = $server_socket;
while (TRUE) {
    #wait for IO to happen
    $read_pool = $socket_pool;
    $_w = $_e = NULL;
    $mod_fd = stream_select($read_pool, $_w, $_e, 5);
    if ($mod_fd === FALSE)
        # error or interrupt
        break;

    foreach ($read_pool as $socket) {
        # accept new connections
        if ($socket === $server_socket) {
            $conn_socket = stream_socket_accept($server_socket);
            if( $conn_socket !== FALSE ) {
                $socket_pool[] = $conn_socket;
                echo "Connection accepted\n";
            } else {
                echo "Error accepting connection\n";
            }

        # handle messages
        } else {
            $sock_data = fread($socket, 100);

            # connection close
            if (strlen($sock_data) === 0) {
                $key_to_del = array_search($socket, $socket_pool, TRUE);
                fclose($socket);
                unset($socket_pool[$key_to_del]);
                echo "Connection closed\n";

            # error
            } else if ($sock_data === FALSE) {
                echo "Something bad happened, close connection\n";
                $key_to_del = array_search($socket, $socket_pool, TRUE);
                unset($socket_pool[$key_to_del]);

            # message received
            } else {
                $reply = "Hello $sock_data!";
                $n = fwrite($socket, $reply, 100);
                if ($n == min(strlen($reply), 100))
                    echo "Message sent to $sock_data\n";
                else
                    echo "Error sending message to $sock_data\n";
            }
        }
    }
}

echo "Exiting...\n";
foreach ($socket_pool as $socket)
    fclose($socket);
unlink(SOCKET_PATH);

?>
