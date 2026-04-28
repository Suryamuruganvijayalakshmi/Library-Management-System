<?php
require_once 'db.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'getInfo') {
    getUserInfo();
} elseif ($action === 'updateProfile') {
    updateProfile();
} elseif ($action === 'changePassword') {
    changePassword();
} elseif ($action === 'getPaymentInfo') {
    getPaymentInfo();
} elseif ($action === 'getActivity') {
    getActivity();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

function getUserInfo() {
    $userId = getUserId();
    $user = findUserById($userId);
    
    if ($user) {
        // Count borrowed books
        $borrowing = loadJSON('borrowing.json');
        $borrowCount = 0;
        foreach ($borrowing as $borrow) {
            if ($borrow['user_id'] == $userId && $borrow['status'] === 'active') {
                $borrowCount++;
            }
        }
        
        $user['borrowed_count'] = $borrowCount;
        echo json_encode(['success' => true, 'user' => $user]);
    } else {
        echo json_encode(['success' => false, 'message' => 'User not found']);
    }
}

function updateProfile() {
    $userId = getUserId();
    $name = $_POST['name'] ?? '';
    $phone = $_POST['phone'] ?? '';

    if (empty($name) || empty($phone)) {
        echo json_encode(['success' => false, 'message' => 'Name and phone are required']);
        return;
    }

    updateUser($userId, ['name' => $name, 'phone' => $phone]);
    echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
}

function changePassword() {
    $userId = getUserId();
    $currentPassword = $_POST['currentPassword'] ?? '';
    $newPassword = $_POST['newPassword'] ?? '';

    if (empty($currentPassword) || empty($newPassword)) {
        echo json_encode(['success' => false, 'message' => 'Current password and new password are required']);
        return;
    }

    $user = findUserById($userId);

    if (!password_verify($currentPassword, $user['password'])) {
        echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
        return;
    }

    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    updateUser($userId, ['password' => $hashedPassword]);
    echo json_encode(['success' => true, 'message' => 'Password changed successfully']);
}

function getPaymentInfo() {
    $userId = getUserId();
    $payment = getPaymentMethod($userId);

    if ($payment) {
        echo json_encode(['success' => true, 'payment' => $payment]);
    } else {
        echo json_encode(['success' => false, 'payment' => null]);
    }
}

function getActivity() {
    $userId = getUserId();
    $logs = loadJSON('activity_log.json');
    
    $activity = [];
    foreach ($logs as $log) {
        if ($log['user_id'] == $userId) {
            $activity[] = $log;
        }
    }
    
    // Sort by date descending and limit to 10
    usort($activity, function($a, $b) {
        return strtotime($b['activity_date']) - strtotime($a['activity_date']);
    });
    
    $activity = array_slice($activity, 0, 10);
    
    echo json_encode(['success' => true, 'activity' => $activity]);
}
?>
