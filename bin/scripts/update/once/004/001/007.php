<?php
/**
 * Removes the table fkey contraint in calendar_events table recur_id column
 */

$account = $this->getAccount();
$serviceManager = $account->getServiceManager();
$db = $serviceManager->get("Netric/Db/Db");

$db->query("ALTER TABLE calendar_events DROP CONSTRAINT IF EXISTS calendar_events_recur_fkey");
$db->query("ALTER TABLE calendar_events ALTER COLUMN recur_id TYPE integer");