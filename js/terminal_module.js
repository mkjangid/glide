/** -----------Created by Manish Jangid------------ 
	------------github @ mkjangid 2019-------------
**/

"use strict";
const Terminal = require('xterm').Terminal
const FitAddon = require('xterm-addon-fit').FitAddon;
const AttachAddon  = require('xterm-addon-attach').AttachAddon;
const io = require('socket.io-client');

var terminal_module = {

	terminals : [],

	initTerminal : function(){
			document.querySelector("#btn-add-terminal").addEventListener("click",function(e){
				terminal_module.addNewTerminal();
			});
			this.addNewTerminal();	
	},

	bringTerminalToFront : function(terminal_index){
		for (let i=0;i<this.terminals.length;i++){
			if (this.terminals[i]!=null && (i+1)!==terminal_index){
				document.getElementById('terminal'+(i+1)).style.display = "none";
			}
		}
		document.getElementById('terminal'+terminal_index).style.display = "block";
	},

	addNewTerminal : function() {
		let containerForTerminals = document.querySelector("#container_terminals");
		let terminalHeaderContainer = document.querySelector("#bottom_header_ul");
		let terminal_index = this.terminals.length+1;

		let element = document.createElement("li");
		let btn_id = "btn-term"+terminal_index;
		let close_btn_id = "btn-close-term"+terminal_index;
		element.innerHTML = '<button type="button" id="'+btn_id+'" >'+ 
			'Terminal &nbsp; <i class="fa fa-times" id="'+close_btn_id+'" aria-hidden="true"></i> </button>';
		element.className = 'bottom_header_ul_li' ;
		element.id = "li-term"+terminal_index;
		terminalHeaderContainer.insertBefore(element, 
			terminalHeaderContainer.children[terminalHeaderContainer.childElementCount-1]);

		document.querySelector("#"+btn_id).addEventListener("click",function(e){
			terminal_module.bringTerminalToFront(terminal_index);
		});

		document.querySelector("#"+close_btn_id).addEventListener("click",function(e){
			e.stopPropagation();
			terminal_module.closeTerminal(terminal_index);
		});

		let element2 = document.createElement("div");
		element2.className = 'terminal_xterm'
		element2.id = "terminal"+terminal_index ;
		containerForTerminals.appendChild(element2);	

		setTimeout(function(){ 
			let socket = io("http://localhost:80");
			const container = document.getElementById('terminal'+terminal_index);
			const terminal = new Terminal();
			const fitAddon = new FitAddon();
			terminal.loadAddon(fitAddon);
			terminal.open(container);
			fitAddon.fit();

			/** inbuilt xterm attach addon is incompatible with socket.io **/
			terminal.onData(function (data) {
			   socket.emit('data', data);
			});

			socket.on('data', function (data) {
			    terminal.write(data);
			});

			terminal_module.terminals[terminal_index-1] = {"socket":socket};

		},1500);

	},

	closeTerminal : function(terminal_index) {
		let socket = this.terminals[terminal_index-1]["socket"] ;
		socket.close();
		this.terminals[terminal_index-1] = null;
		document.getElementById('terminal'+terminal_index).remove();
		document.getElementById('li-term'+terminal_index).remove();
	}
}

module.exports = terminal_module;