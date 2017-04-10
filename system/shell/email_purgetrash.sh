#!/usr/local/bin/bash

# Copy this file to the root/scheduled (or similar dir)
# do not edit directly, it will be overwritten on update
workdir="/doc_root/system"
phpdir="/usr/local/bin"

cd $workdir

$phpdir/php email_purgetrash.php

