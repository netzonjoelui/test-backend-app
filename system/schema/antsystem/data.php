<?php
/**
 * Update datasets in the antsystem database
 */

/**
 * Function used to sync updates via a primary key
 */
function sysEntExists($dbh, $tbl, $col, $val, $additional_cond)
{
	$ret = false;

	if ($tbl && $col)
	{
		$result = $dbh->Query("select $col from $tbl where $col='".$dbh->Escape($val)."' $additional_cond");
		if ($dbh->GetNumberRows($result))
			$ret = true;

		$dbh->FreeResults($result);
	}

	return $ret;
}

// Each record contains
// 0 = table name
// 1 = file name
// 2 = check against column
// 3 = optional additional condition for searching for this record
// 4 = column to cvs match map (array)
// 5 = Update if record already exists (true/false)
// The first column in csv will always be the unique ident
$maps = array();

// zipcodes
// ----------------------------------------------------------------------
$fields = array(
	"zipcode"=>0, 
	"city"=>1, 
	"state"=>2, 
	"latitude"=>3,
	"longitude"=>4, 
	"timezone"=>5, 
	"dst"=>6
);
$maps[] = array("zipcodes", "zipcodes.csv", "zipcode", "", $fields, true);

/**
 * Now execute each map to import data into the database
 */
foreach ($maps as $map)
{
	$fh = fopen(AntConfig::getInstance()->application_path . "/system/schema/antsystem/data/".$map[1], 'r');
	if ($fh)
	{
		$columns = fgetcsv($fh, 10240, ",", "\""); // skip over first row

		while (!feof($fh))
		{
			$query = "";
			$row_data = fgetcsv($fh, 10240, ",", "\"");
			
			if ($map[0] && $row_data[0])
			{
				$exists = sysEntExists($dbh_sys, $map[0], $map[2], $row_data[0], $map[3]);

				if ($exists && $map[5])
				{
					$query = "UPDATE ".$map[0]." SET ";
					$updated = "";
					foreach ($map[4] as $tblcol=>$csvcol)
					{
						if ($updated)
							$updated .= ", ";

						$updated .= " $tblcol='" . $dbh_sys->Escape($row_data[$csvcol]) . "'";
					}
					$query .= $updated." WHERE ".$map[2]."='" . $dbh_sys->Escape($row_data[0]) . "' ";
				}
				else if (!$exists)
				{
					$query = "INSERT INTO ".$map[0]."";
					$cols = "";
					$vals = "";
					foreach ($map[4] as $tblcol=>$csvcol)
					{
						if ($cols)
						{
							$cols .= ", ";
							$vals .= ", ";
						}

						$cols .= $tblcol;
						$vals .= "'" . $dbh_sys->Escape($row_data[$csvcol]) . "'";
					}
					$query .= "($cols) VALUES($vals);";
				}
			}
			
			if ($query)
			{
				$dbh_sys->Query($query);
				//echo "$query \n";
			}
		}
		fclose($fh);
	}
}

// System domain & incoming email address
// ----------------------------------------------------------------------

// Add sys.netric.com to domains
if (!$dbh_sys->GetNumberRows($dbh_sys->Query("SELECT domain FROM email_domains WHERE domain='" . AntConfig::getInstance()->email['dropbox']. "'")))
{
	$dbh_sys->Query("INSERT INTO email_domains(domain, description, active) 
						VALUES ('" . AntConfig::getInstance()->email['dropbox']. "', 'System Email Processing Domain', 't');");

}

// Add incoming email account
if (!$dbh_sys->GetNumberRows($dbh_sys->Query("SELECT id FROM email_users WHERE email_address='" . AntConfig::getInstance()->email['dropbox']. "'")))
{
	$dbh_sys->Query("INSERT INTO email_users(email_address, maildir, password) 
					 VALUES (
						'" . AntConfig::getInstance()->email['dropbox']. "', 
						'" . AntConfig::getInstance()->email['dropbox']. "', 
						md5('" . AntConfig::getInstance()->db['password'] . "')
					 );");
}
else
{
	// Update password just in case we have changed it
	$dbh_sys->Query("UPDATE email_users SET password=md5('" . AntConfig::getInstance()->db['password'] . "') 
						WHERE email_address='" . AntConfig::getInstance()->email['dropbox']. "'"); 
}

// Add catch-all alias for system domain
if (!$dbh_sys->GetNumberRows($dbh_sys->Query("SELECT goto FROM email_alias WHERE address='" . AntConfig::getInstance()->email['dropbox_catchall']. "'")))
{
	$dbh_sys->Query("INSERT INTO email_alias(address, goto, active) 
					 VALUES (
						'" . AntConfig::getInstance()->email['dropbox_catchall']. "', 
						'" . AntConfig::getInstance()->email['dropbox']. "', 
						't'
					 );");
}
