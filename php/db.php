<?php
// File-based database system using JSON
session_start();

$dataDir = __DIR__ . '/../data';

// Ensure data directory exists
if (!is_dir($dataDir)) {
    mkdir($dataDir, 0755, true);
}

// Initialize default data files if they don't exist
function initializeDataFiles() {
    global $dataDir;
    
    $files = [
        'users.json' => [],
        'books.json' => [
            ['id' => 1, 'title' => 'The Great Gatsby', 'author' => 'F. Scott Fitzgerald', 'isbn' => '978-0743273565', 'category' => 'Fiction', 'total_copies' => 5, 'available_copies' => 5],
            ['id' => 2, 'title' => 'To Kill a Mockingbird', 'author' => 'Harper Lee', 'isbn' => '978-0061120084', 'category' => 'Fiction', 'total_copies' => 5, 'available_copies' => 5],
            ['id' => 3, 'title' => '1984', 'author' => 'George Orwell', 'isbn' => '978-0451524935', 'category' => 'Fiction', 'total_copies' => 5, 'available_copies' => 5],
            ['id' => 4, 'title' => 'A Brief History of Time', 'author' => 'Stephen Hawking', 'isbn' => '978-0553380163', 'category' => 'Science', 'total_copies' => 5, 'available_copies' => 5],
            ['id' => 5, 'title' => 'Sapiens', 'author' => 'Yuval Noah Harari', 'isbn' => '978-0062316097', 'category' => 'Non-Fiction', 'total_copies' => 5, 'available_copies' => 5],
            ['id' => 6, 'title' => 'Clean Code', 'author' => 'Robert C. Martin', 'isbn' => '978-0132350884', 'category' => 'Technology', 'total_copies' => 5, 'available_copies' => 5],
            ['id' => 7, 'title' => 'Design Patterns', 'author' => 'Gang of Four', 'isbn' => '978-0201633610', 'category' => 'Technology', 'total_copies' => 5, 'available_copies' => 5],
            ['id' => 8, 'title' => 'The Selfish Gene', 'author' => 'Richard Dawkins', 'isbn' => '978-0192860926', 'category' => 'Science', 'total_copies' => 5, 'available_copies' => 5],
        ],
        'borrowing.json' => [],
        'payment_methods.json' => [],
        'activity_log.json' => [],
        'waiting_list.json' => []
    ];
    
    foreach ($files as $filename => $defaultData) {
        $filepath = $dataDir . '/' . $filename;
        if (!file_exists($filepath)) {
            file_put_contents($filepath, json_encode($defaultData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }
    }
}

// Load JSON file
function loadJSON($filename) {
    global $dataDir;
    $filepath = $dataDir . '/' . $filename;
    
    if (!file_exists($filepath)) {
        return [];
    }
    
    $content = file_get_contents($filepath);
    return json_decode($content, true) ?: [];
}

// Save JSON file
function saveJSON($filename, $data) {
    global $dataDir;
    $filepath = $dataDir . '/' . $filename;
    file_put_contents($filepath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

// Initialize on first load
initializeDataFiles();

function findUserByEmail($email) {
    $users = loadJSON('users.json');
    foreach ($users as $user) {
        if ($user['email'] === $email) {
            return $user;
        }
    }
    return null;
}

function findUserById($id) {
    $users = loadJSON('users.json');
    foreach ($users as $user) {
        if ($user['id'] == $id) {
            return $user;
        }
    }
    return null;
}

function addUser($userData) {
    $users = loadJSON('users.json');
    $maxId = empty($users) ? 0 : max(array_column($users, 'id'));
    $userData['id'] = $maxId + 1;
    $userData['created_at'] = date('Y-m-d H:i:s');
    $userData['updated_at'] = date('Y-m-d H:i:s');
    $userData['balance'] = 0;
    $userData['fine_amount'] = 0;
    $userData['password'] = password_hash($userData['password'], PASSWORD_DEFAULT);
    
    $users[] = $userData;
    saveJSON('users.json', $users);
    return $userData;
}

function updateUser($id, $userData) {
    $users = loadJSON('users.json');
    foreach ($users as &$user) {
        if ($user['id'] == $id) {
            $user = array_merge($user, $userData);
            $user['updated_at'] = date('Y-m-d H:i:s');
            break;
        }
    }
    saveJSON('users.json', $users);
}

function addPaymentMethod($userId, $paymentData) {
    $payments = loadJSON('payment_methods.json');
    $maxId = empty($payments) ? 0 : max(array_column($payments, 'id'));
    $paymentData['id'] = $maxId + 1;
    $paymentData['user_id'] = $userId;
    $paymentData['created_at'] = date('Y-m-d H:i:s');
    
    $payments[] = $paymentData;
    saveJSON('payment_methods.json', $payments);
}

function getPaymentMethod($userId) {
    $payments = loadJSON('payment_methods.json');
    foreach ($payments as $payment) {
        if ($payment['user_id'] == $userId) {
            return $payment;
        }
    }
    return null;
}

function addActivityLog($userId, $action, $bookId = null, $bookTitle = null) {
    $logs = loadJSON('activity_log.json');
    $log = [
        'id' => count($logs) + 1,
        'user_id' => $userId,
        'book_id' => $bookId,
        'action' => $action,
        'book_title' => $bookTitle,
        'activity_date' => date('Y-m-d H:i:s')
    ];
    $logs[] = $log;
    saveJSON('activity_log.json', $logs);
}

function createDatabase($dbFile) {
    $pdo = new PDO('sqlite:' . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create users table
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        email TEXT UNIQUE NOT NULL,
        phone TEXT NOT NULL,
        password TEXT NOT NULL,
        balance REAL DEFAULT 0,
        fine_amount REAL DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Create payment methods table
    $pdo->exec("CREATE TABLE IF NOT EXISTS payment_methods (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        card_name TEXT NOT NULL,
        card_number TEXT NOT NULL,
        card_expiry TEXT NOT NULL,
        card_cvv TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(user_id) REFERENCES users(id)
    )");

    // Create books table
    $pdo->exec("CREATE TABLE IF NOT EXISTS books (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        author TEXT NOT NULL,
        isbn TEXT UNIQUE NOT NULL,
        category TEXT NOT NULL,
        total_copies INTEGER DEFAULT 5,
        available_copies INTEGER DEFAULT 5,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Create borrowing records table
    $pdo->exec("CREATE TABLE IF NOT EXISTS borrowing (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        book_id INTEGER NOT NULL,
        borrow_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        due_date DATETIME NOT NULL,
        return_date DATETIME,
        fine_amount REAL DEFAULT 0,
        status TEXT DEFAULT 'active',
        FOREIGN KEY(user_id) REFERENCES users(id),
        FOREIGN KEY(book_id) REFERENCES books(id)
    )");

    // Create activity log table
    $pdo->exec("CREATE TABLE IF NOT EXISTS activity_log (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        book_id INTEGER,
        action TEXT NOT NULL,
        book_title TEXT,
        activity_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(user_id) REFERENCES users(id),
        FOREIGN KEY(book_id) REFERENCES books(id)
    )");

    // Insert sample books
    insertSampleBooks($pdo);

    return $pdo;
}

function insertSampleBooks($pdo) {
    $books = [
        ['The Great Gatsby', 'F. Scott Fitzgerald', '978-0743273565', 'Fiction'],
        ['To Kill a Mockingbird', 'Harper Lee', '978-0061120084', 'Fiction'],
        ['1984', 'George Orwell', '978-0451524935', 'Fiction'],
        ['A Brief History of Time', 'Stephen Hawking', '978-0553380163', 'Science'],
        ['The Selfish Gene', 'Richard Dawkins', '978-0192860926', 'Science'],
        ['Sapiens', 'Yuval Noah Harari', '978-0062316097', 'Non-Fiction'],
        ['Thinking, Fast and Slow', 'Daniel Kahneman', '978-0374533557', 'Non-Fiction'],
        ['Clean Code', 'Robert C. Martin', '978-0132350884', 'Technology'],
        ['Design Patterns', 'Gang of Four', '978-0201633610', 'Technology'],
        ['The Art of Computer Programming', 'Donald Knuth', '978-0201896831', 'Technology'],
        ['A Brief History of Time', 'Stephen Hawking', '978-0553380163', 'History'],
        ['The History of Rome', 'Michael Grant', '978-0786710829', 'History']
    ];

    foreach ($books as $book) {
        try {
            $stmt = $pdo->prepare("INSERT INTO books (title, author, isbn, category, total_copies, available_copies) VALUES (?, ?, ?, ?, 5, 5)");
            $stmt->execute($book);
        } catch (Exception $e) {
            // Book might already exist
        }
    }
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getUserId() {
    return $_SESSION['user_id'] ?? null;
}

function setUserSession($userId) {
    $_SESSION['user_id'] = $userId;
}

function destroySession() {
    session_destroy();
}
?>
