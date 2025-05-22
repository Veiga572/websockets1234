<?php
$host = "127.0.0.1"; // IP do servidor
$port = 12345; // Mesma porta do servidor

$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
socket_connect($socket, $host, $port);

$socket_stream = socket_export_stream($socket);

stream_set_blocking(STDIN, false);
stream_set_blocking($socket_stream, false);

echo "Ligado ao chat! Digite uma mensagem:\n";

while (true) {
    $read = [$socket_stream, STDIN];
    $write = NULL;
    $except = NULL;

    if (stream_select($read, $write, $except, null) > 0) 
    {
        foreach ($read as $r) 
        {
            if ($r === STDIN) 
            {
                $mensagem = trim(fgets(STDIN));
                if ($mensagem) 
                {
                    socket_write($socket, $mensagem . "\n");
                }
            } 
            elseif ($r === $socket_stream) 
            {
                $mensagem = fgets($socket_stream);
                if ($mensagem !== false) 
                {
                    echo "\nMensagem recebida: " . trim($mensagem) . "\n";
                    echo "Digite uma mensagem:\n";
                } 
                else 
                {
                    echo "Conexão encerrada pelo servidor.\n";
                    break 2;
                }
            }
        }
    }
}

socket_close($socket);
?>