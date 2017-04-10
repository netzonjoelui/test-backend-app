<?php
	ini_set("memory_limit", "2G");	
	$headers_buf = "";
	$num_imported = 0;
	$odef = new CAntObject($dbh, $data['obj_type']);
	
	$ALIB_CACHE_DISABLE = true; // disable caching

	$file = new CAntFsFile($dbh, $data['data_file_id']);
	if (!file_exists($settings_data_path."/tmp"))
		mkdir($settings_data_path."/tmp");

	if (file_exists($settings_data_path."/tmp"))
	{
		$tmpfname = tempnam($settings_data_path."/tmp", "imp");
		$fh = fopen($tmpfname, "w+");

		if ($fh)
		{
			//$buf = $file->read();
			//fwrite($fh, $buf);
			//unset($buf);
			$file->stream($fh);
			fseek($fh, 0);

			// Skip over first row (headers)
			$csvData = fgetcsv($fh, 1024, ',', '"');

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

				if (is_array($data['map_fields']) && $fIsData)
				{
					// Merge duplicates
					// ----------------------------------------------------
					if (is_array($data['merge_by']))
					{
						for ($i = 0; $i < $num; $i++)
						{
							$fmap = $data['map_fields'][$i];

							if ($fmap && $csvData[$i]) // DO Not Import is always and empty string
							{
								$val = $csvData[$i];
								$field = $odef->fields->getField($fmap);
								if ($field['type'] == "fkey" && $csvData[$i])
								{
									if (is_array($field['fkey_table']))
									{
										$result = $dbh->Query("select ".$field['fkey_table']['key']." from ".$field['subtype']." 
																where ".$field['fkey_table']['title']."='".$dval."' and f_deleted is not true");
										if ($dbh->GetNumberRows($result))
											$val = $dbh->GetValue($result, 0, $field['fkey_table']['key']);
										else
											$val = ""; // No foreign key found, exclude from import
									}
								}

								$conditions = array();
								$conditions['conditions'] = array();

								if (count($conditions))
								{
									$olist = new CAntObjectList($dbh, $data['obj_type'], $USER);

									for ($m = 0; $m < count($data['merge_by']); $m++)
									{
										if ($fmap == $data['merge_by'][$m])
										{
											$objList->addCondition("and", $fmap, "is_equal", $val);
										}
									}

									$objList->getObjects();
									$numFound = $olist->getNumObjects();
									if ($numFound == 1)
									{
										$cid = $olist->objects[0][0];
									}
									unset($olist);
								}
							}
						}
					}

					$obj = new CAntObject($dbh, $data['obj_type'], $cid);

					// Handle default values
					// ----------------------------------------------------
					$ofields = $obj->fields->getFields();
					foreach ($ofields as $fname=>$field)
					{
						if ($field['type']=='fkey_multi')
						{
							// Purge
							$obj->removeMValues($fname);

							if (is_array($data[$fname]) && count($data[$fname]))
							{
								// Add new
								foreach ($data[$fname] as $val)
									$obj->setMValue($fname, $val);
							}
						}
						else if ($field['type']=='object' || $field['type']=='object_multi')
						{
						}
						else
						{
							$obj->setValue($fname, $data[$fname]);
						}
					}

					// Import data
					// ----------------------------------------------------
					for ($i = 0; $i < $num; $i++)
					{
						$fmap = $data['map_fields'][$i];

						// Check for dynamic field creation
						if ($fmap == "ant_create_field" || $fmap == "ant_create_field_dd")
						{
							/*
							$fmap = "";
							$odef->addField("fname", "title", "type", "subtype");
							*/
						}

						if ($fmap && $csvData[$i]) // DO Not Import is always and empty string
						{
							$field = $odef->fields->getField($fmap);
							if ($field['type'] == "fkey_multi" && $fmap=="groups" && $csvData[$i] && is_array($field['fkey_table']))
							{
								$groups = explode(";", $csvData[$i]);
								$act_col_exists = $dbh->ColumnExists($field['subtype'], "account_id");

								foreach ($groups as $group)
								{
									$group = trim($group);

									$query = "select ".$field['fkey_table']['key']." from ".$field['subtype']." 
															where ".$field['fkey_table']['title']."='".$dbh->Escape($group)."'";
									if ($act_col_exists)
										$query .= " and account_id='$ACCOUNT'";
									$result = $dbh->Query($query);
									if ($dbh->GetNumberRows($result))
									{
										$gid = $dbh->GetValue($result, 0, $field['fkey_table']['key']);
									}
									else
									{
										$query = "insert into ".$field['subtype']."(".$field['fkey_table']['title']."";
										if ($act_col_exists)
											$query .= ", account_id";
										$query .= ") values('".$dbh->Escape($group)."'";
										if ($act_col_exists)
											$query .= ", '$ACCOUNT'";
										$query .= "); select currval('".$field['subtype']."_id_seq') as id;";

										$result = $dbh->Query($query);
										if ($dbh->GetNumberRows($result))
											$gid = $dbh->GetValue($result, 0, "id");
									}

									if ($gid)
										$obj->setMValue($fmap, $gid);
								}
							}
							if ($field['type'] == "object" || $field['type'] == "object_multi")
							{
							}
							else
							{
								if ($field['type'] == "fkey" && $csvData[$i] && is_array($field['fkey_table']))
								{
									$result = $dbh->Query("select ".$field['fkey_table']['key']." from ".$field['subtype']." 
															where ".$field['fkey_table']['title']."='".$dbh->Escape($csvData[$i])."'");
									if ($dbh->GetNumberRows($result))
									{
										$csvData[$i] = $dbh->GetValue($result, 0, $field['fkey_table']['key']);
									}
									else
									{
										// try numeric id
										if (is_numeric($csvData[$i]))
										{
											$result = $dbh->Query("select ".$field['fkey_table']['key']." from ".$field['subtype']." 
																	where ".$field['fkey_table']['key']."=".$dbh->EscapeNumber($csvData[$i])."");
											if (!$dbh->GetNumberRows($result))
												$csvData[$i] = ""; // No foreign key found, exclude from import
										}
										else
										{
											if ($field['subtype'] != "users" && $field['subtype'] != "user_groups")
											{
												// Try to insert the new value
												$dbh->Query("insert into ".$field['subtype']."(".$field['fkey_table']['title'].") 
															 values('".$dbh->Escape($csvData[$i])."')");

												$result = $dbh->Query("select ".$field['fkey_table']['key']." from ".$field['subtype']." 
																		where ".$field['fkey_table']['title']."='".$dbh->Escape($csvData[$i])."'");
												if ($dbh->GetNumberRows($result))
												{
													$csvData[$i] = $dbh->GetValue($result, 0, $field['fkey_table']['key']);
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
					$obj->save();
					$num_imported++;
				}
			}

			fclose($fh);
			unlink($tmpfname);
		}
	}

	// Email login information
	if ($data['send_notifaction_to'])
	{
		$message = "This email is being sent to inform you that import job id $pid has completed\r\n";
		$message .= $num_imported." ".$odef->titlePl." were successfully imported";
		$headers = array();
		$headers['From']  = $settings_no_reply;
		$headers['To']  = $data['send_notifaction_to'];
		$headers['Subject']  = "Import Completed";
		// Create new email object
		$email = new Email();
		$status = $email->send($headers['To'], $headers, $message);
		unset($email);
	}
?>
