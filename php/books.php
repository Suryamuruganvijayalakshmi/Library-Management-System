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
} elseif ($action === 'getTopBooks') {
    getTopBooks();
} elseif ($action === 'getHighDemandBooks') {
    getHighDemandBooks();
} elseif ($action === 'getRecommendations') {
    getRecommendations();
} elseif ($action === 'estimateFine') {
    estimateFine();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

// Analytics endpoint
function getAnalytics() {
    $borrowing = loadJSON('borrowing.json');
    $books = loadJSON('books.json');

    $totalIssues = 0;
    $totalReturns = 0;
    $perBook = [];
    $hourly = array_fill(0,24,0);
    $dow = array_fill(0,7,0); // 0 Sun .. 6 Sat

    foreach ($borrowing as $b) {
        if (!isset($b['borrow_date'])) continue;
        $totalIssues++;
        $bookId = $b['book_id'];
        if (!isset($perBook[$bookId])) $perBook[$bookId] = 0;
        $perBook[$bookId]++;

        // parse borrow_date
        try {
            $dt = new DateTime($b['borrow_date']);
            $hourly[(int)$dt->format('G')]++;
            $dow[(int)$dt->format('w')]++;
        } catch (Exception $e) {
            // ignore
        }

        if (isset($b['status']) && $b['status'] === 'returned') {
            $totalReturns++;
        }
    }

    // Map book ids to titles and find most/least borrowed
    $bookMap = [];
    foreach ($books as $bk) $bookMap[$bk['id']] = $bk['title'];

    arsort($perBook);
    $mostBorrowed = null;
    $leastBorrowed = null;
    if (!empty($perBook)) {
        $firstKey = array_key_first($perBook);
        $mostBorrowed = ['book_id' => $firstKey, 'title' => ($bookMap[$firstKey] ?? ''), 'count' => $perBook[$firstKey]];
        $lastKey = array_key_last($perBook);
        $leastBorrowed = ['book_id' => $lastKey, 'title' => ($bookMap[$lastKey] ?? ''), 'count' => $perBook[$lastKey]];
    }

    // Peak usage periods: top 3 hours and top 3 days
    $hourlyPairs = [];
    foreach ($hourly as $h => $c) $hourlyPairs[] = ['hour' => $h, 'count' => $c];
    usort($hourlyPairs, function($a,$b){ return $b['count'] - $a['count']; });
    $topHours = array_slice($hourlyPairs, 0, 3);

    $dowPairs = [];
    $days = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
    foreach ($dow as $d => $c) $dowPairs[] = ['day' => $days[$d], 'count' => $c];
    usort($dowPairs, function($a,$b){ return $b['count'] - $a['count']; });
    $topDays = array_slice($dowPairs, 0, 3);

    echo json_encode([
        'success' => true,
        'total_issues' => $totalIssues,
        'total_returns' => $totalReturns,
        'most_borrowed' => $mostBorrowed,
        'least_borrowed' => $leastBorrowed,
        'peak_hours' => $topHours,
        'peak_days' => $topDays
    ]);
}

function getBooks() {
    $search = $_GET['search'] ?? '';
    $category = $_GET['category'] ?? '';
    $books = loadJSON('books.json');
    $waitingCounts = getWaitingCounts();

    // find recent returns (within last 24 hours)
    $borrowing = loadJSON('borrowing.json');
    $recentReturns = [];
    $now = new DateTime();
    foreach ($borrowing as $br) {
        if (isset($br['status']) && $br['status'] === 'returned' && !empty($br['return_date'])) {
            try {
                $ret = new DateTime($br['return_date']);
                $diff = $now->getTimestamp() - $ret->getTimestamp();
                if ($diff <= 24 * 60 * 60) {
                    $recentReturns[$br['book_id']] = true;
                }
            } catch (Exception $e) {
                // ignore parse errors
            }
        }
    }

    // attach waiting_count, demand flag, popularity, and status_label
    foreach ($books as &$b) {
        $id = $b['id'];
        $count = isset($waitingCounts[$id]) ? $waitingCounts[$id] : 0;
        $b['waiting_count'] = $count;
        $b['demand'] = ($count >= 3); // threshold for high demand
        if (!isset($b['popularity'])) $b['popularity'] = 0;

        // compute status: Recently Returned, Available, Reserved, Issued
        if (isset($b['available_copies']) && $b['available_copies'] > 0) {
            if (isset($recentReturns[$id]) && $recentReturns[$id]) {
                $b['status_label'] = 'Recently Returned';
            } else {
                $b['status_label'] = 'Available';
            }
        } else {
            if ($count > 0) {
                $b['status_label'] = 'Reserved';
            } else {
                $b['status_label'] = 'Issued';
            }
        }
    }

    $filtered = array_filter($books, function($book) use ($search, $category) {
        $matchSearch = empty($search) || stripos($book['title'], $search) !== false || stripos($book['author'], $search) !== false || stripos($book['isbn'], $search) !== false;
        $matchCategory = empty($category) || $book['category'] === $category;
        return $matchSearch && $matchCategory;
    });

    echo json_encode(['success' => true, 'books' => array_values($filtered)]);
}

function getWaitingCounts() {
    $waiting = loadJSON('waiting_list.json');
    $counts = [];
    foreach ($waiting as $w) {
        if (isset($w['status']) && $w['status'] !== 'active') continue;
        $id = $w['book_id'];
        if (!isset($counts[$id])) $counts[$id] = 0;
        $counts[$id]++;
    }
    return $counts;
}

function getHighDemandBooks() {
    $books = loadJSON('books.json');
    $waitingCounts = getWaitingCounts();

    $high = [];
    foreach ($books as $b) {
        $id = $b['id'];
        $count = isset($waitingCounts[$id]) ? $waitingCounts[$id] : 0;
        if ($count >= 3) {
            if (!isset($b['popularity'])) $b['popularity'] = 0;
            $b['waiting_count'] = $count;
            $b['demand'] = true;
            $high[] = $b;
        }
    }

    usort($high, function($a, $b) {
        return $b['waiting_count'] - $a['waiting_count'];
    });

    echo json_encode(['success' => true, 'high_demand' => $high]);
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
        
        // Update available copies and increment popularity
        foreach ($books as &$b) {
            if ($b['id'] == $bookId) {
                $b['available_copies']--;
                if (!isset($b['popularity'])) $b['popularity'] = 0;
                $b['popularity']++;
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

function getTopBooks() {
    $books = loadJSON('books.json');

    usort($books, function($a, $b) {
        $pa = isset($a['popularity']) ? $a['popularity'] : 0;
        $pb = isset($b['popularity']) ? $b['popularity'] : 0;
        return $pb - $pa;
    });

    $top = array_slice($books, 0, 5);

    // Normalize missing popularity to 0 in response
    $top = array_map(function($b) {
        if (!isset($b['popularity'])) $b['popularity'] = 0;
        return $b;
    }, $top);

    echo json_encode(['success' => true, 'top_books' => $top]);
}

function getRecommendations() {
    $bookId = $_GET['bookId'] ?? '';
    if (empty($bookId)) {
        echo json_encode(['success' => false, 'message' => 'Book ID required']);
        return;
    }

    $books = loadJSON('books.json');
    $target = null;
    foreach ($books as $b) {
        if ($b['id'] == $bookId) { $target = $b; break; }
    }

    if (!$target) {
        echo json_encode(['success' => false, 'message' => 'Book not found']);
        return;
    }

    $category = $target['category'];
    $candidates = [];
    foreach ($books as $b) {
        if ($b['id'] == $bookId) continue;
        if ($b['category'] === $category) {
            if (!isset($b['popularity'])) $b['popularity'] = 0;
            $candidates[] = $b;
        }
    }

    usort($candidates, function($a, $b) {
        $pa = $a['popularity'] ?? 0;
        $pb = $b['popularity'] ?? 0;
        if ($pb === $pa) return ($b['available_copies'] ?? 0) - ($a['available_copies'] ?? 0);
        return $pb - $pa;
    });

    $recs = array_slice($candidates, 0, 5);
    echo json_encode(['success' => true, 'recommendations' => $recs]);
}

function estimateFine() {
    $borrowId = $_GET['borrowId'] ?? $_POST['borrowId'] ?? '';
    if (empty($borrowId)) {
        echo json_encode(['success' => false, 'message' => 'Borrow ID required']);
        return;
    }

    $borrowing = loadJSON('borrowing.json');
    $record = null;
    foreach ($borrowing as $b) {
        if ($b['id'] == $borrowId) { $record = $b; break; }
    }

    if (!$record) {
        echo json_encode(['success' => false, 'message' => 'Borrow record not found']);
        return;
    }

    // If already returned, use stored fine_amount
    if (isset($record['status']) && $record['status'] === 'returned') {
        $fine = isset($record['fine_amount']) ? $record['fine_amount'] : 0;
        echo json_encode(['success' => true, 'estimated_fine' => $fine]);
        return;
    }

    $dueDate = new DateTime($record['due_date']);
    $today = new DateTime();
    $fine = 0;
    if ($today > $dueDate) {
        $days = $today->diff($dueDate)->days;
        $fine = $days * 0.50;
    }

    echo json_encode(['success' => true, 'estimated_fine' => $fine]);
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

        // Reservation alert: notify next user in waiting list (if any)
        try {
            $waitingList = loadJSON('waiting_list.json');
            // find next active entry for this book by lowest position
            $nextIndex = null;
            $nextEntry = null;
            foreach ($waitingList as $idx => $entry) {
                if ($entry['book_id'] == $borrow['book_id'] && isset($entry['status']) && $entry['status'] === 'active') {
                    if ($nextEntry === null || $entry['position'] < $nextEntry['position']) {
                        $nextEntry = $entry;
                        $nextIndex = $idx;
                    }
                }
            }

            if ($nextEntry) {
                // mark as notified and reserve a copy for them
                $waitingList[$nextIndex]['status'] = 'notified';
                $waitingList[$nextIndex]['notified_date'] = date('Y-m-d H:i:s');
                $waitingList[$nextIndex]['expires_at'] = date('Y-m-d H:i:s', strtotime('+48 hours'));

                // reserve one copy by decrementing available_copies if possible
                foreach ($books as &$bb) {
                    if ($bb['id'] == $borrow['book_id']) {
                        if ($bb['available_copies'] > 0) {
                            $bb['available_copies']--;
                        }
                        break;
                    }
                }

                saveJSON('waiting_list.json', $waitingList);
                saveJSON('books.json', $books);

                // notify the user via activity log
                addActivityLog($nextEntry['user_id'], 'notified', $borrow['book_id'], $bookTitle);
            }
        } catch (Exception $e) {
            // ignore reservation errors
        }

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
        if ($item['user_id'] == $userId && (isset($item['status']) && ($item['status'] === 'active' || $item['status'] === 'notified'))) {
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
