# API Reference Guide

## Base URL
- **Development**: `http://localhost:3000`
- **Production**: Your Vercel deployment URL (e.g., `https://library-app.vercel.app`)

## Authentication Endpoints

### Login
```
POST /api/auth/login
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "password123"
}

Response:
{
  "success": true,
  "message": "Login successful",
  "userId": 1
}
```

### Register
```
POST /api/auth/register
Content-Type: application/json

{
  "name": "John Doe",
  "email": "john@example.com",
  "phone": "1234567890",
  "password": "password123",
  "cardName": "John Doe",
  "cardNumber": "4111111111111111",
  "cardExpiry": "12/25",
  "cardCVV": "123"
}

Response:
{
  "success": true,
  "message": "Registration successful"
}
```

### Check Auth Status
```
GET /api/auth/status

Response:
{
  "success": true,
  "loggedIn": true,
  "userId": 1,
  "userName": "John Doe"
}
```

### Logout
```
POST /api/auth/logout

Response:
{
  "success": true,
  "message": "Logged out"
}
```

## User Endpoints

### Get User Info
```
GET /api/user/info

Response:
{
  "success": true,
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "phone": "1234567890",
    "balance": 0,
    "fine_amount": 0,
    "borrowed_count": 2,
    "created_at": "2024-06-01T12:00:00.000Z"
  }
}
```

### Update Profile
```
POST /api/user/profile
Content-Type: application/json

{
  "name": "Jane Doe",
  "phone": "0987654321"
}

Response:
{
  "success": true,
  "message": "Profile updated"
}
```

### Change Password
```
POST /api/user/password
Content-Type: application/json

{
  "oldPassword": "oldpass123",
  "newPassword": "newpass456"
}

Response:
{
  "success": true,
  "message": "Password changed"
}
```

### Get User Activity
```
GET /api/user/activity

Response:
{
  "success": true,
  "activity": [
    {
      "id": 1,
      "user_id": 1,
      "action": "borrowed The Great Gatsby",
      "timestamp": "2024-06-01T12:30:00.000Z"
    }
  ]
}
```

## Books Endpoints

### Get All Books
```
GET /api/books

Response:
{
  "success": true,
  "books": [
    {
      "id": 1,
      "title": "The Great Gatsby",
      "author": "F. Scott Fitzgerald",
      "isbn": "978-0743273565",
      "category": "Fiction",
      "total_copies": 5,
      "available_copies": 3
    }
  ]
}
```

### Get Borrowed Books
```
GET /api/books/borrowed

Response:
{
  "success": true,
  "borrowed": [
    {
      "id": 1,
      "user_id": 1,
      "book_id": 1,
      "borrow_date": "2024-06-01T10:00:00.000Z",
      "due_date": "2024-06-15T10:00:00.000Z",
      "status": "active",
      "book_details": {
        "id": 1,
        "title": "The Great Gatsby",
        "author": "F. Scott Fitzgerald"
      }
    }
  ]
}
```

### Borrow a Book
```
POST /api/books/borrow
Content-Type: application/json

{
  "bookId": 1
}

Response:
{
  "success": true,
  "message": "Book borrowed successfully",
  "borrow": {
    "id": 1,
    "user_id": 1,
    "book_id": 1,
    "borrow_date": "2024-06-01T12:00:00.000Z",
    "due_date": "2024-06-15T12:00:00.000Z",
    "status": "active"
  }
}
```

### Return a Book
```
POST /api/books/return
Content-Type: application/json

{
  "borrowId": 1
}

Response:
{
  "success": true,
  "message": "Book returned successfully",
  "borrow": {
    "id": 1,
    "user_id": 1,
    "book_id": 1,
    "status": "returned",
    "return_date": "2024-06-05T14:30:00.000Z"
  }
}
```

## Error Responses

All endpoints return errors in this format:

```json
{
  "success": false,
  "message": "Description of the error"
}
```

Common errors:
- `"Not logged in"` - User session expired or not authenticated
- `"Invalid email or password"` - Wrong credentials
- `"Email already registered"` - Account already exists
- `"Cannot borrow book"` - Book not available

## Testing with cURL

```bash
# Login
curl -X POST http://localhost:3000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"password123"}'

# Get books
curl http://localhost:3000/api/books

# Borrow a book
curl -X POST http://localhost:3000/api/books/borrow \
  -H "Content-Type: application/json" \
  -d '{"bookId":1}'
```

## Session Management

- Sessions are stored in cookies
- Session timeout: 24 hours
- All requests require authentication except login/register
- Include credentials in fetch requests:

```javascript
fetch('/api/user/info', {
  method: 'GET',
  credentials: 'include'
});
```

## Rate Limiting

Currently no rate limiting. For production deployment, consider adding:
- `express-rate-limit` package
- Rate limit: 100 requests per 15 minutes per IP
