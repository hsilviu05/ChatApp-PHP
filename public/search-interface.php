<?php
// Session is already started in index.php, so we don't need to start it again
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

use ChatApp\User;
use ChatApp\Chat;

$user = new User();
$chat = new Chat();

// Get current user and all users for the search interface
$currentUser = $user->getCurrentUser();
$allUsers = $user->getAllUsers();
?>

<div id="search-overlay" class="search-overlay" style="display: none;">
    <div class="search-container">
        <div class="search-header">
            <h2><i class="fas fa-search"></i> Search Messages</h2>
            <button class="close-search" onclick="closeSearch()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="search-form">
            <div class="search-input-group">
                <input type="text" id="search-input" placeholder="Search messages..." class="search-input">
                <button onclick="performSearch()" class="search-btn">
                    <i class="fas fa-search"></i>
                </button>
            </div>
            
            <div class="search-filters">
                <div class="filter-group">
                    <label>From User:</label>
                    <select id="sender-filter" class="filter-select">
                        <option value="">All Users</option>
                        <?php foreach ($allUsers as $user): ?>
                            <?php if ($user['id'] != $currentUser['id']): ?>
                                <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['username']) ?></option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label>Date From:</label>
                    <input type="date" id="date-from" class="filter-input">
                </div>
                
                <div class="filter-group">
                    <label>Date To:</label>
                    <input type="date" id="date-to" class="filter-input">
                </div>
                
                <div class="filter-group">
                    <label>Conversation:</label>
                    <select id="conversation-filter" class="filter-select">
                        <option value="">All Conversations</option>
                        <?php foreach ($allUsers as $user): ?>
                            <?php if ($user['id'] != $currentUser['id']): ?>
                                <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['username']) ?></option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
        
        <div class="search-results">
            <div id="search-results-container">
                <div class="search-placeholder">
                    <i class="fas fa-search"></i>
                    <p>Enter a search term to find messages</p>
                </div>
            </div>
        </div>
        
        <div class="search-pagination" id="search-pagination" style="display: none;">
            <button id="prev-page" class="pagination-btn" onclick="changePage(-1)">
                <i class="fas fa-chevron-left"></i> Previous
            </button>
            <span id="page-info">Page 1</span>
            <button id="next-page" class="pagination-btn" onclick="changePage(1)">
                Next <i class="fas fa-chevron-right"></i>
            </button>
        </div>
    </div>
</div>

<style>
.search-overlay {
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

.search-container {
    background: white;
    border-radius: 15px;
    width: 90%;
    max-width: 800px;
    height: 80vh;
    display: flex;
    flex-direction: column;
    box-shadow: 0 20px 40px rgba(0,0,0,0.3);
}

.search-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    border-radius: 15px 15px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.search-header h2 {
    margin: 0;
    font-size: 1.3rem;
    display: flex;
    align-items: center;
    gap: 10px;
}

.close-search {
    background: rgba(255,255,255,0.2);
    border: none;
    color: white;
    padding: 8px 12px;
    border-radius: 50%;
    cursor: pointer;
    transition: background 0.3s;
}

.close-search:hover {
    background: rgba(255,255,255,0.3);
}

.search-form {
    padding: 20px;
    border-bottom: 1px solid #eee;
}

.search-input-group {
    display: flex;
    gap: 10px;
    margin-bottom: 15px;
}

.search-input {
    flex: 1;
    padding: 12px 15px;
    border: 2px solid #e1e5e9;
    border-radius: 25px;
    font-size: 16px;
    outline: none;
    transition: border-color 0.3s;
}

.search-input:focus {
    border-color: #667eea;
}

.search-btn {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    padding: 12px 20px;
    border-radius: 25px;
    cursor: pointer;
    transition: transform 0.2s;
}

.search-btn:hover {
    transform: translateY(-2px);
}

.search-filters {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.filter-group label {
    font-size: 14px;
    color: #666;
    font-weight: 500;
}

.filter-select, .filter-input {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 14px;
    outline: none;
    transition: border-color 0.3s;
}

.filter-select:focus, .filter-input:focus {
    border-color: #667eea;
}

.search-results {
    flex: 1;
    overflow-y: auto;
    padding: 20px;
}

.search-placeholder {
    text-align: center;
    color: #999;
    padding: 40px 20px;
}

.search-placeholder i {
    font-size: 3rem;
    margin-bottom: 15px;
    opacity: 0.5;
}

.search-result-item {
    background: #f8f9fa;
    border-radius: 10px;
    padding: 15px;
    margin-bottom: 15px;
    border-left: 4px solid #667eea;
    transition: transform 0.2s;
}

.search-result-item:hover {
    transform: translateX(5px);
    background: #f0f2f5;
}

.result-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}

.result-sender {
    font-weight: 600;
    color: #333;
}

.result-date {
    font-size: 12px;
    color: #666;
}

.result-conversation {
    font-size: 12px;
    color: #667eea;
    margin-bottom: 8px;
}

.result-message {
    color: #555;
    line-height: 1.4;
}

.search-highlight {
    background: #ffeb3b;
    padding: 2px 4px;
    border-radius: 3px;
    font-weight: 600;
}

.search-pagination {
    padding: 20px;
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 20px;
    border-top: 1px solid #eee;
}

.pagination-btn {
    background: #f8f9fa;
    border: 1px solid #ddd;
    padding: 8px 16px;
    border-radius: 20px;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    gap: 5px;
}

.pagination-btn:hover:not(:disabled) {
    background: #667eea;
    color: white;
    border-color: #667eea;
}

.pagination-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

#page-info {
    font-weight: 500;
    color: #666;
}

.loading {
    text-align: center;
    padding: 40px;
    color: #666;
}

.loading i {
    animation: spin 1s linear infinite;
    font-size: 2rem;
    margin-bottom: 15px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.no-results {
    text-align: center;
    padding: 40px 20px;
    color: #999;
}

.no-results i {
    font-size: 3rem;
    margin-bottom: 15px;
    opacity: 0.5;
}
</style>

<script>
let currentPage = 1;
let totalPages = 1;
let currentSearchParams = {};

// Make functions globally available
window.openSearch = function() {
    console.log('Search button clicked!');
    document.getElementById('search-overlay').style.display = 'flex';
    document.getElementById('search-input').focus();
};

window.closeSearch = function() {
    document.getElementById('search-overlay').style.display = 'none';
    document.getElementById('search-results-container').innerHTML = `
        <div class="search-placeholder">
            <i class="fas fa-search"></i>
            <p>Enter a search term to find messages</p>
        </div>
    `;
    document.getElementById('search-pagination').style.display = 'none';
    currentPage = 1;
};

window.performSearch = function(page = 1) {
    const searchTerm = document.getElementById('search-input').value.trim();
    const senderId = document.getElementById('sender-filter').value;
    const dateFrom = document.getElementById('date-from').value;
    const dateTo = document.getElementById('date-to').value;
    const conversationId = document.getElementById('conversation-filter').value;
    
    if (!searchTerm && !senderId && !dateFrom && !dateTo && !conversationId) {
        alert('Please enter a search term or select at least one filter');
        return;
    }
    
    currentSearchParams = {
        q: searchTerm,
        sender_id: senderId,
        date_from: dateFrom,
        date_to: dateTo,
        conversation_id: conversationId,
        limit: 20,
        offset: (page - 1) * 20
    };
    
    currentPage = page;
    
    // Show loading
    document.getElementById('search-results-container').innerHTML = `
        <div class="loading">
            <i class="fas fa-spinner"></i>
            <p>Searching messages...</p>
        </div>
    `;
    
    // Build query string
    const params = new URLSearchParams();
    Object.keys(currentSearchParams).forEach(key => {
        if (currentSearchParams[key]) {
            params.append(key, currentSearchParams[key]);
        }
    });
    
    // Make API call
    console.log('Search API call:', `api/search-messages.php?${params.toString()}`);
    
    fetch(`api/search-messages.php?${params.toString()}`)
        .then(response => {
            console.log('Search response status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('Search response data:', data);
            if (data.success) {
                displaySearchResults(data);
            } else {
                console.error('Search error:', data.error);
                document.getElementById('search-results-container').innerHTML = `
                    <div class="no-results">
                        <i class="fas fa-exclamation-triangle"></i>
                        <p>Error: ${data.error}</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Search fetch error:', error);
            document.getElementById('search-results-container').innerHTML = `
                <div class="no-results">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p>Error: ${error.message}</p>
                </div>
            `;
        });
}

window.displaySearchResults = function(data) {
    const container = document.getElementById('search-results-container');
    
    if (data.results.length === 0) {
        container.innerHTML = `
            <div class="no-results">
                <i class="fas fa-search"></i>
                <p>No messages found matching your search criteria</p>
            </div>
        `;
        document.getElementById('search-pagination').style.display = 'none';
        return;
    }
    
    const resultsHtml = data.results.map(result => `
        <div class="search-result-item" onclick="openConversation(${result.conversation_user_id})">
            <div class="result-header">
                <span class="result-sender">${result.sender_name}</span>
                <span class="result-date">${result.formatted_date}</span>
            </div>
            <div class="result-conversation">
                Conversation with: ${result.conversation_username}
            </div>
            <div class="result-message">
                ${result.message_highlighted || result.message}
                ${result.attachment_name ? `<br><small><i class="fas fa-paperclip"></i> ${result.attachment_name}</small>` : ''}
            </div>
        </div>
    `).join('');
    
    container.innerHTML = resultsHtml;
    
    // Update pagination
    totalPages = Math.ceil(data.total / 20);
    document.getElementById('page-info').textContent = `Page ${currentPage} of ${totalPages} (${data.total} results)`;
    
    document.getElementById('prev-page').disabled = currentPage <= 1;
    document.getElementById('next-page').disabled = currentPage >= totalPages;
    
    document.getElementById('search-pagination').style.display = 'flex';
}

window.changePage = function(delta) {
    const newPage = currentPage + delta;
    if (newPage >= 1 && newPage <= totalPages) {
        performSearch(newPage);
    }
};

window.openConversation = function(userId) {
    // Find the user in the users list and select them
    const userItems = document.querySelectorAll('.user-item');
    for (let item of userItems) {
        if (item.dataset.userId == userId) {
            item.click();
            break;
        }
    }
    closeSearch();
};

// Add keyboard shortcuts
document.addEventListener('keydown', function(e) {
    if (e.ctrlKey && e.key === 'f') {
        e.preventDefault();
        openSearch();
    }
    
    if (e.key === 'Escape') {
        closeSearch();
    }
    
    if (e.key === 'Enter' && document.getElementById('search-input') === document.activeElement) {
        performSearch();
    }
});
</script> 