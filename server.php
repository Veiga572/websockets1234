<?php
set_time_limit(0);

$host = "127.0.0.1";
$port = 12345;

$server = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
socket_set_option($server, SOL_SOCKET, SO_REUSEADDR, 1);
socket_bind($server, $host, $port);
socket_listen($server);

$clients = [$server];
$handshakes = [];

echo "WebSocket Server running on ws://$host:$port\n";

function perform_handshaking($received_header, $client_socket_resource, $host_name, $port) {
    $headers = [];
    $lines = preg_split("/\r\n/", $received_header);
    foreach($lines as $line) {
        $line = rtrim($line);
        if(preg_match('/\A(\S+): (.*)/m', $line, $matches)) {
            $headers[$matches[1]] = $matches[2];
        }
    }

    $secKey = $headers['Sec-WebSocket-Key'];
    $secAccept = base64_encode(pack('H*', sha1($secKey . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));

    $response = "HTTP/1.1 101 Switching Protocols\r\n";
    $response .= "Upgrade: websocket\r\n";
    $response .= "Connection: Upgrade\r\n";
    $response .= "Sec-WebSocket-Accept: $secAccept\r\n\r\n";

    socket_write($client_socket_resource, $response, strlen($response));
}

function decode_websocket_message($data) {
    $length = ord($data[1]) & 127;
    $masks = substr($data, 2, 4);
    $data = substr($data, 6, $length);
    $text = '';
    for ($i = 0; $i < $length; ++$i) {
        $text .= $data[$i] ^ $masks[$i % 4];
    }
    return $text;
}

function encode_websocket_message($text) {
    $b1 = 0x80 | (0x1 & 0x0f);
    $length = strlen($text);
    
    if($length <= 125)
        $header = pack('CC', $b1, $length);
    elseif($length > 125 && $length < 65536)
        $header = pack('CCn', $b1, 126, $length);
    else
        $header = pack('CCNN', $b1, 127, $length);

    return $header.$text;
}

while (true) {
    $read = $clients;
    socket_select($read, $write, $except, 0);

    if (in_array($server, $read)) {
        $new_client = socket_accept($server);
        $clients[] = $new_client;
        $handshakes[] = false;
        socket_getpeername($new_client, $ip);
        echo "New connection from $ip\n";
        unset($read[array_search($server, $read)]);
    }

    foreach ($read as $changed_socket) {
        $index = array_search($changed_socket, $clients);
        $data = @socket_read($changed_socket, 1024, PHP_BINARY_READ);

        if ($data === false) {
            socket_close($changed_socket);
            unset($clients[$index]);
            unset($handshakes[$index]);
            continue;
        }

        if (!$handshakes[$index]) {
            perform_handshaking($data, $changed_socket, $host, $port);
            $handshakes[$index] = true;
            continue;
        }

        $message = decode_websocket_message($data);
        if ($message) {
            echo "Message received: $message\n";
            $response = encode_websocket_message($message);
            foreach ($clients as $i => $client) {
                if ($client != $server && $client != $changed_socket && $handshakes[$i]) {
                    socket_write($client, $response);
                }
            }
        }
    }
}

socket_close($server);
?>