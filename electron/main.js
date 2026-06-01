const { app, BrowserWindow, Menu, ipcMain } = require('electron');
const path = require('path');
let mainWindow;
let serverInstance;
const isDev = process.env.NODE_ENV === 'development';

// Start local Node server
function startNodeServer() {
  return new Promise((resolve, reject) => {
    try {
      const api = require(path.join(__dirname, '..', 'api', 'index.js'));
      serverInstance = api.startServer(process.env.PORT || 3000, resolve);
    } catch (err) {
      reject(err);
    }
  });
}

// Create window
function createWindow() {
  mainWindow = new BrowserWindow({
    width: 1400,
    height: 900,
    minWidth: 1024,
    minHeight: 768,
    webPreferences: {
      nodeIntegration: false,
      contextIsolation: true,
      enableRemoteModule: false,
      preload: path.join(__dirname, 'preload.js')
    },
    icon: path.join(__dirname, '../assets/icon.png')
  });

  mainWindow.loadURL('http://localhost:3000');

  // Open DevTools in development
  if (isDev) {
    mainWindow.webContents.openDevTools();
  }

  mainWindow.on('closed', () => {
    mainWindow = null;
  });
}

// App event handlers
app.on('ready', async () => {
  try {
    await startNodeServer();
    createWindow();
    createMenu();
  } catch (err) {
    console.error('Failed to start application:', err);
    app.quit();
  }
});

app.on('window-all-closed', () => {
  if (process.platform !== 'darwin') {
    app.quit();
  }
});

app.on('activate', () => {
  if (mainWindow === null) {
    createWindow();
  }
});

// Cleanup on app quit
app.on('before-quit', () => {
  if (serverInstance && typeof serverInstance.close === 'function') {
    serverInstance.close();
  }
});

// Create menu
function createMenu() {
  const template = [
    {
      label: 'File',
      submenu: [
        {
          label: 'Exit',
          accelerator: 'CmdOrCtrl+Q',
          click: () => {
            app.quit();
          }
        }
      ]
    },
    {
      label: 'Edit',
      submenu: [
        { role: 'undo' },
        { role: 'redo' },
        { type: 'separator' },
        { role: 'cut' },
        { role: 'copy' },
        { role: 'paste' }
      ]
    },
    {
      label: 'View',
      submenu: [
        { role: 'reload' },
        { role: 'forceReload' },
        { role: 'toggleDevTools' },
        { type: 'separator' },
        { role: 'resetZoom' },
        { role: 'zoomIn' },
        { role: 'zoomOut' },
        { type: 'separator' },
        { role: 'togglefullscreen' }
      ]
    },
    {
      label: 'Help',
      submenu: [
        {
          label: 'About Library Management System',
          click: () => {
            // You can create an about window here
          }
        }
      ]
    }
  ];

  const menu = Menu.buildFromTemplate(template);
  Menu.setApplicationMenu(menu);
}

// Handle IPC events (if needed for communication between main and renderer)
ipcMain.handle('get-app-version', () => {
  return app.getVersion();
});
