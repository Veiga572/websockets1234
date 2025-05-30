<?php
set_time_limit(0);

$host = "127.0.0.1";
$port = 12345;

// Load rooms from JSON file
$roomsData = json_decode(file_get_contents(__DIR__ . '/rooms.json'), true);
$availableRooms = array_column($roomsData['rooms'], 'id');

$server = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
socket_set_option($server, SOL_SOCKET, SO_REUSEADDR, 1);
socket_bind($server, $host, $port);
socket_listen($server);

$clients = [$server];
$handshakes = [];
$users = [];
$rooms = [];

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
    if (strlen($data) < 2) {
        return '';
    }
    $length = ord($data[1]) & 127;
    if (strlen($data) < 6 + $length) {
        return '';
    }
    $masks = substr($data, 2, 4);
    $data = substr($data, 6, $length);
    $text = '';
    for ($i = 0; $i < $length; ++$i) {
        $text .= $data[$i] ^ $masks[$i % 4];
    }
    return $text;
}

function encode_websocket_message($message) {
    $json = json_encode($message);
    $b1 = 0x80 | (0x1 & 0x0f);
    $length = strlen($json);
    
    if($length <= 125)
        $header = pack('CC', $b1, $length);
    elseif($length > 125 && $length < 65536)
        $header = pack('CCn', $b1, 126, $length);
    else
        $header = pack('CCNN', $b1, 127, $length);

    return $header.$json;
}

function write_chat_log($room_id, $nickname, $message) {
    $timestamp = date('YmdH'); // year, month, day, hour
    $filename = __DIR__ . '/logs/' . $room_id . '_' . $timestamp . '.log';
    if (!file_exists(__DIR__ . '/logs')) {
        mkdir(__DIR__ . '/logs', 0777, true);
    }
    $log_entry = '[' . date('Y-m-d H:i:s') . "] $nickname: $message\n";
    file_put_contents($filename, $log_entry, FILE_APPEND);
}

while (true) {
    $read = $clients;
    socket_select($read, $write, $except, 0);

    if (in_array($server, $read)) {
        $new_client = socket_accept($server);
        $clients[] = $new_client;
        $handshakes[] = false;
        $users[] = '';
        $rooms[] = '';
        socket_getpeername($new_client, $ip);
        echo "New connection from $ip\n";
        unset($read[array_search($server, $read)]);
    }

    foreach ($read as $changed_socket) {
        $index = array_search($changed_socket, $clients);
        $data = @socket_read($changed_socket, 1024, PHP_BINARY_READ);

        if ($data === false) {
            $nickname = $users[$index];
            $room = $rooms[$index];
            if ($nickname && $room) {
                $leave_msg = encode_websocket_message([
                    'type' => 'leave',
                    'nickname' => $nickname,
                    'room' => $room
                ]);
                foreach ($clients as $i => $client) {
                    if ($client != $server && $handshakes[$i] && $rooms[$i] === $room) {
                        socket_write($client, $leave_msg);
                    }
                }
            }
            socket_close($changed_socket);
            unset($clients[$index]);
            unset($handshakes[$index]);
            unset($users[$index]);
            unset($rooms[$index]);
            continue;
        }

        if (!$handshakes[$index]) {
            perform_handshaking($data, $changed_socket, $host, $port);
            $handshakes[$index] = true;
            continue;
        }

        $message = decode_websocket_message($data);
        if ($message) {
            $decoded = json_decode($message, true);
            if ($decoded && isset($decoded['type'])) {
                echo "Message received: " . print_r($decoded, true) . "\n";

                switch($decoded['type']) {
                    case 'join':
                        if (isset($decoded['nickname']) && isset($decoded['room'])) {
                            // Validate that the room exists
                            if (in_array($decoded['room'], $availableRooms)) {
                                $users[$index] = $decoded['nickname'];
                                $rooms[$index] = $decoded['room'];
                                $response = encode_websocket_message([
                                    'type' => 'join',
                                    'nickname' => $decoded['nickname'],
                                    'room' => $decoded['room']
                                ]);
                            }
                        }
                        break;
                    case 'message':
                        if (isset($decoded['nickname']) && isset($decoded['room']) && isset($decoded['message'])) {
                            $response = encode_websocket_message([
                                'type' => 'message',
                                'nickname' => $decoded['nickname'],
                                'room' => $decoded['room'],
                                'message' => $decoded['message']
                            ]);
                            // Log the message
                            write_chat_log($decoded['room'], $decoded['nickname'], $decoded['message']);
                        }
                        break;
                }

                if (isset($response)) {
                    foreach ($clients as $i => $client) {
                        if ($client != $server && $handshakes[$i] && 
                            isset($rooms[$i]) && isset($decoded['room']) && 
                            $rooms[$i] === $decoded['room']) {
                            socket_write($client, $response);
                        }
                    }
                }
            }
        }
    }
}

socket_close($server);
?>