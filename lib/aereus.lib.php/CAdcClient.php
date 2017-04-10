<?php
/****************************************************************************
	
	Class:		CAdcClient

	Purpose:	ANT Datacenter Database Client

	Author:		Jeff Baker, jeff.baker@aereus.com
				Copyright (c) 2006 Aereus Corporation. All rights reserved.

	Std Usage:
				// Create connection object	
				$adc = new CAdcClient("testserv.aereus.com", 22, "username", "mypass");
				$adc->query("select * from visits");

	Samples:
				// Create connection object	
				$adc = new CAdcClient("testserv.aereus.com", 22, "username", "mypass");
				// Update the table visits where id = '1'
				$query = $adc->update("visits", "id", "1");
				$query->set("count", "10");
				$adc->execute($query);

				// Insert into visitors
				$query = $adc->insert("visits");
				$query->set("year", "2006");
 				$query->set("month", "12");
				$adc->execute($query);

				// Select id, name from visitors - can also use * for wildcard
				$query = $adc->select("visits", "id, name");
				$adc->execute($query);
				$num = $adc->getNumRows();
				for ($i = 0; $i < $num; $i++)
				{
					echo $adc->getValue($i, "id");
				} 
*****************************************************************************/

class CAdcClient
{
	var $m_url;
	var $m_user;
	var $m_pass;

	var $m_parcer;
	var $m_path;

	var $m_curind;
	var $m_fInRow;
	var $m_cols;
	
	function CAdcClient($server, $dbid, $user, $pass) 
	{
		$this->m_url = "http://".$server."/datacenter/xml_query.awp?dbid=$dbid";
		$this->m_url .= "&user=".base64_encode($user)."&password=".md5($pass);
	}

	function query($query)
	{
		$this->m_curind = 0;

		$this->m_parcer = xml_parser_create();
		xml_set_object($this->m_parcer, $this);
		xml_set_element_handler($this->m_parcer, "startElement", "endElement");
		xml_set_character_data_handler($this->m_parcer, "characterData");
	
		// Run query
		if (($fp = fopen($this->m_url . "&query=".rawurlencode($query),"r"))) 
		{
			while ($data = fread($fp, 4096)) 
			{
				if (!xml_parse($this->m_parcer, $data, feof($fp)))
				{
					//die(sprintf("XML error: %s at line %d", xml_error_string(xml_get_error_code($xml_parser)), 
					//		xml_get_current_line_number($xml_parser)));
				}
			}
			xml_parser_free($this->m_parcer);
		}

	}

	function execute($queryObj)
	{
		if ($queryObj)
		{
			$query = $queryObj->buildQuery();
			$this->query($query);
		}
	}
	
	function __destruct() 
	{
	}

	function getNumRows()
	{
		return count($this->m_rows);
	}

	function getValue($row, $col)
	{
		return $this->m_rows[$row]->m_vars[$col];
	}


	function update($table, $col=null, $val=null)
	{
		return new CAdcUpdate($table, $col, $val);
	}

	function insert($table)
	{
		return new CAdcInsert($table);
	}

	function select($table, $cols, $where=null, $order=null, $limit=null)
	{
		return new CAdcSelect($table, $cols, $where, $order, $limit);
	}

	function startElement($parser, $name, $attrs)
	{
		$this->m_path .= ($this->m_path) ?  ":".$name : $name;

		switch ($this->m_path)
		{
		case 'RESULT:DATASET:ROW':
			$this->m_rows[$this->m_curind] = new CAdcClientRow();
			$this->m_fInRow = true;
			break;
		default:
			// If inside post then populate variable
			if ($this->m_fInRow)
			{
				$this->m_rows[$this->m_curind]->m_curname = strtolower($name);
				$this->m_rows[$this->m_curind]->m_vars[strtolower($name)] = "";
			}
			break;
		}
	}

	function endElement($parser, $name)
	{
		switch ($this->m_path)
		{
		case 'RESULT:DATASET:ROW':
			$this->m_curind++;
			$this->m_fInRow = false;
			break;
		}
		
		$this->m_path = substr($this->m_path, 0, strrpos($this->m_path, ":"));
	}

	function characterData($parser, $data)
	{
		switch ($this->m_path)
		{
		default:
			if ($this->m_fInRow && $data)
			{
				$name = $this->m_rows[$this->m_curind]->m_curname;
				$this->m_rows[$this->m_curind]->m_vars[strtolower($name)] .= rawurldecode($data);
			}
			break;
		}
	}
}

/*************************************************************************
*	Class:		CAdcClientRow
*
*	Purpose:	Helper class hold row values (parent has array of this)
**************************************************************************/
class CAdcClientRow
{
	var $m_vars;
	var $m_curname;
	var $m_curtitle;

	function CAdcClientRow()
	{
		$this->m_vars = array();
	}
}

/*************************************************************************
*	Class:		CAdcUpdate
*
*	Purpose:	Helper class to build update query
**************************************************************************/
class CAdcUpdate
{
	var $query;
	var $set;

	var $updatecol;
	var $updatecond;

	function CAdcUpdate($table, $col=null, $cond=null)
	{
		$this->query = "update $table set ";
		$this->updatecol = $col;
		$this->updatecond = $cond;
	}

	function set($col, $val)
	{
		if ($set)
			$this->query .= ", ";

		$this->query .= "$col='$val'";
	}

	function buildQuery()
	{
		if ($this->updatecol && $this->updatecond)
			$this->query .= " where ".$this->updatecol." = '".$this->updatecond."'";

		return $this->query;
	}
}

/*************************************************************************
*	Class:		CAdcInsert
*
*	Purpose:	Helper class to build insert queries
**************************************************************************/
class CAdcInsert
{
	var $query;

	var $cols;
	var $vals;

	function CAdcInsert($table)
	{
		$this->query = "insert into $table ";
	}
	
	function set($col, $val)
	{
		if ($this->cols)
			$this->cols .= ", ";
		$this->cols .= $col;
		
		if ($this->vals)
			$this->vals .= ", ";
		$this->vals .= "'$val'";
	}

	function buildQuery()
	{
		if ($this->cols && $this->vals)
		{
			$this->query .= "(".$this->cols.") values";
			$this->query .= "(".$this->vals.")";
			return $this->query;
		}
	}
}

/*************************************************************************
*	Class:		CAdcSelect
*
*	Purpose:	Helper class to execute select queries
**************************************************************************/
class CAdcSelect
{
	var $query;

	function CAdcSelect($table, $cols, $where=null, $order=null, $limit=null)
	{
		$this->query = "select $cols from $table";
		if ($where)
			$this->query .= " where $where ";
		if ($order)
			$this->query .= " order by $order";
		if ($limit)
			$this->query .= " limit $limit";
	}
	
	function buildQuery()
	{
		return $this->query;
	}
}

//========================================================================
//
//	Test routines below
//
//========================================================================

/*
$adc = new CAdcClient("testserv.aereus.com", 26, "username", "mypass");

$query = $adc->select("visits", "id, count");
$adc->execute($query);
$num = $adc->getNumRows();
for ($i = 0; $i < $num; $i++)
{
	echo $adc->getValue($i, "id")."<br>";
}
*/
?>
