<?php
// Session is already started in index.php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

use ChatApp\Reaction;

$reaction = new Reaction();
$availableReactions = $reaction->getAvailableReactions();
?>

<!-- Reaction Picker Modal -->
<div id="reaction-picker" class="reaction-picker" style="display: none;">
    <div class="reaction-picker-content">
        <div class="reaction-picker-header">
            <h4>Add Reaction</h4>
            <button class="close-reaction-picker" onclick="closeReactionPicker()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="reaction-grid">
            <?php foreach ($availableReactions as $emoji => $type): ?>
                <button class="reaction-emoji" onclick="addReaction('<?= $type ?>')" data-type="<?= $type ?>">
                    <?= $emoji ?>
                </button>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Reaction Tooltip -->
<div id="reaction-tooltip" class="reaction-tooltip" style="display: none;">
    <div class="tooltip-content">
        <div class="tooltip-header">
            <span class="tooltip-emoji"></span>
            <span class="tooltip-count"></span>
        </div>
        <div class="tooltip-users">
            <!-- Users who reacted will be loaded here -->
        </div>
    </div>
</div>

<style>
.reaction-picker {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 2000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.reaction-picker-content {
    background: white;
    border-radius: 15px;
    padding: 20px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.3);
    max-width: 400px;
    width: 90%;
}

.reaction-picker-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid #eee;
}

.reaction-picker-header h4 {
    margin: 0;
    color: #333;
    font-size: 18px;
}

.close-reaction-picker {
    background: none;
    border: none;
    color: #666;
    cursor: pointer;
    padding: 5px;
    border-radius: 50%;
    transition: background 0.3s;
}

.close-reaction-picker:hover {
    background: #f0f0f0;
}

.reaction-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 15px;
}

.reaction-emoji {
    background: none;
    border: 2px solid #e1e5e9;
    border-radius: 10px;
    padding: 15px;
    font-size: 24px;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
}

.reaction-emoji:hover {
    border-color: #667eea;
    background: #f8f9fa;
    transform: scale(1.1);
}

.reaction-emoji.active {
    border-color: #667eea;
    background: #667eea;
    color: white;
}

/* Message Reaction Styles */
.message-reactions {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
    margin-top: 8px;
    align-items: center;
}

.reaction-button {
    background: #f8f9fa;
    border: 1px solid #e1e5e9;
    border-radius: 15px;
    padding: 4px 8px;
    font-size: 12px;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    gap: 4px;
    min-width: 30px;
    justify-content: center;
}

.reaction-button:hover {
    background: #e9ecef;
    border-color: #667eea;
}

.reaction-button.active {
    background: #667eea;
    color: white;
    border-color: #667eea;
}

.reaction-button .reaction-emoji {
    font-size: 14px;
}

.reaction-button .reaction-count {
    font-weight: 500;
    font-size: 11px;
}

.add-reaction-btn {
    background: none;
    border: 1px dashed #ccc;
    border-radius: 15px;
    padding: 4px 8px;
    font-size: 12px;
    cursor: pointer;
    transition: all 0.3s;
    color: #666;
    display: flex;
    align-items: center;
    gap: 4px;
}

.add-reaction-btn:hover {
    border-color: #667eea;
    color: #667eea;
    background: #f8f9fa;
}

/* Reaction Tooltip */
.reaction-tooltip {
    position: absolute;
    background: white;
    border-radius: 10px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.2);
    padding: 15px;
    z-index: 1500;
    max-width: 250px;
    border: 1px solid #e1e5e9;
}

.tooltip-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.tooltip-emoji {
    font-size: 20px;
}

.tooltip-count {
    font-weight: 600;
    color: #333;
}

.tooltip-users {
    font-size: 14px;
    color: #666;
    line-height: 1.4;
}

.tooltip-users .user-item {
    padding: 2px 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.tooltip-users .user-avatar {
    width: 20px;
    height: 20px;
    background: #667eea;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 10px;
    font-weight: bold;
}

/* Animation for reactions */
@keyframes reactionPop {
    0% { transform: scale(0); opacity: 0; }
    50% { transform: scale(1.2); }
    100% { transform: scale(1); opacity: 1; }
}

.reaction-button.animate {
    animation: reactionPop 0.3s ease-out;
}
</style>

<script>
let currentMessageId = null;
let reactionPickerPosition = { x: 0, y: 0 };

// Make functions globally available
window.showReactionPicker = function(messageId, event) {
    currentMessageId = messageId;
    
    // Position the picker near the click
    const picker = document.getElementById('reaction-picker');
    const rect = event.target.getBoundingClientRect();
    
    picker.style.display = 'flex';
    
    // Store position for later use
    reactionPickerPosition = { x: rect.left, y: rect.top };
};

window.closeReactionPicker = function() {
    document.getElementById('reaction-picker').style.display = 'none';
    currentMessageId = null;
};

window.addReaction = function(reactionType) {
    if (!currentMessageId) return;
    
    const reactionData = {
        message_id: currentMessageId,
        reaction_type: reactionType
    };
    
    fetch('api/toggle-reaction.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(reactionData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update the message reactions display
            updateMessageReactions(currentMessageId, data.reactions, data.user_reactions);
            
            // Close the picker
            closeReactionPicker();
            
            // Send WebSocket update for real-time
            if (window.websocket && window.websocket.readyState === WebSocket.OPEN) {
                window.websocket.send(JSON.stringify({
                    type: 'reaction_update',
                    message_id: currentMessageId,
                    reactions: data.reactions,
                    user_reactions: data.user_reactions
                }));
            }
        } else {
            console.error('Error adding reaction:', data.error);
        }
    })
    .catch(error => {
        console.error('Error adding reaction:', error);
    });
};

window.updateMessageReactions = function(messageId, reactions, userReactions) {
    const messageElement = document.querySelector(`[data-message-id="${messageId}"]`);
    if (!messageElement) return;
    
    const reactionsContainer = messageElement.querySelector('.message-reactions');
    if (!reactionsContainer) return;
    
    // Clear existing reactions
    reactionsContainer.innerHTML = '';
    
    // Add reaction buttons
    reactions.forEach(reaction => {
        const isUserReacted = userReactions.includes(reaction.type);
        const button = document.createElement('button');
        button.className = `reaction-button ${isUserReacted ? 'active' : ''}`;
        button.onclick = () => addReaction(reaction.type);
        button.setAttribute('data-reaction-type', reaction.type);
        
        button.innerHTML = `
            <span class="reaction-emoji">${reaction.emoji}</span>
            <span class="reaction-count">${reaction.count}</span>
        `;
        
        // Add tooltip for user list
        button.onmouseenter = (e) => showReactionTooltip(e, reaction);
        button.onmouseleave = hideReactionTooltip;
        
        reactionsContainer.appendChild(button);
    });
    
    // Add "add reaction" button
    const addButton = document.createElement('button');
    addButton.className = 'add-reaction-btn';
    addButton.innerHTML = '<i class="fas fa-plus"></i> Add';
    addButton.onclick = (e) => showReactionPicker(messageId, e);
    reactionsContainer.appendChild(addButton);
};

window.showReactionTooltip = function(event, reaction) {
    const tooltip = document.getElementById('reaction-tooltip');
    const tooltipEmoji = tooltip.querySelector('.tooltip-emoji');
    const tooltipCount = tooltip.querySelector('.tooltip-count');
    const tooltipUsers = tooltip.querySelector('.tooltip-users');
    
    tooltipEmoji.textContent = reaction.emoji;
    tooltipCount.textContent = `${reaction.count} ${reaction.count === 1 ? 'reaction' : 'reactions'}`;
    
    // Show usernames who reacted (simplified for now)
    tooltipUsers.innerHTML = `<div class="user-item">${reaction.count} people reacted</div>`;
    
    // Position tooltip
    const rect = event.target.getBoundingClientRect();
    tooltip.style.left = (rect.left + rect.width / 2) + 'px';
    tooltip.style.top = (rect.top - 10) + 'px';
    tooltip.style.display = 'block';
};

window.hideReactionTooltip = function() {
    document.getElementById('reaction-tooltip').style.display = 'none';
};

// Close reaction picker when clicking outside
document.addEventListener('click', function(e) {
    const picker = document.getElementById('reaction-picker');
    if (picker.style.display === 'flex' && !picker.contains(e.target)) {
        closeReactionPicker();
    }
});

// Close reaction picker with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeReactionPicker();
    }
});
</script>
