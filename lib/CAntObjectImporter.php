<?php
/**
 * This class handles importing data into ANT from multiple sources
 *
 * @category  CAntObject
 * @package   CAntObjectImporter
 * @copyright Copyright (c) 2003-2012 Aereus Corporation (http://www.aereus.com)
 */
require_once("lib/AntFs.php");

class CAntObjectImporter
{
	/**
     * Store field mapping
	 *
	 * Data is stored as an associative array or if we are working
	 * with a csv, it will be numeric as a string. So ["0"] = "name" means 
	 * pull data from column 0 and put it into the "name" field of the selected object.
     *
     * @var array
	 */
	public $fieldMaps = array();

	/**
     * Field defaults
	 *
	 * Associative array of defaults to use if any value is null. ['name'] = "untitled"
	 * would set the 'name' field of each imported object to 'untitled' if value
	 * was not set in the import.
     *
     * @var array
	 */
	public $fieldDefaults = array();

	/**
     * Merge by fields
	 *
	 * Array of fields to combine for merging existing data. For example, array('name', 'phone')
	 * would cause the import to update matching records where name and phone match the imported data
	 * rather than creating new records.
     *
     * @var array
	 */
	public $mergeBy = array();

	/**
     * Store last error generated
     *
     * @var string
	 */
	public $lastError = "";

	/**
     * Track number of records imported
     *
     * @var integer
	 */
	public $numImported = 0;

	/**
     * Track number of records merged into existing records
     *
     * @var integer
	 */
	public $numMerged = 0;

	/**
     * Main object we working with
     *
     * @var CAntObject
	 */
	public $obj = null;
	
	/**
     * Handle to account database
     *
     * @var CDatabase
	 */
	public $dbh = null;

	/**
     * Handle to user class
     *
     * @var AntUser
	 */
	public $user = null;

	/**
     * ID of AntFs file to use for source
     *
     * @var integer
	 */
	private $fileId = 0;

	/**
     * Full path to temporary file
     *
     * @var integer
	 */
	private $tmpFilePath = "";

	/**
	 * Class constructor
	 *
	 * @param CDatabase $dbh Handle to account database
	 * @param string $obj_type The name of the object we are importing
	 * @param AntUser $user Optional reference to current AntUser
	 */
	function __construct($dbh, $obj_type, $user=null)
	{
		$this->dbh = $dbh;
		$this->obj = new CAntObject($dbh, $obj_type); // Load object type for definition
		$this->user = $user;
	}

	/**
	 * Add a field map from source column to object field
	 *
	 * @param string $sourceCol The column name or number (if csv)
	 * @param string $objField The object field to place column data into
	 */
	public function addFieldMap($sourceCol, $objField)
	{
		$this->fieldMaps[$sourceCol] = $objField;
	}

	/**
	 * Set a default value for a field if the data in the column is null
	 *
	 * @param string $objField The name ofthe object field to set
	 * @param string $val The value to set field to
	 * @param string $on Currently only supports 'null' which of course is the default value
	 */
	public function addFieldDefault($objField, $val, $on='null')
	{
		$this->fieldDefaults[$objField] = $val;
	}

	/**
	 * Add fields to merge impors by
	 *
	 * This is used to match imported data to objects already existing in ANT.
	 * If set, the field name MUST be a field that is being imporeted. For instance,
	 * you cannot merge by first_name if you have not mapped first_name to a column
	 * in the imported data.
	 *
	 * @param string $objField The field name to merge by
	 */
	public function addMergeBy($objField)
	{
		$this->mergeBy[] = $objField;
	}

	/**
	 * Set source to ant file
	 *
	 * @param integer $fileId The id of the temp file being imported
	 * @param string $type In the future different file formats will be suppored, right now defaults to csv
	 */
	public function setSourceAntFS($fileId, $type='csv')
	{
		$this->fileId = $fileId;
		$antfs = new AntFs($this->dbh);

		/// Now copy file to local temp file
		// Load the file and make sure we have a tmp dir to work with
		$file = $antfs->openFileById($fileId);

		if ($file)
			$this->tmpFilePath = $file->copyToTemp();
		else
			return false;

		return true;
	}

	/**
	 * Set source to a local file
	 *
	 * @param integer $fileId The id of the temp file being imported
	 * @param string $type In the future different file formats will be suppored, right now defaults to csv
	 */
	public function setSourceFile($path, $type='csv')
	{
		// Make sure we have a tmp dir to work with
		if (!file_exists(AntConfig::getInstance()->data_path."/tmp"))
			mkdir(AntConfig::getInstance()->data_path."/tmp");

		// Process if we have a tmp working directory
		if (file_exists(AntConfig::getInstance()->data_path."/tmp"))
		{
			// Save ANT file to a local temp file
			$tmpfname = tempnam(AntConfig::getInstance()->data_path."/tmp", "imp");
			if (copy($path, $tmpfname))
			{
				// Set class variable (will be deleted later)
				$this->tmpFilePath = $tmpfname;
			}
		}
	}

	/**
	 * Clean up any temporarily allocated resources
	 */
	public function cleanup()
	{
		if ($this->tmpFilePath)
			@unlink($this->tmpFilePath);
	}

	/**
	 * Run import
	 */
	public function import()
	{
		global $ALIB_CACHE_DISABLE;

		$dbh = $this->dbh;
		$headers_buf = "";
		$this->numImported = 0;
		$this->numMerged = 0;
		$ALIB_CACHE_DISABLE = true; // disable caching

		// Open file
		if ($this->tmpFilePath)
		{
			$fh = fopen($this->tmpFilePath, "r");
		}

		// no temp file, that is a problem!
		if (!$fh)
		{
			$this->cleanup();
			return false;
		}

		// Skip over first row (headers)
		$csvData = fgetcsv($fh, 1024, ',', '"');

		// Loop through remainder of file
		while (!feof($fh))
		{
			$csvData = fgetcsv($fh, 1024, ',', '"');
			$num = count($csvData);
			$cid = null;

			// Check for blank line
			$fIsData = false;
			for ($i = 0; $i < $num; $i++)
			{
				if ($csvData[$i])
				{
					$fIsData = true;
					break;
				}
			}
			
			if (count($this->fieldMaps) && $fIsData)
			{
				// Merge duplicates
				// ----------------------------------------------------
				if (count($this->mergeBy))
				{
					$conditionsSet = false;

					$objList = new CAntObjectList($dbh, $this->obj->object_type, $this->user);

					// Loop through each column
					for ($i = 0; $i < $num; $i++)
					{
						$fmap = $this->fieldMaps[$i];

						if ($fmap && $csvData[$i]) // DO Not Import is always and empty string
						{
							$val = $csvData[$i];
							$field = $this->obj->def->getField($fmap);

							if ($field->type == "fkey" && $csvData[$i])
							{
								if (is_array($field->fkeyTable))
								{
									$result = $dbh->Query("select ".$field->fkeyTable['key']." from ".$field->subtype." 
															where ".$field->fkeyTable['title']."='".$dval."' and f_deleted is not true");
									if ($dbh->GetNumberRows($result))
										$val = $dbh->GetValue($result, 0, $field->fkeyTable['key']);
									else
										$val = ""; // No foreign key found, exclude from import
								}
							}
							if ($val)
							{

								for ($m = 0; $m < count($this->mergeBy); $m++)
								{
									if ($fmap == $this->mergeBy[$m])
									{
										$objList->addCondition("and", $fmap, "is_equal", $val);
										$conditionsSet = true;
									}
								}
							}
						}
					}

					// If we found a merge by value to test against
					if ($conditionsSet)
					{
						$objList->getObjects();
						$numFound = $objList->getNumObjects();
						if ($numFound)
						{
							$mindat = $objList->getObjectMin(0);
							$cid = $mindat['id'];
							$this->numMerged++;
						}
						unset($objList);
					}
				}

				$obj = new CAntObject($dbh, $this->obj->object_type, $cid, $this->user);

				// Handle default values
				// ----------------------------------------------------
				$ofields = $obj->def->getFields();
				foreach ($ofields as $fname=>$field)
				{
					if ($field->type=='fkey_multi' || $field->type=='object_multi')
					{
						// Purge
						$obj->removeMValues($fname);

						if(isset($this->fieldDefaults[$fname]) && is_array($this->fieldDefaults[$fname]) && count($this->fieldDefaults[$fname]))
						{
							// Add new
							foreach ($this->fieldDefaults[$fname] as $val)
								$obj->setMValue($fname, $val);
						}
						else
						{
                            if(isset($this->fieldDefaults[$fname]))
							    $obj->setMValue($fname, $this->fieldDefaults[$fname]);
						}
					}
					else
					{
                        if(isset($this->fieldDefaults[$fname]))
						    $obj->setValue($fname, $this->fieldDefaults[$fname]);
					}
				}

				// Import data
				// ----------------------------------------------------
				for ($i = 0; $i < $num; $i++)
				{
                    $fmap = null;
                    if(isset($this->fieldMaps[$i]))
					    $fmap = $this->fieldMaps[$i];

					// Check for dynamic field creation
					if ($fmap == "ant_create_field" || $fmap == "ant_create_field_dd")
					{
						/*
						$fmap = "";
						$this->obj->addField("fname", "title", "type", "subtype");
						*/
					}

					if ($fmap && $csvData[$i]) // DO Not Import is always and empty string
					{
						$field = $this->obj->def->getField($fmap);
						if ($field->type == "fkey_multi" && $fmap=="groups" && $csvData[$i] && is_array($field->fkeyTable))
						{
							$groups = explode(";", $csvData[$i]);
							$act_col_exists = $dbh->ColumnExists($field->subtype, "account_id");

							foreach ($groups as $group)
							{
								$group = trim($group);

								$gid = $this->obj->getGroupingEntryByName($fmap, $group);

								// Not found, let's try to add it
								if (!$gid)
								{
									$data = $obj->addGroupingEntry($fmap, $groups);
									$gid = $data['id'];
								}

								if ($gid)
									$obj->setMValue($fmap, $gid);
							}
						}
						if ($field->type == "object_multi")
						{
						}
						else
						{
							if ($field->type == "fkey" && $csvData[$i] && is_array($field->fkeyTable))
							{
								$result = $dbh->Query("select ".$field->fkeyTable['key']." from ".$field->subtype." 
														where ".$field->fkeyTable['title']."='".$dbh->Escape($csvData[$i])."'");
								if ($dbh->GetNumberRows($result))
								{
									$csvData[$i] = $dbh->GetValue($result, 0, $field->fkeyTable['key']);
								}
								else
								{
									// try numeric id
									if (is_numeric($csvData[$i]))
									{
										$result = $dbh->Query("select ".$field->fkeyTable['key']." from ".$field->subtype." 
																where ".$field->fkeyTable['key']."=".$dbh->EscapeNumber($csvData[$i])."");
										if (!$dbh->GetNumberRows($result))
											$csvData[$i] = ""; // No foreign key found, exclude from import
									}
									else
									{
										if ($field->subtype != "users" && $field->subtype != "user_groups")
										{
											// Try to insert the new value
											$dbh->Query("insert into ".$field->subtype."(".$field->fkeyTable['title'].") 
														 values('".$dbh->Escape($csvData[$i])."')");

											$result = $dbh->Query("select ".$field->fkeyTable['key']." from ".$field->subtype." 
																	where ".$field->fkeyTable['title']."='".$dbh->Escape($csvData[$i])."'");
											if ($dbh->GetNumberRows($result))
											{
												$csvData[$i] = $dbh->GetValue($result, 0, $field->fkeyTable['key']);
											}
											else
											{
												$csvData[$i] = ""; // No foreign key found, exclude from import
											}
										}
										else
										{
											$csvData[$i] = ""; // No foreign key found, exclude from import
										}
									}
								}
							}

							if ($csvData[$i])
								$obj->setValue($fmap, $csvData[$i]);
						}
					}
				}

				// Save values
				$oid = $obj->save();

				$this->numImported++;
			}
		}

		fclose($fh);
		$this->cleanup();
	}
}
