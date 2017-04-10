#!/usr/local/bin/bash

# Copy this file to the root/scheduled (or similar dir)
# do not edit directly, it will be overwritten on update
workdir="/var/www/html/ant.aereus.com/system"
phpdir="/usr/local/bin"

cd $workdir

$phpdir/php error_report.php
# Uncomment the below if this is a worker node
#$phpdir/php worker.php
$phpdir/php cal_email_reminders.php
$phpdir/php workflow_actions.php
$phpdir/php index_queue_process.php
