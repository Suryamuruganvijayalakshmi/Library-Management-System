# Library Management System - Desktop Application Setup

## Prerequisites

Before building the desktop application, ensure you have:

1. **Node.js** (v14 or higher) - Download from https://nodejs.org/
2. **PHP** (8.0 or higher) - Already installed on your system
3. **Git** (optional, for version control)

## Installation Steps

### 1. Install Node.js Dependencies

Open PowerShell/Command Prompt in the project directory and run:

```powershell
npm install
```

This will install:
- Electron (for desktop app framework)
- Electron-builder (for creating installers)
- Concurrently (to run PHP server and Electron together)
- Wait-on (to wait for server to start)

### 2. Run the Application (Development)

```powershell
npm start
```

This will:
- Start the PHP development server on localhost:8000
- Launch the Electron window with the application
- Open DevTools for debugging

### 3. Build the Application

To create a Windows installer and portable executable:

```powershell
npm run build-win
```

This will generate:
- **NSIS Installer** (`Library Management System Setup 1.0.0.exe`) - for installation
- **Portable Executable** (`Library Management System 1.0.0.exe`) - standalone version

The built files will be in the `dist/` directory.

## Features

✅ **Standalone Desktop App** - No browser needed
✅ **Integrated PHP Server** - Runs automatically on startup
✅ **File-based Database** - JSON storage (no database installation needed)
✅ **Modern UI** - Beautiful gradient design optimized for desktop
✅ **User Authentication** - Secure login/registration system
✅ **Book Management** - Browse, borrow, and return books
✅ **User Profile** - Manage personal information and payment methods
✅ **Activity Tracking** - View your library history

## Directory Structure

```
Library Management System/
├── electron/
│   ├── main.js          # Electron main process
│   └── preload.js       # Security configuration
├── php/
│   ├── auth.php         # Authentication handler
│   ├── books.php        # Book management
│   ├── user.php         # User operations
│   └── db.php           # Database abstraction
├── data/                # JSON data files (auto-created)
│   ├── users.json
│   ├── books.json
│   ├── borrowing.json
│   ├── payment_methods.json
│   └── activity_log.json
├── assets/
│   └── style.css        # Application styling
├── index.html           # Login page
├── dashboard.html       # Main dashboard
└── package.json         # Project configuration
```

## Troubleshooting

### "PHP command not found"
- Ensure PHP is installed and added to your system PATH
- On Windows, add PHP directory to Environment Variables

### Port 8000 already in use
- The application will try to use port 8000
- If occupied, modify `main.js` to use a different port

### "Cannot find module 'electron'"
- Run `npm install` again
- Delete `node_modules` folder and reinstall: `rm -r node_modules && npm install`

### Application crashes on startup
- Check that all PHP files are present in the `/php/` directory
- Ensure JSON data files can be created (folder permissions)

## First Time Users

1. Click "Register here" on the login screen
2. Fill in your information and card details
3. Click "Create Account"
4. Login with your credentials
5. Browse the library and borrow books!

## Features Overview

### Dashboard
- View statistics (books borrowed, outstanding fines)
- Access quick actions
- See activity history

### Browse Books
- Search by title, author, or ISBN
- Filter by category
- View book details
- Borrow available books

### My Books
- View currently borrowed books
- See due dates
- Return books when done

### Profile
- Update personal information
- Change password
- View saved payment method
- See account history

## Support

For issues or feature requests, please ensure:
1. All PHP files are intact
2. Data directory has proper write permissions
3. PHP version is 8.0 or higher
4. Node.js is installed correctly

---

**Version:** 1.0.0
**Built with:** Electron, PHP, HTML5, CSS3, JavaScript
