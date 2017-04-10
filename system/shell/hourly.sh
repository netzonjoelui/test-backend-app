#!/usr/local/bin/bash

# Copy this file to the root/scheduled (or similar dir)
# do not edit directly, it will be overwritten on update
workdir="/var/www/html/ant.aereus.com/system"
phpdir="/usr/local/bin"
logdir="/tmp"

cd $workdir

$phpdir/php email_detach.php $1 > $logdir/ant_eml_det.log
$phpdir/php uf_to_ans.php $1 > $logdir/ant_uftoans.log
