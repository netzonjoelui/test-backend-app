#!/usr/local/bin/bash

# Copy this file to the root/scheduled (or similar dir)
# do not edit directly, it will be overwritten on update
workdir="/doc_root/system"
phpdir="/usr/aereus/apache/php5/bin"

cd $workdir

$phpdir/php cal_email_reminders.php
$phpdir/php workflow_actions.php
