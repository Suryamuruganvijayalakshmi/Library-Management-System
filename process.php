<?php
require_once 'php/db.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

if ($action === 'login') {
    handleLogin();
} elseif ($action === 'register') {
    handleRegister();
} elseif ($action === 'logout') {
    handleLogout();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

function handleLogin() {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Email and password are required']);
        return;
    }

    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        setUserSession($user['id']);
        echo json_encode(['success' => true, 'message' => 'Login successful']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
    }
}

function handleRegister() {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $password = $_POST['password'] ?? '';
    $cardName = $_POST['cardName'] ?? '';
    $cardNumber = $_POST['cardNumber'] ?? '';
    $cardExpiry = $_POST['cardExpiry'] ?? '';
    $cardCVV = $_POST['cardCVV'] ?? '';

    if (empty($name) || empty($email) || empty($phone) || empty($password) || empty($cardName) || empty($cardNumber)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        return;
    }

    $pdo = getDB();

    // Check if user already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Email already registered']);
        return;
    }

    try {
        $pdo->beginTransaction();

        // Insert user
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (name, email, phone, password) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $email, $phone, $hashedPassword]);
        $userId = $pdo->lastInsertId();

        // Store payment method
        $stmt = $pdo->prepare("INSERT INTO payment_methods (user_id, card_name, card_number, card_expiry, card_cvv) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $cardName, $cardNumber, $cardExpiry, $cardCVV]);

        // Log activity
        $stmt = $pdo->prepare("INSERT INTO activity_log (user_id, action) VALUES (?, ?)");
        $stmt->execute([$userId, 'registered']);

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Registration successful']);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()]);
    }
}

function handleLogout() {
    destroySession();
    echo json_encode(['success' => true, 'message' => 'Logged out']);
}
?>
