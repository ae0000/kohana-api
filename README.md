## Kohana-api information ##
###### *Last edited: Wed, 16 July 2010 (stroppytux)* ######
The kohana-api module is meant as a drop in solution for websites needing clients
to be able to have access to the data remotely.

The basic structure behind the API is divided into 4 distinct sections that each
perform an action on the input or output payload. The 4 sections are:

1. #### headers ####
	Requests received from a client contain HTTP/1.1 headers that inform the API
	on what type of payload the request contains and the type of result the client
	expects back. The Api_Header class handles all this information.

2. #### payload ####
	When a client sends a request, the information inside the request is converted
	into a universialy usable payload, and the output that needs to be sent back
	to the client uses the same payload construct. This is handled by the Api_Payload
	class.

3. #### parsers ####
	When the client sends information that needs to be handled, the information
	first get changed into a payload. The payload is then passed to the code that
	will generate the output. The Api_Parser decides on the type of information
	the client sent, then converts that information into a payload using one of
	the parsers defined in the /classes/api/parser/ directory.

4. #### creators ####
	The client has the ability to specify a list of acceptable formats it can
	process by setting the Accept-Type HTTP/1.1 header. If the client specifies
	a format that we have a creator for (located in /classes/api/creators), then
	the Api_Creator will take the output payload and convert it into the format
	to be returned to the client.

### Testing the API ###
When the API is first installed into the modules directory within Kohana, and
you have enabled the module in the /application/bootstrap.php file, you will
have access to the default API controller. This controller just parses information
received from a client, then creates the output information to mirror the input.

A bash script located in `/modules/api/docs/tests` has been created in order to
test the API. Edit the configuration file to point to your server, then run:

* Send an **XML** request to the client receiving **XML** back

	modules/api/docs/tests/api.sh

* Send an **XML** request receiving **JSON** back

	modules/api/docs/tests/api.sh xml 0.8

* Send a **JSON** request receiving **XML** back

	modules/api/docs/tests/api.sh json 0.6

* Send a **GET** request receiving **JSON** back

	modules/api/docs/tests/api.sh get 0.8

The `api.sh` can accept two parameters:

1. ### Reqest payload ###
	The first parameter is the type of payload you would like to send to the server.
	This can be on of: `xml`, `json`, `post`, or `get`. The payload content is
	defined in the payloads.(xml|json|form). The *GET* and *POST* requests both
	use the payload.form file.

2. ### Response payload ###
	The second parameter contains a numerical value that is passed into the
	Accept-Type header. The Accept-Type has a default value defined as **0.5**
	for the request payload you are sending. If you set the second value to any
	value higher than this, the server will return the payload type set in the
	Accept-Type header.

### Integrating the API ###

The API can be integrated into your own code in many different ways. As an example
I have created a controller called `example.php` that loads a class in the
*/classes/process* directory, then calls methods within that class. The received
payload needs to be nested appropriatly. Any values nested deeper in the request
payload will be passed into the method as parameters.


