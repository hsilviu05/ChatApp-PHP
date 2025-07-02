let currentUser = null;
let currentReceiver = null;
let ws = null;
let typingTimeout = null;
let selectedFile = null;

// Initialize the app
document.addEventListener('DOMContentLoaded', function() {
    initApp();
});

function initApp() {
    // Get current user info from session
    fetch('api/get-current-user.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                currentUser = data.user;
                initWebSocket();
                loadUsers();
                setupEventListeners();
            } else {
                window.location.href = 'login.php';
            }
        })
        .catch(error => {
            console.error('Error getting user info:', error);
            window.location.href = 'login.php';
        });
}

function setupEventListeners() {
    const messageInput = document.getElementById('message-input');
    const sendBtn = document.getElementById('send-btn');
    const fileInput = document.getElementById('file-input');
    
    messageInput.addEventListener('input', function() {
        sendBtn.disabled = !this.value.trim() && !selectedFile;
        sendTypingIndicator(true);
    });
    
    messageInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });
    
    sendBtn.addEventListener('click', sendMessage);
    
    fileInput.addEventListener('change', function(e) {
        selectedFile = e.target.files[0];
        sendBtn.disabled = !messageInput.value.trim() && !selectedFile;
        
        if (selectedFile) {
            // Show file preview
            showFilePreview(selectedFile);
        }
    });
}

function showFilePreview(file) {
    const messageInput = document.getElementById('message-input');
    const filePreview = document.createElement('div');
    filePreview.className = 'file-preview';
    filePreview.innerHTML = `
        <div style="background: #f8f9fa; padding: 10px; border-radius: 8px; margin-bottom: 10px; display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-file" style="color: #667eea;"></i>
            <div>
                <div style="font-weight: 600;">${file.name}</div>
                <div style="font-size: 12px; color: #6c757d;">${formatFileSize(file.size)}</div>
            </div>
            <button type="button" onclick="removeFile()" style="margin-left: auto; background: none; border: none; color: #dc3545; cursor: pointer;">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    
    messageInput.parentNode.insertBefore(filePreview, messageInput);
}

function removeFile() {
    selectedFile = null;
    document.getElementById('file-input').value = '';
    document.querySelector('.file-preview')?.remove();
    document.getElementById('send-btn').disabled = !document.getElementById('message-input').value.trim();
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

function initWebSocket() {
    ws = new WebSocket('ws://localhost:8080');
    
    ws.onopen = function() {
        console.log('Connected to chat server');
        ws.send(JSON.stringify({
            type: 'auth',
            user_id: currentUser.id,
            username: currentUser.username
        }));
    };
    
    ws.onmessage = function(event) {
        const data = JSON.parse(event.data);
        handleWebSocketMessage(data);
    };
    
    ws.onclose = function() {
        console.log('Disconnected from chat server');
        setTimeout(initWebSocket, 1000);
    };
    
    ws.onerror = function(error) {
        console.error('WebSocket error:', error);
    };
}

function handleWebSocketMessage(data) {
    switch (data.type) {
        case 'auth_success':
            console.log('Authentication successful');
            break;
        case 'message':
            handleIncomingMessage(data);
            break;
        case 'typing':
            handleTypingIndicator(data);
            break;
        case 'read':
            handleReadReceipt(data);
            break;
    }
}

function handleIncomingMessage(data) {
    if (data.sender_id == currentReceiver || data.receiver_id == currentReceiver) {
        // If it's a file message, we need to fetch the message with attachments from the database
        if (data.has_attachment && (!data.attachments || data.attachments.length === 0)) {
            // Fetch the latest message from this sender to get the attachments
            fetchLatestMessageWithAttachments(data.sender_id, data);
        } else {
            displayMessage(data);
        }
        markAsRead(data.sender_id);
    }
    updateUnreadCount(data.sender_id);
}

function fetchLatestMessageWithAttachments(senderId, originalData) {
    fetch(`api/get-messages.php?user_id=${senderId}&limit=1`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.messages.length > 0) {
                const latestMessage = data.messages[data.messages.length - 1];
                // Use the original WebSocket data but with attachments from the database
                const messageData = {
                    ...originalData,
                    attachments: latestMessage.attachments || [],
                    created_at: latestMessage.created_at,
                    is_read: latestMessage.is_read
                };
                displayMessage(messageData);
            } else {
                // Fallback to displaying without attachments
                displayMessage(originalData);
            }
        })
        .catch(error => {
            console.error('Error fetching message with attachments:', error);
            displayMessage(originalData);
        });
}

function handleTypingIndicator(data) {
    if (data.sender_id == currentReceiver) {
        const indicator = document.getElementById('typing-indicator');
        if (data.is_typing) {
            if (!indicator) {
                const typingDiv = document.createElement('div');
                typingDiv.id = 'typing-indicator';
                typingDiv.className = 'typing-indicator';
                typingDiv.textContent = 'typing...';
                document.getElementById('chat-messages').appendChild(typingDiv);
            }
        } else {
            indicator?.remove();
        }
    }
}

function handleReadReceipt(data) {
    // Update seen indicators for sent messages
    const messages = document.querySelectorAll('.message.sent');
    messages.forEach(msg => {
        const timeElement = msg.querySelector('.message-time');
        if (timeElement && !timeElement.querySelector('.seen-indicator')) {
            timeElement.innerHTML += ' <i class="fas fa-check-double seen-indicator"></i>';
        }
    });
}

function loadUsers() {
    fetch('api/get-users.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayUsers(data.users);
            }
        })
        .catch(error => console.error('Error loading users:', error));
}

function displayUsers(users) {
    const usersList = document.getElementById('users-list');
    usersList.innerHTML = '';
    
    users.forEach(user => {
        if (user.id != currentUser.id) {
            const userDiv = document.createElement('div');
            userDiv.className = 'user-item';
            userDiv.setAttribute('data-user-id', user.id);
            userDiv.onclick = () => selectUser(user.id, user.username);
            
            userDiv.innerHTML = `
                <div class="user-avatar-small">
                    ${user.username.charAt(0).toUpperCase()}
                </div>
                <div>
                    <div style="font-weight: 600;">${user.username}</div>
                    <div style="font-size: 12px; opacity: 0.7;">Click to chat</div>
                </div>
                <div class="unread-badge" id="unread-${user.id}" style="display: none;">0</div>
            `;
            
            usersList.appendChild(userDiv);
            updateUnreadCount(user.id);
        }
    });
}

function selectUser(userId, username) {
    currentReceiver = userId;
    
    // Update UI
    document.querySelectorAll('.user-item').forEach(item => {
        item.classList.remove('active');
    });
    document.querySelector(`[data-user-id="${userId}"]`).classList.add('active');
    
    // Show chat area
    document.getElementById('chat-messages').innerHTML = '';
    document.getElementById('message-input').disabled = false;
    document.getElementById('send-btn').disabled = true;
    
    // Load messages
    loadMessages(userId);
    markConversationAsRead(userId);
}

function loadMessages(userId) {
    fetch(`api/get-messages.php?user_id=${userId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayMessages(data.messages);
            }
        })
        .catch(error => console.error('Error loading messages:', error));
}

function displayMessages(messages) {
    const container = document.getElementById('chat-messages');
    container.innerHTML = '';
    
    if (messages.length === 0) {
        container.innerHTML = '<div style="text-align: center; color: #6c757d; margin-top: 50px;">No messages yet. Start the conversation!</div>';
        return;
    }
    
    messages.forEach(message => {
        displayMessage(message);
    });
    
    container.scrollTop = container.scrollHeight;
}

function displayMessage(message) {
    const container = document.getElementById('chat-messages');
    const isSent = message.sender_id == currentUser.id;
    
    const messageDiv = document.createElement('div');
    messageDiv.className = `message ${isSent ? 'sent' : 'received'}`;
    
    const time = new Date(message.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
    
    let attachmentsHtml = '';
    if (message.attachments && message.attachments.length > 0) {
        message.attachments.forEach(attachment => {
            attachmentsHtml += `
                <div class="file-attachment" onclick="downloadFile('${attachment.filename}')">
                    <i class="fas ${getFileIcon(attachment.mime_type)} file-icon"></i>
                    <div class="file-info">
                        <div class="file-name">${attachment.original_filename}</div>
                        <div class="file-size">${formatFileSize(attachment.file_size)}</div>
                    </div>
                </div>
            `;
        });
    }
    
    let seenIndicator = '';
    if (isSent && message.is_read) {
        seenIndicator = ' <i class="fas fa-check-double seen-indicator"></i>';
    }
    
    messageDiv.innerHTML = `
        <div class="message-content">
            <div>${escapeHtml(message.message)}</div>
            ${attachmentsHtml}
            <div class="message-time">
                ${time}${seenIndicator}
            </div>
        </div>
    `;
    
    container.appendChild(messageDiv);
    container.scrollTop = container.scrollHeight;
}

function getFileIcon(mimeType) {
    if (mimeType.startsWith('image/')) return 'fa-image';
    if (mimeType === 'application/pdf') return 'fa-file-pdf';
    if (mimeType.includes('word')) return 'fa-file-word';
    if (mimeType.includes('excel') || mimeType.includes('spreadsheet')) return 'fa-file-excel';
    if (mimeType === 'text/plain') return 'fa-file-alt';
    return 'fa-file';
}

function downloadFile(filename) {
    window.open(`api/download-file.php?filename=${filename}`, '_blank');
}

function sendMessage() {
    const input = document.getElementById('message-input');
    const message = input.value.trim();
    
    if (!message && !selectedFile) return;
    
    if (selectedFile) {
        sendFileWithMessage();
    } else {
        sendTextMessage(message);
    }
}

function sendTextMessage(message) {
    const messageData = {
        sender_id: currentUser.id,
        receiver_id: currentReceiver,
        message: message,
        timestamp: new Date().toISOString()
    };
    
    // Send via WebSocket for real-time
    if (ws && ws.readyState === WebSocket.OPEN) {
        ws.send(JSON.stringify({
            type: 'message',
            ...messageData
        }));
    }
    
    // Send to server for persistence
    fetch('api/send-message.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(messageData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('message-input').value = '';
            displayMessage({
                ...messageData,
                sender_name: currentUser.username,
                created_at: messageData.timestamp,
                is_read: false
            });
        }
    })
    .catch(error => console.error('Error sending message:', error));
}

function sendFileWithMessage() {
    if (!currentReceiver) {
        alert('Please select a user to send the file to');
        return;
    }
    
    const formData = new FormData();
    formData.append('file', selectedFile);
    formData.append('receiver_id', currentReceiver);
    formData.append('message', document.getElementById('message-input').value.trim());
    
    fetch('api/upload-file.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('message-input').value = '';
            removeFile();
            
            // Display the message immediately
            displayMessage({
                sender_id: currentUser.id,
                receiver_id: currentReceiver,
                message: document.getElementById('message-input').value.trim() || 'Sent a file',
                sender_name: currentUser.username,
                created_at: new Date().toISOString(),
                is_read: false,
                attachments: [{
                    filename: data.filename,
                    original_filename: data.original_filename,
                    file_size: data.file_size,
                    mime_type: data.mime_type
                }]
            });
            
            // Send WebSocket message to notify receiver about the file
            if (ws && ws.readyState === WebSocket.OPEN) {
                ws.send(JSON.stringify({
                    type: 'message',
                    sender_id: currentUser.id,
                    receiver_id: currentReceiver,
                    message: document.getElementById('message-input').value.trim() || 'Sent a file',
                    timestamp: new Date().toISOString(),
                    has_attachment: true,
                    saved: true
                }));
            }
        } else {
            alert('Error sending file: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error sending file:', error);
        alert('Error sending file');
    });
}

function sendTypingIndicator(isTyping) {
    if (ws && ws.readyState === WebSocket.OPEN && currentReceiver) {
        ws.send(JSON.stringify({
            type: 'typing',
            sender_id: currentUser.id,
            receiver_id: currentReceiver,
            is_typing: isTyping
        }));
    }
    
    if (typingTimeout) {
        clearTimeout(typingTimeout);
    }
    
    if (isTyping) {
        typingTimeout = setTimeout(() => {
            sendTypingIndicator(false);
        }, 1000);
    }
}

function markAsRead(senderId) {
    fetch('api/mark-read.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            sender_id: senderId
        })
    });
}

function markConversationAsRead(userId) {
    fetch('api/mark-conversation-read.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            user_id: userId
        })
    });
}

function updateUnreadCount(senderId) {
    fetch(`api/get-unread-count.php?sender_id=${senderId}`)
        .then(response => response.json())
        .then(data => {
            const badge = document.getElementById(`unread-${senderId}`);
            if (data.count > 0) {
                badge.textContent = data.count;
                badge.style.display = 'flex';
            } else {
                badge.style.display = 'none';
            }
        });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}