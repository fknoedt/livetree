// creates an EventSource object
if (typeof (EventSource) !== 'undefined') {

	var source = new EventSource("/index.sse.php");

	// upon connection opening
	source.onopen = function (event) {
		console.log('onopen', event);
	};

	// upon connection error
	source.onerror = function (event) {
		console.log('onerror', event);
	};

	// upon message retrieving
	source.addEventListener(
		'reload',
		function (event) {

			if(isJson(event.data)) {

				var aData = JSON.parse(jsonData);

			}
			else {

				var aData = {message: event.data}

			}

			var response = 'Tree Reloaded';

			// notify each line of data response
			if(! isEmpty(aData)) {

				for (var fieldId in aData)
					response += '\n' + aData[fieldId];

			}

			// logs output to console
			document.getElementById('console_log').innerHTML = 'reload: ' + event.data + '<br/>' + document.getElementById('console_log').innerHTML;

			$(".livetree_logo").notify(response, { position:"bottom center", className: "warn" });

			reloadTree();

		}

	);

	source.addEventListener(
		"ping",
		function(event) {

			// logs output to console
			document.getElementById('console_log').innerHTML = 'ping: ' + event.data + '<br/>' + document.getElementById('console_log').innerHTML;

		}
	);

}
else {

	// throws functional error
	document.getElementById("result").innerHTML = 'Sorry, your "browser" does not support server-sent events. Get a real browser =)';

}