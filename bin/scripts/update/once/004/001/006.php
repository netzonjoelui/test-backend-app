<?php
/**
 * Add the messages application in the applications table
 *
 * This script will check if the messages is existing in the applications table
 * If not, then it will add a new entry for messages
 */
use Netric\Account\Module\Module;

$account = $this->getAccount();
$serviceManager = $account->getServiceManager();
$db = $serviceManager->get("Netric/Account/Module/DataMapper/DataMapper");

$messageModule = $db->get("messages");

// Check if the email message is not yet in the applications table
if(!$messageModule)
{
    $module = new Module();

    $data = array(
        "id" => null,
        "name" => "messages",
        "title" => "Messages",
        "short_title" => "Messages",
        "scope" => Module::SCOPE_EVERYONE,
        "system" => true,
        "icon" => "envelope-o",
        "default_route" => "all-messages"
    );

    // Import the email message data
    $module->fromArray($data);

    // Set the module as dirty so it can be saved
    $module->setDirty(true);

    // Save the email message module
    $result = $db->save($module);
}