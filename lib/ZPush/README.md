# Z-Push for Netric

We utilize Z-Push to enable ActiveSync in netric.

There are only 3 edits that need to be preserved when updating:

1. ./backend/netric/* 
2. ./README.md (this file)
3. lib/core/zlog.php

In zlog we have modified it to push anything but debug to the system log tagged with netric
so errors can be logged with the rest of netric. All edits begin with 
/* BEGIN NETRIC CUSTOMIZATION */ and end with /* BEGIN NETRIC CUSTOMIZATION */

It is important that NO other files are updated because we can keep z-push 
updated to the latest version by continuing to download and replace all the files in this directory.

The config is in ../config/zpush.config.php and our entry point in ../public/async.php
which sets up z-push to run within netric and then loads ./index.php.