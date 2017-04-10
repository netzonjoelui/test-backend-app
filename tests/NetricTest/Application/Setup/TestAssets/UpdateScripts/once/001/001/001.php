<?php
/**
 * This is a test update which should do two things:
 *
 * 1. It increments the schema version of the account which happens automatically
 *
 * 2. It updates the name of the account so the unit test can confirm the update ran
 */

$account = $this->getAccount();

// Change the name so the unit test can check
$data = $account->toArray();
$data['description'] = "edited";
$account->fromArray($data);