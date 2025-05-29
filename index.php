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
            <div class="card-header">
                <h3 class="card-title">Chat Room</h3>
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
        const ws = new WebSocket('ws://localhost:12345');
        const messagesDiv = document.getElementById('messages');
        const messageInput = document.getElementById('messageInput');

        ws.onopen = () => {
            appendMessage('System', 'Connected to chat server');
        };

        ws.onmessage = (event) => {
            appendMessage('User', event.data);
        };

        ws.onclose = () => {
            appendMessage('System', 'Disconnected from chat server');
        };

        function sendMessage() {
            const message = messageInput.value.trim();
            if (message) {
                ws.send(message);
                appendMessage('You', message);
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