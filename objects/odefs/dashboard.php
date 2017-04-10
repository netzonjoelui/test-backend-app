<?php
/**
 * dashboard object definition
 */
$obj_revision = 24;
$defaultActivityLevel = 2;

$obj_fields = array();
$obj_fields['name']					= array('title'=>'Name', 'type'=>'text', 'subtype'=>'512', 'required'=>true, 'readonly'=>false);
$obj_fields['description']			= array('title'=>'Description', 'type'=>'text', 'subtype'=>'', 'readonly'=>false);
$obj_fields['app_dash']				= array('title'=>'Application Dashboard', 'type'=>'text', 'subtype'=>'256', 'readonly'=>true);
$obj_fields['layout']				= array('title'=>'Layout', 'type'=>'text', 'subtype'=>'', 'readonly'=>true);

$obj_fields['num_columns']				= array('title'=>'Num Columns',
											  'type'=>'number',
											  'subtype'=>'',
											  'optional_values'=>array("1"=>"One", "2"=>"Two", "3"=>"Three"));

$obj_fields['scope']				= array('title'=>'Scope',
											  'type'=>'text',
											  'subtype'=>'32',
											  'optional_values'=>array("system"=>"System/Everyone", "user"=>"User"));

$obj_fields['groups'] = array('title'=>'Groups',
								  'type'=>'fkey_multi',
								  'subtype'=>'object_groupings',
								  'fkey_table'=>array("key"=>"id", "title"=>"name", "parent"=>"parent_id",
																									"ref_table"=>array(
																									"table"=>"object_grouping_mem", 
																									"this"=>"object_id", 
																									"ref"=>"grouping_id"
																												   )));

// Timestamps
$default = array("value"=>"now", "on"=>"create");
$obj_fields['ts_entered']	= array('title'=>'Time Created', 'type'=>'timestamp', 'subtype'=>'', 'readonly'=>true, 'default'=>$default);
$default = array("value"=>"now", "on"=>"update");
$obj_fields['ts_updated']	= array('title'=>'Time Updated', 'type'=>'timestamp', 'subtype'=>'', 'readonly'=>true, 'default'=>$default);

// References
$default = array("value"=>-3, "on"=>"null");
$obj_fields['owner_id'] = array('title'=>'Owner',
									  'type'=>'object',
									  'subtype'=>'user',
								  	  'default'=>$default);
                                      
// Set views
$obj_views = array();

$view = new CAntObjectView();
$view->id = "sys_1";
$view->name = "All Dashboards";
$view->description = "Viewing All Dashboards";
$view->fDefault = true;
$view->view_fields = array("name", "description");
//$view->conditions[] = new CAntObjectCond("and", "owner_id", "is_equal", "-3");
$view->sort_order[] = new CAntObjectSort("name", "asc");
$obj_views[] = $view;
unset($view);

?>
