<?php
/**
 * Story object used to track features/requirements/stories for projects
 *
 * Name: project_story
 */
$obj_revision = 15;

$obj_fields = array();
$obj_fields['name']				= array('title'=>'Title', 'type'=>'text', 'subtype'=>'512', 'readonly'=>false);
$obj_fields['notes']			= array('title'=>'Description', 'type'=>'text', 'subtype'=>'', 'readonly'=>false);
$obj_fields['date_start']		= array('title'=>'Date Start', 'type'=>'date', 'subtype'=>'', 'readonly'=>false);
$obj_fields['date_completed']	= array('title'=>'Date Completed', 'type'=>'date', 'subtype'=>'', 'readonly'=>false);
$obj_fields['cost_estimated']	= array('title'=>'Estimated Time', 'type'=>'number', 'subtype'=>'', 'readonly'=>false);
$obj_fields['cost_actual'] 		= array('title'=>'Actual Time', 'type'=>'number', 'subtype'=>'', 'readonly'=>false);

// Timestamps
$default = array("value"=>"now", "on"=>"create");
$obj_fields['ts_updated']	= array('title'=>'Time Changed', 'type'=>'timestamp', 'subtype'=>'', 'readonly'=>true, 'default'=>$default);
$default = array("value"=>"now", "on"=>"null");
$obj_fields['ts_entered']	= array('title'=>'Time Entered', 'type'=>'timestamp', 'subtype'=>'', 'readonly'=>true, 'default'=>$default);

// References
$obj_fields['project_id'] = array('title'=>'Project',
									  'type'=>'object',
									  'subtype'=>'project');

$obj_fields['milestone_id'] = array('title'=>'Milestone',
									  'type'=>'object',
									  'subtype'=>'project_milestone');

$obj_fields['owner_id'] = array('title'=>'Owner',
									  'type'=>'object',
									  'subtype'=>'user',
									  'default'=>array("value"=>"-3", "on"=>"null"),
								  	);

$obj_fields['customer_id'] = array('title'=>'Contact',
								   'type'=>'object',
								   'subtype'=>'customer');

$obj_fields['priority_id'] = array('title'=>'Priority',
								  'type'=>'fkey',
								  'subtype'=>'object_groupings',
								  'fkey_table'=>array("key"=>"id", "title"=>"name", "parent"=>"parent_id",
																									"ref_table"=>array(
																									"table"=>"object_grouping_mem", 
																									"this"=>"object_id", 
																									"ref"=>"grouping_id"
																												   )));
$obj_fields['status_id'] = array('title'=>'Status',
								  'type'=>'fkey',
								  'subtype'=>'object_groupings',
								  'fkey_table'=>array("key"=>"id", "title"=>"name", "parent"=>"parent_id",
																									"ref_table"=>array(
																									"table"=>"object_grouping_mem", 
																									"this"=>"object_id", 
																									"ref"=>"grouping_id"
																												   )));
$obj_fields['type_id'] = array('title'=>'Type',
								  'type'=>'fkey',
								  'subtype'=>'object_groupings',
								  'fkey_table'=>array("key"=>"id", "title"=>"name", "parent"=>"parent_id",
																									"ref_table"=>array(
																									"table"=>"object_grouping_mem", 
																									"this"=>"object_id", 
																									"ref"=>"grouping_id"
																												   )));

// Set views
$obj_views = array();

$view = new CAntObjectView();
$view->id = "sys_all";
$view->name = "All Stories";
$view->description = "View all stories both in the backlog and those assigned to a milestone/sprint";
$view->fDefault = true;
$view->view_fields = array("name", "status_id", "priority_id", "owner_id", "cost_estimated");
//$view->conditions[] = new CAntObjectCond("and", "date_start", "is_greater_or_equal", "now");
$view->sort_order[] = new CAntObjectSort("ts_entered", "desc");
$obj_views[] = $view;
unset($view);

$view = new CAntObjectView();
$view->id = "sys_inprogress";
$view->name = "In-Progress";
$view->description = "Stoies that are currently being worked on";
$view->fDefault = false;
$view->view_fields = array("name", "status_id", "priority_id", "owner_id", "cost_estimated");
$view->conditions[] = new CAntObjectCond("and", "status_id", "is_equal", "In-Progress");
$view->sort_order[] = new CAntObjectSort("priority_id", "desc");
$view->sort_order[] = new CAntObjectSort("ts_entered", "desc");
$obj_views[] = $view;
unset($view);

$view = new CAntObjectView();
$view->id = "sys_backlog";
$view->name = "Backlog";
$view->description = "Stoies not yet assigned to a milestone/sprint and incomplete";
$view->fDefault = false;
$view->view_fields = array("name", "status_id", "priority_id", "owner_id", "cost_estimated");
$view->conditions[] = new CAntObjectCond("and", "milestone_id", "is_equal", "");
$view->conditions[] = new CAntObjectCond("and", "status_id", "is_not_equal", "Completed");
$view->conditions[] = new CAntObjectCond("and", "status_id", "is_not_equal", "Rejected");
$view->sort_order[] = new CAntObjectSort("priority_id", "desc");
$view->sort_order[] = new CAntObjectSort("ts_entered", "desc");
$obj_views[] = $view;
unset($view);

$view = new CAntObjectView();
$view->id = "sys_completed";
$view->name = "Completed";
$view->description = "Stoies that are completed";
$view->fDefault = false;
$view->view_fields = array("name", "status_id", "priority_id", "owner_id", "cost_estimated");
$view->conditions[] = new CAntObjectCond("and", "status_id", "is_equal", "Completed");
$view->sort_order[] = new CAntObjectSort("ts_entered", "desc");
$obj_views[] = $view;
unset($view);
