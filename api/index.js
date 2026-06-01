const express = require('express');
const session = require('express-session');
const bodyParser = require('body-parser');
const path = require('path');
const crypto = require('crypto');
const db = require('./db');

const app = express();

// Middleware
app.use(bodyParser.json());
app.use(bodyParser.urlencoded({ extended: true }));
app.use(session({
    secret: 'your-secret-key-change-in-production',
    resave: false,
    saveUninitialized: false,
    cookie: { 
        secure: false,
        httpOnly: true,
        maxAge: 24 * 60 * 60 * 1000 
    }
}));

// Serve static files
app.use(express.static(path.join(__dirname, '../')));

// Session helpers
function setUserSession(req, userId) {
    req.session.userId = userId;
}

function getUserId(req) {
    return req.session.userId;
}

function isLoggedIn(req) {
    return !!req.session.userId;
}

function destroySession(req) {
    req.session.destroy();
}

// Authentication Routes
app.post('/api/auth/login', (req, res) => {
    const { email, password } = req.body;

    if (!email || !password) {
        return res.json({ success: false, message: 'Email and password are required' });
    }

    const user = db.findUserByEmail(email);
    const hashedPassword = crypto.createHash('sha256').update(password).digest('hex');

    if (user && user.password === hashedPassword) {
        setUserSession(req, user.id);
        return res.json({ success: true, message: 'Login successful', userId: user.id });
    } else {
        return res.json({ success: false, message: 'Invalid email or password' });
    }
});

app.post('/api/auth/register', (req, res) => {
    const { name, email, phone, password, cardName, cardNumber, cardExpiry, cardCVV } = req.body;

    if (!name || !email || !phone || !password || !cardName || !cardNumber) {
        return res.json({ success: false, message: 'All fields are required' });
    }

    if (db.findUserByEmail(email)) {
        return res.json({ success: false, message: 'Email already registered' });
    }

    try {
        const userData = { name, email, phone, password };
        const user = db.addUser(userData);

        const paymentData = { card_name: cardName, card_number: cardNumber, card_expiry: cardExpiry, card_cvv: cardCVV };
        db.addPaymentMethod(user.id, paymentData);
        db.addActivityLog(user.id, 'registered');

        setUserSession(req, user.id);
        return res.json({ success: true, message: 'Registration successful' });
    } catch (error) {
        return res.json({ success: false, message: 'Registration failed: ' + error.message });
    }
});

app.post('/api/auth/logout', (req, res) => {
    destroySession(req);
    return res.json({ success: true, message: 'Logged out' });
});

app.get('/api/auth/status', (req, res) => {
    if (isLoggedIn(req)) {
        const user = db.findUserById(getUserId(req));
        return res.json({ success: true, loggedIn: true, userId: user.id, userName: user.name });
    }
    return res.json({ success: true, loggedIn: false });
});

// User Routes
app.get('/api/user/info', (req, res) => {
    if (!isLoggedIn(req)) {
        return res.json({ success: false, message: 'Not logged in' });
    }

    const userId = getUserId(req);
    const user = db.findUserById(userId);

    if (user) {
        const borrowed = db.getBorrowedBooks(userId);
        user.borrowed_count = borrowed.length;
        return res.json({ success: true, user });
    }
    return res.json({ success: false, message: 'User not found' });
});

app.post('/api/user/profile', (req, res) => {
    if (!isLoggedIn(req)) {
        return res.json({ success: false, message: 'Not logged in' });
    }

    const userId = getUserId(req);
    const { name, phone } = req.body;

    const updated = db.updateUser(userId, { name, phone });
    return res.json({ success: !!updated, message: updated ? 'Profile updated' : 'Failed to update' });
});

app.post('/api/user/password', (req, res) => {
    if (!isLoggedIn(req)) {
        return res.json({ success: false, message: 'Not logged in' });
    }

    const userId = getUserId(req);
    const { oldPassword, newPassword } = req.body;
    const user = db.findUserById(userId);

    const oldHashedPassword = crypto.createHash('sha256').update(oldPassword).digest('hex');
    if (user.password !== oldHashedPassword) {
        return res.json({ success: false, message: 'Incorrect old password' });
    }

    const newHashedPassword = crypto.createHash('sha256').update(newPassword).digest('hex');
    db.updateUser(userId, { password: newHashedPassword });
    return res.json({ success: true, message: 'Password changed' });
});

app.get('/api/user/activity', (req, res) => {
    if (!isLoggedIn(req)) {
        return res.json({ success: false, message: 'Not logged in' });
    }

    const userId = getUserId(req);
    const activity = db.getUserActivity(userId);
    return res.json({ success: true, activity });
});

// Books Routes
app.get('/api/books', (req, res) => {
    if (!isLoggedIn(req)) {
        return res.json({ success: false, message: 'Not logged in' });
    }

    const books = db.getBooks();
    return res.json({ success: true, books });
});

app.get('/api/books/borrowed', (req, res) => {
    if (!isLoggedIn(req)) {
        return res.json({ success: false, message: 'Not logged in' });
    }

    const userId = getUserId(req);
    const borrowed = db.getBorrowedBooks(userId);
    return res.json({ success: true, borrowed });
});

app.post('/api/books/borrow', (req, res) => {
    if (!isLoggedIn(req)) {
        return res.json({ success: false, message: 'Not logged in' });
    }

    const userId = getUserId(req);
    const { bookId } = req.body;

    const borrow = db.borrowBook(userId, bookId);
    if (borrow) {
        return res.json({ success: true, message: 'Book borrowed successfully', borrow });
    }
    return res.json({ success: false, message: 'Cannot borrow book' });
});

app.post('/api/books/return', (req, res) => {
    if (!isLoggedIn(req)) {
        return res.json({ success: false, message: 'Not logged in' });
    }

    const { borrowId } = req.body;
    const borrow = db.returnBook(borrowId);

    if (borrow) {
        return res.json({ success: true, message: 'Book returned successfully', borrow });
    }
    return res.json({ success: false, message: 'Cannot return book' });
});

// Serve index.html for root
app.get('/', (req, res) => {
    res.sendFile(path.join(__dirname, '../index.html'));
});

app.get('/dashboard', (req, res) => {
    res.sendFile(path.join(__dirname, '../dashboard.html'));
});

// 404 handler
app.use((req, res) => {
    res.status(404).json({ success: false, message: 'Endpoint not found' });
});

function startServer(port = process.env.PORT || 3000, callback) {
    return app.listen(port, () => {
        console.log(`Server running on http://localhost:${port}`);
        if (typeof callback === 'function') callback();
    });
}

module.exports = { app, startServer };

if (require.main === module) {
    startServer();
}
