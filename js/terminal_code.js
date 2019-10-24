const Terminal = require('xterm').Terminal
const FitAddon = require('xterm-addon-fit').FitAddon;
const AttachAddon  = require('xterm-addon-attach').AttachAddon 
const io = require('socket.io-client');

const socket = io("http://localhost:80");
const container = document.getElementById('terminal_xterm');
const terminal = new Terminal();
const fitAddon = new FitAddon();
terminal.loadAddon(fitAddon);
terminal.open(container);
fitAddon.fit();

//const attachAddon = new AttachAddon(socket);
//terminal.loadAddon(attachAddon);

/***using socket.io instead of inbuilt xterm attach addon as it is incompatible with socket.io **/
terminal.onData(function (data) {
   socket.emit('data', data);
});

socket.on('data', function (data) {
    terminal.write(data);
});