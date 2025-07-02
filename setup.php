<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config.php';

use ChatApp\Db;

echo "Setting up Chat App Database...\n";

try {
    $db = Db::getInstance();
    
    // Create tables
    if ($db->createTables()) {
        echo "✅ Database tables created successfully!\n";
        echo "\nSetup complete! You can now:\n";
        echo "1. Start the WebSocket server: php server/chat-server.php\n";
        echo "2. Start the PHP development server: php -S localhost:8000 -t public\n";
        echo "3. Visit http://localhost:8000 to use the chat app\n";
    } else {
        echo "❌ Error creating database tables\n";
    }
} catch (Exception $e) {
    echo "❌ Setup failed: " . $e->getMessage() . "\n";
    echo "\nPlease check your database configuration in config.php\n";
} 