# Real-Time Chat App in PHP

A modern, real-time chat application built with PHP, WebSockets, and MySQL. Features include user authentication, real-time messaging, typing indicators, and message history.

## 🚀 Features

- ✅ **User Authentication** - Register and login system
- 💬 **Real-time Messaging** - Instant message delivery via WebSockets
- 🔄 **Message History** - Persistent message storage in MySQL
- 🔍 **Advanced Message Search** - Search through conversation history with filters
- 👥 **Groups & Channels** - Create and manage group conversations
- 👀 **Typing Indicators** - See when someone is typing
- ✅ **Read Status** - Track message delivery and read status
- 📱 **Responsive Design** - Works on desktop and mobile
- 🎨 **Modern UI** - Clean and intuitive interface

## 📋 Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Composer
- WebSocket support (Ratchet library)

## 🛠️ Installation

### 1. Clone the Repository
```bash
git clone <repository-url>
cd ChatApp
```

### 2. Install Dependencies
```bash
composer install
```

### 3. Configure Database
Edit `config.php` with your database credentials:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'chatapp');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
define('DB_PORT', '3306');
```

### 4. Create Database
Create a MySQL database named `chatapp` (or whatever you specified in config.php).

### 5. Run Setup Script
```bash
php setup.php
```

This will create all necessary database tables.

## 🚀 Running the Application

### 1. Start the WebSocket Server
```bash
php server/chat-server.php
```
The WebSocket server will start on port 8080.

### 2. Start the PHP Development Server
```bash
php -S localhost:8000 -t public
```

### 3. Access the Application
Open your browser and go to: `http://localhost:8000`

## 📁 Project Structure

```
ChatApp/
├── config.php              # Database configuration
├── composer.json           # Dependencies
├── setup.php              # Database setup script
├── README.md              # This file
├── public/                # Web-accessible files
│   ├── index.php          # Main chat interface
│   ├── login.php          # Login/register page
│   ├── logout.php         # Logout handler
│   ├── search-interface.php # Search interface component
│   ├── groups-interface.php # Groups interface component
│   └── api/               # API endpoints
│       ├── send-message.php
│       ├── get-messages.php
│       ├── search-messages.php
│       ├── create-group.php
│       ├── get-groups.php
│       ├── get-group-messages.php
│       ├── send-group-message.php
│       ├── mark-read.php
│       ├── mark-conversation-read.php
│       ├── get-users.php
│       ├── get-current-user.php
│       ├── get-unread-count.php
│       ├── upload-file.php
│       ├── get-message-attachments.php
│       └── download-file.php
├── server/
│   └── chat-server.php    # WebSocket server
└── src/                   # PHP classes
    ├── Db.php             # Database connection
    ├── User.php           # User management
    ├── Chat.php           # Chat functionality
    └── Group.php          # Group management
```

## 🔧 Configuration

### Database Configuration
Edit `config.php` to match your database settings:
- `DB_HOST`: Database host (usually localhost)
- `DB_NAME`: Database name
- `DB_USER`: Database username
- `DB_PASS`: Database password
- `DB_PORT`: Database port (usually 3306)

### WebSocket Configuration
The WebSocket server runs on `localhost:8080` by default. You can modify this in `server/chat-server.php`.

## 🎯 Usage

### Registration
1. Visit the login page
2. Click "Sign up" to switch to registration form
3. Fill in username, email, and password
4. Click "Register"

### Login
1. Enter your username/email and password
2. Click "Login"

### Chatting
1. Select a user from the sidebar
2. Type your message in the input field
3. Press Enter or click "Send"
4. Messages appear in real-time

### Searching Messages
1. Click the search icon in the header or press Ctrl+F
2. Enter your search term in the search box
3. Optionally use filters (user, date range, conversation)
4. Click "Search" or press Enter
5. Browse results and click on any message to open that conversation

### Using Groups
1. Click the groups icon in the header
2. **My Groups**: View groups you're a member of
3. **Create Group**: Start a new group with a name and select members
4. **Discover**: Browse available groups to join
5. Click on any group to open the group chat
6. Send messages that all group members can see

## 🔒 Security Features

- Password hashing using PHP's `password_hash()`
- Session-based authentication
- Input validation and sanitization
- SQL injection prevention with prepared statements
- XSS protection with `htmlspecialchars()`

## 🚀 Advanced Features

### Advanced Message Search
- **Text Search**: Search for specific words or phrases in messages
- **User Filter**: Filter messages by sender
- **Date Range**: Search messages within specific date ranges
- **Conversation Filter**: Search within specific conversations
- **Highlighted Results**: Search terms are highlighted in results
- **Pagination**: Navigate through large result sets
- **Keyboard Shortcuts**: Use Ctrl+F to open search

### Groups & Channels
- **Create Groups**: Create new group conversations with custom names
- **Add Members**: Invite users to join your groups
- **Group Chat**: Send messages to multiple users simultaneously
- **Group Management**: View group info, member count, and creator details
- **Real-time Updates**: Group messages update in real-time via WebSockets
- **Member Management**: Add/remove members (group creator only)
- **Group Discovery**: Browse available groups to join

### Typing Indicators
When a user starts typing, other users will see a "typing..." indicator.

### Read Status
Messages are automatically marked as read when viewed.

### Unread Message Counts
The sidebar shows unread message counts for each user.

### Real-time Updates
All messages, typing indicators, and read receipts are delivered in real-time via WebSockets.

## 🐛 Troubleshooting

### WebSocket Connection Issues
- Make sure the WebSocket server is running: `php server/chat-server.php`
- Check that port 8080 is not blocked by firewall
- Verify WebSocket support in your browser

### Database Connection Issues
- Verify database credentials in `config.php`
- Ensure MySQL service is running
- Check that the database exists

### Composer Issues
- Run `composer install` to install dependencies
- Make sure Composer is installed on your system

## 🔄 Development

### Adding New Features
1. Create new API endpoints in `public/api/`
2. Update the Chat or User classes as needed
3. Modify the frontend JavaScript in `public/index.php`
4. Update the WebSocket server for real-time features

### Database Schema
The application uses these main tables:
- `users`: User accounts and authentication
- `messages`: Chat messages with sender/receiver info
- `groups`: For future group chat functionality
- `group_members`: Group membership

## 📝 License

This project is open source and available under the MIT License.

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## 📞 Support

If you encounter any issues or have questions, please open an issue on the repository. 