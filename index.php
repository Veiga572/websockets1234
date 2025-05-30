<?php
session_start();

// Load rooms from JSON file
$roomsData = json_decode(file_get_contents(__DIR__ . '/rooms.json'), true);

// Handle new room creation
if (isset($_POST['room_name']) && isset($_POST['room_description'])) {
    $newRoom = [
        "id" => strtolower(str_replace(' ', '-', $_POST['room_name'])),
        "name" => htmlspecialchars($_POST['room_name']),
        "description" => htmlspecialchars($_POST['room_description'])
    ];
    $roomsData['rooms'][] = $newRoom;
    file_put_contents(__DIR__ . '/rooms.json', json_encode($roomsData, JSON_PRETTY_PRINT));
    $_SESSION['room_created'] = true;
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat Rooms</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/css/tabler.min.css">
    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/js/tabler.min.js"></script>
</head>

<body class="theme-light">
    <div class="container py-4">
        <?php if (isset($_SESSION['room_created'])): ?>
            <div class="alert alert-success" role="alert">
                Room created successfully!
            </div>
            <?php unset($_SESSION['room_created']); ?>
        <?php endif; ?>
        <!-- Modal -->
        <div class="modal fade" id="createRoomModal" tabindex="-1" aria-labelledby="createRoomModalLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="createRoomModalLabel">Create New Chat Room</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="post">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Room Name</label>
                                <input type="text" name="room_name" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Room Description</label>
                                <input type="text" name="room_description" class="form-control" required>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Create Room</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- Rest of your page content -->
    </div>
</body>

</html>
<?php
// --- LOGIN HANDLING ---
$login_error = null;
$usersData = json_decode(file_get_contents(__DIR__ . '/users.json'), true);
if (isset($_POST['login_username']) && isset($_POST['login_password'])) {
    $username = $_POST['login_username'];
    $password = $_POST['login_password'];
    $found = false;
    foreach ($usersData['users'] as $user) {
        if ($user['username'] === $username && $user['password'] === $password) {
            $_SESSION['logged_in'] = true;
            $_SESSION['username'] = $username;
            $found = true;
            break;
        }
    }
    if (!$found) {
        $login_error = 'Invalid username or password';
    }
}

// Only allow joining chat if logged in
if (isset($_POST['room']) && isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    $_SESSION['room'] = htmlspecialchars($_POST['room']);
    $_SESSION['nickname'] = $_SESSION['username']; // Use username as nickname
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
                        <?php if ($login_error): ?>
                            <div class="alert alert-danger" role="alert">
                                <?php echo $login_error; ?>
                            </div>
                        <?php endif; ?>
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" name="login_username" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="login_password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Select Room</label>
                            <select name="room" id="room" class="form-select mb-3" required>
                                <option value="">Select a room</option>
                                <?php foreach ($roomsData['rooms'] as $room): ?>
                                    <option value="<?php echo htmlspecialchars($room['id']); ?>">
                                        <?php echo htmlspecialchars($room['name']); ?> -
                                        <?php echo htmlspecialchars($room['description']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">Join Chat</button>
                            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#createRoomModal">Create Chat Room</button>
                        </div>
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
                            <?php foreach ($roomsData['rooms'] as $room): ?>
                                <option value="<?php echo htmlspecialchars($room['id']); ?>" <?php echo (isset($_SESSION['room']) && $_SESSION['room'] === $room['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($room['name']); ?>
                                </option>
                            <?php endforeach; ?>
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
                    switch (data.type) {
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
</body>

</html>