<?php
session_start();

// Load rooms from JSON file
$roomsData = json_decode(file_get_contents(__DIR__ . '/rooms.json'), true);
?>
<?php
if (isset($_POST['nickname']) && isset($_POST['room'])) {
    $_SESSION['nickname'] = htmlspecialchars($_POST['nickname']);
    $_SESSION['room'] = htmlspecialchars($_POST['room']);
}

// Handle room change
if (isset($_POST['room']) && isset($_SESSION['nickname'])) {
    $_SESSION['room'] = htmlspecialchars($_POST['room']);
}

if (!isset($_SESSION['nickname'])) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Chat Login</title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/css/tabler.min.css">
    </head>
    <body class="theme-light">
        <div class="container py-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Enter Chat Room</h3>
                </div>
                <div class="card-body">
                    <form method="post">
                        <div class="mb-3">
                            <label class="form-label">Choose your nickname</label>
                            <input type="text" name="nickname" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Select Room</label>
                            <select name="room" id="room" class="form-select mb-3" required>
                                <option value="">Select a room</option>
                                <?php foreach ($roomsData['rooms'] as $room): ?>
                                    <option value="<?php echo htmlspecialchars($room['id']); ?>">
                                        <?php echo htmlspecialchars($room['name']); ?> - <?php echo htmlspecialchars($room['description']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Join Chat</button>
                    </form>
                </div>
            </div>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/js/tabler.min.js"></script>
    </body>
    </html>
    <?php
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WebSocket Chat</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/css/tabler.min.css">
</head>
<body class="theme-light">
    <div class="container py-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="card-title mb-0">Chat Room: <?php echo $_SESSION['room']; ?></h3>
                    <small class="text-muted">Connected as: <?php echo $_SESSION['nickname']; ?></small>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <form method="post" class="m-0" id="roomForm">
                        <select name="room" class="form-select form-select-sm" onchange="switchRoom(this.value)">
                            <option value="chat-room" <?php echo $_SESSION['room'] === 'chat-room' ? 'selected' : ''; ?>>Chat Room</option>
                            <option value="game-room" <?php echo $_SESSION['room'] === 'game-room' ? 'selected' : ''; ?>>Game Room</option>
                        </select>
                    </form>
                    <form method="post" action="logout.php" class="m-0">
                        <button type="submit" class="btn btn-sm btn-outline-danger">Logout</button>
                    </form>
                </div>
            </div>
            <div class="card-body">
                <div id="messages" class="mb-3" style="height: 300px; overflow-y: auto;"></div>
                <div class="input-group">
                    <input type="text" id="messageInput" class="form-control" placeholder="Type your message...">
                    <button class="btn btn-primary" onclick="sendMessage()">Send</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/js/tabler.min.js"></script>
    <script>
        let nickname = '<?php echo $_SESSION['nickname']; ?>';
        let currentRoom = '<?php echo $_SESSION['room']; ?>';
        let ws = new WebSocket('ws://localhost:12345');
        const messagesDiv = document.getElementById('messages');
        const messageInput = document.getElementById('messageInput');

        function connectWebSocket() {
            ws = new WebSocket('ws://localhost:12345');

            ws.onopen = () => {
                appendMessage('System', 'Connected to chat server');
                ws.send(JSON.stringify({
                    type: 'join',
                    nickname: nickname,
                    room: currentRoom
                }));
            };

            ws.onmessage = (event) => {
                const data = JSON.parse(event.data);
                if (data.room === currentRoom) {
                    switch(data.type) {
                        case 'join':
                            appendMessage('System', `${data.nickname} joined the room`);
                            break;
                        case 'leave':
                            appendMessage('System', `${data.nickname} left the room`);
                            break;
                        case 'message':
                            appendMessage(data.nickname, data.message);
                            break;
                    }
                }
            };

            ws.onclose = () => {
                appendMessage('System', 'Disconnected from chat server');
            };
        }

        function switchRoom(newRoom) {
            if (newRoom !== currentRoom) {
                // Send leave message for current room
                ws.send(JSON.stringify({
                    type: 'leave',
                    nickname: nickname,
                    room: currentRoom
                }));

                // Update room
                currentRoom = newRoom;
                messagesDiv.innerHTML = ''; // Clear messages

                // Update the room header
                document.querySelector('.card-title.mb-0').textContent = 'Chat Room: ' + newRoom;

                // Send join message for new room
                ws.send(JSON.stringify({
                    type: 'join',
                    nickname: nickname,
                    room: newRoom
                }));

                // Submit the form to update session
                document.getElementById('roomForm').submit();
            }
        }

        function sendMessage() {
            const message = messageInput.value.trim();
            if (message) {
                ws.send(JSON.stringify({
                    type: 'message',
                    nickname: nickname,
                    room: currentRoom,
                    message: message
                }));
                messageInput.value = '';
            }
        }

        function appendMessage(sender, message) {
            const messageElement = document.createElement('div');
            messageElement.className = 'mb-2';
            messageElement.innerHTML = `<strong>${sender}:</strong> ${message}`;
            messagesDiv.appendChild(messageElement);
            messagesDiv.scrollTop = messagesDiv.scrollHeight;
        }

        messageInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                sendMessage();
            }
        });

        // Initial connection
        connectWebSocket();
    </script>
</body>
</html>