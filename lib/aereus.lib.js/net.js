/**
 * @fileOverview alib.net Is the network namespace
 *
 * @author:	joe, sky.stebnicki@aereus.com; 
 * 			Copyright (c) 2013 Aereus Corporation. All rights reserved.
 */

/**
 * Create net namespace
 *
 * @object
 */
alib.net = {};

/**
 * Optinal request prefix used before any net http requests
 *
 * This is commonly used to redirect requests like in the case where
 * we are unit testing and a call to /controller/myCon while
 * running in js-test-driver at an alternative port will not return
 * anything, setting this to "http://localhost" will force requests
 * away from the test server and to the local apache server.
 *
 * @var {string}
 */
alib.net.prefixHttp = "";
