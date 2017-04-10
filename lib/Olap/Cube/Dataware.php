<?php
/**
 * Implementation of OLAP cube that utilizes the ANT datawarehouse for cube data
 *
 * This class more closely represents classic ROLAP implementations of OLAP.
 * It is abstracted so we can move the datawarehouse to other backends int he future
 * if needed.
 *
 * @category  Olap_Cube
 * @package   Dataware
 * @copyright Copyright (c) 2003-2012 Aereus Corporation (http://www.aereus.com)
 */

/**
 * Implementation of OLAP cube that gets cube data from the ANT datawarehouse
 */
class Olap_Cube_Dataware extends Olap_Cube
{
	/**
     * Unique id of this cube
     *
     * @var integer
	 */
	public $id = null;

	/**
     * Unique cube name
     *
     * @var string
	 */
	protected $cubeName = null;

	/**
     * Craete the cube ($this->cubename) on the first insert if it does not already exist
     *
     * @var bool
	 */
	protected $createIfMissing = true;

	/**
     * Set the schema name for the data-warehouse
     *
     * @const string
	 */
	const schemaName = "dataware";

	/**
	 * Class constructor
	 *
	 * @param CDatabase $dbh Handle to account database
	 * @param string $cubename Unique name of the cube to load
	 * @param bool $createIfMissing If true then create the cube on insert if it does not exist. Default = true
	 */
	public function __construct($dbh, $cubename, $createIfMissing = true)
	{
		$this->createIfMissing = $createIfMissing;
		$this->cubeName = $cubename;

		parent::__construct($dbh);

		$this->checkDatawareSchema();

		if ($this->cubeName)
			$this->load();
	}

	/**
	 * Load cube, and create it if it is missing
	 */
	private function checkDatawareSchema()
	{
		$dbh = $this->dbh;

		/* No longer using schemas because each account has its own schema already
		if (!$dbh->GetNumberRows($dbh->Query("SELECT schema_name FROM information_schema.schemata WHERE schema_name = '".self::schemaName."';")))
			$dbh->Query("CREATE SCHEMA ".self::schemaName.";");
		 */
	}

	/**
	 * Load cube, and create it if it is missing
	 */
	private function load()
	{
		if (!$this->cubeName)
			return false;

		$result = $this->dbh->Query("SELECT id FROM dataware_olap_cubes WHERE name='".$this->dbh->Escape($this->cubeName)."'");
		if ($this->dbh->GetNumberRows($result))
		{
			$this->id = $this->dbh->GetValue($result, 0, "id");
			$this->loadDimensions();
			$this->loadMeasures();
		}
		else if ($this->createIfMissing) // cube does not exist, let's make it
		{
			$result = $this->dbh->Query("INSERT INTO dataware_olap_cubes(name) VALUES('".$this->dbh->Escape($this->cubeName)."'); 
										 SELECT currval('dataware_olap_cubes_id_seq') as id;");
			if ($this->dbh->GetNumberRows($result))
			{
				$this->id = $this->dbh->GetValue($result, 0, "id");

				// Now create fact table
				$this->dbh->Query("CREATE TABLE facts_".$this->id."();");
				// No longer using schemas because each account has its own schema already
				//$this->dbh->Query("CREATE TABLE ".self::schemaName.".facts_".$this->id."();");

				// Create dimension data file where values will be stored for keys
				$this->dbh->Query("CREATE TABLE dimdat_".$this->id."(id bigserial, dim_id integer, 
																							value character varying(512), 
																							label text);");
				// No longer using schemas because each account has its own schema already
				/*
				$this->dbh->Query("CREATE TABLE ".self::schemaName.".dimdat_".$this->id."(id bigserial, dim_id integer, 
																							value character varying(512), 
																							label text);");
				 */

				// Add index on new dimdata table
				$this->dbh->Query("CREATE INDEX dimdat_".$this->id."_val_idx
									ON dimdat_".$this->id." (value ASC NULLS LAST)");
			}
		}
	}

	/**
	 * Load the available dimensions for this cube if $this->id is not null
	 */
	private function loadDimensions()
	{
		if (!$this->id)
			return false;

		// Clear array
		$this->dimensions = array();

		$result = $this->dbh->Query("SELECT id, name, type FROM dataware_olap_cube_dims WHERE cube_id='".$this->id."'");
		$num = $this->dbh->GetNumberRows($result);

		for ($i = 0; $i < $num; $i++)
		{
			$data = $this->dbh->GetRow($result, $i);

			$dim = new Olap_Cube_Dimension();
			$dim->id = $data['id'];
			$dim->name = $data['name'];
			$dim->type = $data['type'];

			$this->dimensions[] = $dim;
		}
	}

	/**
	 * Load the available measures for this cube if $this->id is not null
	 */
	private function loadMeasures()
	{
		if (!$this->id)
			return false;

		// Clear array
		$this->measures = array();

		$result = $this->dbh->Query("SELECT id, name FROM dataware_olap_cube_measures WHERE cube_id='".$this->id."'");
		$num = $this->dbh->GetNumberRows($result);

		for ($i = 0; $i < $num; $i++)
		{
			$data = $this->dbh->GetRow($result, $i);

			$meas = new Olap_Cube_Measure();
			$meas->id = $data['id'];
			$meas->name = $data['name'];

			$this->measures[] = $meas;
		}
	}

	/**
	 * Add a new dimension to this cube
	 *
	 * @param string $name Then unique name of this dimension
	 * @return Olap_Cube_Measure on success, false on failure
	 */
	private function addNewDimension($name, $type="")
	{
		if (!$this->id)
			return false;

		$ret = false;

		// Try to determine type if not set
		if (!$type)
		{
			if ($name == "time" || substr($name, -3)=="_ts")
			{
				$type = "time";
			}
			else if (substr($name, -2)=="_i" || substr($name, -4)=="_num")
			{
				$type = "numeric";
			}
			else
			{
				$type = "string";
			}
		}

		// Add to list of dimensions
		$result = $this->dbh->Query("INSERT INTO dataware_olap_cube_dims(cube_id, name, type) 
									 VALUES('".$this->id."', '".$this->dbh->Escape($name)."', '".$this->dbh->Escape($type)."');
									 SELECT currval('dataware_olap_cube_dims_id_seq') as id;");
		if ($this->dbh->GetNumberRows($result))
		{
			$dimid = $this->dbh->GetValue($result, 0, "id");

			// Set the column type
			switch ($type)
			{
			case 'numeric':
				$coltype = "numeric";
				break;
			case 'time':
				$coltype = "timestamp without time zone";
				break;
			case 'string':
			default:
				$coltype = "integer";
				break;
			}

			// Add dimension culumn to fact table
			$this->dbh->Query("ALTER TABLE facts_".$this->id." ADD COLUMN dim_".$dimid." $coltype;");

			// Add index on new dim col
			$this->dbh->Query("CREATE INDEX facts_".$this->id."_dim_".$dimid."_idx
								ON facts_".$this->id." (dim_".$dimid." ASC NULLS LAST)");

			$dim = new Olap_Cube_Dimension();
			$dim->id = $dimid;
			$dim->name = $name;
			$dim->type = $type;

			$this->dimensions[] = $dim;

			$ret = $dim;
		}

		return $ret;
	}

	/**
	 * Check to see if a measure already exists, and optionally add it
	 *
	 * @param string $name Then unique name of this measure
	 * @param bool $createIfMissing If set to true (default) then create the measure if missing
	 * @return Olap_Cube_Measure
	 */
	public function getDimension($name, $type="", $createIfMissing=true)
	{
		for ($i = 0; $i < count($this->dimensions); $i++)
		{
			if ($this->dimensions[$i]->name == $name)
				return $this->dimensions[$i];
		}

		// Dimension was not found, let's create it
		if ($createIfMissing)
			return $this->addNewDimension($name);
		else
			return null;
	}

	/**
	 * Get the key (id) for a given value from the dimdat table
	 *
	 * @param string $name Then unique name of this measure
	 * @param mixed $value Can either be a string value or an associative arry with 'id', 'value' dimensions
	 * @param bool $createIfMissing If set to true (default) then create the measure if missing
	 * @return bigint The unique key of the dimension value
	 */
	public function getDimensionKey($name, $val, $createIfMissing = true)
	{
		$dim = $this->getDimension($name, $createIfMissing);

		if (!$dim)
			return false;

		// If numeric or timestamp then return the actual value
		if ($dim->type == "time" || $dim->type == "numeric")
			return $val;

		$ret = false;

		$selVal = (is_array($val)) ? $val['id'] : $val;

		$query = "SELECT id, value, label from dimdat_".$this->id;
		$query .= " WHERE dim_id='".$dim->id."' AND value='".$this->dbh->Escape($selVal)."'";

		$result = $this->dbh->Query($query);
		if ($this->dbh->GetNumberRows($result))
		{
			$ret = $this->dbh->GetValue($result, 0, "id");
		}
		else if ($createIfMissing)
		{
			$query = "INSERT INTO dimdat_".$this->id."(dim_id, value, label) VALUES('".$dim->id."', ";
			if (is_array($val))
				$query .= "'".$this->dbh->Escape($val['id'])."', '".$this->dbh->Escape($val['label'])."'";
			else
				$query .= "'".$this->dbh->Escape($val)."', '".$this->dbh->Escape($val)."'";
			$query .= "); select currval('dimdat_".$this->id."_id_seq') as id;";
			$result = $this->dbh->Query($query);
			if ($this->dbh->GetNumberRows($result))
				$ret = $this->dbh->GetValue($result, 0, "id");
		}

		return $ret;
	}

	/**
	 * Get the original value for a key (id) from the dimdat table
	 *
	 * @param string $name Then unique name of this dimension
	 * @param string $key The current key stored to get the value for
	 * @param string $format Usually used by time dimensions to group parts
	 * @return string The string of the orignial named value for the key
	 */
	public function getDimensionValFromKey($name, $key, $format=null)
	{
		$dim = $this->getDimension($name, false);

		if (!$dim)
			return false;

		// If numeric or timestamp then return the actual value
		if ($dim->type == "time" || $dim->type == "numeric")
		{
			if ($dim->type == "time" && $format!=null)
			{
				$time = @strtotime($key);

				if ($time != -1)
				{
					// Utilize PHP date formats
					$key = date($format, $time);

					// Calculate quarter
					$key = str_replace("Q", "Q".(floor((date("m", $time) - 1) / 3) + 1), $key);
				}
			}

			return $key;
		}

		$ret = false;
        
		$query = "SELECT id, value, label from dimdat_".$this->id;
		$query .= " WHERE dim_id='".$dim->id."' AND id=".$this->dbh->EscapeNumber($key);

		$result = $this->dbh->Query($query);
		if ($this->dbh->GetNumberRows($result))
			$ret = $this->dbh->GetValue($result, 0, "value");

		return $ret;
	}

	/**
	 * Add a new measure to this cube
	 *
	 * @param string $name Then unique name of this measure
	 * @return Olap_Cube_Measure on success, false on failure
	 */
	private function addNewMeasure($name)
	{
		if (!$this->id)
			return false;

		$ret = false;

		// Add to list of measures
		$result = $this->dbh->Query("INSERT INTO dataware_olap_cube_measures(cube_id, name) 
									 VALUES('".$this->id."', '".$this->dbh->Escape($name)."');
									 SELECT currval('dataware_olap_cube_measures_id_seq') as id;");
		if ($this->dbh->GetNumberRows($result))
		{
			$mid = $this->dbh->GetValue($result, 0, "id");

			// Add dimension culumn to fact table
			$this->dbh->Query("ALTER TABLE facts_".$this->id." ADD COLUMN m_".$mid." numeric;");

			$meas = new Olap_Cube_Measure();
			$meas->id = $mid;
			$meas->name = $name;

			$this->measures[] = $meas;

			$ret = $meas;
		}

		return $ret;
	}

	/**
	 * Check to see if a measure already exists, and optionally add it
	 *
	 * @param string $name Then unique name of this measure
	 * @param bool $createIfMissing If set to true (default) then create the measure if missing
	 * @return Olap_Cube_Measure
	 */
	public function getMeasure($name, $createIfMissing=true)
	{
		for ($i = 0; $i < count($this->measures); $i++)
		{
			if ($this->measures[$i]->name == $name)
				return $this->measures[$i];
		}

		// Measure was not found, let's create it
		if ($createIfMissing)
			return $this->addNewMeasure($name);
		else
			return null;
	}

	/**
	 * If implemented this supports writeback then write data to the cube.
	 *
	 * If the cube does not support writeback, then this function MUST be implemented and return false
	 *
	 * Example code:
	 * <code>
	 * 	$measures = array("visits"=>100);
	 * 	$dimensions = array("page"=>"/index.php", "country"=>array(1=>"us")); // 1 = "us"
	 * 	$cube->wireData($measures, $dimensions);
	 * </code>
	 * 
	 * @param array $measures Associative array of measures 'name'=>value
	 * @param array $data Associateive array of dimension data. ('dimensionanme'=>'value'). Value may be key/value array like 'id'=>'label'
	 * @param bool $increment If set to true then do not overwrite matching record, but increment it. Default = false.
	 */
	public function writeData($measures, $data, $increment=false)
	{
		if (!$this->id)
			return false;

		$ret = false; // assume failure

		if (!$measures['count'])
			$measures['count'] = 1; // default to 1 for count unless specified in the measures

		// Make sure an entry exists in the fact table matching the data/dimensions
		$res = $this->lookForFactEntry($data);

		// Set measure value
		if ($res)
		{
			$set = "";
			foreach ($measures as $mname=>$mval)
			{
				if ($increment)
				{
					$curval = ($res['measures'][$mname]) ? $res['measures'][$mname] : 0;
					$mval = (float) $curval + (float) $mval;
				}

				if ($set) $set .= ", ";

				$meas = $this->getMeasure($mname);
				$set .= "m_".$meas->id."=".$this->dbh->EscapeNumber($mval)." ";
			}

			if ($set)
			{
				$query = "UPDATE facts_".$this->id." SET ";
				$query .= $set;
				$query .= " WHERE ".$res['where'].";";
				$this->dbh->Query($query);

				$ret = true;
			}
		}

		return $ret;
	}

	/**
	 * Query data
	 *
	 * $data = $cube->getData(array("count"), array("time.year.quarter"), array("measures.visits"));
	 *
	 * // IDEA: Blow is a possible replacement idea for a Olap_Cube_Dataset object that has dimensions & measures properties
	 * and the dimensions will in turn reference a Dataset
	 * ->getDimByName('/index.php')->getDimByName('us')->getMeasure('hits'); 
	 *
	 * For now we will just have it return data
	 *
	 * @param Olap_Cube_Query $ocqQuery Query object used for pulling data
	 *
	 * @return array Muti-dimensional array in the following format: ["dim1_value"]["meas_name"] or ["dim1_value"]["dim2_value"]["meas_name"]
	 */
	public function getData($ocqQuery)
	{
		$measures = $ocqQuery->measures;
		$dimensions = $ocqQuery->dimensions;
		$filters = $ocqQuery->filters;

		$ret = array();

		// count is always included
		if (!isset($measures['count']))
			$measures['count'] = "sum";

		$where = $this->buildSliceQuery($filters);
		$query = "SELECT * FROM facts_".$this->id;
		// Do a join to get foreign keys
		// TODO: join foreign values here in [fielname]_val
		// this will also make it easy to format timestamps
		if ($where)
			$query .= " WHERE $where";

		// Loop through the results and summarize them
		// Should we be doing this in the database instead?
		// Right now I'd rather put the load on the client than the database, that may change
		// as the dataset grows though
		$result = $this->dbh->Query($query);
		for ($i = 0; $i < $this->dbh->GetNumberRows($result); $i++)
		{
			$row = $this->dbh->GetRow($result, $i);

			foreach ($measures as $mname=>$magg)
			{
				$meas = $this->getMeasure($mname, false);

				// If no dimensions then just populate measures
				if (!count($dimensions) && $meas)
				{
					$ret[$mname] = $this->aggregateValue($row['m_' . $meas->id], $ret[$mname], $magg);
				}
				else if ($meas)
				{
                    // dimension 1
                    $dim1 = $this->getDimension($dimensions[0]['name']);
                    $d1val = $this->getDimensionValFromKey($dim1->name, $row["dim_".$dim1->id], $dimensions[0]['fun']);
                    
                    if(!isset($ret[$d1val]))
                    {
                        $ret[$d1val] = array();
                        $ret[$d1val]['count'] = 0;
                    }
                    else if(!is_array($ret[$d1val]))
                    {
                        $ret[$d1val] = array();
                        $ret[$d1val]['count'] = 0;
                    }
                    
					if(count($dimensions) == 2)
					{
                        // dimension 2
						$dim2 = $this->getDimension($dimensions[1]['name']);
						$d2val = $this->getDimensionValFromKey($dim2->name, $row["dim_".$dim2->id], $dimensions[1]['fun']);
                        						
						if (!is_array($ret[$d1val][$d2val]))
						{
                            $ret[$d1val][$d2val] = array();
                            $ret[$d1val][$d2val]['count'] = 0;
                            unset($ret[$d1val]['count']);
                        }
                        
                        $ret[$d1val][$d2val]['count']++;
						$ret[$d1val][$d2val][$mname] = $this->aggregateValue($row['m_' . $meas->id], $ret[$d1val][$d2val][$mname], $magg);
					}
					else if (count($dimensions) == 1)
					{
                        $dimMeasVal = null;
                        if(isset($ret[$d1val][$mname]))
                            $dimMeasVal = $ret[$d1val][$mname];
                            
						$ret[$d1val]['count']++;
						$ret[$d1val][$mname] = $this->aggregateValue($row['m_' . $meas->id], $dimMeasVal, $magg);
					}
				}
			}
		}

		$this->dbh->FreeResults($result); // keep it clean and small

		// Now handle avg
		foreach ($measures as $mname=>$magg)
		{
			if ($magg != "avg")
				continue;  // skip

			$meas = $this->getMeasure($mname, false);

			// If no dimensions then just populate measures
			if (!count($dimensions) && $meas)
			{
                $ret["count"] = $i;
				$ret[$mname] = $ret[$mname] / $ret["count"];
			}
			else if ($meas)
			{
				if (count($dimensions) == 2)
				{
					foreach ($ret as $dim1name=>$dim1)
					{
						foreach ($dim1 as $dim2name=>$dim2)
						{
							$ret[$dim1name][$dim2name][$mname] = $dim2[$mname] / $dim2["count"];
						}
					}
				}
				else if (count($dimensions) == 1)
				{
					foreach ($ret as $dim1name=>$dim1)
					{
						$ret[$dim1name][$mname] = $dim1[$mname] / $dim1["count"];
					}
				}
			}
		}

		return $ret;
	}

	/**
     * Query data and send results in a tabular format
     *
     * @return array sing-dimensional array in the following format: array("colname"=>"colval)
     */
    public function getTabularData($ocqQuery)
    {
		$dimensions = $ocqQuery->dimensions;
		$filters = $ocqQuery->filters;

		$ret = array();

		// count is always included
		if (!isset($measures['count']))
			$measures['count'] = "sum";

		$where = $this->buildSliceQuery($filters);
		$query = "SELECT * FROM facts_".$this->id;
		// Do a join to get foreign keys
		// TODO: join foreign values here in [fielname]_val
		// this will also make it easy to format timestamps
		if ($where)
			$query .= " WHERE $where";

		// Loop through the results and summarize them
		// Should we be doing this in the database instead?
		// Right now I'd rather put the load on the client than the database, that may change
		// as the dataset grows though
		$result = $this->dbh->Query($query);
		for ($i = 0; $i < $this->dbh->GetNumberRows($result); $i++)
		{
			$row = $this->dbh->GetRow($result, $i);

			$retRow = array();

			foreach ($dimensions as $dim)
			{
				// name, sort, fun
				$dim1 = $this->getDimension($dim['name']);
				$dval = $this->getDimensionValFromKey($dim1->name, $row["dim_".$dim1->id], $dim['fun']);
				$retRow[$dim1->name] = $dval;
			}

			$ret[] = $retRow;
		}

		$this->dbh->FreeResults($result); // keep it clean and small

		return $ret;
	}

	/**
	 * Delete this cube and all related data
	 */
	public function remove()
	{
		if (!$this->id)
			return false;

		// Remove cube
		$this->dbh->Query("DELETE FROM dataware_olap_cubes where id='".$this->id."'");

		// Remove dimension data table
		$this->dbh->Query("DROP TABLE dimdat_".$this->id);

		// Remove fact table
		$this->dbh->Query("DROP TABLE facts_".$this->id);
	}

	/**
	 * Make sure an entry exists given the data provided
	 *
	 * @param array $data The dimension data that goes into the fact table
	 * @return array with 'measures'[mname] and 'where' assoc values
	 */
	private function lookForFactEntry($data, $createIfMissing = true)
	{
		$ret = array();
		$ret['measures'] = array();
		$ret['where'] = null;

		// Convert values to conditions
		$fitlerData = array();
		foreach ($data as $dname=>$dval)
		{
			$fitlerData[] = array("blogic"=>"and", "field"=>$dname, "operator"=>"is_equal", "condition"=>$dval);
		}

		$tblname = "facts_".$this->id;
		$where = $this->buildSliceQuery($fitlerData);

		if ($where)
		{
			$query = "SELECT * from $tblname WHERE ".$where;
			$result = $this->dbh->Query($query);
			if (!$this->dbh->GetNumberRows($result) && $createIfMissing)
			{
				$targets = "";
				$values = "";

				foreach ($data as $dname=>$dval)
				{
					if ($targets) $targets .= ", ";
					if ($values) $values .= ", ";

					$dim = $this->getDimension($dname);
					$key = $this->getDimensionKey($dname, $dval, $createIfMissing);

					$targets .= "dim_".$dim->id;
					$values .= "'$key'";
				}

				if ($targets && $values)
				{
					$this->dbh->Query("INSERT INTO $tblname($targets) VALUES($values);");

					// Update where query now that we've entered data
					$where = $this->buildSliceQuery($fitlerData);
				}
			}
			else
			{
				// Aggregate current value of all matching data
				for ($i = 0; $i < count($this->measures); $i++)
				{
					for ($j = 0; $j < $this->dbh->GetNumberRows($result); $j++)
					{
						$val = $this->dbh->GetValue($result, $j, "m_".$this->measures[$i]->id);

						// Initialize values if not set
						if (!$val) $val = 0;
						if (!$ret['measures'][$this->measures[$i]->name]) $ret['measures'][$this->measures[$i]->name] = 0;

						$ret['measures'][$this->measures[$i]->name] += $val;
					}
				}
			}

			// Send condition in return value for updating
			$ret['where'] = $where;
		}

		return $ret;
	}

	/**
	 * Build the where condition based on the filter
	 */
	private function buildSliceQuery($filter)
	{
		$buf = "";
		foreach ($filter as $condition)
		{
			$dim = $this->getDimension($condition['field']);
			$key = $this->getDimensionKey($condition['field'], $condition['condition'], false);

			if ($dim)
			{
				if ($buf) $buf .= " ".$condition['blogic']." ";
				$buf .= $this->buildQueryOperatorCond("dim_".$dim->id, $condition['operator'], $key, $dim->type);
			}
		}

		return $buf;
	}

	/**
	 * Create condition based on operator and type
	 *
	 * @param string $fieldName The name of the field being queried
	 * @param string $operator The operator being used for the query
	 * @param string $value The value to test for
	 * @param string $type The type of field being queried. Defaults to string.
	 * @return string the operator and condition part of the query
	 */
	private function buildQueryOperatorCond($fieldName, $operator, $value, $type="string")
	{
		$buf = "";

		switch ($operator)
		{
		case "is_not_equal":
			$buf = $this->buildQueryOpIsNotEqual($fieldName, $value, $type);
			break;
		case "is_greater":
			$buf = $this->buildQueryOpIsGreater($fieldName, $value, $type);
			break;
		case "is_less":
			$buf = $this->buildQueryOpIsLess($fieldName, $value, $type);
			break;
		case "is_greater_or_equal":
			$buf = $this->buildQueryOpIsGreaterOrEq($fieldName, $value, $type);
			break;
		case "is_less_or_equal":
			$buf = $this->buildQueryOpIsLessOrEq($fieldName, $value, $type);
			break;
		case "day_is_equal":
			$buf = $this->buildQueryOpDayIsEqual($fieldName, $value, $type);
			break;
		case "month_is_equal":
			$buf = $this->buildQueryOpMonthIsEqual($fieldName, $value, $type);
			break;
		case "year_is_equal":
			$buf = $this->buildQueryOpYearIsEqual($fieldName, $value, $type);
			break;
		case "last_x_days":
			$buf = $this->buildQueryOpLastXDays($fieldName, $value, $type);
			break;
		case "last_x_weeks":
			$buf = $this->buildQueryOpLastXWeeks($fieldName, $value, $type);
			break;
		case "last_x_months":
			$buf = $this->buildQueryOpLastXMonths($fieldName, $value, $type);
			break;
		case "last_x_years":
			$buf = $this->buildQueryOpLastXYears($fieldName, $value, $type);
			break;
		case "next_x_days":
			$buf = $this->buildQueryOpNextXDays($fieldName, $value, $type);
			break;
		case "next_x_weeks":
			$buf = $this->buildQueryOpNextXWeeks($fieldName, $value, $type);
			break;
		case "next_x_months":
			$buf = $this->buildQueryOpNextXMonths($fieldName, $value, $type);
			break;
		case "next_x_years":
			$buf = $this->buildQueryOpNextXYears($fieldName, $value, $type);
			break;
		case "is_equal":
		default:
			$buf = $this->buildQueryOpIsEqual($fieldName, $value, $type);
			break;
		}

		return $buf;
	}

	/**
	 * Craete is equal condition
	 *
	 * @param string $fieldName The name of the field being queried
	 * @param string $value Condition Value
	 * @param string $type The type of the field being queried
	 * @param string The operator and the condition for the query
	 */
	private function buildQueryOpIsEqual($fieldName, $value, $type)
	{
		if ($value)
			$buf = "$fieldName='$value'";
		else
			$buf = "$fieldName is null";

		return $buf;
	}

	/**
	 * Creat is not equal cond
	 *
	 * @param string $fieldName The name of the field being queried
	 * @param string $value Condition Value
	 * @param string $type The type of the field being queried
	 * @param string The operator and the condition for the query
	 */
	private function buildQueryOpIsNotEqual($fieldName, $value, $type)
	{
		if ($value)
			$buf = "$fieldName!='$value'";
		else
			$buf = "$fieldName is not null";

		return $buf;
	}

	/**
	 * Creat is not equal cond
	 *
	 * @param string $fieldName The name of the field being queried
	 * @param string $value Condition Value
	 * @param string $type The type of the field being queried
	 * @param string The operator and the condition for the query
	 */
	private function buildQueryOpIsGreater($fieldName, $value, $type)
	{
		if ($value && ($type == "time" || $type == "numeric"))
		{
			return "$fieldName > '$value'";
		}
		else
		{
			return "$fieldName is null"; // default if queried on a non-compatible type
		}
	}

	/**
	 * Field is less than 
	 *
	 * @param string $fieldName The name of the field being queried
	 * @param string $value Condition Value
	 * @param string $type The type of the field being queried
	 * @param string The operator and the condition for the query
	 */
	private function buildQueryOpIsLess($fieldName, $value, $type)
	{
		if ($value && ($type == "time" || $type == "numeric"))
		{
			return "$fieldName < '$value'";
		}
		else
		{
			return "$fieldName is null"; // default if queried on a non-compatible type
		}
	}

	/**
	 * Field is greater than or equal to
	 *
	 * @param string $fieldName The name of the field being queried
	 * @param string $value Condition Value
	 * @param string $type The type of the field being queried
	 * @param string The operator and the condition for the query
	 */
	private function buildQueryOpIsGreaterOrEq($fieldName, $value, $type)
	{
		if ($value && ($type == "time" || $type == "numeric"))
		{
			return "$fieldName >= '$value'";
		}
		else
		{
			return "$fieldName is null"; // default if queried on a non-compatible type
		}
	}

	/**
	 * Field is less than or equal to
	 *
	 * @param string $fieldName The name of the field being queried
	 * @param string $value Condition Value
	 * @param string $type The type of the field being queried
	 * @param string The operator and the condition for the query
	 */
	private function buildQueryOpIsLessOrEq($fieldName, $value, $type)
	{
		if ($value && ($type == "time" || $type == "numeric"))
		{
			return "$fieldName <= '$value'";
		}
		else
		{
			return "$fieldName is null"; // default if queried on a non-compatible type
		}
	}

	/**
	 * Day part of time is equal to
	 *
	 * @param string $fieldName The name of the field being queried
	 * @param string $value Condition Value
	 * @param string $type The type of the field being queried
	 * @param string The operator and the condition for the query
	 */
	private function buildQueryOpDayIsEqual($fieldName, $value, $type)
	{
		if ($value && $type == "time")
		{
			$buf = "extract(day from $fieldName)=";
			if ($value == "<%current_day%>")
				$buf .= "extract('day' from now())";
			else
				$buf .= "'$value'";

			return $buf;
		}
		else
		{
			return "$fieldName is null"; // default if queried on a non-compatible type
		}
	}

	/**
	 * Month part of time is equal to
	 *
	 * @param string $fieldName The name of the field being queried
	 * @param string $value Condition Value
	 * @param string $type The type of the field being queried
	 * @param string The operator and the condition for the query
	 */
	private function buildQueryOpMonthIsEqual($fieldName, $value, $type)
	{
		if ($value && $type == "time")
		{
			$buf = "extract(month from $fieldName)=";
			if ($value == "<%current_month%>")
				$buf .= "extract('month' from now())";
			else
				$buf .= "'$value'";

			return $buf;
		}
		else
		{
			return "$fieldName is null"; // default if queried on a non-compatible type
		}
	}

	/**
	 * Year part of time is equal to
	 *
	 * @param string $fieldName The name of the field being queried
	 * @param string $value Condition Value
	 * @param string $type The type of the field being queried
	 * @param string The operator and the condition for the query
	 */
	private function buildQueryOpYearIsEqual($fieldName, $value, $type)
	{
		if ($value && $type == "time")
		{
			$buf = "extract(year from $fieldName)=";
			if ($value == "<%current_year%>")
				$buf .= "extract('year' from now())";
			else
				$buf .= "'$value'";

			return $buf;
		}
		else
		{
			return "$fieldName is null"; // default if queried on a non-compatible type
		}
	}

	/**
	 * Within last x number of days
	 *
	 * @param string $value Condition Value
	 * @param string $type The type of the field being queried
	 * @param string The operator and the condition for the query
	 */
	private function buildQueryOpLastXDays($fieldName, $value, $type)
	{
		if (is_numeric($value) && $type == "time")
		{
			return "$fieldName>=(now()-INTERVAL '$value days')";
		}
		else
		{
			return "$fieldName is null"; // default if queried on a non-compatible type
		}
	}

	/**
	 * Within last x number of weeks
	 *
	 * @param string $fieldName The name of the field being queried
	 * @param string $value Condition Value
	 * @param string $type The type of the field being queried
	 * @param string The operator and the condition for the query
	 */
	private function buildQueryOpLastXWeeks($fieldName, $value, $type)
	{
		if (is_numeric($value) && $type == "time")
		{
			return "$fieldName>=(now()-INTERVAL '$value weeks')";
		}
		else
		{
			return "$fieldName is null"; // default if queried on a non-compatible type
		}
	}

	/**
	 * Within last x number of months
	 *
	 * @param string $fieldName The name of the field being queried
	 * @param string $value Condition Value
	 * @param string $type The type of the field being queried
	 * @param string The operator and the condition for the query
	 */
	private function buildQueryOpLastXMonths($fieldName, $value, $type)
	{
		if (is_numeric($value) && $type == "time")
		{
			return "$fieldName>=(now()-INTERVAL '$value months')";
		}
		else
		{
			return "$fieldName is null"; // default if queried on a non-compatible type
		}
	}

	/**
	 * Within last x number of years
	 *
	 * @param string $fieldName The name of the field being queried
	 * @param string $value Condition Value
	 * @param string $type The type of the field being queried
	 * @param string The operator and the condition for the query
	 */
	private function buildQueryOpLastXYears($fieldName, $value, $type)
	{
		if (is_numeric($value) && $type == "time")
		{
			return "$fieldName>=(now()-INTERVAL '$value years')";
		}
		else
		{
			return "$fieldName is null"; // default if queried on a non-compatible type
		}
	}

	/**
	 * Within next x number of days
	 *
	 * @param string $fieldName The name of the field being queried
	 * @param string $value Condition Value
	 * @param string $type The type of the field being queried
	 * @param string The operator and the condition for the query
	 */
	private function buildQueryOpNextXDays($fieldName, $value, $type)
	{
		if (is_numeric($value) && $type == "time")
		{
			return "$fieldName>=(now()+INTERVAL '$value days')";
		}
		else
		{
			return "$fieldName is null"; // default if queried on a non-compatible type
		}
	}

	/**
	 * Within next x number of weeks
	 *
	 * @param string $fieldName The name of the field being queried
	 * @param string $value Condition Value
	 * @param string $type The type of the field being queried
	 * @param string The operator and the condition for the query
	 */
	private function buildQueryOpNextXWeeks($fieldName, $value, $type)
	{
		if (is_numeric($value) && $type == "time")
		{
			return "$fieldName>=(now()+INTERVAL '$value weeks')";
		}
		else
		{
			return "$fieldName is null"; // default if queried on a non-compatible type
		}
	}

	/**
	 * Within next x number of months
	 *
	 * @param string $fieldName The name of the field being queried
	 * @param string $value Condition Value
	 * @param string $type The type of the field being queried
	 * @param string The operator and the condition for the query
	 */
	private function buildQueryOpNextXMonths($fieldName, $value, $type)
	{
		if (is_numeric($value) && $type == "time")
		{
			return "$fieldName>=(now()+INTERVAL '$value months')";
		}
		else
		{
			return "$fieldName is null"; // default if queried on a non-compatible type
		}
	}

	/**
	 * Within next x number of years
	 *
	 * @param string $fieldName The name of the field being queried
	 * @param string $value Condition Value
	 * @param string $type The type of the field being queried
	 * @param string The operator and the condition for the query
	 */
	private function buildQueryOpNextXYears($fieldName, $value, $type)
	{
		if (is_numeric($value) && $type == "time")
		{
			return "$fieldName>=(now()+INTERVAL '$value years')";
		}
		else
		{
			return "$fieldName is null"; // default if queried on a non-compatible type
		}
	}
}
