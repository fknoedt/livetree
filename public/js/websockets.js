/**
 * javascript used to control WebSocket oriented page
 * @type {WebSocket}
 */

// connects to WebSocket to listen to update messages
var conn = new WebSocket(wsProtocol + '://' + wsHost + ':' + wsPort);

conn.onopen = function(e)
{

	// logs connection
	document.getElementById('console_log').innerHTML = 'websocket connected<br/>' + document.getElementById('console_log').innerHTML;


};

conn.onmessage = function(e)
{

	if(e.data == 'reload')
		reloadTree();

	document.getElementById('console_log').innerHTML = 'websocket: ' + e.data + '<br/>' + document.getElementById('console_log').innerHTML;

};

conn.onclose = function(e)
{

	alert('disconnected: ' + e.data);

	// call it again so it creates a new conn
	require ('./websockets.js');

	alert('websocket.js called');


}