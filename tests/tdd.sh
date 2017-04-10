#!/bin/bash
while true ; do
	inotifywait -qq *.php &&
	clear &&
	phpunit --colors KataTest.php;
done