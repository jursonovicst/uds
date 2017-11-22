#!/usr/bin/php
<?php

$master = array();

$server = stream_socket_server("unix://./socket", $errno, $errstr);
if (!$server) {
    echo "$errstr ($errno)\n";
} else {
    $master[] = $server;

    while (TRUE) {

        $read = $master;
        $mod_fd = stream_select($read, $_w = NULL, $_e = NULL, 5);
        if ($mod_fd === FALSE)
            break;

        foreach( $read as $socket ) {
            if ($socket === $server) {
                $conn = stream_socket_accept($server);
                if( $conn ) {
                    $master[] = $conn;
                    echo "Connection accepted from '" . stream_socket_get_name($conn, TRUE) . "'\n";
                } else {
                    echo "Error accepting socket\n";
                }
            } else {
                $sock_data = fread($socket, 100);
                if (strlen($sock_data) === 0) { // connection closed
                    $key_to_del = array_search($socket, $master, TRUE);
                    fclose($socket);
                    unset($master[$key_to_del]);
                    echo "Connection closed";
                } else if ($sock_data === FALSE) {
                    echo "Something bad happened\n";
                    $key_to_del = array_search($socket, $master, TRUE);
                    unset($master[$key_to_del]);
                } else {
                    fwrite($socket, "Hello " . $sock_data . "!", 100);
                    echo "Message sent to " . $sock_data . "\n";
                }
            }
        }
    }
}
?>
