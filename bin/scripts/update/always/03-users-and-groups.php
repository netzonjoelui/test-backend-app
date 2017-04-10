<?php
/**
 * Add default users and groups for each account
 */
use Netric\EntityGroupings\Group;

$account = $this->getAccount();
if (!$account)
    throw new \RuntimeException("This must be run only against a single account");

/*
 * First make sure default user groups exist
 */
$groupsData = require("data/account/user-groups.php");
$groupingsLoader = $account->getServiceManager()->get("EntityGroupings_Loader");
$groupings = $groupingsLoader->get("user", "groups");
foreach ($groupsData as $groupData) {
    if (!$groupings->getByName($groupData['name'])) {
        $group = new Group();
        $group->id = $groupData['id'];
        $group->name = $groupData['name'];
        $groupings->add($group);
    }
}
$groupingsLoader->save($groupings);

/*
 * Now make sure default users exists - with no password so no login
 */
$usersData = require("data/account/users.php");
$entityLoader = $account->getServiceManager()->get("EntityLoader");
foreach ($usersData as $userData) {
    if (!$entityLoader->get("user", $userData['id'])) {
        $user = $entityLoader->create("user");
        $user->fromArray($userData);
        $entityLoader->save($user);
    }
}