# Library Management System - Desktop Application
## Complete Windows Setup Guide

---

## 📋 Quick Start (Easiest Method)

### Option 1: Use Batch Script (Recommended for Windows)
1. Double-click `run-desktop.bat`
2. The application will automatically:
   - Check dependencies (Node.js, PHP)
   - Install required packages
   - Start the PHP server
   - Launch the desktop application

### Option 2: Use PowerShell Script
```powershell
Set-ExecutionPolicy -ExecutionPolicy RemoteSigned -Scope CurrentUser
.\run-desktop.ps1
```

---

## 🔧 Prerequisites Installation

### Step 1: Install Node.js

1. Visit https://nodejs.org/
2. Download **LTS (Long Term Support)** version
3. Run the installer
4. **IMPORTANT**: Check "Add to PATH" during installation
5. Click "Install"
6. Verify installation in Command Prompt:
   ```
   node --version
   npm --version
   ```

### Step 2: Verify PHP Installation

1. Open Command Prompt
2. Type: `php --version`
3. You should see: `PHP 8.x.x` or higher
4. If not found, PHP needs to be installed or added to PATH

---

## 🚀 Running the Application

### Method 1: Batch Script (Easiest)
```
Double-click: run-desktop.bat
```

### Method 2: PowerShell
```powershell
.\run-desktop.ps1
```

### Method 3: Command Line
```bash
npm install
npm start
```

**What happens:**
- ✅ PHP server starts on localhost:8000
- ✅ Electron window opens automatically
- ✅ Application fully functional

---

## 📦 Building the Installer

### Option 1: Use Batch Script
```
Double-click: build-installer.bat
```

### Option 2: Command Line
```bash
npm run build-win
```

**Output:**
- `Library Management System Setup 1.0.0.exe` - Full installer
- `Library Management System 1.0.0.exe` - Portable executable

**Generated files location:** `dist/` folder

---

## 📁 Project Structure

```
Library Management System/
├── 📄 run-desktop.bat           ← Double-click to run (Windows)
├── 📄 run-desktop.ps1           ← PowerShell launcher
├── 📄 build-installer.bat       ← Build Windows installer
├── 📄 package.json              ← Project configuration
├── 📂 electron/
│   ├── main.js                  ← Electron main process
│   └── preload.js               ← Security config
├── 📂 php/
│   ├── auth.php                 ← Login/Register
│   ├── books.php                ← Book operations
│   ├── user.php                 ← User management
│   └── db.php                   ← Database layer
├── 📂 data/                     ← Auto-created JSON database
│   ├── users.json
│   ├── books.json
│   ├── borrowing.json
│   ├── payment_methods.json
│   └── activity_log.json
├── 📂 assets/
│   └── style.css                ← Application styling
├── 📄 index.html                ← Login page
└── 📄 dashboard.html            ← Main interface
```

---

## ⚡ Features

✅ **Standalone Desktop Application**
- No browser needed
- Looks like a real Windows app
- Taskbar integration

✅ **Integrated Server**
- PHP server runs automatically
- No manual server startup needed
- Runs on localhost:8000

✅ **Complete Database**
- JSON-based storage (no installation needed)
- Auto-created on first run
- Stores users, books, borrowings, payments

✅ **Full Functionality**
- User registration & login
- Book browsing & searching
- Book borrowing & returning
- Profile management
- Activity tracking
- Payment method storage

---

## 🔑 First Time Setup

### 1. Launch Application
Double-click `run-desktop.bat`

### 2. Create Account
- Click "Register here"
- Fill in details:
  - Full Name
  - Email
  - Phone Number
  - Password
  - Card Details (securely stored)
- Click "Create Account"

### 3. Login
- Use your email and password
- Click "Login"

### 4. Enjoy!
- Browse the library
- Borrow books
- Manage your profile

---

## 🆘 Troubleshooting

### Problem: "Node.js not found"
**Solution:**
- Download from https://nodejs.org/
- Run installer
- **Check "Add to PATH"**
- Restart Command Prompt

### Problem: "PHP not found"
**Solution:**
- PHP is already on your system (used in the web version)
- Ensure it's added to PATH
- Or update your PATH in Environment Variables

### Problem: Port 8000 already in use
**Solution:**
- Another application is using port 8000
- Close other applications
- Or modify `electron/main.js` line 18 to use different port

### Problem: "Cannot find module" error
**Solution:**
```bash
rm -r node_modules
npm install
npm start
```

### Problem: Application won't start
**Solution:**
1. Verify PHP works: `php -v`
2. Verify Node.js works: `node -v`
3. Check that all `.php` files exist in `/php/` folder
4. Check that `/data/` folder exists

### Problem: Data not saving
**Solution:**
- Ensure `/data/` folder has write permissions
- Check that JSON files aren't corrupted
- Restart the application

---

## 🎯 Development

### Run in Development Mode
```bash
npm start
```
- Opens DevTools automatically
- Hot-reload available
- See console logs

### Building for Distribution
```bash
npm run build-win
```
Creates installer in `dist/` folder

### Building Portable Version Only
```bash
npm run build -- --publish never
```

---

## 📊 Data Location

All user data is stored in JSON files:
- **Users:** `data/users.json`
- **Books:** `data/books.json`
- **Borrowing Records:** `data/borrowing.json`
- **Payment Methods:** `data/payment_methods.json`
- **Activity Log:** `data/activity_log.json`

**Data is stored locally on your computer** - No cloud storage, no privacy concerns!

---

## 🔒 Security

✅ Passwords hashed with bcrypt (PHP PASSWORD_DEFAULT)
✅ Card data stored securely in local JSON
✅ Session-based authentication
✅ Input validation on all forms
✅ No internet connection required after startup

---

## 📝 First User Credentials (Demo)

After running the build version, a default book catalog includes:
- The Great Gatsby
- To Kill a Mockingbird
- 1984
- A Brief History of Time
- Sapiens
- Clean Code
- Design Patterns
- The Selfish Gene

---

## 🎨 Customization

### Change Application Icon
Replace `assets/icon.png` and `assets/icon.ico` with your own

### Change Application Name
Edit `package.json`:
```json
"productName": "Your App Name",
"name": "your-app-name"
```

### Change Default Window Size
Edit `electron/main.js`:
```javascript
width: 1400,      // Change this
height: 900,      // And this
```

---

## 📞 Support

If you encounter issues:
1. Check that all prerequisites are installed
2. Review the Troubleshooting section above
3. Ensure all files are in correct locations
4. Try running `npm install` again

---

## 📦 Creating Distribution Package

### Step 1: Build Installer
```bash
npm run build-win
```

### Step 2: Share Files
Files in `dist/` folder:
- `Library Management System Setup 1.0.0.exe` - Installer for distribution
- `Library Management System 1.0.0.exe` - Portable version (no installation needed)

### Step 3: Users Can:
- **Install:** Run `Setup 1.0.0.exe` and follow prompts
- **Run Portable:** Extract and run `1.0.0.exe` directly

---

## ✅ Verification Checklist

- [ ] Node.js installed (`node -v` works)
- [ ] PHP installed (`php -v` shows 8.0+)
- [ ] All files present in correct folders
- [ ] Can run `npm install` successfully
- [ ] Application starts with `npm start`
- [ ] Can register a new account
- [ ] Can login with created account
- [ ] Can browse and borrow books
- [ ] Can view profile and payment methods
- [ ] Can build installer (`npm run build-win`)

---

## 🎉 Success!

Your Library Management System is now ready to use as a desktop application!

**Version:** 1.0.0
**Platform:** Windows (x64)
**Technology:** Electron + PHP + HTML5
