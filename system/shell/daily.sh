#!/usr/local/bin/bash

# Copy this file to the root/scheduled (or similar dir)
# do not edit directly, it will be overwritten on update
workdir="/var/www/html/ant.aereus.com/system"
phpdir="/usr/local/bin"
logdir="/tmp"

cd $workdir

$phpdir/php accounts_info.php $1 > $logdir/ant_acc.log
$phpdir/php email_purgespam.php $1 > $logdir/ant_eml_pspam.log
$phpdir/php email_purgetrash.php $1 > $logdir/ant_eml_ptrash.log
$phpdir/php email_threadcount.php $1 > $logdir/ant_eml_tcnt.log
$phpdir/php email_index.php $1 > $logdir/ant_eml_ind.log
$phpdir/php sync_newmessages.php $1 > $logdir/ant_eml_synn.log
$phpdir/php workflow_sweep_daily.php $1 > $logdir/ant_dlywf.log
$phpdir/php facebook_sync.php $1 > $logdir/ant_fbsync.log
