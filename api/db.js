const fs = require('fs');
const path = require('path');

const dataDir = path.join(process.cwd(), 'data');

// Ensure data directory exists
if (!fs.existsSync(dataDir)) {
    fs.mkdirSync(dataDir, { recursive: true });
}

// Initialize default data files
function initializeDataFiles() {
    const files = {
        'users.json': [],
        'books.json': [
            { id: 1, title: 'The Great Gatsby', author: 'F. Scott Fitzgerald', isbn: '978-0743273565', category: 'Fiction', total_copies: 5, available_copies: 5 },
            { id: 2, title: 'To Kill a Mockingbird', author: 'Harper Lee', isbn: '978-0061120084', category: 'Fiction', total_copies: 5, available_copies: 5 },
            { id: 3, title: '1984', author: 'George Orwell', isbn: '978-0451524935', category: 'Fiction', total_copies: 5, available_copies: 5 },
            { id: 4, title: 'A Brief History of Time', author: 'Stephen Hawking', isbn: '978-0553380163', category: 'Science', total_copies: 5, available_copies: 5 },
            { id: 5, title: 'Sapiens', author: 'Yuval Noah Harari', isbn: '978-0062316097', category: 'Non-Fiction', total_copies: 5, available_copies: 5 },
            { id: 6, title: 'Clean Code', author: 'Robert C. Martin', isbn: '978-0132350884', category: 'Technology', total_copies: 5, available_copies: 5 },
            { id: 7, title: 'Design Patterns', author: 'Gang of Four', isbn: '978-0201633610', category: 'Technology', total_copies: 5, available_copies: 5 },
            { id: 8, title: 'The Selfish Gene', author: 'Richard Dawkins', isbn: '978-0192860926', category: 'Science', total_copies: 5, available_copies: 5 }
        ],
        'borrowing.json': [],
        'payment_methods.json': [],
        'activity_log.json': [],
        'waiting_list.json': []
    };

    for (const [filename, defaultData] of Object.entries(files)) {
        const filepath = path.join(dataDir, filename);
        if (!fs.existsSync(filepath)) {
            fs.writeFileSync(filepath, JSON.stringify(defaultData, null, 2));
        }
    }
}

// Load JSON file
function loadJSON(filename) {
    const filepath = path.join(dataDir, filename);
    if (!fs.existsSync(filepath)) {
        return [];
    }
    try {
        return JSON.parse(fs.readFileSync(filepath, 'utf8'));
    } catch {
        return [];
    }
}

// Save JSON file
function saveJSON(filename, data) {
    const filepath = path.join(dataDir, filename);
    fs.writeFileSync(filepath, JSON.stringify(data, null, 2));
}

// User functions
function findUserByEmail(email) {
    const users = loadJSON('users.json');
    return users.find(u => u.email === email) || null;
}

function findUserById(id) {
    const users = loadJSON('users.json');
    return users.find(u => u.id == id) || null;
}

function addUser(userData) {
    const users = loadJSON('users.json');
    const maxId = users.length === 0 ? 0 : Math.max(...users.map(u => u.id));
    
    const crypto = require('crypto');
    userData.id = maxId + 1;
    userData.created_at = new Date().toISOString();
    userData.updated_at = new Date().toISOString();
    userData.balance = 0;
    userData.fine_amount = 0;
    userData.password = crypto.createHash('sha256').update(userData.password).digest('hex');
    
    users.push(userData);
    saveJSON('users.json', users);
    return userData;
}

function updateUser(id, userData) {
    const users = loadJSON('users.json');
    const index = users.findIndex(u => u.id == id);
    if (index === -1) return null;
    
    users[index] = { ...users[index], ...userData, updated_at: new Date().toISOString() };
    saveJSON('users.json', users);
    return users[index];
}

// Payment functions
function addPaymentMethod(userId, paymentData) {
    const payments = loadJSON('payment_methods.json');
    const maxId = payments.length === 0 ? 0 : Math.max(...payments.map(p => p.id));
    
    const payment = {
        id: maxId + 1,
        user_id: userId,
        ...paymentData,
        created_at: new Date().toISOString()
    };
    
    payments.push(payment);
    saveJSON('payment_methods.json', payments);
    return payment;
}

function getPaymentMethods(userId) {
    const payments = loadJSON('payment_methods.json');
    return payments.filter(p => p.user_id == userId);
}

// Activity log functions
function addActivityLog(userId, action) {
    const logs = loadJSON('activity_log.json');
    const maxId = logs.length === 0 ? 0 : Math.max(...logs.map(l => l.id));
    
    const log = {
        id: maxId + 1,
        user_id: userId,
        action: action,
        timestamp: new Date().toISOString()
    };
    
    logs.push(log);
    saveJSON('activity_log.json', logs);
    return log;
}

function getUserActivity(userId) {
    const logs = loadJSON('activity_log.json');
    return logs.filter(l => l.user_id == userId).sort((a, b) => new Date(b.timestamp) - new Date(a.timestamp));
}

// Book functions
function getBooks() {
    return loadJSON('books.json');
}

function getBookById(id) {
    const books = loadJSON('books.json');
    return books.find(b => b.id == id) || null;
}

function updateBook(id, bookData) {
    const books = loadJSON('books.json');
    const index = books.findIndex(b => b.id == id);
    if (index === -1) return null;
    
    books[index] = { ...books[index], ...bookData };
    saveJSON('books.json', books);
    return books[index];
}

// Borrowing functions
function borrowBook(userId, bookId) {
    const book = getBookById(bookId);
    if (!book || book.available_copies <= 0) return null;
    
    const borrowing = loadJSON('borrowing.json');
    const maxId = borrowing.length === 0 ? 0 : Math.max(...borrowing.map(b => b.id));
    
    const borrow = {
        id: maxId + 1,
        user_id: userId,
        book_id: bookId,
        borrow_date: new Date().toISOString(),
        due_date: new Date(Date.now() + 14 * 24 * 60 * 60 * 1000).toISOString(),
        status: 'active'
    };
    
    borrowing.push(borrow);
    saveJSON('borrowing.json', borrowing);
    
    // Update available copies
    updateBook(bookId, { available_copies: book.available_copies - 1 });
    addActivityLog(userId, `borrowed ${book.title}`);
    
    return borrow;
}

function getBorrowedBooks(userId) {
    const borrowing = loadJSON('borrowing.json');
    const books = loadJSON('books.json');
    
    return borrowing
        .filter(b => b.user_id == userId && b.status === 'active')
        .map(b => {
            const book = books.find(bk => bk.id == b.book_id);
            return { ...b, book_details: book };
        });
}

function returnBook(borrowId) {
    const borrowing = loadJSON('borrowing.json');
    const index = borrowing.findIndex(b => b.id == borrowId);
    if (index === -1) return null;
    
    const borrow = borrowing[index];
    const book = getBookById(borrow.book_id);
    
    borrow.status = 'returned';
    borrow.return_date = new Date().toISOString();
    
    borrowing[index] = borrow;
    saveJSON('borrowing.json', borrowing);
    
    // Update available copies
    updateBook(borrow.book_id, { available_copies: book.available_copies + 1 });
    addActivityLog(borrow.user_id, `returned ${book.title}`);
    
    return borrow;
}

// Initialize on load
initializeDataFiles();

module.exports = {
    loadJSON,
    saveJSON,
    findUserByEmail,
    findUserById,
    addUser,
    updateUser,
    addPaymentMethod,
    getPaymentMethods,
    addActivityLog,
    getUserActivity,
    getBooks,
    getBookById,
    updateBook,
    borrowBook,
    getBorrowedBooks,
    returnBook
};
