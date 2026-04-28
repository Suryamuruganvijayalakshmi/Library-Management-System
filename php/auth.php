<?php
require_once 'db.php';

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

    $user = findUserByEmail($email);

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

    // Check if user already exists
    if (findUserByEmail($email)) {
        echo json_encode(['success' => false, 'message' => 'Email already registered']);
        return;
    }

    try {
        $userData = [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'password' => $password
        ];
        
        $user = addUser($userData);
        
        $paymentData = [
            'card_name' => $cardName,
            'card_number' => $cardNumber,
            'card_expiry' => $cardExpiry,
            'card_cvv' => $cardCVV
        ];
        
        addPaymentMethod($user['id'], $paymentData);
        addActivityLog($user['id'], 'registered');
        
        echo json_encode(['success' => true, 'message' => 'Registration successful']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()]);
    }
}

function handleLogout() {
    destroySession();
    echo json_encode(['success' => true, 'message' => 'Logged out']);
}
?>
