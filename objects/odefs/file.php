<?php
/**
 * AntFs file object
 *
 * Name: file
 */
$obj_revision = 22;
$icon = "file";
$parentField = "folder_id";

$obj_fields = array(
	'name' => array(
		'title'=>'Name',
		'type'=>'text',
		'subtype'=>'512',
		'readonly'=>false,
	),

	// Size in bytes
	'file_size' => array(
		'title'=>'Size',
		'type'=>'number',
		'subtype'=>'',
		'readonly'=>true,
	),

	// The filetype extension
	'filetype' => array(
		'title'=>'Name',
		'type'=>'text',
		'subtype'=>'32',
		'readonly'=>true,
	),

	// where the file is stored in the storage engine
	'storage_path' => array(
		'title'=>'Storage Path',
		'type'=>'text',
		'subtype'=>'',
		'readonly'=>true,
	),

	// Deprecated - path to local file on server
	'dat_local_path' => array(
		'title'=>'Lcl Path',
		'type'=>'text',
		'subtype'=>'',
		'readonly'=>true,
	),

	// Deprecated - key used on ANS server
	'dat_ans_key' => array(
		'title'=>'ANS Key',
		'type'=>'text',
		'subtype'=>'',
		'readonly'=>true,
	),

);

// Timestamps
$default = array("value"=>"now", "on"=>"update");
$obj_fields['ts_updated']	= array('title'=>'Last Updated', 'type'=>'timestamp', 'subtype'=>'', 'readonly'=>true, 'default'=>$default);
$default = array("value"=>"now", "on"=>"null");
$obj_fields['ts_entered']	= array('title'=>'Time Entered', 'type'=>'timestamp', 'subtype'=>'', 'readonly'=>true, 'default'=>$default);

// References
$obj_fields['folder_id'] = array('title'=>'Folder',
									  'type'=>'object',
									  'subtype'=>'folder');

$obj_fields['owner_id'] = array('title'=>'Owner',
									  'type'=>'object',
									  'subtype'=>'user',
									  'default'=>array("value"=>"-3", "on"=>"null"),
								  	);

$default_form_mobile_xml = "";

// Set views
$obj_views = array();

$view = new CAntObjectView();
$view->id = "sys_1";
$view->name = "Files &amp; Documents";
$view->description = "View all files and directories";
$view->fDefault = true;
$view->view_fields = array("name", "ts_updated", "owner_id", "file_size");
//$view->conditions[] = new CAntObjectCond("and", "owner_id", "is_equal", "-3");
$view->sort_order[] = new CAntObjectSort("name", "asc");
$obj_views[] = $view;
unset($view);
