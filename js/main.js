let conn = null
let name;

// init
$('#logoff').hide();
//$('.message-content').hide();

// events functions
$('#btn-login').click(function() {
	var n = $('#user-name').val();
	if(n == '') {
		alert('Empty name.');
	} else {
		name = n;
		conn = new connect();
		$('#login').hide();
		$('#logoff').show();
		//$('.message-content').show();
	}
});

$('#user-name').keydown(function(event){
	if(event.key == "Enter") {
		$('#btn-login').click();
	}
});

$('#btn-logoff').click(function() {
	conn.closeConnection();
	conn = null;
	$('#logoff').hide();
	//$('.message-content').hide();
	$('#login').show();
});

$('#btn-send').click(function(){
	if(conn != null) {
		var msg = $('#message').val();
		$('#message').val('');
		conn.sendMessage(msg);
	}
});

$('#message').keydown(function(event){
	if(event.key == 'Enter') {
		$('#btn-send').click();
	}
});

// Component Functions
function addMessage(msg, username, sendTime) {
	$('#messages').val($('#messages').val()+sendTime+'	'+username+': '+msg+'\n\n');
}

function clearComponents() {
	$('#messages').val('');
	$('#message').val('');
	$('#online-peoples').val('');
}

function addOnlineUsers(users) {	
	let textArea = $('#online-peoples');
	textArea.val('');	
	let i = 0;
	for(i = 0; i < users.length; i++) {		
		textArea.val(textArea.val()+users[i]+'\n');
	}
}



// connection functions.
function connect() {
	
	let connection;
	
	connection = new WebSocket('ws://localhost:8080/server.php');
	
	connection.onopen = function() {
		connection.send(buildData('name', name));
		alert('Welcome to the chat. Developer By Matheus Henrique Pitz and IO Apps. \n http://www.ioapps.com.br/');
	};
	connection.onerror = function(error) {
		alert('error');
	};
	connection.onmessage = function(e) {			
		let jsonData = JSON.parse(e.data);		
		if(jsonData.type == 'message') {
			addMessage(jsonData.data, jsonData.fromName, jsonData.sendTime);			
		} else {						
			addOnlineUsers(jsonData.data);
		}
	};
	connection.onclose = function() {
		alert('closed');
	};
	
	this.sendMessage = function(msg) {
		connection.send(buildData('message', msg));
	}
	
	this.closeConnection = function() {
		connection.close();
	}
	
	/*
	type = message/name
	data = your data.
	*/
	function buildData(type, data) {
		return '{"type": "'+type+'", "data": "'+data+'"}';
	}
}