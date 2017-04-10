<?php
/**
 * Default users that should exist in every account
 */
namespace data\account;

use Netric\Entity\ObjType\UserEntity;

return array(
    array(
        "id"=>UserEntity::USER_ANONYMOUS,
        "name"=>"anonymous",
        "full_name"=>"Anonymous"
    ),
    array(
        "id"=>UserEntity::USER_CURRENT,
        "name"=>"current",
        "full_name"=>"Current User"
    ),
    array(
        "id"=>UserEntity::USER_SYSTEM,
        "name"=>"system",
        "full_name"=>"System"
    ),
    array(
        "id"=>UserEntity::USER_WORKFLOW,
        "name"=>"workflow",
        "full_name"=>"Workflow"
    ),
);
