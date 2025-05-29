<?php
session_start();

if (isset($_POST['nickname'])) {
    $_SESSION['nickname'] = htmlspecialchars($_POST['nickname']);
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
                <h3 class="card-title">Chat Room</h3>
                <div class="d-flex align-items-center gap-2">
                    <span class="text-muted">Welcome, <?php echo $_SESSION['nickname']; ?></span>
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
        const nickname = '<?php echo $_SESSION['nickname']; ?>';
        const ws = new WebSocket('ws://localhost:12345');
        const messagesDiv = document.getElementById('messages');
        const messageInput = document.getElementById('messageInput');

        ws.onopen = () => {
            appendMessage('System', 'Connected to chat server');
            // Send user joined message
            ws.send(JSON.stringify({
                type: 'join',
                nickname: nickname
            }));
        };

        ws.onmessage = (event) => {
            const data = JSON.parse(event.data);
            switch(data.type) {
                case 'join':
                    appendMessage('System', `${data.nickname} joined the chat`);
                    break;
                case 'leave':
                    appendMessage('System', `${data.nickname} left the chat`);
                    break;
                case 'message':
                    appendMessage(data.nickname, data.message);
                    break;
            }
        };

        ws.onclose = () => {
            appendMessage('System', 'Disconnected from chat server');
        };

        function sendMessage() {
            const message = messageInput.value.trim();
            if (message) {
                ws.send(JSON.stringify({
                    type: 'message',
                    nickname: nickname,
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
    </script>
</body>
</html>