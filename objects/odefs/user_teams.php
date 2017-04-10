<?php
/**
 * user_teams object definition
 */
$obj_revision = 2;

$isPrivate = true;
$defaultActivityLevel = 1;
$storeRevisions = true;

$obj_fields = array(

    'name' => array(
        'title'=>'Name',
        'type'=>'text',
        'subtype'=>'256',
        'readonly'=>false,
        'require'=>true
    ),

    'parent_id' => array(
        'title'=>'Parent',
        'type'=>'integer',
        'readonly'=>false,
        'require'=>false
    )
);