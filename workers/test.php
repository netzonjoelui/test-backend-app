<?php
require_once('lib/Worker.php'); 
require_once('lib/pdf/class.ezpdf.php'); 

if (is_array($g_workerFunctions))
{
    $g_workerFunctions["tests/background"] = "test_background";
    $g_workerFunctions["tests/deferred"] = "test_deferred";
}

function test_background($job)
{
    $data = $job->workload();

    return strrev($data);
}

function test_deferred($job)
{
    $data = $job->workload();
    $job->defer(60);
    return strrev($data);
}
