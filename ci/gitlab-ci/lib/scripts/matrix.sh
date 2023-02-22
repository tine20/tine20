#!/bin/sh
matrix_send_message() {
    roomid=$1
    message=$2

    if test -z "$MATRIX_SERVER"; then
        echo "MATRIX_SERVER needs to be set!"
        return 1
    fi

    if test -z "$MATRIX_ACCESS_TOKEN"; then
        if test -z "$MATRIX_USERNAME" || test -z "$MATRIX_PASSWORD"; then
            echo "Either MATRIX_ACCESS_TOKEN or MATRIX_USERNAME and MATRIX_PASSWORD needs to be set!"
            return 1
        fi
    
        response=$(curl -s -XPOST -d '{"type":"m.login.password", "user":"'"$MATRIX_USERNAME"'", "password":"'"$MATRIX_PASSWORD"'"}' "https://$MATRIX_SERVER/_matrix/client/r0/login")
        MATRIX_ACCESS_TOKEN=$(echo "$response" | jq -r '.access_token')
        
    fi

    curl -XPOST -d '{}' "https://$MATRIX_SERVER/_matrix/client/r0/rooms/$roomid/join?access_token=$MATRIX_ACCESS_TOKEN"

    curl -XPOST -d '{"msgtype":"m.text", "body":"'"$message"'"}' "https://$MATRIX_SERVER/_matrix/client/r0/rooms/$roomid/send/m.room.message?access_token=$MATRIX_ACCESS_TOKEN"
}
