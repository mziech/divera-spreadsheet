#!/bin/sh

echo "Enter access key:"
read key

curl -o all.json -X GET "https://www.divera247.com/api/v2/pull/all?accesskey=$key" -H "accept: application/json"


echo "Enter access key:"
read key

curl -o events.json -X GET "https://www.divera247.com/api/v2/events?accesskey=$key" -H "accept: application/json"
