<?php
require_once 'db.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'getBooks') {
    getBooks();
} elseif ($action === 'borrowBook') {
    borrowBook();
} elseif ($action === 'getBorrowedBooks') {
    getBorrowedBooks();
} elseif ($action === 'returnBook') {
    returnBook();
} elseif ($action === 'joinWaitingList') {
    joinWaitingList();
} elseif ($action === 'getWaitingList') {
    getWaitingList();
} elseif ($action === 'leaveWaitingList') {
    leaveWaitingList();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

function getBooks() {
    $search = $_GET['search'] ?? '';
    $category = $_GET['category'] ?? '';
    
    $books = loadJSON('books.json');
    
    $filtered = array_filter($books, function($book) use ($search, $category) {
        $matchSearch = empty($search) || stripos($book['title'], $search) !== false || stripos($book['author'], $search) !== false || stripos($book['isbn'], $search) !== false;
        $matchCategory = empty($category) || $book['category'] === $category;
        return $matchSearch && $matchCategory;
    });
    
    echo json_encode(['success' => true, 'books' => array_values($filtered)]);
}

function borrowBook() {
    $bookId = $_POST['bookId'] ?? '';
    $userId = getUserId();

    if (empty($bookId)) {
        echo json_encode(['success' => false, 'message' => 'Book ID is required']);
        return;
    }

    $books = loadJSON('books.json');
    $borrowing = loadJSON('borrowing.json');
    
    // Find book
    $book = null;
    foreach ($books as $b) {
        if ($b['id'] == $bookId) {
            $book = $b;
            break;
        }
    }
    
    if (!$book || $book['available_copies'] <= 0) {
        echo json_encode(['success' => false, 'message' => 'Book is not available']);
        return;
    }

    // Check if user already borrowed this book
    foreach ($borrowing as $borrow) {
        if ($borrow['user_id'] == $userId && $borrow['book_id'] == $bookId && $borrow['status'] === 'active') {
            echo json_encode(['success' => false, 'message' => 'You already borrowed this book']);
            return;
        }
    }

    // Check user balance and fines
    $user = findUserById($userId);
    if ($user['fine_amount'] > 0) {
        echo json_encode(['success' => false, 'message' => 'Please pay your outstanding fines before borrowing']);
        return;
    }

    try {
        // Create borrowing record
        $dueDate = date('Y-m-d H:i:s', strtotime('+14 days'));
        $maxId = empty($borrowing) ? 0 : max(array_column($borrowing, 'id'));
        
        $newBorrow = [
            'id' => $maxId + 1,
            'user_id' => $userId,
            'book_id' => $bookId,
            'borrow_date' => date('Y-m-d H:i:s'),
            'due_date' => $dueDate,
            'return_date' => null,
            'fine_amount' => 0,
            'status' => 'active'
        ];
        
        $borrowing[] = $newBorrow;
        
        // Update available copies
        foreach ($books as &$b) {
            if ($b['id'] == $bookId) {
                $b['available_copies']--;
                break;
            }
        }
        
        saveJSON('books.json', $books);
        saveJSON('borrowing.json', $borrowing);
        
        // Log activity
        addActivityLog($userId, 'borrowed', $bookId, $book['title']);
        
        echo json_encode(['success' => true, 'message' => 'Book borrowed successfully']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error borrowing book: ' . $e->getMessage()]);
    }
}

function getBorrowedBooks() {
    $userId = getUserId();
    $borrowing = loadJSON('borrowing.json');
    $books = loadJSON('books.json');
    
    $borrowed = [];
    
    foreach ($borrowing as $borrow) {
        if ($borrow['user_id'] == $userId && $borrow['status'] === 'active') {
            // Find book details
            $book = null;
            foreach ($books as $b) {
                if ($b['id'] == $borrow['book_id']) {
                    $book = $b;
                    break;
                }
            }
            
            if ($book) {
                $item = [
                    'id' => $borrow['id'],
                    'book_title' => $book['title'],
                    'author' => $book['author'],
                    'borrow_date' => $borrow['borrow_date'],
                    'due_date' => $borrow['due_date'],
                    'fine_amount' => $borrow['fine_amount'],
                    'status' => $borrow['status']
                ];
                
                // Calculate fine if overdue
                $dueDate = new DateTime($borrow['due_date']);
                $today = new DateTime();
                if ($today > $dueDate) {
                    $days = $today->diff($dueDate)->days;
                    $item['fine_amount'] = $days * 0.50;
                }
                
                $borrowed[] = $item;
            }
        }
    }
    
    usort($borrowed, function($a, $b) {
        return strtotime($b['borrow_date']) - strtotime($a['borrow_date']);
    });
    
    echo json_encode(['success' => true, 'borrowed' => $borrowed]);
}

function returnBook() {
    $borrowId = $_POST['borrowId'] ?? '';
    $userId = getUserId();

    if (empty($borrowId)) {
        echo json_encode(['success' => false, 'message' => 'Borrow ID is required']);
        return;
    }

    $borrowing = loadJSON('borrowing.json');
    $books = loadJSON('books.json');
    
    // Find borrow record
    $borrowIndex = -1;
    $borrow = null;
    
    foreach ($borrowing as $idx => $b) {
        if ($b['id'] == $borrowId && $b['user_id'] == $userId && $b['status'] === 'active') {
            $borrow = $b;
            $borrowIndex = $idx;
            break;
        }
    }
    
    if (!$borrow) {
        echo json_encode(['success' => false, 'message' => 'Borrow record not found']);
        return;
    }

    try {
        // Calculate fine if overdue
        $dueDate = new DateTime($borrow['due_date']);
        $today = new DateTime();
        $fineAmount = 0;

        if ($today > $dueDate) {
            $days = $today->diff($dueDate)->days;
            $fineAmount = $days * 0.50;
        }

        // Update borrowing record
        $borrowing[$borrowIndex]['return_date'] = date('Y-m-d H:i:s');
        $borrowing[$borrowIndex]['status'] = 'returned';
        $borrowing[$borrowIndex]['fine_amount'] = $fineAmount;

        // Increase available copies
        foreach ($books as &$b) {
            if ($b['id'] == $borrow['book_id']) {
                $b['available_copies']++;
                break;
            }
        }

        // Update user fine amount
        $user = findUserById($userId);
        $user['fine_amount'] += $fineAmount;
        updateUser($userId, $user);

        saveJSON('books.json', $books);
        saveJSON('borrowing.json', $borrowing);

        // Get book title
        $bookTitle = '';
        foreach ($books as $b) {
            if ($b['id'] == $borrow['book_id']) {
                $bookTitle = $b['title'];
                break;
            }
        }
        
        // Log activity
        addActivityLog($userId, 'returned', $borrow['book_id'], $bookTitle);

        $message = 'Book returned successfully';
        if ($fineAmount > 0) {
            $message .= '. Fine of $' . number_format($fineAmount, 2) . ' has been applied.';
        }
        echo json_encode(['success' => true, 'message' => $message]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error returning book: ' . $e->getMessage()]);
    }
}

function joinWaitingList() {
    $bookId = $_POST['bookId'] ?? '';
    $userId = getUserId();

    if (empty($bookId)) {
        echo json_encode(['success' => false, 'message' => 'Book ID is required']);
        return;
    }

    $waitingList = loadJSON('waiting_list.json');
    $books = loadJSON('books.json');
    
    // Find book
    $book = null;
    foreach ($books as $b) {
        if ($b['id'] == $bookId) {
            $book = $b;
            break;
        }
    }
    
    if (!$book) {
        echo json_encode(['success' => false, 'message' => 'Book not found']);
        return;
    }

    // Check if user already on waiting list for this book
    foreach ($waitingList as $item) {
        if ($item['user_id'] == $userId && $item['book_id'] == $bookId && $item['status'] === 'active') {
            echo json_encode(['success' => false, 'message' => 'You are already on the waiting list for this book']);
            return;
        }
    }

    try {
        $maxId = empty($waitingList) ? 0 : max(array_column($waitingList, 'id'));
        
        $newEntry = [
            'id' => $maxId + 1,
            'user_id' => $userId,
            'book_id' => $bookId,
            'position' => count(array_filter($waitingList, function($item) use ($bookId) {
                return $item['book_id'] == $bookId && $item['status'] === 'active';
            })) + 1,
            'request_date' => date('Y-m-d H:i:s'),
            'status' => 'active'
        ];
        
        $waitingList[] = $newEntry;
        saveJSON('waiting_list.json', $waitingList);
        
        addActivityLog($userId, 'joined_waiting_list', $bookId, $book['title']);
        
        echo json_encode(['success' => true, 'message' => 'You have been added to the waiting list']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error joining waiting list: ' . $e->getMessage()]);
    }
}

function getWaitingList() {
    $userId = getUserId();
    $waitingList = loadJSON('waiting_list.json');
    $books = loadJSON('books.json');
    
    $userWaitingList = [];
    
    foreach ($waitingList as $item) {
        if ($item['user_id'] == $userId && $item['status'] === 'active') {
            $book = null;
            foreach ($books as $b) {
                if ($b['id'] == $item['book_id']) {
                    $book = $b;
                    break;
                }
            }
            
            if ($book) {
                $userWaitingList[] = [
                    'id' => $item['id'],
                    'book_title' => $book['title'],
                    'author' => $book['author'],
                    'request_date' => $item['request_date'],
                    'position' => $item['position'],
                    'available_copies' => $book['available_copies']
                ];
            }
        }
    }
    
    usort($userWaitingList, function($a, $b) {
        return $a['position'] - $b['position'];
    });
    
    echo json_encode(['success' => true, 'waiting_list' => $userWaitingList]);
}

function leaveWaitingList() {
    $waitingId = $_POST['waitingId'] ?? '';
    $userId = getUserId();

    if (empty($waitingId)) {
        echo json_encode(['success' => false, 'message' => 'Waiting list ID is required']);
        return;
    }

    $waitingList = loadJSON('waiting_list.json');
    
    $found = false;
    foreach ($waitingList as &$item) {
        if ($item['id'] == $waitingId && $item['user_id'] == $userId && $item['status'] === 'active') {
            $item['status'] = 'cancelled';
            $found = true;
            break;
        }
    }
    
    if (!$found) {
        echo json_encode(['success' => false, 'message' => 'Waiting list entry not found']);
        return;
    }

    try {
        saveJSON('waiting_list.json', $waitingList);
        echo json_encode(['success' => true, 'message' => 'You have been removed from the waiting list']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error leaving waiting list: ' . $e->getMessage()]);
    }
}
?>
