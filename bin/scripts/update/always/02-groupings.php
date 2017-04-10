<?php
/**
 * Add default groups only if none exist - allowing accounts to override defaults
 */
use Netric\EntityGroupings\Group;

$account = $this->getAccount();
if (!$account)
    throw new \RuntimeException("This must be run only against a single account");

$defaultGroups= require("data/account/groupings.php");

$groupingsLoader = $account->getServiceManager()->get("EntityGroupings_Loader");

foreach ($defaultGroups as $objType=>$fields) {
    foreach ($fields as $fieldName=>$groupsData) {

        // Get groupings for each objType and $fieldName
        $groupings = $groupingsLoader->get($objType, $fieldName);

        // Only create default groupings if none exist
        if (count($groupings->getAll()) > 0) {
            continue;
        }

        // Loop through each group and add
        foreach ($groupsData as $groupData) {
            if (!$groupings->getByName($groupData['name'])) {
                $group = new Group();

                // Required data
                $group->name = $groupData['name'];

                if (isset($groupData['id']))
                    $group->id = $groupData['id'];

                if (isset($groupData['color']))
                    $group->color = $groupData['color'];

                if (isset($groupData['sort_oder']))
                    $group->sortOrder = $groupData['sort_oder'];

                $groupings->add($group);
            }
        }

        // Save changes to groupings
        $groupingsLoader->save($groupings);
    }
}