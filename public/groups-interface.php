<?php
// Session is already started in index.php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

use ChatApp\User;
use ChatApp\Group;

$user = new User();
$group = new Group();

// Get current user and all users for the groups interface
$currentUser = $user->getCurrentUser();
$allUsers = $user->getAllUsers();
$userGroups = $group->getUserGroups($currentUser['id']);
?>

<div id="groups-overlay" class="groups-overlay" style="display: none;">
    <div class="groups-container">
        <div class="groups-header">
            <h2><i class="fas fa-users"></i> Groups & Channels</h2>
            <button class="close-groups" onclick="closeGroups()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="groups-tabs">
            <button class="tab-btn active" onclick="switchTab('my-groups')">My Groups</button>
            <button class="tab-btn" onclick="switchTab('create-group')">Create Group</button>
            <button class="tab-btn" onclick="switchTab('discover')">Discover</button>
        </div>
        
        <!-- My Groups Tab -->
        <div id="my-groups" class="tab-content active">
            <div class="groups-list">
                <?php if (empty($userGroups)): ?>
                    <div class="no-groups">
                        <i class="fas fa-users"></i>
                        <p>You haven't joined any groups yet</p>
                        <button onclick="switchTab('create-group')" class="create-first-group-btn">
                            Create Your First Group
                        </button>
                    </div>
                <?php else: ?>
                    <?php foreach ($userGroups as $groupItem): ?>
                        <div class="group-item" onclick="openGroup(<?= $groupItem['id'] ?>)">
                            <div class="group-avatar">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="group-info">
                                <div class="group-name"><?= htmlspecialchars($groupItem['name']) ?></div>
                                <div class="group-meta">
                                    <?= $groupItem['member_count'] ?> members • 
                                    Created by <?= htmlspecialchars($groupItem['creator_name']) ?>
                                </div>
                            </div>
                            <div class="group-actions">
                                <button class="group-action-btn" onclick="event.stopPropagation(); showGroupMenu(<?= $groupItem['id'] ?>)">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Create Group Tab -->
        <div id="create-group" class="tab-content">
            <div class="create-group-form">
                <div class="form-group">
                    <label>Group Name</label>
                    <input type="text" id="group-name" placeholder="Enter group name..." maxlength="100">
                </div>
                
                <div class="form-group">
                    <label>Add Members</label>
                    <div class="members-selector">
                        <?php foreach ($allUsers as $userItem): ?>
                            <?php if ($userItem['id'] != $currentUser['id']): ?>
                                <label class="member-checkbox">
                                    <input type="checkbox" value="<?= $userItem['id'] ?>">
                                    <span class="checkbox-label"><?= htmlspecialchars($userItem['username']) ?></span>
                                </label>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <button onclick="createGroup()" class="create-group-btn">
                    <i class="fas fa-plus"></i> Create Group
                </button>
            </div>
        </div>
        
        <!-- Discover Tab -->
        <div id="discover" class="tab-content">
            <div class="discover-groups">
                <div class="loading-discover" id="loading-discover">
                    <i class="fas fa-spinner fa-spin"></i>
                    <p>Loading available groups...</p>
                </div>
                <div class="discover-list" id="discover-list" style="display: none;"></div>
            </div>
        </div>
    </div>
</div>

<!-- Group Chat Interface -->
<div id="group-chat-overlay" class="group-chat-overlay" style="display: none;">
    <div class="group-chat-container">
        <div class="group-chat-header">
            <div class="group-info">
                <h3 id="group-chat-name">Group Name</h3>
                <span id="group-member-count">0 members</span>
            </div>
            <div class="group-chat-actions">
                <button class="group-info-btn" onclick="showGroupInfo()">
                    <i class="fas fa-info-circle"></i>
                </button>
                <button class="close-group-chat" onclick="closeGroupChat()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
        
        <div class="group-chat-messages" id="group-chat-messages">
            <div class="loading-messages">
                <i class="fas fa-spinner fa-spin"></i>
                <p>Loading messages...</p>
            </div>
        </div>
        
        <div class="group-chat-input">
            <input type="text" id="group-message-input" placeholder="Type a message..." maxlength="1000">
            <button onclick="sendGroupMessage()" class="send-group-btn">
                <i class="fas fa-paper-plane"></i>
            </button>
        </div>
    </div>
</div>

<!-- Group Info Modal -->
<div id="group-info-modal" class="modal-overlay" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Group Information</h3>
            <button class="close-modal" onclick="closeGroupInfo()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body" id="group-info-content">
            <!-- Group info will be loaded here -->
        </div>
    </div>
</div>

<style>
.groups-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.8);
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.groups-container {
    background: white;
    border-radius: 15px;
    width: 90%;
    max-width: 800px;
    height: 80vh;
    display: flex;
    flex-direction: column;
    box-shadow: 0 20px 40px rgba(0,0,0,0.3);
}

.groups-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    border-radius: 15px 15px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.groups-header h2 {
    margin: 0;
    font-size: 1.3rem;
    display: flex;
    align-items: center;
    gap: 10px;
}

.close-groups {
    background: rgba(255,255,255,0.2);
    border: none;
    color: white;
    padding: 8px 12px;
    border-radius: 50%;
    cursor: pointer;
    transition: background 0.3s;
}

.close-groups:hover {
    background: rgba(255,255,255,0.3);
}

.groups-tabs {
    display: flex;
    border-bottom: 1px solid #eee;
}

.tab-btn {
    flex: 1;
    padding: 15px;
    border: none;
    background: #f8f9fa;
    cursor: pointer;
    transition: all 0.3s;
    font-size: 14px;
    font-weight: 500;
}

.tab-btn.active {
    background: white;
    border-bottom: 3px solid #667eea;
    color: #667eea;
}

.tab-btn:hover:not(.active) {
    background: #e9ecef;
}

.tab-content {
    display: none;
    flex: 1;
    overflow-y: auto;
    padding: 20px;
}

.tab-content.active {
    display: block;
}

.group-item {
    display: flex;
    align-items: center;
    padding: 15px;
    border-radius: 10px;
    margin-bottom: 10px;
    background: #f8f9fa;
    cursor: pointer;
    transition: all 0.3s;
}

.group-item:hover {
    background: #e9ecef;
    transform: translateX(5px);
}

.group-avatar {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 20px;
    margin-right: 15px;
}

.group-info {
    flex: 1;
}

.group-name {
    font-weight: 600;
    color: #333;
    margin-bottom: 5px;
}

.group-meta {
    font-size: 12px;
    color: #666;
}

.group-actions {
    margin-left: 10px;
}

.group-action-btn {
    background: none;
    border: none;
    color: #666;
    cursor: pointer;
    padding: 5px;
    border-radius: 50%;
    transition: background 0.3s;
}

.group-action-btn:hover {
    background: #e9ecef;
}

.no-groups {
    text-align: center;
    padding: 60px 20px;
    color: #999;
}

.no-groups i {
    font-size: 4rem;
    margin-bottom: 20px;
    opacity: 0.5;
}

.create-first-group-btn {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 25px;
    cursor: pointer;
    margin-top: 20px;
    transition: transform 0.2s;
}

.create-first-group-btn:hover {
    transform: translateY(-2px);
}

.create-group-form {
    max-width: 500px;
    margin: 0 auto;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #333;
}

.form-group input[type="text"] {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid #e1e5e9;
    border-radius: 8px;
    font-size: 16px;
    outline: none;
    transition: border-color 0.3s;
}

.form-group input[type="text"]:focus {
    border-color: #667eea;
}

.members-selector {
    max-height: 200px;
    overflow-y: auto;
    border: 2px solid #e1e5e9;
    border-radius: 8px;
    padding: 10px;
}

.member-checkbox {
    display: flex;
    align-items: center;
    padding: 8px;
    cursor: pointer;
    border-radius: 5px;
    transition: background 0.3s;
}

.member-checkbox:hover {
    background: #f8f9fa;
}

.member-checkbox input[type="checkbox"] {
    margin-right: 10px;
}

.checkbox-label {
    font-size: 14px;
    color: #333;
}

.create-group-btn {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    padding: 15px 30px;
    border-radius: 25px;
    cursor: pointer;
    font-size: 16px;
    font-weight: 500;
    width: 100%;
    transition: transform 0.2s;
}

.create-group-btn:hover {
    transform: translateY(-2px);
}

/* Group Chat Styles */
.group-chat-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.8);
    z-index: 1100;
    display: flex;
    align-items: center;
    justify-content: center;
}

.group-chat-container {
    background: white;
    border-radius: 15px;
    width: 95%;
    max-width: 1000px;
    height: 90vh;
    display: flex;
    flex-direction: column;
    box-shadow: 0 20px 40px rgba(0,0,0,0.3);
}

.group-chat-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    border-radius: 15px 15px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.group-info h3 {
    margin: 0;
    font-size: 1.2rem;
}

.group-member-count {
    font-size: 14px;
    opacity: 0.8;
}

.group-chat-actions {
    display: flex;
    gap: 10px;
}

.group-info-btn, .close-group-chat {
    background: rgba(255,255,255,0.2);
    border: none;
    color: white;
    padding: 8px 12px;
    border-radius: 50%;
    cursor: pointer;
    transition: background 0.3s;
}

.group-info-btn:hover, .close-group-chat:hover {
    background: rgba(255,255,255,0.3);
}

.group-chat-messages {
    flex: 1;
    overflow-y: auto;
    padding: 20px;
    background: #f8f9fa;
}

.group-chat-input {
    display: flex;
    gap: 10px;
    padding: 20px;
    border-top: 1px solid #eee;
    background: white;
    border-radius: 0 0 15px 15px;
}

.group-chat-input input {
    flex: 1;
    padding: 12px 15px;
    border: 2px solid #e1e5e9;
    border-radius: 25px;
    font-size: 16px;
    outline: none;
    transition: border-color 0.3s;
}

.group-chat-input input:focus {
    border-color: #667eea;
}

.send-group-btn {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    padding: 12px 20px;
    border-radius: 50%;
    cursor: pointer;
    transition: transform 0.2s;
}

.send-group-btn:hover {
    transform: translateY(-2px);
}

/* Modal Styles */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.8);
    z-index: 1200;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-content {
    background: white;
    border-radius: 15px;
    width: 90%;
    max-width: 500px;
    max-height: 80vh;
    overflow-y: auto;
    box-shadow: 0 20px 40px rgba(0,0,0,0.3);
}

.modal-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    border-radius: 15px 15px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h3 {
    margin: 0;
}

.close-modal {
    background: rgba(255,255,255,0.2);
    border: none;
    color: white;
    padding: 8px 12px;
    border-radius: 50%;
    cursor: pointer;
    transition: background 0.3s;
}

.close-modal:hover {
    background: rgba(255,255,255,0.3);
}

.modal-body {
    padding: 20px;
}

.loading-messages, .loading-discover {
    text-align: center;
    padding: 40px;
    color: #666;
}

.loading-messages i, .loading-discover i {
    animation: spin 1s linear infinite;
    font-size: 2rem;
    margin-bottom: 15px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>

<script>
let currentGroupId = null;
let currentGroupInfo = null;

// Make functions globally available
window.openGroups = function() {
    console.log('Groups button clicked!');
    document.getElementById('groups-overlay').style.display = 'flex';
    loadUserGroups();
};

window.closeGroups = function() {
    document.getElementById('groups-overlay').style.display = 'none';
};

window.switchTab = function(tabName) {
    // Hide all tab contents
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // Remove active class from all tab buttons
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Show selected tab content
    document.getElementById(tabName).classList.add('active');
    
    // Add active class to clicked button
    event.target.classList.add('active');
    
    // Load content based on tab
    if (tabName === 'discover') {
        loadDiscoverGroups();
    }
};

window.loadUserGroups = function() {
    fetch('api/get-groups.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Groups are already loaded from PHP
                console.log('User groups loaded:', data.groups);
            }
        })
        .catch(error => {
            console.error('Error loading groups:', error);
        });
};

window.loadDiscoverGroups = function() {
    const loadingDiv = document.getElementById('loading-discover');
    const discoverList = document.getElementById('discover-list');
    
    loadingDiv.style.display = 'block';
    discoverList.style.display = 'none';
    
    // For now, show a message that discover is coming soon
    setTimeout(() => {
        loadingDiv.style.display = 'none';
        discoverList.style.display = 'block';
        discoverList.innerHTML = `
            <div class="no-groups">
                <i class="fas fa-search"></i>
                <p>Discover feature coming soon!</p>
                <p>You can create your own groups or ask friends to invite you.</p>
            </div>
        `;
    }, 1000);
};

window.createGroup = function() {
    const groupName = document.getElementById('group-name').value.trim();
    const memberCheckboxes = document.querySelectorAll('.member-checkbox input[type="checkbox"]:checked');
    const memberIds = Array.from(memberCheckboxes).map(cb => parseInt(cb.value));
    
    if (!groupName) {
        alert('Please enter a group name');
        return;
    }
    
    const groupData = {
        name: groupName,
        member_ids: memberIds
    };
    
    fetch('api/create-group.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(groupData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Group created successfully!');
            document.getElementById('group-name').value = '';
            memberCheckboxes.forEach(cb => cb.checked = false);
            switchTab('my-groups');
            // Reload the page to show new group
            setTimeout(() => location.reload(), 1000);
        } else {
            alert('Error creating group: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error creating group:', error);
        alert('Error creating group. Please try again.');
    });
};

window.openGroup = function(groupId) {
    currentGroupId = groupId;
    document.getElementById('groups-overlay').style.display = 'none';
    document.getElementById('group-chat-overlay').style.display = 'flex';
    
    // Load group messages
    loadGroupMessages(groupId);
};

window.closeGroupChat = function() {
    document.getElementById('group-chat-overlay').style.display = 'none';
    currentGroupId = null;
    currentGroupInfo = null;
};

window.loadGroupMessages = function(groupId) {
    const messagesContainer = document.getElementById('group-chat-messages');
    
    fetch(`api/get-group-messages.php?group_id=${groupId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                currentGroupInfo = data.group;
                displayGroupMessages(data.messages);
                updateGroupHeader();
            } else {
                messagesContainer.innerHTML = `<p>Error loading messages: ${data.error}</p>`;
            }
        })
        .catch(error => {
            console.error('Error loading group messages:', error);
            messagesContainer.innerHTML = '<p>Error loading messages. Please try again.</p>';
        });
};

window.displayGroupMessages = function(messages) {
    const container = document.getElementById('group-chat-messages');
    
    if (messages.length === 0) {
        container.innerHTML = `
            <div class="no-messages">
                <i class="fas fa-comments"></i>
                <p>No messages yet. Start the conversation!</p>
            </div>
        `;
        return;
    }
    
    const messagesHtml = messages.map(message => `
        <div class="message ${message.sender_id == currentUser.id ? 'sent' : 'received'}">
            <div class="message-header">
                <span class="sender-name">${message.sender_name}</span>
                <span class="message-time">${new Date(message.created_at).toLocaleTimeString()}</span>
            </div>
            <div class="message-content">${message.message}</div>
        </div>
    `).join('');
    
    container.innerHTML = messagesHtml;
    container.scrollTop = container.scrollHeight;
};

window.updateGroupHeader = function() {
    if (currentGroupInfo) {
        document.getElementById('group-chat-name').textContent = currentGroupInfo.name;
        document.getElementById('group-member-count').textContent = `${currentGroupInfo.member_count} members`;
    }
};

window.sendGroupMessage = function() {
    const input = document.getElementById('group-message-input');
    const message = input.value.trim();
    
    if (!message || !currentGroupId) return;
    
    const messageData = {
        group_id: currentGroupId,
        message: message
    };
    
    fetch('api/send-group-message.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(messageData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            input.value = '';
            // Reload messages to show the new message
            loadGroupMessages(currentGroupId);
        } else {
            alert('Error sending message: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error sending group message:', error);
        alert('Error sending message. Please try again.');
    });
};

window.showGroupInfo = function() {
    if (!currentGroupInfo) return;
    
    const modal = document.getElementById('group-info-modal');
    const content = document.getElementById('group-info-content');
    
    content.innerHTML = `
        <div class="group-info-details">
            <h4>Group Details</h4>
            <p><strong>Name:</strong> ${currentGroupInfo.name}</p>
            <p><strong>Created by:</strong> ${currentGroupInfo.creator_name}</p>
            <p><strong>Members:</strong> ${currentGroupInfo.member_count}</p>
            <p><strong>Created:</strong> ${new Date(currentGroupInfo.created_at).toLocaleDateString()}</p>
        </div>
    `;
    
    modal.style.display = 'flex';
};

window.closeGroupInfo = function() {
    document.getElementById('group-info-modal').style.display = 'none';
};

// Add keyboard shortcuts
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeGroups();
        closeGroupChat();
        closeGroupInfo();
    }
    
    if (e.key === 'Enter' && document.getElementById('group-message-input') === document.activeElement) {
        sendGroupMessage();
    }
});
</script>
