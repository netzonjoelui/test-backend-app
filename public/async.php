<?php
/**
 * Entry point for ActiveSync api calls. This code pretty much just re-routes everything
 * to our z-push code so most of what is below is just getting it setup to run and
 * including it.
 */

/*
 * Tell z-push to use a custom location for the config file
 */
define('ZPUSH_CONFIG', __DIR__ . '/../config/zpush.config.php');

/*
 * Set execution path relative to lib/ZPush
 */
chdir(dirname(__DIR__) . "/lib/ZPush");

/*
 * Load index of z-push and hand off execution
 */
include("index.php");
