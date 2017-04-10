<?php
/**
 * AntFs folder object
 *
 * Name: folder
 */
$obj_revision = 16;
$storeRevisions = false; // no need for revisins to be stored

$icon = "folder";
$parentField = "parent_id";

$obj_fields = array();
$obj_fields['name']	= array('title'=>'Name', 'type'=>'text', 'subtype'=>'512', 'readonly'=>false);
$obj_fields['f_system'] = array('title'=>"System", 'type'=>'bool', 'subtype'=>'', 'readonly'=>true);

// Timestamps
$default = array("value"=>"now", "on"=>"create");
$obj_fields['ts_updated']	= array('title'=>'Time Changed', 'type'=>'timestamp', 'subtype'=>'', 'readonly'=>true, 'default'=>$default);
$default = array("value"=>"now", "on"=>"null");
$obj_fields['ts_entered']	= array('title'=>'Time Entered', 'type'=>'timestamp', 'subtype'=>'', 'readonly'=>true, 'default'=>$default);

// References
$obj_fields['parent_id'] = array('title'=>'Parent',
									  'type'=>'object',
									  'subtype'=>'folder');

$obj_fields['owner_id'] = array('title'=>'Owner',
									  'type'=>'object',
									  'subtype'=>'user',
									  'default'=>array("value"=>"-3", "on"=>"null"),
								  	);

$default_form_xml = "";

$default_form_mobile_xml = "";
