<?php
/**
 * report object definition
 */
$obj_revision = 21;

$obj_fields = array();
$obj_fields['name']					= array('title'=>'Name', 'type'=>'text', 'subtype'=>'512', 'readonly'=>false);
$obj_fields['description']			= array('title'=>'Description', 'type'=>'text', 'subtype'=>'', 'readonly'=>false);
$obj_fields['f_display_table'] 		= array('title'=>'Display Table', 'type'=>'bool', 'subtype'=>'', 'readonly'=>false);
$obj_fields['f_display_chart'] 		= array('title'=>'Display Chart', 'type'=>'bool', 'subtype'=>'', 'readonly'=>false);

// Chart properties
$obj_fields['chart_type']			= array('title'=>'Chart Type', 'type'=>'text', 'subtype'=>'128', 'readonly'=>true);
$obj_fields['chart_measure']		= array('title'=>'X-Axis', 'type'=>'text', 'subtype'=>'256', 'readonly'=>true);
$obj_fields['chart_measure_agg']	= array('title'=>'X-Axis Aggregate', 'type'=>'text', 'subtype'=>'32', 'readonly'=>true);
$obj_fields['chart_dim1']			= array('title'=>'Y-Axis', 'type'=>'text', 'subtype'=>'256', 'readonly'=>true);
$obj_fields['chart_dim1_grp']		= array('title'=>'Y-Axis By', 'type'=>'text', 'subtype'=>'32', 'readonly'=>true);
$obj_fields['chart_dim2']			= array('title'=>'Grouping', 'type'=>'text', 'subtype'=>'256', 'readonly'=>true);
$obj_fields['chart_dim2_grp']		= array('title'=>'Grouping By', 'type'=>'text', 'subtype'=>'32', 'readonly'=>true);

// Table properties
$obj_fields['table_type']			= array('title'=>'Table Type', 'type'=>'text', 'subtype'=>'128', 'readonly'=>false);
$obj_fields['f_row_totals'] 		= array('title'=>'Row Totals', 'type'=>'bool', 'subtype'=>'', 'readonly'=>false);
$obj_fields['f_column_totals'] 		= array('title'=>'Column Totals', 'type'=>'bool', 'subtype'=>'', 'readonly'=>false);
$obj_fields['f_sub_totals'] 		= array('title'=>'Subtotals', 'type'=>'bool', 'subtype'=>'', 'readonly'=>false);


// These are depricated with the new olap cubes
$obj_fields['f_calculate'] 			= array('title'=>'Calculate Fields', 'type'=>'bool', 'subtype'=>'', 'readonly'=>false);
$obj_fields['dim_one_fld']			= array('title'=>'Dimension 1 Field', 'type'=>'text', 'subtype'=>'256', 'readonly'=>true);
$obj_fields['dim_one_grp']			= array('title'=>'Dimension 1 Group', 'type'=>'text', 'subtype'=>'32', 'readonly'=>true);
$obj_fields['dim_two_fld']			= array('title'=>'Dimension 2 Field', 'type'=>'text', 'subtype'=>'256', 'readonly'=>true);
$obj_fields['dim_two_grp']			= array('title'=>'Dimension 2 Group', 'type'=>'text', 'subtype'=>'32', 'readonly'=>true);
$obj_fields['measure_one_fld']		= array('title'=>'Measure 1 Field', 'type'=>'text', 'subtype'=>'256', 'readonly'=>true);
$obj_fields['measure_one_agg']		= array('title'=>'Measure 1 Aggregate', 'type'=>'text', 'subtype'=>'32', 'readonly'=>true);
// end: depricated

$obj_fields['dataware_cube']			= array('title'=>'DW Cube Path', 'type'=>'text', 'subtype'=>'512', 'readonly'=>false);
$obj_fields['custom_report']		= array('title'=>'Custom Report', 'type'=>'text', 'subtype'=>'512', 'readonly'=>true);
$obj_fields['obj_type']				= array('title'=>'Object Type', 'type'=>'text', 'subtype'=>'256', 'readonly'=>true);

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
$obj_fields['ts_created']	= array('title'=>'Time Created', 'type'=>'timestamp', 'subtype'=>'', 'readonly'=>true, 'default'=>$default);
$default = array("value"=>"now", "on"=>"update");
$obj_fields['ts_updated']	= array('title'=>'Time Updated', 'type'=>'timestamp', 'subtype'=>'', 'readonly'=>true, 'default'=>$default);

// References
$default = array("value"=>"-3", "on"=>"null");
$obj_fields['owner_id'] = array('title'=>'Owner',
									  'type'=>'fkey',
									  'subtype'=>'users',
									  'default'=>$default,
									  'fkey_table'=>array("key"=>"id", "title"=>"name"));

// Set views
$obj_views = array();

$view = new CAntObjectView();
$view->id = "sys_1";
$view->name = "Reports";
$view->description = "Default list of reports";
$view->fDefault = true;
$view->view_fields = array("name", "description");
//$view->conditions[] = new CAntObjectCond("and", "owner_id", "is_equal", "-3");
$view->sort_order[] = new CAntObjectSort("name", "asc");
$obj_views[] = $view;
unset($view);

?>
