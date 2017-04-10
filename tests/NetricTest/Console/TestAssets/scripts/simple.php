<?php
/**
 * This is a simple test script used to verify the BinScript can run scripts in the right application context
 */

$account = $this->getAccount();

// Change the name so the unit test can check
$data = $account->toArray();
$data['description'] = "edited";
$account->fromArray($data);