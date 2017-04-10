<?php
/*
	$cdata = new CChartData("Sales", "Revenue", "Sales Figures for 2006", "(hi there)", "$", "0");
	// Single Series
	$cdata->addEntry("25", "Item A", "FF9966");
	$cdata->addEntry("17", "Item B", "FF0000");
	$cdata->addEntry("23", "Item C", "006F00");
	$cdata->addEntry("60", "Item D", "0099FF");
	$cdata->addCategory("Jan", "January");
	$cdata->addCategory("Feb", "February");
	$cdata->addCategory("Mar", "March");

	// Multi-Series
	$set = $cdata->addSet("Product A");
	$set->addEntry("8343");
	$set->addEntry("6300");
	$set->addEntry("2900");

	$set = $cdata->addSet("Product B");
	$set->addEntry("9343");
	$set->addEntry("5200");
	$set->addEntry("8000");

	// Print XML
 	echo $cdata->getData();
*/

class CChart
{
	var $swf_file;
	var $data_path;
	var $chart_types;

	function CChart($type=null, $datafile=null)
	{
		$this->data_path = $datafile;

		$this->chart_types = array();
		// Format: category, title, file, series(1 for single, 2 for multi)
		$tcat = "Column";
		$this->chart_types["Column3D"] 		= array($tcat, "3D Column Chart", "FC_2_3_Column3D.swf", 1);
		$this->chart_types["MSColumn3D"] 	= array($tcat, "Multi-series 3D Column Chart", "FC_2_3_MSColumn3D.swf", 2);
		$this->chart_types["Column2D"] 		= array($tcat, "2D Column Chart", "FC_2_3_Column2D.swf", 1);
		$this->chart_types["MSColumn2D"] 	= array($tcat, "Multi-series 2D Column Chart", "FC_2_3_MSColumn2D.swf", 2);
		$this->chart_types["Bar2D"] 		= array($tcat, "2D Bar Chart", "FC_2_3_Bar2D.swf", 1);
		$this->chart_types["MSBar2D"] 		= array($tcat, "Multi-series 2D Bar Chart", "FC_2_3_MSBar2D.swf", 2);
		$tcat = "Line";
		$this->chart_types["Line"] 			= array($tcat, "2D Line Chart", "FC_2_3_Line.swf", 1);
		$this->chart_types["MSLine"] 		= array($tcat, "Multi-series 2D Line Chart", "FC_2_3_MSLine.swf", 2);
		$tcat = "Pie";
		$this->chart_types["Pie3D"] 		= array($tcat, "3D Pie Chart", "FC_2_3_Pie3D.swf", 1);
		$this->chart_types["Pie2D"] 		= array($tcat, "2D Pie Chart", "FC_2_3_Pie2D.swf", 1);
		$this->chart_types["Doughnut2D"] 	= array($tcat, "2D Doughnut Chart", "FC_2_3_Doughnut2D.swf", 1);
		$tcat = "Area";
		$this->chart_types["Area2D"] 		= array($tcat, "2D Area Chart", "FC_2_3_Area2D.swf", 1);
		$this->chart_types["MSArea2D"] 		= array($tcat, "Multi-series 2D Area Chart", "FC_2_3_MSArea2D.swf", 2);
		$tcat = "Scatter/Bubble";
		$this->chart_types["MSScatter"] 	= array($tcat, "2D Scatter Chart", "FC_2_3_MSScatter.swf", 3);
		$this->chart_types["MSBubble"] 		= array($tcat, "3D/2D Bubble Chart", "FC_2_3_MSBubble.swf", 3);
		$tcat = "Stacked";
		$this->chart_types["StckdColumn3D"]	= array($tcat, "3D Stacked Column", "FC_2_3_StckdColumn3D.swf", 2);
		$this->chart_types["StckdArea"] 	= array($tcat, "2D Stacked Area Chart", "FC_2_3_StckdArea.swf", 2);
		$this->chart_types["StckdBar2D"] 	= array($tcat, "2D Stacked Bar", "FC_2_3_StckdBar2D.swf", 2);
		$this->chart_types["StckdColumn2D"]	= array($tcat, "2D Stacked Column", "FC_2_3_StckdColumn2D.swf", 2);
		$tcat = "Candlestick";
		$this->chart_types["Candlestick"]	= array($tcat, "Candle stick chart", "FC_2_3_Candlestick.swf", 3);
		$tcat = "Radar";
		$this->chart_types["Radar"] 		= array($tcat, "Radar Chart", "FC_2_3_Radar.swf", 2);
		$tcat = "Grid";
		$this->chart_types["SSGrid"] 		= array($tcat, "Grid", "FC_2_3_SSGrid.swf", 3);
		$tcat = "Combination";
		$this->chart_types["MSColumnLine_DY_3D"]	= array($tcat, "Multi-series 3D Column + Multi-series Line Dual Y", 
												  			"FC_2_3_MSColumnLine_DY_3D.swf", 3);
		$this->chart_types["MSColumnLine_DY_2D"] 	= array($tcat, "Multi-series 2D Column + Multi-series Line Dual Y", 
															"FC_2_3_MSColumnLine_DY_2D.swf", 3);
		$tcat = "Stacked Combination";
		$this->chart_types["StackedArea_MSLine_DY_2D"] = array($tcat, "2D Stacked Area + Multi-series Line Dual Y", 
															   "FC_2_3_StackedArea_MSLine_DY_2D.swf", 3);
		$this->chart_types["StckdColumn_MSLine_DY_2D"] = array($tcat, "2D Stacked Column + Multi-series Line Dual Y", 
															   "FC_2_3_StckdColumn_MSLine_DY_2D.swf", 3);
		
	
		if ($type)	
			$this->swf_file = $this->chart_types[$type][2];

		// Initialize options
		$this->setOptions();
	}

	function getListOfGraphs($series = null)
	{
		$ret = array();

		switch ($series)
		{
		case 'single':
			foreach ($this->chart_types as $name=>$graph)
			{
				$buf = array();
				// Check for single series
				if (1 == $graph[3])
				{
					$buf['name'] = $name;
					$buf['category'] = $graph[0];
					$buf['title'] = $graph[1];
					$ret[] = $buf;
				}
			}
			break;
		case 'multi':
			foreach ($this->chart_types as $name=>$graph)
			{
				$buf = array();
				// Check for multi series
				if (2 == $graph[3])
				{
					$buf['name'] = $name;
					$buf['category'] = $graph[0];
					$buf['title'] = $graph[1];
					$ret[] = $buf;
				}
			}
			break;
		default: // all
			foreach ($this->chart_types as $name=>$graph)
			{
				$buf = array();
				$buf['name'] = $name;
				$buf['category'] = $graph[0];
				$buf['title'] = $graph[1];
				$ret[] = $buf;
			}
			break;
		}

		return $ret;
	}

	function getChart($width=540, $height=420, $bg_color="FFFFFF", $transparent=false, $license=null)
	{

		$ret = "<OBJECT classid=\"clsid:D27CDB6E-AE6D-11cf-96B8-444553540000\" 
					codebase=\"http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,0,0\" 
					WIDTH=\"$width\" HEIGHT=\"$height\" id=\"FC_2_3_Column3D\">";
		$ret .= "<PARAM NAME=movie VALUE=\"/lib/fcharts/".$this->swf_file."\">";
		$ret .= "<PARAM NAME=\"FlashVars\" VALUE=\"&dataURL=".$this->data_path."\">";
		$ret .= "<PARAM NAME=quality VALUE=high>";
		$ret .= "<PARAM NAME=bgcolor VALUE=#$bg_color>";
		$ret .= "<EMBED src=\"/lib/fcharts/".$this->swf_file."\" ";
		$ret .= "		FlashVars=\"&dataURL=".$this->data_path."\" quality=high bgcolor=#$bg_color ";
		$ret .= "		WIDTH=\"$width\" HEIGHT=\"$height\" NAME=\"FC_2_3_Column3D\" ";
		$ret .= "		TYPE=\"application/x-shockwave-flash\" PLUGINSPAGE=\"http://www.macromedia.com/go/getflashplayer\">";
		$ret .= "</EMBED>";
		$ret .= "</OBJECT>test";

		return $ret;
	}


	function getGraphOptions($gname)
	{
		return $this->chart_types[$gname][4];
	}

	// Set all additional options for each chart type (very long)
	function setOptions()
	{
		$cname = "Column3D";
		// Canvas properties
		$this->chart_types[$cname][4][] = array("canvasBgColor", "Canvas background color", "HexColorCode", null);
		$this->chart_types[$cname][4][] = array("canvasBaseColor", "Canvas base color", "HexColorCode", null);
		$this->chart_types[$cname][4][] = array("canvasBgDepth", "Canvas background depth", "number", null);
		$this->chart_types[$cname][4][] = array("canvasBaseDepth", "Canvas base 3D depth", "number", null);
		$this->chart_types[$cname][4][] = array("showCanvasBg", "Show background canvas", "bool", array("true"=>1, "false"=>0));
		$this->chart_types[$cname][4][] = array("showCanvasBase", "Show base canvas", "bool", array("true"=>1, "false"=>0));
	}
}


class CChartData
{
	var $categories;
	var $sets;
	var $attribs;

	function CChartData($xaxisname='', $yaxisname='', $caption='', 
						$subcaption='', $numberPrefix='', $decimalPrecision='')
	{
		$this->categories = array();
		$this->sets = array();
		$this->attribs = array();

		if ($xaxisname)
			$this->attribs['xaxisname'] = $xaxisname;
		if ($yaxisname)
			$this->attribs['yaxisname'] = $yaxisname;
		if ($caption)
			$this->attribs['caption'] = $caption;
		if ($subcaption)
			$this->attribs['subcaption'] = $subcaption;
		if ($numberPrefix)
			$this->attribs['numberPrefix'] = $numberPrefix;
		if ($decimalPrecision)
			$this->attribs['decimalPrecision'] = $decimalPrecision;
	}

	function setAttribute($name, $value)
	{
		$this->attribs[$name] = $value;
	}

	function addCategory($lbl, $hover=null)
	{
		$ind = count($this->categories);
		$this->categories[$ind][0] = $lbl;
		$this->categories[$ind][1] = $hover;
	}
	
	function addSet($name, $color)
	{
		$ind = count($this->sets);
		$this->sets[$ind] = new CChartDataSet();
		$this->sets[$ind]->attribs['name'] = $name;
		$this->sets[$ind]->attribs['color'] = $color;

		return $this->sets[$ind];
	}

	function addEntry($value, $name=null, $color=null)
	{
		if (!count($this->sets))
			$this->sets[0] = new CChartDataSet();

		$this->sets[0]->addEntry($value, $name, $color);
	}

	function getData()
	{
		$ret = "<graph ";

		foreach ($this->attribs as $name=>$val)
		{
			if ($val !== null && $val != '')
				$ret .= "$name=\"".$val."\" ";
		}
		
		if ($this->attribs['decimalPrecision'] === null)
			$ret .= "decimalPrecision='0' ";

		$ret .= ">";
		if (count($this->categories))
		{
			$ret .= "<categories>";
			foreach ($this->categories as $cat)
			{
				$ret .= "<category name=\"".$cat[0]."\" ";
				if ($cat[1])
					$ret .= " hoverText=\"".$cat[1]."\" ";
				$ret .= "/>";
			}
			$ret .= "</categories>";
		}
		$numsets = count($this->sets);
		if ($numsets)
		{
			// Multi-series
			if ($numsets > 1)
			{
				foreach ($this->sets as $set)
				{
					$ret .= "<dataset ";
					if ($set->attribs['name'])
						$ret .= "seriesname=\"".$set->attribs['name']."\" ";
					if ($set->attribs['color'])
						$ret .= "color=\"".$set->attribs['color']."\" ";
					if ($set->attribs['showValues'])
						$ret .= "showValues=\"".$set->attribs['showValues']."\" ";
					$ret .= ">";

					foreach ($set->entries as $ent)
					{
						$ret .= "<set ";
						if ($ent['name'])
							$ret .= "name=\"".$ent['name']."\" ";
						if ($ent['value'])
							$ret .= "value=\"".$ent['value']."\" ";
						$ret .= "/>";

					}

					$ret .= "</dataset>";
				}
			}
			// Single-series
			else
			{
				foreach ($this->sets[0]->entries as $ent)
				{
					$ret .= "<set ";
					if ($ent['name'])
						$ret .= "name=\"".$ent['name']."\" ";
					if ($ent['value'])
						$ret .= "value=\"".$ent['value']."\" ";
					if ($ent['color'])
						$ret .= "color=\"".$ent['color']."\" ";
					$ret .= "/>";

				}
			}
		}

		$ret .= "</graph>";

		return $ret;
	}
}

class CChartDataSet
{
	var $entries;
	var $attribs;

	function CChartDataSet()
	{
		$this->entries = array();
		$this->attribs = array();
	}

	function addEntry($value, $name=null, $color=null)
	{
		$ind = count($this->entries);
		$this->entries[$ind]['value'] = $value;
		$this->entries[$ind]['name'] = $name;
		$this->entries[$ind]['color'] = $color;
	}
}

if ($_GET['test'])
{
	$chart = new CChart("Column3D", "/datacenter/xml_chartdata.awp?gid=6");
	echo $chart->getChart();
}
?>
