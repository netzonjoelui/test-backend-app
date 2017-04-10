<?php
function GetStock(&$dbh, $sym)
{
	$toget = "id, extract('EPOCH' from last_updated) as last_updated, symbol, name, price, price_change, percent_change";
	if (is_numeric($sym))
		$result = $dbh->Query("select $toget from stocks where id='$sym'");
	else
	{
		$sym = strtoupper($sym);
		$result = $dbh->Query("select $toget from stocks where symbol='$sym'");
	}
	if ($dbh->GetNumberRows($result))
	{
		$row = $dbh->GetNextRow($result, 0);
		$last_update = $row["last_updated"];
		if ($row["last_updated"])
		{
			$datetime = $row["last_updated"]; //strtotime(substr($row["last_updated"], 0, strpos($row["last_updated"], ".")));
			$diff = time() - $datetime;
		}
		
		//echo "-Stock Expired found: $diff<br>";
		if (!$diff || $diff > (3600)) // 1 Hour
		{
			if (UpdateStock($dbh, $row['symbol']))
				$res = GetStock($dbh, $sym);
			else
				$res = false;
		}
		else
		{
			$res = $row;
		}	
	}
	else
	{
		if (UpdateStock($dbh, $sym))
			$res = GetStock($dbh, $sym);
		else
			$res = false;
	}
	$dbh->FreeResults($result);
	
	return $res;
}

function UpdateStock(&$dbh, $sym)
{
	if (!is_numeric($sym) && $sym != '')
	{
		$sym = strtoupper($sym);
		$url ="http://finance.yahoo.com/d/quotes.csv?s=$sym&f=sl1c2n";
		$fp = @fopen($url, "r");
		if($fp)
		{
			$array = fgetcsv($fp , 4096 , ', ');
			fclose($fp);
			$symbol = $array[0];
			$last = $array[1];
			$change = $array[2];
			$name = $array[3];
			$arrChange = explode(" - ", $change);
			
			$change_val = $arrChange[0];
			$change_per = $arrChange[1];
			
			if ($last != 0 && $last != '0')
			{
				if ($dbh->GetNumberRows($dbh->Query("select id from stocks where symbol='$sym'")))
				{
					$query = "update stocks set name='$name', price='$last', price_change='$change_val', 
							  percent_change='$change_per', last_updated='now' where symbol='$sym'";
				}
				else
				{
					$query = "insert into stocks(symbol, name, price, price_change, percent_change, last_updated)
							  values('$sym', '$name', '$last', '$change_val', '$change_per', 'now')";
				}
				$dbh->Query($query);
				return true;
			}
			else
			{
				return false;
			}	
			
		}
	}
	else
	{
		return false;
	}
}
?>
