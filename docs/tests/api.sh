#!/bin/bash
# Load in our configuration file
source `dirname ${0}`/test.conf

# Create some test payloads
POST_XML="`dirname ${0}`/${xml}";
POST_JSON="`dirname ${0}`/${json}";
POST_FORM="`dirname ${0}`/${form}";

# Define the default headers used by any payload type
POST_HOST=${hostname}
POST_HEAD="HTTP/1.1\r\nHost: ${POST_HOST}\r\nConnection: close\r\n"
POST_HEAD="${POST_HEAD}Accept-Language: ${language};q=0.5, en-us;q=0.3\r\n"

# Check if we want to inable compression
if ${compress}; then
	POST_HEAD="${POST_HEAD}Accept-Encoding: gzip\r\n"
fi

# Set the checking functions for linux and darwin
if [ `uname` == 'Linux' ]; then
	STAT='stat -c%s'
	MD5H='md5sum'
elif [ `uname` == 'Darwin' ]; then
	STAT='stat -f%z'
	MD5H='md5 -q'
fi

# Set the default payload defined in the conf file
if [ ${1} ]; then
	default=${1}
fi

# Check if we are testing the xml or the json request
case "${default}" in
	get)
		POST_HEAD="${POST_HEAD}Accept: application/xml;q=0.5, application/json;q=${2:-0.4}\r\n"
		QUERY="GET /api?$(cat ${POST_FORM}) ${POST_HEAD}"
		PAYLOAD='/dev/null'
		;;
	post)
		POST_HEAD="${POST_HEAD}Content-Type: application/x-www-form-urlencoded; charset=\"utf-8\"\r\n"
		POST_HEAD="${POST_HEAD}Content-Length: `${STAT} ${POST_FORM}`\r\n"
		POST_HEAD="${POST_HEAD}Content-MD5: `${MD5H} ${POST_FORM}|sed 's/ .*//g'`\r\n"
		POST_HEAD="${POST_HEAD}Accept: application/xml;q=0.5, application/json;q=${2:-0.4}\r\n"
		QUERY="POST /api ${POST_HEAD}"
		PAYLOAD=${POST_FORM}
		;;
	xml)
		POST_HEAD="${POST_HEAD}Content-Type: application/xml; charset=\"utf-8\"\r\n"
		POST_HEAD="${POST_HEAD}Content-Length: `${STAT} ${POST_XML}`\r\n"
		POST_HEAD="${POST_HEAD}Content-MD5: `${MD5H} ${POST_XML}|sed 's/ .*//g'`\r\n"
		POST_HEAD="${POST_HEAD}Accept: application/xml;q=0.5, application/json;q=${2:-0.4}\r\n"
		QUERY="POST /api ${POST_HEAD}"
		PAYLOAD=${POST_XML}
		;;
	json|*)
		POST_HEAD="${POST_HEAD}Content-Type: application/json; charset=\"utf-8\"\r\n"
		POST_HEAD="${POST_HEAD}Content-Length: `${STAT} ${POST_JSON}`\r\n"
		POST_HEAD="${POST_HEAD}Content-MD5: `${MD5H} ${POST_JSON}|sed 's/ .*//g'`\r\n"
		POST_HEAD="${POST_HEAD}Accept: application/json;q=0.5, application/xml;q=${2:-0.4}\r\n"
		QUERY="POST /api ${POST_HEAD}"
		PAYLOAD=${POST_JSON}
		;;
esac

# Check if we need to display the output headers and data
if ${debug}; then
	echo "-- REQUEST HEADERS ---------------------------------------------------"
	echo -e ${POST_HEAD}
	echo -e "\n-- REQUEST PAYLOAD ---------------------------------------------------"
	cat ${PAYLOAD}
	echo -e "\n-- RESPONSE ----------------------------------------------------------"
fi

# Transmit our POST data to the server and await a response
exec 3<>/dev/tcp/${POST_HOST}/80 || exit 1
echo -e ${QUERY} >&3; cat "${PAYLOAD}" >&3
cat <&3 #| more

