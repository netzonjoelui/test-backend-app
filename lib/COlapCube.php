<?php
/*======================================================================================
	
	class:		COlapCube

	Purpose:	OnLine Analytical Processing Cube 

	Author:		joe, sky.stebnicki@aereus.com
				Copyright (c) 2010 Aereus Corporation. All rights reserved.

	Usage:		$cube = new COlapCube($dbh, "opportunity", $USEROBJ);
				$cube->setConditions($cond_arr); // usually $_POST is passed
				$cube->addMeasure("amount", "sum");
				$cube->setDimension(1, "owner_id");
				$cube->setDimension(2, "ts_entered", "month");
				$cube->queryValues();

	Depends:	lib/CAntObject.php
				lib/CAntObjectList.php
				lib/aereus.lib.php/CChart.php - used for printing chart XML
				settings.php - must be loaded in parent document

	Variables:	

======================================================================================*/
require_once("lib/CAntObject.php");
require_once("lib/CAntObjectList.php");
require_once("lib/aereus.lib.php/CChart.php");

class COlapCube
{
	var $dbh;
	var $user;
	var $dimension1;
	var $dimension1_group;
	var $dimension1_type;
	var $dimension1_arr;
	var $dimension2;
	var $dimension2_group;
	var $dimension2_type;
	var $dimension2_arr;
	var $measures;
	var $values;
	var $obj_type;
	var $mainObject;
	var $strCondition;
	var $query = ""; // Last query run

	function COlapCube($dbh, $obj_type, $user)
	{
		$this->dbh = $dbh;
		$this->user = $user;
		$this->obj_type = $obj_type;
		$this->mainObject = new CAntObject($dbh, $this->obj_type); 
		$this->values = null;
		$this->formConditions = null;
		//$this->strCondition = ""; // this has been replaced wih formConditions above
		// Dimension 1 properties 
		//$this->dimension1 = "owner_id";
		//$this->dimension1_group = ""; // Used for time/date only
		$this->dimension1_arr = array();
		// Dimension 2 properties
		//$this->dimension2 = "ts_entered";
		//$this->dimension2_group = "month"; // Used for time/date only
		//$this->measures = array("amount"=>"sum");
		$this->dimension2_arr = array();
		// group dimension properties
		$this->dimension_arr_labels = array();
	}

	function setDimension($ind, $column, $group="")
	{
		if ($ind == 0)
		{
			$this->dimension1 = $column;
			$this->dimension1_group = $group; // Used for time/date only
		}
		else
		{
			$this->dimension2 = $column;
			$this->dimension2_group = $group; // Used for time/date only
		}
	}

	function addMeasure($field, $method)
	{
		$this->measures[$field] = $method;
	}

	function setFormConditions($condition_vars)
	{
		// Get list but do not actually run query (last arg set to false)
		$this->formConditions = $condition_vars;
	}

	// Depreicated
	function setConditions($condition_vars)
	{
		// Get list but do not actually run query (last arg set to false)
		$ol = new CAntObjectList($this->dbh, $this->obj_type, $this->user, $condition_vars, null, 0, null, false);
		$ol->processFormConditions($condition_vars);
		$this->strCondition = $ol->buildConditionString();
	}

	function convertDimGroupLabel($type, $curval, $groupby="")
	{
		$dimval = $curval;

		// TODO: create conversion cache to reduce cpu cycles
		switch ($groupby)
		{
		case 'month':
			$dimval = date("Ym M", strtotime($dimval));
			break;
		case 'quarter':
			$dimval = date("Y", strtotime($dimval))." ".(floor(date('m', strtotime($dimval)) / 3.1) + 1);
			break;
		case 'year':
			$dimval = date("Y", strtotime($dimval));
			break;
		case 'day':
			$dimval = date("m/d/Y", strtotime($dimval));
			break;
		case 'hour':
			$dimval = date("G", strtotime($dimval));
			break;
		case 'minute':
			$dimval = date("i", strtotime($dimval));
			break;
		}

		return $dimval;
	}

	function queryValues()
	{
		$ol = new CAntObjectList($this->dbh, $this->obj_type, $this->user);
		if ($this->formConditions)
			$ol->processFormConditions($this->formConditions);

		// Get dimension col types
		$ol->addMinField($this->dimension1); // pull 'name' in initial query
		$ftype = $this->mainObject->getFieldType($this->dimension1);
		$this->dimension1_type = $ftype['type'];	
		if ($this->dimension2)
		{
			$ol->addMinField($this->dimension2); // pull 'name' in initial query
			$ftype = $this->mainObject->getFieldType($this->dimension2);
			$this->dimension2_type = $ftype['type'];	
		}

		// Pull objects and set values
		$this->values = array();
		$ol->getObjects(0, 1000);
		$num = $ol->getNumObjects();
		$total = $ol->getTotalNumObjects();
		$offset = 0;
		for ($i = 0; $i < $num; $i++)
		{
			$row = $ol->getObjectMin($i);

			// Get group names for dimension 1 and 2
			$d1 = $row[$this->dimension1];
			$di = $this->convertDimGroupLabel($this->dimension1_type, $d1, $this->dimension1_group);

			if ($this->dimension2)
			{
				$d2 = $row[$this->dimension2];
				$d2 = $this->convertDimGroupLabel($this->dimension2_type, $d2, $this->dimension2_type);
			}

			// Populate dimension array (grouped labels)
			if (!$this->isInDimArray($this->dimension1_arr, $d1))
				$this->dimension1_arr[] = array($d1, $this->getDimensionLabel(1, $d1));

			if ($this->dimension2)
			{
				if (!$this->isInDimArray($this->dimension2_arr, $d2))
					$this->dimension2_arr[] = array($d2, $this->getDimensionLabel(2, $d2));
			}

			// Set MOLAP cube = [dimension1][dimension2(optional][measure]
			if (!isset($this->values[$d1]))
				$this->values[$d1] = array();

			// Get measure values
			if ($this->dimension2) // populate two dimensional values
			{
				if (!isset($this->values[$d1][$d2]))
					$this->values[$d1][$d2] = array();

				foreach ($this->measures as $field=>$perform)
				{
					if (!is_numeric($this->values[$d1][$d2][$field]))
						$this->values[$d1][$d2][$field] = 0;

					switch ($perform)
					{
					case 'sum':
						$val = $row[$field];
						$this->values[$d1][$d2][$field] += (double)$val;
						break;
					case 'avg':
						$val = $row[$field];
						//$this->values[$d1][$d2][$field]++; // simple increment
						break;
					case 'count':
					default:
						$this->values[$d1][$d2][$field]++; // simple increment
						break;
					}
				}
			}
			else // populate single dimensional values
			{
				foreach ($this->measures as $field=>$perform)
				{
					if (!is_numeric($this->values[$d1][$field]))
						$this->values[$d1][$field] = 0;

					switch ($perform)
					{
					case 'sum':
						$val = $row[$field];
						$this->values[$d1][$field] += (double)$val;
						break;
					case 'avg':
						$val = $row[$field];
						//$this->values[$d1][$d2][$field]++; // simple increment
						break;
					case 'count':
					default:
						$this->values[$d1][$field]++; // simple increment
						break;
					}
				}
			}

			// If result set is larger than 1000
			$offset++;
			if ($i == $num && ($num+$offset) < $total)
			{
				$ol->getObjects($offset, 1000); // Get next page
				$num = $ol->getNumObjects();
				$total = $ol->getTotalNumObjects();
			}
		}

		$this->sortResults();
	}

	function sortResults()
	{
		// Sort
		if (count($this->dimension1_arr))
		{
			$vals = array();
			foreach ($this->dimension1_arr as $ent)
				$vals[] = $ent[0];

			if ($this->dimension1_type == "timestamp" || $this->dimension1_type == "date")
				array_multisort($vals, SORT_DESC, $this->dimension1_arr);
			else
				array_multisort($vals, SORT_ASC, $this->dimension1_arr);
		}
		if (count($this->dimension2_arr))
		{
			$vals = array();
			foreach ($this->dimension2_arr as $ent)
				$vals[] = $ent[0];

			if ($this->dimension2_type == "timestamp" || $this->dimension2_type == "date")
				array_multisort($vals, SORT_DESC, $this->dimension2_arr);
			else
				array_multisort($vals, SORT_ASC, $this->dimension2_arr);
		}
	}

	function buildQuery()
	{
		// Get dimension col types
		$ftype = $this->mainObject->getFieldType($this->dimension1);
		$this->dimension1_type = $ftype['type'];	
		if ($this->dimension2)
		{
			$ftype = $this->mainObject->getFieldType($this->dimension2);
			$this->dimension2_type = $ftype['type'];	
		}

		$tbl = $this->mainObject->object_table;

		$query .= "select ";

		$strDimCols = "";
		if ($this->dimension1)
		{
			$ftype = $this->mainObject->getFieldType($this->dimension1);
			switch ($ftype['type'])
			{
			case 'date':
			case 'timestamp':
				switch ($this->dimension1_group)
				{
				case 'month':
					$strDimCols .= "to_char(".$this->dimension1.", 'YYYYMM Mon') as d1_str";
					break;
				case 'quarter':
					$strDimCols .= "to_char(".$this->dimension1.", 'YYYY Q') as d1_str";
					break;
				case 'year':
					$strDimCols .= "to_char(".$this->dimension1.", 'YYYY') as d1_str";
					break;
				case 'day':
					$strDimCols .= "to_char(".$this->dimension1.", 'MM/DD/YYYY') as d1_str";
					break;
				case 'hour':
					$strDimCols .= "to_char(".$this->dimension1.", 'HH24') as d1_str";
					break;
				case 'minute':
					$strDimCols .= "to_char(".$this->dimension1.", 'MI') as d1_str";
					break;
				}
				break;
			default:
				$strDimCols .= $this->dimension1." as d1";;
			}
		}

		if ($this->dimension2)
		{
			$strDimCols .= ", ";
			$ftype = $this->mainObject->getFieldType($this->dimension2);
			switch ($ftype['type'])
			{
			case 'date':
			case 'timestamp':
				switch ($this->dimension2_group)
				{
				case 'month':
					$strDimCols .= "to_char(".$this->dimension2.", 'YYYYMM Mon') as d2_str";
					break;
				case 'quarter':
					$strDimCols .= "to_char(".$this->dimension2.", 'YYYY Q') as d2_str";
					break;
				case 'year':
					$strDimCols .= "to_char(".$this->dimension2.", 'YYYY') as d2_str";
					break;
				case 'day':
					$strDimCols .= "to_char(".$this->dimension2.", 'MM/DD/YYYY') as d2_str";
					break;
				case 'hour':
					$strDimCols .= "to_char(".$this->dimension2.", 'HH24') as d2_str";
					break;
				case 'minute':
					$strDimCols .= "to_char(".$this->dimension2.", 'MI') as d2_str";
					break;
				}
				break;
			default:
				$strDimCols .= $this->dimension2." as d2";;
			}
		}

		$query .= $strDimCols." ";

		// Add measures
		$strMeas = " ";
		foreach ($this->measures as $field=>$perform)
		{
			if ($strMeas) $strMeas .= ", ";
			$strMeas .= $perform."(".$field.") as $field";
		}
		$query .= $strMeas;

		// Add table
		$query .= " from $tbl ";

		// Add condition
		if ($this->strCondition)
			$query .= " where id is not null and ".$this->strCondition;

		// Add group by dimensions
		$query .= " group by ";
		// D1
		$query .= "d1";
		if ($this->dimension1_type == "timestamp" || $this->dimension1_type == "date")
			$query .= "_str";
		// D2
		if ($this->dimension2)
		{
			$query .= ", d2";
			if ($this->dimension2_type == "timestamp" || $this->dimension2_type == "date")
				$query .= "_str";
		}

		//echo $query;
		$this->query = $query;
		return $query;
	}

	function queryValuesOld()
	{
		$result = $this->dbh->Query($this->buildQuery());
		$num = $this->dbh->GetNumberRows($result);

		$this->values = array();
		for ($i = 0; $i < $num; $i++)
		{
			$row = $this->dbh->GetRow($result, $i);

			if ($this->dimension1_type == "timestamp" || $this->dimension1_type == "date")
				$d1 = $row["d1_str"];
			else
				$d1 = $row["d1"];

			if ($this->dimension2)
			{
				if ($this->dimension2_type == "timestamp" || $this->dimension2_type == "date")
					$d2 = $row["d2_str"];
				else
					$d2 = $row["d2"];
			}

			if (!$this->isInDimArray($this->dimension1_arr, $d1))
				$this->dimension1_arr[] = array($d1, $this->getDimensionLabel(1, $d1));

			if ($this->dimension2)
			{
				if (!$this->isInDimArray($this->dimension2_arr, $d2))
					$this->dimension2_arr[] = array($d2, $this->getDimensionLabel(2, $d2));
			}

			// Set MOLAP cube = [dimension1][dimension2(optional][measure]
			if (!isset($this->values[$d1]))
				$this->values[$d1] = array();

			if ($this->dimension2)
			{
				if (!isset($this->values[$d1][$d2]))
					$this->values[$d1][$d2] = array();

				foreach ($this->measures as $field=>$perform)
				{
					$this->values[$d1][$d2][$field] = ($row[$field])?$row[$field]:"0";
				}
			}
			else
			{
				foreach ($this->measures as $field=>$perform)
				{
					$this->values[$d1][$field] = ($row[$field])?$row[$field]:"0";
				}
			}
		}

		// Sort
		if (count($this->dimension1_arr))
		{
			$vals = array();
			foreach ($this->dimension1_arr as $ent)
				$vals[] = $ent[0];

			if ($this->dimension1_type == "timestamp" || $this->dimension1_type == "date")
				array_multisort($vals, SORT_DESC, $this->dimension1_arr);
			else
				array_multisort($vals, SORT_ASC, $this->dimension1_arr);
		}
		if (count($this->dimension2_arr))
		{
			$vals = array();
			foreach ($this->dimension2_arr as $ent)
				$vals[] = $ent[0];

			if ($this->dimension2_type == "timestamp" || $this->dimension2_type == "date")
				array_multisort($vals, SORT_DESC, $this->dimension2_arr);
			else
				array_multisort($vals, SORT_ASC, $this->dimension2_arr);
		}
	}

	function getData()
	{
		echo "<data>";
		// Display data
		foreach ($this->dimension1_arr as $d1)
		{
			echo "<dimension value=\"".rawurlencode($d1[0])."\" label=\"".rawurlencode($d1[1])."\">";
			if ($this->dimension2)
			{
				foreach ($this->dimension2_arr as $d2)
				{
					echo "<dimension value=\"".rawurlencode($d2[0])."\" label=\"".rawurlencode($d2[1])."\">";
					foreach ($this->measures as $field=>$perform)
					{
						echo "<measure name=\"".rawurlencode($field)."\">".round($this->values[$d1[0]][$d2[0]][$field], 0)."</measure>";
					}
					echo "</dimension>";
				}
			}
			else
			{
				foreach ($this->measures as $field=>$perform)
				{
					echo "<measure name=\"".rawurlencode($field)."\">".round($this->values[$d1[0]][$field], 0)."</measure>";
				}
			}
			echo "</dimension>";
		}
		echo "</data>";
	}

	function setChartData($chart)
	{

		$cdata = $chart->creatXmlData("", "", "", NULL, NULL, "0");
		$cdata->setAttribute("showValues", "0");
		$cdata->setAttribute("showNames", "1");
		$cdata->setAttribute("showAnchors", "1");
		$cdata->setAttribute("numberPrefix", "");
		$cdata->setAttribute("decimalPrecision", "2");
		$cdata->setAttribute("divLineDecimalPrecision", "2");
		$cdata->setAttribute("limitsDecimalPrecision", "2");
		$cdata->setAttribute("bgAlpha", "0");

		// Check for multi-series chart
		if ($this->dimension2)
		{
			foreach ($this->dimension2_arr as $d2)
			{
				$cdata->addCategory($d2[1]);
			}
		}
			
		// Display data
		foreach ($this->dimension1_arr as $d1)
		{
			if ($this->dimension2)
			{
				$set = $cdata->addSet($d1[1]);
				foreach ($this->dimension2_arr as $d2)
				{
					foreach ($this->measures as $field=>$perform)
					{
						$set->addEntry(($this->values[$d1[0]][$d2[0]][$field])?$this->values[$d1[0]][$d2[0]][$field]:0);
					}
				}
			}
			else
			{
				$cdata->addCategory($d1[1]);
				foreach ($this->measures as $field=>$perform)
				{
					$cdata->addEntry($this->values[$d1[0]][$field], $d1[1]);
				}
			}
		}
		//echo $cdata->getData();
	}

	function getDimensionLabel($d, $val)
	{
		$ret = $val;

		// Translate values
		switch($d)
		{
		case 1:
			$dcol = $this->dimension1;
			$grouping = $this->dimension1_group;
			$type  = $this->dimension1_type;
			break;
		case 2:
			$dcol = $this->dimension2;
			$grouping = $this->dimension2_group;
			$type  = $this->dimension2_type;
			break;
		}

		if ($type == "fkey")
		{
			$ret = $this->mainObject->getForeignValue($dcol, $val);
		}
		else if ($type == "timestamp" || $type == "date")
		{
			switch ($grouping)
			{
			case 'month':
				// Skip over year
				//$ret = substr($val, 7);
				break;			
			case 'quarter':
				// Skip over year
				$ret = "Q".substr($val, 5);
				break;
			case 'year':
				// isolate year
				//$ret = substr($val, 0, 4);
				break;			
			}
		}

		return $ret;
	}

	function isInDimArray($dimarr, $value)
	{
		foreach ($dimarr as $dimval)
		{
			if ($dimval[0] == $value)
				return true;
		}

		return false;
	}
}
?>
