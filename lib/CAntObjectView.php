<?php
/**
 * Object view class used to define views for object lists
 *
 * These can either be created by the end user, an administrator, or by a default system view
 * found in the object definition.
 *
 */

/**
 * View class
 */
class CAntObjectView
{
	/**
	 * User id if this view is owned by an individual user
	 *
	 * @var int
	 */
	public $userid;

	/**
	 * Set if this view is owned by a team
	 *
	 * @var int
	 */
	public $teamid = null;

	/**
	 * Set if this view is owned by a report
	 *
	 * TODO: Investigate - we may not need this any longer now that we are using the new OLAP classes.
	 *
	 * @var int
	 */
	public $reportId;

	var $id;
	var $name;
	var $description;
	var $filterKey;
	var $fDefault;
	var $view_fields;
	var $conditions;
	var $sort_order;

	/**
	 * Optional reference to object def for dereferencing fkey values
	 *
	 * @var CAntObject
	 */
	public $objDef = null;

	/**
	 * Constructor
	 */
	function CAntObjectView()
	{
		$this->view_fields = array();
		$this->conditions = array();
		$this->sort_order = array();
	}

	/**
	 * Get the xml for this view for the object def
	 *
	 * @param int $userid Used to determine if the user owns this view or not
	 * @param string $userdef Optional custom user default selected
	 * @return string Formatted xml for this view
	 */
	public function getXml($userid=null, $userdef=null)
	{
		$str = "";

		$str .= "<view>";
		$str .= "<id>".$this->id."</id>";
		$str .= "<name>".rawurlencode($this->name)."</name>";
		$str .= "<description>".rawurlencode($this->description)."</description>";
		$str .= "<filter_key>".$this->filterKey."</filter_key>";
		$str .= "<f_system>".(($this->userid==$userid)?'f':'t')."</f_system>";
		// Now that we allow the object loader to determine default based on user, team, and system
		// we do not need to set it here like we used to (below)
		$str .= "<f_default>".(($this->fDefault)?'t':'f')."</f_default>";

		/*
		if ($userdef)
		{
			if ($userdef == $this->id)
				$str .= "<f_default>t</f_default>";
			else
				$str .= "<f_default>f</f_default>";
		}
		else
		{
			$str .= "<f_default>".(($this->fDefault)?'t':'f')."</f_default>";
		}
		 */

		$str .= "<view_fields>";
		foreach ($this->view_fields as $field)
			$str .= "<field>".$field."</field>";
		$str .= "</view_fields>";

		$str .= "<conditions>";
		foreach ($this->conditions as $cond)
		{
			// Check for label in fkey or fkey_multi
			if ($this->objDef != null && $cond->value && !is_numeric($cond->value))
			{
				$field = $this->objDef->fields->getField($cond->fieldName);
				if ($field['type'] == "fkey" || $field['type'] == "fkey_multi")
				{
					$grp = $this->objDef->getGroupingEntryByPath($cond->fieldName, $cond->value);
					if ($grp['id'])
						$cond->value = $grp["id"];
				}
			}

			$str .= "<condition>";
			$str .= "<blogic>".$cond->blogic."</blogic>";
			$str .= "<field_name>".$cond->fieldName."</field_name>";
			$str .= "<operator>".$cond->operator."</operator>";
			$str .= "<value>".$cond->value."</value>";
			$str .= "</condition>";
		}
		$str .= "</conditions>";

		$str .= "<sort_order>";
		foreach ($this->sort_order as $order)
		{
			$str .= "<order_by>";
			$str .= "<field_name>".$order->fieldName."</field_name>";
			$str .= "<order>".$order->order."</order>";
			$str .= "</order_by>";
		}
		$str .= "</sort_order>";

		$str .= "</view>";

		return $str;
	}

	/**
	 * Convert the data for this view to an array
	 *
	 * @return array
	 */
	public function toArray($userid=null)
	{
		$ret = array(
			"id" => $this->id,
			"name" => $this->name,
			"description" => $this->description,
			"filter_key" => $this->filterKey,
			"f_system" => (($this->userid==$userid)?false:true),
			"f_default" => $this->fDefault,
			"view_fields" => array(),
			"conditions" => array(),
			"sort_order" => array(),
		);

		// Add view fields
		foreach ($this->view_fields as $field)
			$ret['view_fields'][] = $field;

		// Add conditions
		foreach ($this->conditions as $cond)
		{
			// Check for label in fkey or fkey_multi
			if ($this->objDef != null && $cond->value && !is_numeric($cond->value))
			{
				$field = $this->objDef->def->getField($cond->fieldName);
				if ($field->type == "fkey" || $field->type == "fkey_multi")
				{
					$grp = $this->objDef->getGroupingEntryByPath($cond->fieldName, $cond->value);
					if ($grp['id'])
						$cond->value = $grp["id"];
				}
			}

			$ret['conditions'][] = array(
				"blogic" => $cond->blogic,
				"field_name" => $cond->fieldName,
				"operator" => $cond->operator,
				"value" => $cond->value,
			);
		}

		// Add sort order
		foreach ($this->sort_order as $order)
		{
			$ret['sort_order'][] = array(
				"field_name" => $order->fieldName,
				"order" => $order->order,
			);
		}

		return $ret;
	}

	/**
	 * Load this view from the data store
	 *
	 * @param CDatabase $dbh Handle to account database
	 */
	public function loadAttribs($dbh)
	{
		if ($this->id)
		{
			// Get view_fields
			$res2 = $dbh->Query("select app_object_type_fields.name from app_object_type_fields, app_object_view_fields where 
								 app_object_view_fields.field_id=app_object_type_fields.id and app_object_view_fields.view_id='".$this->id."'
								 order by app_object_view_fields.sort_order");
			$num2 = $dbh->GetNumberRows($res2);
			for ($j = 0; $j < $num2; $j++)
			{
				$row2 = $dbh->GetRow($res2, $j);
				$this->view_fields[] = $row2['name'];
			}

			// Get conditions
			$res2 = $dbh->Query("select app_object_view_conditions.id, app_object_type_fields.name, app_object_view_conditions.blogic, 
								 app_object_view_conditions.operator, app_object_view_conditions.value 
								 from app_object_type_fields, app_object_view_conditions where 
								 app_object_view_conditions.field_id=app_object_type_fields.id 
								 and app_object_view_conditions.view_id='".$this->id."'
								 order by app_object_view_conditions.id");
			$num2 = $dbh->GetNumberRows($res2);
			for ($j = 0; $j < $num2; $j++)
			{
				$row2 = $dbh->GetRow($res2, $j);
				$this->conditions[] = new CAntObjectCond($row2['blogic'], $row2['name'], $row2['operator'], $row2['value']);
			}

			// Get sort order
			$res2 = $dbh->Query("select app_object_type_fields.name, app_object_view_orderby.order_dir
								 from app_object_type_fields, app_object_view_orderby where 
								 app_object_view_orderby.field_id=app_object_type_fields.id and app_object_view_orderby.view_id='".$this->id."'
								 order by app_object_view_orderby.sort_order");
			$num2 = $dbh->GetNumberRows($res2);
			for ($j = 0; $j < $num2; $j++)
			{
				$row2 = $dbh->GetRow($res2, $j);
				$this->sort_order[] = new CAntObjectSort($row2['name'], $row2['order_dir']);
			}
		}
	}

	/**
 	 * Save data for this view
	 *
	 * @param CDatabase $dbh Handle to account database
	 * @param string $obj_type The type of object this view is used for
	 */
	public function save($dbh, $obj_type)
	{
		$obj = new CAntObject($dbh, $obj_type);

		$result = $dbh->Query("insert into app_object_views(name, description, filter_key, user_id, object_type_id, report_id)
								values('".$dbh->Escape($this->name)."', '".$dbh->Escape($this->description)."', 
									   '".$dbh->Escape($this->filterKey)."', ".$dbh->EscapeNumber($this->userid).", 
									   '".$obj->object_type_id."', ".$dbh->EscapeNumber($this->reportId).");
								select currval('app_object_views_id_seq') as id;");
		if ($dbh->GetNumberRows($result))
			$this->id = $dbh->GetValue($result, 0, "id");

		// Save conditions
		foreach ($this->conditions as $cond)
		{
			$field = $obj->fields->getField($cond->fieldName);

			if ($field)
			{
				$dbh->Query("insert into app_object_view_conditions(view_id, field_id, blogic, operator, value)
								values('".$this->id."', '".$field['id']."', '".$cond->blogic."', 
									   '".$cond->operator."', '".$cond->value."')");
			}
		}

		// Save fields
		$sort_order = 1;
		foreach ($this->view_fields as $fld)
		{
			$field = $obj->fields->getField($fld);

			if ($field)
			{
				$dbh->Query("insert into app_object_view_fields(view_id, field_id, sort_order)
											 values('".$this->id."', '".$field['id']."', '$sort_order')");
			}

			$sort_order++;
		}

		// order by
		$sort_order = 1;
		foreach ($this->sort_order as $sort)
		{
			$field = $obj->fields->getField($sort->fieldName);

			if ($field)
			{
				$dbh->Query("insert into app_object_view_orderby(view_id, field_id, order_dir, sort_order)
							 values('".$this->id."', '".$field['id']."', '".$sort->direction."', '$sort_order')");
			}

			$sort_order++;
		}

		return $this->id;
	}
}
