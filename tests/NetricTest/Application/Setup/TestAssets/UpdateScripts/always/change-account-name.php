<?php
/**
 * This is an example of an 'always' script that runs every single time an account is updated
 *
 * We use it in unit tests to assure always scripts are run by updating the name
 */

$account = $this->getAccount();

// Change the name so the unit test can check
$data = $account->toArray();
$data['description'] = "always";
$account->fromArray($data);