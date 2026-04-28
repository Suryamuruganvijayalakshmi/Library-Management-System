const { contextBridge, ipcRenderer } = require('electron');

// Expose protected methods that allow the renderer process to use
// the ipcRenderer without exposing the entire object
contextBridge.exposeInMainWorld('electron', {
  ipcRenderer: {
    invoke: (channel, ...args) => ipcRenderer.invoke(channel, ...args),
    on: (channel, func) => {
      ipcRenderer.on(channel, (event, ...args) => func(...args));
    },
    off: (channel, func) => {
      ipcRenderer.off(channel, func);
    }
  }
});

// Expose app version
contextBridge.exposeInMainWorld('app', {
  getVersion: () => ipcRenderer.invoke('get-app-version')
});
