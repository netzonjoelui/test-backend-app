<?php
/*======================================================================================
	
	Module:		CChart, CChartData

	Purpose:	Create charts (swf objects) and create Chart Data xml feeds

	Author:		joe, sky.stebnicki@aereus.com
				Copyright (c) 2008 Aereus Corporation. All rights reserved.
	
	Depends:	

	Docs:		http://www.fusioncharts.com/free/Default.asp?gMenuItemId=1

	Usage:		$cdata = new CChartData("xAxisName", "yAxisName", "Sales Figures for 2006", "(hi there)", "$", "0");
				// or
				$cdata = $chart->creatXmlData("xAxisName", "yAxisName", "Caption", "subcaption", "numpre", "0");

				// Single Series
				$cdata->addEntry("25", "Item A", "FF9966");
				$cdata->addEntry("17", array("Item Name", "Hover text"), "FF0000"); // You can use optional array in name for hover/name
				$cdata->addEntry("23", "Item C", "006F00");
				$cdata->addEntry("60", "Item D", "0099FF");
				$cdata->addCategory("Jan", "January");
				$cdata->addCategory("Feb", "February");
				$cdata->addCategory("Mar", "March");

				// Multi-Series
				$set->addCategory("Month 1");
				$set->addCategory("Month 2");
				$set->addCategory("Month 3");
				
				$set = $cdata->addSet("Product A", "FF0000");
				$set->addEntry("8343");
				$set->addEntry("6300");
				$set->addEntry("2900");

				$set = $cdata->addSet("Product B", "FF0000");
				$set->addEntry("9343");
				$set->addEntry("5200");
				$set->addEntry("8000");

				// Gantt
				$chart = new CChart("Gantt");

				$cdata = $chart->creatXmlData();
				$cdata->setProcessAttrib("headerText", "Project");
				$cdata->setProcessAttrib("fontSize", "11");
				$cdata->setProcessAttrib("isBold", "1");
				
				$catset = $cdata->addCategorySet();
				$catset->addCategory("Timeline", null, "02/01/2007 00:00:00", "08/31/2007 23:59:59");

				$catset = $cdata->addCategorySet();
				$catset->addCategory("Feb", null, "02/01/2007 00:00:00", "02/28/2007 23:59:59");
				$catset->addCategory("Mar", null, "03/01/2007 00:00:00", "03/31/2007 23:59:59");
				$catset->addCategory("Apr", null, "04/01/2007 00:00:00", "04/30/2007 23:59:59");
				$catset->addCategory("May", null, "05/01/2007 00:00:00", "05/31/2007 23:59:59");
				$catset->addCategory("Jun", null, "06/01/2007 00:00:00", "06/30/2007 23:59:59");
				$catset->addCategory("Jul", null, "07/01/2007 00:00:00", "07/31/2007 23:59:59");
				$catset->addCategory("Aug", null, "08/01/2007 00:00:00", "08/31/2007 23:59:59");

				$cdata->addProcess("Project 1", 1); // ID has to be unique
				$cdata->addProcess("Project 2", 2); // ID has to be unique
				$cdata->addProcess("Project 3", 3); // ID has to be unique


				$cdata->addTask("02/04/2007 00:00:00", "04/06/2007 00:00:00", 1, "Project 1", "0");
				$cdata->addTask("02/04/2007 00:00:00", "02/10/2007 00:00:00", 2, "Project 2", "0");
				$cdata->addTask("02/08/2007 00:00:00", "02/19/2007 00:00:00", 3, "Project 3", "0");

				$cdata->addTrendline("08/14/2007 00:00:00", null, "Today", "333333", 2);
				$cdata->addTrendline("05/3/2007 00:00:00", "05/10/2007 00:00:00", "Vacation", "FF5904", null, "1", 20);

				echo $chart->getChart(800, 400);

				// Print XML
				echo $cdata->getData();
				

	Variables:	

======================================================================================*/
$G_CCHART_DEFAULT_COLORS  = array(
							array("Poly Green", "9D9C6E"), array("Shallow Bay", "C2B274"), array("Milk Chocolate", "A18E6E"), 
							array("Magdalene", "7C6B64"), array("Adelaide", "5A3A45"),

							array("Butter", "EBD877"), array("Stone", "C4BEAE"), array("Muggy", "ACC1B2"), 
							array("Lake Blue", "79A29A"), array("Gray Green", "AFCD98"),
						  
							array("Soft Green", "B5C666"), array("Mustard", "C2AE19"), array("Rusty", "C6891E")
					  	  );
class CChartData
{
	var $categories;
	var $sets;
	var $attribs;
	var $defaultColorsUsed;
	// Gantt variables
	var $processes;
	var $tasks;
	var $trendlines;
	var $process_attribs;
	var $task_attribs;

	function CChartData($xaxisname='', $yaxisname='', $caption='', 
						$subcaption='', $numberPrefix='', $decimalPrecision='')
	{
		$this->categories = array();
		$this->sets = array();
		$this->attribs = array();
		$this->processes = array();
		$this->tasks = array();
		$this->trendlines = array();
		$this->process_attribs = array();

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

		// Set default gant chart attribs
		$this->process_attribs["fontSize"] = "11";
		$this->process_attribs["isBold"] = "0";
		$this->process_attribs["align"] = "left";
		$this->process_attribs["headerFontSize"] = "16";
		$this->process_attribs["headerVAlign"] = "bottom";
		$this->process_attribs["headerAlign"] = "right";

		$this->task_attribs['showName'] = "1";

		$this->defaultColorsUsed = array();
	}

	function setAttribute($name, $value)
	{
		$this->attribs[$name] = $value;
	}

	function addCategorySet()
	{
		$ind = count($this->categories);
		$this->categories[$ind] = new CChartDataCategorySet();
		return $this->categories[$ind];
	}

	function addCategory($lbl, $hover=null, $unique=false)
	{
		if (!count($this->categories))
		{
			$this->categories[0] = new CChartDataCategorySet();
		}
        
		$this->categories[0]->addCategory($lbl, $hover);

		//$ind = count($this->categories);
		//$this->categories[$ind][0] = $lbl;
		//$this->categories[$ind][1] = $hover;
	}

	function setProcessAttrib($name, $value)
	{
		$this->process_attribs[$name] = $value;
		// fontSize='11' isBold='1' align='left' headerText='What to do?' headerFontSize='16' headerVAlign='bottom' headerAlign='right'
	}

	function addProcess($name, $id)
	{
		$ind = count($this->processes);
		$this->processes[$ind]["name"] = $name;
		$this->processes[$ind]["id"] = $id;
	}

	function setTaskAttrib($name, $value)
	{
		$this->task_attribs[$name] = $value;
		// fontSize='11' isBold='1' align='left' headerText='What to do?' headerFontSize='16' headerVAlign='bottom' headerAlign='right'
	}
	function addTask($start, $end, $id, $name=null, $showname="0", $link='')
	{
		$ind = count($this->tasks);
		$this->tasks[$ind]["start"] = $start;
		$this->tasks[$ind]["end"] = $end;
		$this->tasks[$ind]["pid"] = $id;
		$this->tasks[$ind]["name"] = $name;
		$this->tasks[$ind]["showName"] = $showname;
		$this->tasks[$ind]["link"] = $link;
	}

	/******************************************************************************
	 * Function: addTrendline
	 *
	 * Params:	1. $start = required
	 * 			2. $end = optional
	 * 			3. $displayValue = required display name
	 ******************************************************************************/
	function addTrendline($start, $end, $displayValue, $color=null, $thickness=null, $isTrendZone=null, $alpha=null)
	{
		$ind = count($this->trendlines);
		$this->trendlines[$ind]["start"] = $start;
		$this->trendlines[$ind]["end"] = $end;
		$this->trendlines[$ind]["displayValue"] = $displayValue;
		$this->trendlines[$ind]["color"] = $color;
		$this->trendlines[$ind]["thickness"] = $thickness;
		$this->trendlines[$ind]["isTrendZone"] = $isTrendZone;
		$this->trendlines[$ind]["alpha"] = $alpha;
	}
	
	function addSet($name, $color=null)
	{
		$ind = count($this->sets);
		$this->sets[$ind] = new CChartDataSet();
		$this->sets[$ind]->attribs['name'] = $name;
		if ($color)
			$this->sets[$ind]->attribs['color'] = $color;

		return $this->sets[$ind];
	}

	function addEntry($value, $name=null, $color=null, $alpha=null)
	{
		if (!count($this->sets))
		{
			$this->sets[0] = new CChartDataSet();
		}

		$this->sets[0]->addEntry($value, $name, $color, $alpha);
	}

	// $cseries_type = SS/MS
	function getData($cseries_type="")
	{
		$ret = "<graph dateFormat='mm/dd/yyyy' ";

		foreach ($this->attribs as $name=>$val)
		{
			if ($val !== null && $val != '')
				$ret .= "$name='".$val."' ";
		}
		
		if ($this->attribs['decimalPrecision'] === null)
			$ret .= "decimalPrecision='0' ";

		$ret .= ">";
		for ($i = 0; $i < count($this->categories); $i++)
		{
			$ret .= "<categories>";
			foreach ($this->categories[$i]->categories as $cat)
			{
				$ret .= "<category";
				if ($cat["start"])
					$ret .= " start='".$cat["start"]."'";
				if ($cat["end"])
					$ret .= " end='".$cat["end"]."'";
				if ($cat["name"])
					$ret .= " name='".$cat["name"]."'";
				if ($cat["hover"])
					$ret .= " hoverText='".$cat["hover"]."'";
				$ret .= "/>";
			}
			$ret .= "</categories>";
		}
		$numsets = count($this->sets);
		if ($numsets)
		{
			// Multi-series
			if ($numsets > 1 || $cseries_type=="MS")
			{
				for ($i = 0; $i < $numsets; $i++)
				{
					if (!$this->sets[$i]->attribs['color'])
					{
						$this->sets[$i]->attribs['color'] = $this->getRandColor();
					}

					$set = $this->sets[$i];

					$ret .= "<dataset ";
					if ($set->attribs['name'])
						$ret .= "seriesname='".$set->attribs['name']."' ";
					if ($set->attribs['color'])
						$ret .= "color='".$set->attribs['color']."' ";
					if ($set->attribs['showValues'])
						$ret .= "showValues='".$set->attribs['showValues']."' ";
					$ret .= ">";

					foreach ($set->entries as $ent)
					{
						$ret .= "<set ";
						if ($ent['name'])
							$ret .= "name='".$ent['name']."' ";
						if ($ent['hoverText'])
							$ret .= "hoverText='".$ent['hoverText']."' ";
						$ret .= "value='".$ent['value']."' ";
						$ret .= "/>";

					}

					$ret .= "</dataset>";
				}
			}
			// Single-series
			else
			{
				for ($i = 0; $i < count($this->sets[0]->entries); $i++)
				{
					if (!$this->sets[0]->entries[$i]['color'])
						$this->sets[0]->entries[$i]['color'] = $this->getRandColor();

					$ent = $this->sets[0]->entries[$i];

					$ret .= "<set ";
					if ($ent['name'])
						$ret .= "name='".$ent['name']."' ";
					if ($ent['hoverText'])
						$ret .= "hoverText='".$ent['hoverText']."' ";
					$ret .= "value='".$ent['value']."' ";
					if ($ent['color'])
						$ret .= "color='".$ent['color']."' ";
					if ($ent['alpha'])
						$ret .= "alpha='".$ent['alpha']."' ";
					$ret .= "/>";

				}
			}
		}

		// The below are primarily used for gantt charts (currently)
		$numpro = count($this->processes);
		if ($numpro)
		{
			$ret .= "<processes";
			foreach ($this->process_attribs as $name=>$val)
				$ret .= " $name='$val'";
			$ret .= ">";
			for ($i = 0; $i < count($this->processes); $i++)
			{
				$ent = $this->processes[$i];

				$ret .= "<process ";
				if ($ent['name'])
					$ret .= "name='".$ent['name']."' ";
				if ($ent['id'])
					$ret .= "id='".$ent['id']."' ";
				$ret .= "/>";
			}
			$ret .= "</processes>";
		}

		$numtasks = count($this->tasks);
		if ($numtasks)
		{
			$ret .= "<tasks ";
			foreach ($this->task_attribs as $name=>$val)
				$ret .= " $name='$val'";
			$ret .= ">";
			for ($i = 0; $i < count($this->tasks); $i++)
			{
				$ent = $this->tasks[$i];

				$ret .= "<task ";
				$ret .= "start='".$ent['start']."' ";
				$ret .= "end='".$ent['end']."' ";
				$ret .= "processId='".$ent['pid']."' ";
				$ret .= "animate='1' ";
				if ($ent['name'])
					$ret .= "name='".$ent['name']."' ";
				if (strlen($ent['showName']))
					$ret .= "showName='".$ent['showName']."' ";
				if ($ent['link'])
					$ret .= "link='".$ent['link']."' ";
				$ret .= "/>";
			}
			$ret .= "</tasks>";
		}

		$numtrends = count($this->trendlines);
		if ($numtrends)
		{
			$ret .= "<trendlines>";
			for ($i = 0; $i < count($this->trendlines); $i++)
			{
				if (!$this->trendlines[$i]['color'])
					$this->trendlines[$i]['color'] = $this->getRandColor();

				$ent = $this->trendlines[$i];

				$ret .= "<line ";
				$ret .= "start='".$ent['start']."' ";
				if ($ent['end'])
					$ret .= "end='".$ent['end']."' ";
				if ($ent['displayValue'])
					$ret .= "displayValue='".$ent['displayValue']."' ";
				if ($ent['thickness'])
					$ret .= "thickness='".$ent['thickness']."' ";
				if ($ent['color'])
					$ret .= "color='".$ent['color']."' ";
				if ($ent['isTrendZone'])
					$ret .= "isTrendZone='".$ent['isTrendZone']."' ";
				if ($ent['alpha'])
					$ret .= "alpha='".$ent['alpha']."' ";
				$ret .= "/>";
			}
			$ret .= "</trendlines>";
		}

		$ret .= "</graph>";

		return $ret;
	}

	function getRandColor()
	{
		global $G_CCHART_DEFAULT_COLORS;
		$tmp_arr = array();
		
		for ($i = 0; $i < count($G_CCHART_DEFAULT_COLORS); $i++)
		{
			$clr = $G_CCHART_DEFAULT_COLORS[$i];
			if (!in_array($clr[1], $this->defaultColorsUsed))
				$tmp_arr[] = $clr[1];
		}

		if (count($tmp_arr))
		{
			$use = $tmp_arr[rand(0, count($tmp_arr)-1)];
			$this->defaultColorsUsed[] = $use;
			return $use;
		}
		else
			return "0000FF";
	}
}

class CChartDataCategorySet
{
	var $attribs;
	var $categories;

	function CChartDataCategorySet()
	{
		$this->attribs = array();
		$this->categories= array();
	}

	// Attributes
	// name (required and set in constructor)
	// start (used for gantt)
	// end (used for gantt)
	function addCategory($lbl, $hover=null, $start=null, $end=null)
	{
		$ind = count($this->categories);
		$this->categories[$ind]["name"] = $lbl;
		$this->categories[$ind]["hover"] = $hover;
		$this->categories[$ind]["start"] = $start;
		$this->categories[$ind]["end"] = $end;
	}
}

class CChartDataSet
{
	var $entries;
	var $attribs;
	var $defaultColors;
	var $defaultColorsUsed;
	var $type; // SS, Comb, or MS

	function CChartDataSet()
	{
		$this->entries = array();
		$this->attribs = array();
		$this->defaultColorsUsed = array();
	}

	function addEntry($value, $name=null, $color=null, $alpha='100')
	{
		$ind = count($this->entries);
		$this->entries[$ind]['value'] = $value;
		if (is_array($name))
		{
			$this->entries[$ind]['name'] = $name[0];
			$this->entries[$ind]['hoverText'] = $name[1];
		}
		else
			$this->entries[$ind]['name'] = $name;
		$this->entries[$ind]['alpha'] = $alpha;
		if ($color)
			$this->entries[$ind]['color'] = $color;
	}
}

class CChart
{
	var $swf_file;
	var $data_path;
	var $xml_data;
	var $chart_types;
	var $cseries_type; // SS or MS for single series or multi-series
	var $basePath = "/lib/Aereus/fcharts"; // Make swf path configurable

	function CChart($type=null, $datafile=null)
	{
		$this->data_path = $datafile;
		$this->xml_data = null;

		$this->chart_types = array();
		// Format: category, title, file, series(1 for single, 2 for multi)
		$tcat = "Column";
		$this->chart_types["Column3D"] 		= array($tcat, "3D Column Chart", "FCF_Column3D.swf", 1);
		$this->chart_types["MSColumn3D"] 	= array($tcat, "Multi-series 3D Column Chart", "FCF_MSColumn3D.swf", 2);
		$this->chart_types["Column2D"] 		= array($tcat, "2D Column Chart", "FCF_Column2D.swf", 1);
		$this->chart_types["MSColumn2D"] 	= array($tcat, "Multi-series 2D Column Chart", "FCF_MSColumn2D.swf", 2);
		$this->chart_types["Bar2D"] 		= array($tcat, "2D Bar Chart", "FCF_Bar2D.swf", 1);
		$this->chart_types["MSBar2D"] 		= array($tcat, "Multi-series 2D Bar Chart", "FCF_MSBar2D.swf", 2);
		$tcat = "Line";
		$this->chart_types["Line"] 			= array($tcat, "2D Line Chart", "FCF_Line.swf", 1);
		$this->chart_types["MSLine"] 		= array($tcat, "Multi-series 2D Line Chart", "FCF_MSLine.swf", 2);
		$tcat = "Pie";
		$this->chart_types["Pie3D"] 		= array($tcat, "3D Pie Chart", "FCF_Pie3D.swf", 1);
		$this->chart_types["Pie2D"] 		= array($tcat, "2D Pie Chart", "FCF_Pie2D.swf", 1);
		$this->chart_types["Doughnut2D"] 	= array($tcat, "2D Doughnut Chart", "FCF_Doughnut2D.swf", 1);
		$tcat = "Funnel";
		$this->chart_types["Funnel"] 		= array($tcat, "Funnel Graph", "FCF_Funnel.swf", 1);		// NEW
		$tcat = "Gantt";
		$this->chart_types["Gantt"] 		= array($tcat, "Gantt Chart", "FCF_Gantt.swf", 1);			// NEW
		$tcat = "Area";
		$this->chart_types["Area2D"] 		= array($tcat, "2D Area Chart", "FC_2_3_Area2D.swf", 1);
		$this->chart_types["MSArea2D"] 		= array($tcat, "Multi-series 2D Area Chart", "FCF_MSArea2D.swf", 2);
		$tcat = "Scatter/Bubble";
		$this->chart_types["MSScatter"] 	= array($tcat, "2D Scatter Chart", "FC_2_3_MSScatter.swf", 3);
		$this->chart_types["MSBubble"] 		= array($tcat, "3D/2D Bubble Chart", "FC_2_3_MSBubble.swf", 3);
		$tcat = "Stacked";
		$this->chart_types["StckdColumn3D"]	= array($tcat, "3D Stacked Column", "FCF_StackedColumn3D.swf", 2);
		$this->chart_types["StckdArea"] 	= array($tcat, "2D Stacked Area Chart", "FCF_StackedArea2D.swf", 2);
		$this->chart_types["StckdBar2D"] 	= array($tcat, "2D Stacked Bar", "FCF_StackedBar2D.swf", 2);
		$this->chart_types["StckdColumn2D"]	= array($tcat, "2D Stacked Column", "FCF_StackedColumn2D.swf", 2);
		$tcat = "Candlestick";
		$this->chart_types["Candlestick"]	= array($tcat, "Candle stick chart", "FCF_Candlestick.swf", 3);
		$tcat = "Radar";
		$this->chart_types["Radar"] 		= array($tcat, "Radar Chart", "FC_2_3_Radar.swf", 2);
		$tcat = "Grid";
		$this->chart_types["SSGrid"] 		= array($tcat, "Grid", "FC_2_3_SSGrid.swf", 3);
		$tcat = "Combination";
		$this->chart_types["MSColumnLine_DY_3D"]	= array($tcat, "Multi-series 3D Column + Multi-series Line Dual Y", 
												  			"FC_2_3_MSColumnLine_DY_3D.swf", 3);
		$this->chart_types["MSColumnLine_DY_2D"] 	= array($tcat, "Multi-series 2D Column + Multi-series Line Dual Y", 
															"FCF_MSColumn2DLineDY.swf", 3);
		$tcat = "Stacked Combination";
		$this->chart_types["StackedArea_MSLine_DY_2D"] = array($tcat, "2D Stacked Area + Multi-series Line Dual Y", 
															   "FCF_MSColumn3DLineDY.swf", 3);
		$this->chart_types["StckdColumn_MSLine_DY_2D"] = array($tcat, "2D Stacked Column + Multi-series Line Dual Y", 
			"FC_2_3_StckdColumn_MSLine_DY_2D.swf", 3);	
		/*
		 * Version 2.3
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
		 */

		// Set the chart type
		$this->setChartType($type);

		// Initialize options
		$this->setOptions();
	}

	function setChartType($type)
	{
		if ($type)	
		{
			$this->swf_file = $this->chart_types[$type][2];
			$this->cseries_type = (substr($type, 0, 2) == "MS" || substr($type, 0, 5) == "Stckd") ? "MS" : "SS";
		}
	}

	function creatXmlData($xaxisname='', $yaxisname='', $caption='', 
					 	  $subcaption='', $numberPrefix='', $decimalPrecision='')
	{
		$this->xml_data = new CChartData($xaxisname, $yaxisname, $caption, $subcaption, $numberPrefix, $decimalPrecision);

		return $this->xml_data;
	}

	function setXmlData($cls)
	{
		$this->xml_data = $cls;

		return $this->xml_data;
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

	function getChart($width=400, $height=300, $type=null)
	{
		global $_SERVER;

		// See if chart type has been changed
		if ($type)
			$this->setChartType($type);

		if ($width && !$height)
			$height = $width;

		if ($this->xml_data)
			$datavar = "dataXML=".$this->xml_data->getData($this->cseries_type);
		else
			$datavar = "dataURL=".$this->data_path;

		// Determine if we need to use https or not
		if ($_SERVER['SERVER_PORT'] && $_SERVER['SERVER_PORT'] == "443")
			$codebasepre = "https";
		else
			$codebasepre = "http";

		$ret = "<OBJECT classid=\"clsid:D27CDB6E-AE6D-11cf-96B8-444553540000\" 
					codebase=\"$codebasepre://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,0,0\" 
					WIDTH=\"$width\" HEIGHT=\"$height\" id=\"FC_2_3_Column3D\">";
		$ret .= "<PARAM NAME=movie VALUE=\"".$this->basePath."/".$this->swf_file."\">";
		$ret .= "<PARAM NAME=\"FlashVars\" VALUE=\"&".$datavar."&chartWidth=$width&chartHeight=$height\">";
		$ret .= "<PARAM NAME=quality VALUE=high>";
		//$ret .= "<PARAM NAME=bgcolor VALUE=#$bg_color>";
		$ret .= "<param name=\"wmode\" value=\"transparent\">";
		$ret .= "<EMBED src=\"".$this->basePath."/".$this->swf_file."\" ";
		$ret .= "		FlashVars=\"&".$datavar."&chartWidth=$width";
		$ret .= "&chartHeight=$height\" quality=high wmode=\"transparent\" ";
		//$ret .= "&chartHeight=$height\" quality=high bgcolor=#$bg_color ";
		$ret .= "		WIDTH=\"$width\" HEIGHT=\"$height\" NAME=\"FC_2_3_Column3D\" ";
		$ret .= "		TYPE=\"application/x-shockwave-flash\" PLUGINSPAGE=\"$codebasepre://www.macromedia.com/go/getflashplayer\">";
		$ret .= "</EMBED>";
		$ret .= "</OBJECT>";

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
		// Background
		$this->chart_types[$cname][4][] = array("bgColor", "Chart Background Color", "HexColorCode", null);
		$this->chart_types[$cname][4][] = array("bgAlpha", "Chart Alpha Level", "number", null);
		// Canvas properties
		$this->chart_types[$cname][4][] = array("canvasBgColor", "Canvas background color", "HexColorCode", null);
		$this->chart_types[$cname][4][] = array("canvasBaseColor", "Canvas base color", "HexColorCode", null);
		$this->chart_types[$cname][4][] = array("canvasBgDepth", "Canvas background depth", "number", null);
		$this->chart_types[$cname][4][] = array("canvasBaseDepth", "Canvas base 3D depth", "number", null);
		$this->chart_types[$cname][4][] = array("showCanvasBg", "Show background canvas", "bool", array("true"=>1, "false"=>0));
		$this->chart_types[$cname][4][] = array("showCanvasBase", "Show base canvas", "bool", array("true"=>1, "false"=>0));
		// Limits
		$this->chart_types[$cname][4][] = array("yAxisMinValue", "Lower Limit of y-axis", "number", null);
		$this->chart_types[$cname][4][] = array("yAxisMaxValue", "Upper Limit of y-axis", "number", null);
		// Generic
		$this->chart_types[$cname][4][] = array("shownames", "Show x-axis names", "bool", array("true"=>1, "false"=>0));
		$this->chart_types[$cname][4][] = array("showValues", "Show y-axis values", "bool", array("true"=>1, "false"=>0));
		$this->chart_types[$cname][4][] = array("showLimits", "Display limits", "bool", array("true"=>1, "false"=>0));
		$this->chart_types[$cname][4][] = array("rotateNames", "Rotate category names", "bool", array("true"=>1, "false"=>0));
		$this->chart_types[$cname][4][] = array("animation", "Animate chart", "bool", array("true"=>1, "false"=>0));
		$this->chart_types[$cname][4][] = array("chartLeftMargin", "Chart Left Margin", "number", null);
		$this->chart_types[$cname][4][] = array("chartRightMargin", "Chart Right Margin", "number", null);
		$this->chart_types[$cname][4][] = array("chartTopMargin", "Chart Top Margin", "number", null);
		$this->chart_types[$cname][4][] = array("chartBottomMargin", "Chart Bottom Margin", "number", null);
		// Fonts
		$this->chart_types[$cname][4][] = array("baseFont", "Canvas font", "text", null);
		$this->chart_types[$cname][4][] = array("baseFontSize", "Canvas font size", "text", null);
		$this->chart_types[$cname][4][] = array("baseFontColor", "Canvas font color", "HexColorCode", null);
		$this->chart_types[$cname][4][] = array("outCnvBaseFont", "Outside canvas font", "text", null);
		$this->chart_types[$cname][4][] = array("outCnvBaseFontSze", "Outside canvas font size", "text", null);
		$this->chart_types[$cname][4][] = array("outCnvBaseFontColor", "Outside canvas font color", "HexColorCode", null);
		// Number formatting
		$this->chart_types[$cname][4][] = array("numberPrefix", "Number prefix", "text", null);
		$this->chart_types[$cname][4][] = array("numberSuffix", "Number suffix", "text", null);
		$this->chart_types[$cname][4][] = array("formatNumber", "Format numbers", "bool", array("true"=>1, "false"=>0));
		$this->chart_types[$cname][4][] = array("formatNumberScale", "Scale numbers (1k, 2m)", "bool", array("true"=>1, "false"=>0));
		// Zero Plane
		$this->chart_types[$cname][4][] = array("zeroPlaneShowBorder", "Show zero-plane norder", "bool", array("true"=>1, "false"=>0));
		$this->chart_types[$cname][4][] = array("zeroPlaneBorderColor", "Zero-Plane border color", "HexColorCode", null);
		$this->chart_types[$cname][4][] = array("zeroPlaneColor", "Zero-Plane color", "HexColorCode", null);
		$this->chart_types[$cname][4][] = array("zeroPlaneAlpha", "Zero-Plane alpha", "number", null);
		
		$cname = "Column2D";
		// Background
		$this->chart_types[$cname][4][] = array("bgColor", "Chart Background Color", "HexColorCode", null);
		$this->chart_types[$cname][4][] = array("bgAlpha", "Chart Alpha Level", "number", null);
		// Canvas properties
		$this->chart_types[$cname][4][] = array("canvasBgColor", "Canvas background color", "HexColorCode", null);
		$this->chart_types[$cname][4][] = array("canvasBgAlpha", "Canvas background alpha", "number", null);
		$this->chart_types[$cname][4][] = array("canvasBorderColor", "Canvas border color", "HexColorCode", null);
		$this->chart_types[$cname][4][] = array("canvasBorderThickness", "Canvas border thickness", "number", null);
		// Limits
		$this->chart_types[$cname][4][] = array("yAxisMinValue", "Lower Limit of y-axis", "number", null);
		$this->chart_types[$cname][4][] = array("yAxisMaxValue", "Upper Limit of y-axis", "number", null);
		// Generic
		$this->chart_types[$cname][4][] = array("shownames", "Show x-axis names", "bool", array("true"=>1, "false"=>0));
		$this->chart_types[$cname][4][] = array("showValues", "Show y-axis values", "bool", array("true"=>1, "false"=>0));
		$this->chart_types[$cname][4][] = array("showLimits", "Display limits", "bool", array("true"=>1, "false"=>0));
		$this->chart_types[$cname][4][] = array("rotateNames", "Rotate category names", "bool", array("true"=>1, "false"=>0));
		$this->chart_types[$cname][4][] = array("animation", "Animate chart", "bool", array("true"=>1, "false"=>0));
		$this->chart_types[$cname][4][] = array("showColumnShadow", "Show Column Shadow", "bool", array("true"=>1, "false"=>0));
		$this->chart_types[$cname][4][] = array("chartLeftMargin", "Chart Left Margin", "number", null);
		$this->chart_types[$cname][4][] = array("chartRightMargin", "Chart Right Margin", "number", null);
		$this->chart_types[$cname][4][] = array("chartTopMargin", "Chart Top Margin", "number", null);
		$this->chart_types[$cname][4][] = array("chartBottomMargin", "Chart Bottom Margin", "number", null);
		// Fonts
		$this->chart_types[$cname][4][] = array("baseFont", "Canvas font", "text", null);
		$this->chart_types[$cname][4][] = array("baseFontSize", "Canvas font size", "text", null);
		$this->chart_types[$cname][4][] = array("baseFontColor", "Canvas font color", "HexColorCode", null);
		$this->chart_types[$cname][4][] = array("outCnvBaseFont", "Outside canvas font", "text", null);
		$this->chart_types[$cname][4][] = array("outCnvBaseFontSze", "Outside canvas font size", "text", null);
		$this->chart_types[$cname][4][] = array("outCnvBaseFontColor", "Outside canvas font color", "HexColorCode", null);
		// Number formatting
		$this->chart_types[$cname][4][] = array("numberPrefix", "Number prefix", "text", null);
		$this->chart_types[$cname][4][] = array("numberSuffix", "Number suffix", "text", null);
		$this->chart_types[$cname][4][] = array("formatNumber", "Format numbers", "bool", array("true"=>1, "false"=>0));
		$this->chart_types[$cname][4][] = array("formatNumberScale", "Scale numbers (1k, 2m)", "bool", array("true"=>1, "false"=>0));
		// Zero Plane
		$this->chart_types[$cname][4][] = array("zeroPlaneThickness", "Zero-plane thickness", "number", null);
		$this->chart_types[$cname][4][] = array("zeroPlaneColor", "Zero-Plane color", "HexColorCode", null);
		$this->chart_types[$cname][4][] = array("zeroPlaneAlpha", "Zero-Plane alpha", "number", null);
		
		$cname = "Bar2D";
		// Background
		$this->chart_types[$cname][4][] = array("bgColor", "Chart Background Color", "HexColorCode", null);
		$this->chart_types[$cname][4][] = array("bgAlpha", "Chart Alpha Level", "number", null);
		// Canvas properties
		$this->chart_types[$cname][4][] = array("canvasBgColor", "Canvas background color", "HexColorCode", null);
		$this->chart_types[$cname][4][] = array("canvasBgAlpha", "Canvas background alpha", "number", null);
		$this->chart_types[$cname][4][] = array("canvasBorderColor", "Canvas border color", "HexColorCode", null);
		$this->chart_types[$cname][4][] = array("canvasBorderThickness", "Canvas border thickness", "number", null);
		// Limits
		$this->chart_types[$cname][4][] = array("yAxisMinValue", "Lower Limit of y-axis", "number", null);
		$this->chart_types[$cname][4][] = array("yAxisMaxValue", "Upper Limit of y-axis", "number", null);
		// Generic
		$this->chart_types[$cname][4][] = array("shownames", "Show x-axis names", "bool", array("true"=>1, "false"=>0));
		$this->chart_types[$cname][4][] = array("showValues", "Show y-axis values", "bool", array("true"=>1, "false"=>0));
		$this->chart_types[$cname][4][] = array("showLimits", "Display limits", "bool", array("true"=>1, "false"=>0));
		$this->chart_types[$cname][4][] = array("rotateNames", "Rotate category names", "bool", array("true"=>1, "false"=>0));
		$this->chart_types[$cname][4][] = array("animation", "Animate chart", "bool", array("true"=>1, "false"=>0));
		$this->chart_types[$cname][4][] = array("showColumnShadow", "Show Column Shadow", "bool", array("true"=>1, "false"=>0));
		$this->chart_types[$cname][4][] = array("chartLeftMargin", "Chart Left Margin", "number", null);
		$this->chart_types[$cname][4][] = array("chartRightMargin", "Chart Right Margin", "number", null);
		$this->chart_types[$cname][4][] = array("chartTopMargin", "Chart Top Margin", "number", null);
		$this->chart_types[$cname][4][] = array("chartBottomMargin", "Chart Bottom Margin", "number", null);
		// Fonts
		$this->chart_types[$cname][4][] = array("baseFont", "Canvas font", "text", null);
		$this->chart_types[$cname][4][] = array("baseFontSize", "Canvas font size", "text", null);
		$this->chart_types[$cname][4][] = array("baseFontColor", "Canvas font color", "HexColorCode", null);
		$this->chart_types[$cname][4][] = array("outCnvBaseFont", "Outside canvas font", "text", null);
		$this->chart_types[$cname][4][] = array("outCnvBaseFontSze", "Outside canvas font size", "text", null);
		$this->chart_types[$cname][4][] = array("outCnvBaseFontColor", "Outside canvas font color", "HexColorCode", null);
		// Number formatting
		$this->chart_types[$cname][4][] = array("numberPrefix", "Number prefix", "text", null);
		$this->chart_types[$cname][4][] = array("numberSuffix", "Number suffix", "text", null);
		$this->chart_types[$cname][4][] = array("formatNumber", "Format numbers", "bool", array("true"=>1, "false"=>0));
		$this->chart_types[$cname][4][] = array("formatNumberScale", "Scale numbers (1k, 2m)", "bool", array("true"=>1, "false"=>0));
		// Zero Plane
		$this->chart_types[$cname][4][] = array("zeroPlaneThickness", "Zero-plane thickness", "number", null);
		$this->chart_types[$cname][4][] = array("zeroPlaneColor", "Zero-Plane color", "HexColorCode", null);
		$this->chart_types[$cname][4][] = array("zeroPlaneAlpha", "Zero-Plane alpha", "number", null);

		$cname = "Line";
		// Background
		$this->chart_types[$cname][4][] = array("bgColor", "Chart Background Color", "HexColorCode", null);
		$this->chart_types[$cname][4][] = array("bgAlpha", "Chart Alpha Level", "number", null);
		// Canvas properties
		$this->chart_types[$cname][4][] = array("canvasBgColor", "Canvas background color", "HexColorCode", null);
		$this->chart_types[$cname][4][] = array("canvasBgAlpha", "Canvas background alpha", "number", null);
		$this->chart_types[$cname][4][] = array("canvasBorderColor", "Canvas border color", "HexColorCode", null);
		$this->chart_types[$cname][4][] = array("canvasBorderThickness", "Canvas border thickness", "number", null);
		// Limits
		$this->chart_types[$cname][4][] = array("yAxisMinValue", "Lower Limit of y-axis", "number", null);
		$this->chart_types[$cname][4][] = array("yAxisMaxValue", "Upper Limit of y-axis", "number", null);
		// Generic
		$this->chart_types[$cname][4][] = array("shownames", "Show x-axis names", "bool", array("true"=>1, "false"=>0));
		$this->chart_types[$cname][4][] = array("showValues", "Show y-axis values", "bool", array("true"=>1, "false"=>0));
		$this->chart_types[$cname][4][] = array("showLimits", "Display limits", "bool", array("true"=>1, "false"=>0));
		$this->chart_types[$cname][4][] = array("rotateNames", "Rotate category names", "bool", array("true"=>1, "false"=>0));
		$this->chart_types[$cname][4][] = array("animation", "Animate chart", "bool", array("true"=>1, "false"=>0));
		$this->chart_types[$cname][4][] = array("chartLeftMargin", "Chart Left Margin", "number", null);
		$this->chart_types[$cname][4][] = array("chartRightMargin", "Chart Right Margin", "number", null);
		$this->chart_types[$cname][4][] = array("chartTopMargin", "Chart Top Margin", "number", null);
		$this->chart_types[$cname][4][] = array("chartBottomMargin", "Chart Bottom Margin", "number", null);
		// Line Properties
		$this->chart_types[$cname][4][] = array("lineColor", "Line color", "HexColorCode", null);
		$this->chart_types[$cname][4][] = array("lineThickness", "Line thickness", "number", null);
		$this->chart_types[$cname][4][] = array("lineAlpha", "Line alpha", "number", null);
		// Line Shadow Properties
		$this->chart_types[$cname][4][] = array("showShadow", "Show line shadow", "bool", array("true"=>1, "false"=>0));
		$this->chart_types[$cname][4][] = array("shadowColor", "Line shadow color", "HexColorCode", null);
		$this->chart_types[$cname][4][] = array("shadowThickness", "Line shadow thickness", "number", null);
		$this->chart_types[$cname][4][] = array("shadowAlpha", "Line shadow alpha", "number", null);
		// Anchor properties	
		$this->chart_types[$cname][4][] = array("showAnchors", "Show anchors", "bool", array("true"=>1, "false"=>0));
		$this->chart_types[$cname][4][] = array("anchorSides", "Anchor sides", "number", array("three"=>3, "four"=>4));
		$this->chart_types[$cname][4][] = array("anchorBorderColor", "Anchor border color", "HexColorCode", null);
		$this->chart_types[$cname][4][] = array("anchorBgColor", "Anchor background color", "HexColorCode", null);
		$this->chart_types[$cname][4][] = array("anchorBgAlpha", "Anchor background alpha", "number", null);
		$this->chart_types[$cname][4][] = array("anchorAlpha", "Anchor alpha", "number", null);
		// Fonts
		$this->chart_types[$cname][4][] = array("baseFont", "Canvas font", "text", null);
		$this->chart_types[$cname][4][] = array("baseFontSize", "Canvas font size", "text", null);
		$this->chart_types[$cname][4][] = array("baseFontColor", "Canvas font color", "HexColorCode", null);
		$this->chart_types[$cname][4][] = array("outCnvBaseFont", "Outside canvas font", "text", null);
		$this->chart_types[$cname][4][] = array("outCnvBaseFontSze", "Outside canvas font size", "text", null);
		$this->chart_types[$cname][4][] = array("outCnvBaseFontColor", "Outside canvas font color", "HexColorCode", null);
		// Number formatting
		$this->chart_types[$cname][4][] = array("numberPrefix", "Number prefix", "text", null);
		$this->chart_types[$cname][4][] = array("numberSuffix", "Number suffix", "text", null);
		$this->chart_types[$cname][4][] = array("formatNumber", "Format numbers", "bool", array("true"=>1, "false"=>0));
		$this->chart_types[$cname][4][] = array("formatNumberScale", "Scale numbers (1k, 2m)", "bool", array("true"=>1, "false"=>0));
		// Zero Plane
		$this->chart_types[$cname][4][] = array("zeroPlaneThickness", "Zero-plane thickness", "number", null);
		$this->chart_types[$cname][4][] = array("zeroPlaneColor", "Zero-Plane color", "HexColorCode", null);
		$this->chart_types[$cname][4][] = array("zeroPlaneAlpha", "Zero-Plane alpha", "number", null);

		$cname = "Area2D";
		// Background
		$this->chart_types[$cname][4][] = array("bgColor", "Chart Background Color", "HexColorCode", null);
		$this->chart_types[$cname][4][] = array("bgAlpha", "Chart Alpha Level", "number", null);
		// Canvas properties
		$this->chart_types[$cname][4][] = array("canvasBgColor", "Canvas background color", "HexColorCode", null);
		$this->chart_types[$cname][4][] = array("canvasBgAlpha", "Canvas background alpha", "number", null);
		$this->chart_types[$cname][4][] = array("canvasBorderColor", "Canvas border color", "HexColorCode", null);
		$this->chart_types[$cname][4][] = array("canvasBorderThickness", "Canvas border thickness", "number", null);
		// Limits
		$this->chart_types[$cname][4][] = array("yAxisMinValue", "Lower Limit of y-axis", "number", null);
		$this->chart_types[$cname][4][] = array("yAxisMaxValue", "Upper Limit of y-axis", "number", null);
		// Generic
		$this->chart_types[$cname][4][] = array("shownames", "Show x-axis names", "bool", array("true"=>1, "false"=>0));
		$this->chart_types[$cname][4][] = array("showValues", "Show y-axis values", "bool", array("true"=>1, "false"=>0));
		$this->chart_types[$cname][4][] = array("showLimits", "Display limits", "bool", array("true"=>1, "false"=>0));
		$this->chart_types[$cname][4][] = array("rotateNames", "Rotate category names", "bool", array("true"=>1, "false"=>0));
		$this->chart_types[$cname][4][] = array("animation", "Animate chart", "bool", array("true"=>1, "false"=>0));
		$this->chart_types[$cname][4][] = array("showColumnShadow", "Show Column Shadow", "bool", array("true"=>1, "false"=>0));
		$this->chart_types[$cname][4][] = array("chartLeftMargin", "Chart Left Margin", "number", null);
		$this->chart_types[$cname][4][] = array("chartRightMargin", "Chart Right Margin", "number", null);
		$this->chart_types[$cname][4][] = array("chartTopMargin", "Chart Top Margin", "number", null);
		$this->chart_types[$cname][4][] = array("chartBottomMargin", "Chart Bottom Margin", "number", null);
		// Area
		$this->chart_types[$cname][4][] = array("showAreaBorder", "Show area border", "bool", array("true"=>1, "false"=>0));
		$this->chart_types[$cname][4][] = array("areaBorderThickness", "Area border thickness", "number", null);
		$this->chart_types[$cname][4][] = array("areaBorderColor", "Area border color", "HexColorCode", null);
		$this->chart_types[$cname][4][] = array("areaBgColor", "Area background color", "HexColorCode", null);
		$this->chart_types[$cname][4][] = array("areaAlpha", "Area alpha", "number", null);
		// Fonts
		$this->chart_types[$cname][4][] = array("baseFont", "Canvas font", "text", null);
		$this->chart_types[$cname][4][] = array("baseFontSize", "Canvas font size", "text", null);
		$this->chart_types[$cname][4][] = array("baseFontColor", "Canvas font color", "HexColorCode", null);
		$this->chart_types[$cname][4][] = array("outCnvBaseFont", "Outside canvas font", "text", null);
		$this->chart_types[$cname][4][] = array("outCnvBaseFontSze", "Outside canvas font size", "text", null);
		$this->chart_types[$cname][4][] = array("outCnvBaseFontColor", "Outside canvas font color", "HexColorCode", null);
		// Number formatting
		$this->chart_types[$cname][4][] = array("numberPrefix", "Number prefix", "text", null);
		$this->chart_types[$cname][4][] = array("numberSuffix", "Number suffix", "text", null);
		$this->chart_types[$cname][4][] = array("formatNumber", "Format numbers", "bool", array("true"=>1, "false"=>0));
		$this->chart_types[$cname][4][] = array("formatNumberScale", "Scale numbers (1k, 2m)", "bool", array("true"=>1, "false"=>0));
		// Zero Plane
		$this->chart_types[$cname][4][] = array("zeroPlaneThickness", "Zero-plane thickness", "number", null);
		$this->chart_types[$cname][4][] = array("zeroPlaneColor", "Zero-Plane color", "HexColorCode", null);
		$this->chart_types[$cname][4][] = array("zeroPlaneAlpha", "Zero-Plane alpha", "number", null);

		$cname = "Pie2D";
		// Background
		$this->chart_types[$cname][4][] = array("bgColor", "Chart Background Color", "HexColorCode", null);
		$this->chart_types[$cname][4][] = array("bgAlpha", "Chart Alpha Level", "number", null);
		// Generic
		$this->chart_types[$cname][4][] = array("shownames", "Show names", "bool", array("true"=>1, "false"=>0));
		$this->chart_types[$cname][4][] = array("showValues", "Show values", "bool", array("true"=>1, "false"=>0));
		$this->chart_types[$cname][4][] = array("showPercentageValues", "Show percentage values", "bool", array("true"=>1, "false"=>0));
		$this->chart_types[$cname][4][] = array("showPercentageInLabel", "Show percentage in label", "bool", array("true"=>1, "false"=>0));
		$this->chart_types[$cname][4][] = array("animation", "Animate chart", "bool", array("true"=>1, "false"=>0));
		$this->chart_types[$cname][4][] = array("chartLeftMargin", "Chart Left Margin", "number", null);
		$this->chart_types[$cname][4][] = array("chartRightMargin", "Chart Right Margin", "number", null);
		$this->chart_types[$cname][4][] = array("chartTopMargin", "Chart Top Margin", "number", null);
		$this->chart_types[$cname][4][] = array("chartBottomMargin", "Chart Bottom Margin", "number", null);
		// Pie
		$this->chart_types[$cname][4][] = array("pieBorderThickness", "Pie Border Thickness", "number", null);
		$this->chart_types[$cname][4][] = array("pieBorderAlpha", "Pie Border Alpha", "number", null);
		$this->chart_types[$cname][4][] = array("pieFillAlpha", "Pie Fill Alpha", "number", null);
		// Slicing
		$this->chart_types[$cname][4][] = array("slicingDistance", "Distance of slice", "number", null);
		$this->chart_types[$cname][4][] = array("nameTBDistance", "Distance of label", "number", null);
		// Pie Shadow
		$this->chart_types[$cname][4][] = array("showShadow", "Show pie shadow", "bool", array("true"=>1, "false"=>0));
		$this->chart_types[$cname][4][] = array("shadowColor", "Pie shadow color", "HexColorCode", null);
		$this->chart_types[$cname][4][] = array("shadowAlpha", "Pie shadow alpha", "number", null);
		$this->chart_types[$cname][4][] = array("shadowXShift", "Shadow x-shift", "number", null);
		$this->chart_types[$cname][4][] = array("shadowYShift", "Shadow y-shift", "number", null);
		// Fontsk
		$this->chart_types[$cname][4][] = array("baseFont", "Canvas font", "text", null);
		$this->chart_types[$cname][4][] = array("baseFontSize", "Canvas font size", "text", null);
		$this->chart_types[$cname][4][] = array("baseFontColor", "Canvas font color", "HexColorCode", null);
		$this->chart_types[$cname][4][] = array("outCnvBaseFont", "Outside canvas font", "text", null);
		$this->chart_types[$cname][4][] = array("outCnvBaseFontSze", "Outside canvas font size", "text", null);
		$this->chart_types[$cname][4][] = array("outCnvBaseFontColor", "Outside canvas font color", "HexColorCode", null);
		// Number formatting
		$this->chart_types[$cname][4][] = array("numberPrefix", "Number prefix", "text", null);
		$this->chart_types[$cname][4][] = array("numberSuffix", "Number suffix", "text", null);
		$this->chart_types[$cname][4][] = array("formatNumber", "Format numbers", "bool", array("true"=>1, "false"=>0));
		$this->chart_types[$cname][4][] = array("formatNumberScale", "Scale numbers (1k, 2m)", "bool", array("true"=>1, "false"=>0));

		$cname = "Pie3D";
		// Background
		$this->chart_types[$cname][4][] = array("bgColor", "Chart Background Color", "HexColorCode", null);
		$this->chart_types[$cname][4][] = array("bgAlpha", "Chart Alpha Level", "number", null);
		// Generic
		$this->chart_types[$cname][4][] = array("shownames", "Show names", "bool", array("true"=>1, "false"=>0));
		$this->chart_types[$cname][4][] = array("showValues", "Show values", "bool", array("true"=>1, "false"=>0));
		$this->chart_types[$cname][4][] = array("showPercentageValues", "Show percentage values", "bool", array("true"=>1, "false"=>0));
		$this->chart_types[$cname][4][] = array("showPercentageInLabel", "Show percentage in label", "bool", array("true"=>1, "false"=>0));
		$this->chart_types[$cname][4][] = array("animation", "Animate chart", "bool", array("true"=>1, "false"=>0));
		$this->chart_types[$cname][4][] = array("chartLeftMargin", "Chart Left Margin", "number", null);
		$this->chart_types[$cname][4][] = array("chartRightMargin", "Chart Right Margin", "number", null);
		$this->chart_types[$cname][4][] = array("chartTopMargin", "Chart Top Margin", "number", null);
		$this->chart_types[$cname][4][] = array("chartBottomMargin", "Chart Bottom Margin", "number", null);
		// Pie
		$this->chart_types[$cname][4][] = array("pieRadius", "Radius of pie", "number", null);
		$this->chart_types[$cname][4][] = array("pieBorderThickness", "Pie Border Thickness", "number", null);
		$this->chart_types[$cname][4][] = array("pieBorderThickness", "Pie Border Thickness", "number", null);
		$this->chart_types[$cname][4][] = array("pieBorderAlpha", "Pie Border Alpha", "number", null);
		$this->chart_types[$cname][4][] = array("pieFillAlpha", "Pie Fill Alpha", "number", null);
		// Fontsk
		$this->chart_types[$cname][4][] = array("pieRadius", "Radius of pie", "number", null);
		$this->chart_types[$cname][4][] = array("baseFontSize", "Canvas font size", "text", null);
		$this->chart_types[$cname][4][] = array("baseFontColor", "Canvas font color", "HexColorCode", null);
		$this->chart_types[$cname][4][] = array("outCnvBaseFont", "Outside canvas font", "text", null);
		$this->chart_types[$cname][4][] = array("outCnvBaseFontSze", "Outside canvas font size", "text", null);
		$this->chart_types[$cname][4][] = array("outCnvBaseFontColor", "Outside canvas font color", "HexColorCode", null);
		// Number formatting
		$this->chart_types[$cname][4][] = array("numberPrefix", "Number prefix", "text", null);
		$this->chart_types[$cname][4][] = array("numberSuffix", "Number suffix", "text", null);
		$this->chart_types[$cname][4][] = array("formatNumber", "Format numbers", "bool", array("true"=>1, "false"=>0));
		$this->chart_types[$cname][4][] = array("formatNumberScale", "Scale numbers (1k, 2m)", "bool", array("true"=>1, "false"=>0));

		$cname = "Doughnut2D";
		// Background
		$this->chart_types[$cname][4][] = array("bgColor", "Chart Background Color", "HexColorCode", null);
		$this->chart_types[$cname][4][] = array("bgAlpha", "Chart Alpha Level", "number", null);
		// Generic
		$this->chart_types[$cname][4][] = array("shownames", "Show names", "bool", array("true"=>1, "false"=>0));
		$this->chart_types[$cname][4][] = array("showValues", "Show values", "bool", array("true"=>1, "false"=>0));
		$this->chart_types[$cname][4][] = array("showPercentageValues", "Show percentage values", "bool", array("true"=>1, "false"=>0));
		$this->chart_types[$cname][4][] = array("showPercentageInLabel", "Show percentage in label", "bool", array("true"=>1, "false"=>0));
		$this->chart_types[$cname][4][] = array("animation", "Animate chart", "bool", array("true"=>1, "false"=>0));
		$this->chart_types[$cname][4][] = array("chartLeftMargin", "Chart Left Margin", "number", null);
		$this->chart_types[$cname][4][] = array("chartRightMargin", "Chart Right Margin", "number", null);
		$this->chart_types[$cname][4][] = array("chartTopMargin", "Chart Top Margin", "number", null);
		$this->chart_types[$cname][4][] = array("chartBottomMargin", "Chart Bottom Margin", "number", null);
		// Pie
		$this->chart_types[$cname][4][] = array("pieBorderThickness", "Pie Border Thickness", "number", null);
		$this->chart_types[$cname][4][] = array("pieBorderAlpha", "Pie Border Alpha", "number", null);
		$this->chart_types[$cname][4][] = array("pieFillAlpha", "Pie Fill Alpha", "number", null);
		// Slicing
		$this->chart_types[$cname][4][] = array("slicingDistance", "Distance of slice", "number", null);
		$this->chart_types[$cname][4][] = array("nameTBDistance", "Distance of label", "number", null);
		// Pie Shadow
		$this->chart_types[$cname][4][] = array("showShadow", "Show pie shadow", "bool", array("true"=>1, "false"=>0));
		$this->chart_types[$cname][4][] = array("shadowColor", "Pie shadow color", "HexColorCode", null);
		$this->chart_types[$cname][4][] = array("shadowAlpha", "Pie shadow alpha", "number", null);
		$this->chart_types[$cname][4][] = array("shadowXShift", "Shadow x-shift", "number", null);
		$this->chart_types[$cname][4][] = array("shadowYShift", "Shadow y-shift", "number", null);
		// Fontsk
		$this->chart_types[$cname][4][] = array("baseFont", "Canvas font", "text", null);
		$this->chart_types[$cname][4][] = array("baseFontSize", "Canvas font size", "text", null);
		$this->chart_types[$cname][4][] = array("baseFontColor", "Canvas font color", "HexColorCode", null);
		$this->chart_types[$cname][4][] = array("outCnvBaseFont", "Outside canvas font", "text", null);
		$this->chart_types[$cname][4][] = array("outCnvBaseFontSze", "Outside canvas font size", "text", null);
		$this->chart_types[$cname][4][] = array("outCnvBaseFontColor", "Outside canvas font color", "HexColorCode", null);
		// Number formatting
		$this->chart_types[$cname][4][] = array("numberPrefix", "Number prefix", "text", null);
		$this->chart_types[$cname][4][] = array("numberSuffix", "Number suffix", "text", null);
		$this->chart_types[$cname][4][] = array("formatNumber", "Format numbers", "bool", array("true"=>1, "false"=>0));
		$this->chart_types[$cname][4][] = array("formatNumberScale", "Scale numbers (1k, 2m)", "bool", array("true"=>1, "false"=>0));
		// Zero Plane
		$this->chart_types[$cname][4][] = array("zeroPlaneThickness", "Zero-plane thickness", "number", null);
		$this->chart_types[$cname][4][] = array("zeroPlaneColor", "Zero-Plane color", "HexColorCode", null);
		$this->chart_types[$cname][4][] = array("zeroPlaneAlpha", "Zero-Plane alpha", "number", null);

		$cname = "Funnel";
		// Background
		$this->chart_types[$cname][4][] = array("bgColor", "Chart Background Color", "HexColorCode", null);
		$this->chart_types[$cname][4][] = array("bgAlpha", "Chart Alpha Level", "number", null);
		$this->chart_types[$cname][4][] = array("bgSWF", "Path to an swf file", "text", null);
		// Generic
		$this->chart_types[$cname][4][] = array("shownames", "Show names", "bool", array("true"=>1, "false"=>0));
		$this->chart_types[$cname][4][] = array("showValues", "Show values", "bool", array("true"=>1, "false"=>0));
		$this->chart_types[$cname][4][] = array("animation", "Animate chart", "bool", array("true"=>1, "false"=>0));
		// Funnel Properties
		$this->chart_types[$cname][4][] = array("fillAlpha", "Alpha level of fill", "number", null);
		$this->chart_types[$cname][4][] = array("funnelBaseWidth", "Base Width", "number", null);
		$this->chart_types[$cname][4][] = array("funnelBaseHeight", "Base Height", "number", null);
		// Slicing
		$this->chart_types[$cname][4][] = array("isSliced", "Slice the funnel", "bool", null);
		$this->chart_types[$cname][4][] = array("slicingDistance", "Distance of the slice in px", "number", null);
		// Fonts
		$this->chart_types[$cname][4][] = array("baseFont", "Canvas font", "text", null);
		$this->chart_types[$cname][4][] = array("baseFontSize", "Canvas font size", "text", null);
		$this->chart_types[$cname][4][] = array("baseFontColor", "Canvas font color", "HexColorCode", null);
		$this->chart_types[$cname][4][] = array("outCnvBaseFont", "Outside canvas font", "text", null);
		$this->chart_types[$cname][4][] = array("outCnvBaseFontSze", "Outside canvas font size", "text", null);
		$this->chart_types[$cname][4][] = array("outCnvBaseFontColor", "Outside canvas font color", "HexColorCode", null);
		// Number formatting
		$this->chart_types[$cname][4][] = array("numberPrefix", "Number prefix", "text", null);
		$this->chart_types[$cname][4][] = array("numberSuffix", "Number suffix", "text", null);
		$this->chart_types[$cname][4][] = array("formatNumber", "Format numbers", "bool", array("true"=>1, "false"=>0));
		$this->chart_types[$cname][4][] = array("formatNumberScale", "Scale numbers (1k, 2m)", "bool", array("true"=>1, "false"=>0));
		// Chart Margins
		$this->chart_types[$cname][4][] = array("chartLeftMargin", "Left Margin", "number", null);
		$this->chart_types[$cname][4][] = array("chartRightMargin", "Right Margin", "number", null);
		$this->chart_types[$cname][4][] = array("chartTopMargin", "Top Margin", "number", null);
		$this->chart_types[$cname][4][] = array("chartBottomMargin", "Bottom Margin", "number", null);
		// Borders
		$this->chart_types[$cname][4][] = array("showBorder", "Show Border", "bool", null);
		$this->chart_types[$cname][4][] = array("borderColor", "Border Color", "HexColorCode", null);
		$this->chart_types[$cname][4][] = array("borderThickness", "Thickness of Border (in px)", "number", null);
		$this->chart_types[$cname][4][] = array("borderAlpha", "Border Alpha", "number", null);
		// Hover Options
		$this->chart_types[$cname][4][] = array("showhovercap", "Show Hover Captions", "bool", null);
		$this->chart_types[$cname][4][] = array("hoverCapBgColor", "Hover Caption Bg Color", "HexColorCode", null);
		$this->chart_types[$cname][4][] = array("hoverCapBorderColor", "Hover Caption Border Color", "HexColorCode", null);
		$this->chart_types[$cname][4][] = array("hoverCapSepChar", "Char that separates val from name in hover caption)", "text", null);

		$cname = "MSColumn3D";
		// Background
		$this->chart_types[$cname][4][] = array("bgColor", "Chart Background Color", "HexColorCode", null);
		$this->chart_types[$cname][4][] = array("bgAlpha", "Chart Alpha Level", "number", null);
		// Canvas properties
		$this->chart_types[$cname][4][] = array("canvasBgColor", "Canvas background color", "HexColorCode", null);
		$this->chart_types[$cname][4][] = array("canvasBaseColor", "Canvas base color", "HexColorCode", null);
		$this->chart_types[$cname][4][] = array("canvasBgDepth", "Canvas background depth", "number", null);
		$this->chart_types[$cname][4][] = array("canvasBaseDepth", "Canvas base 3D depth", "number", null);
		$this->chart_types[$cname][4][] = array("showCanvasBg", "Show background canvas", "bool", array("true"=>1, "false"=>0));
		$this->chart_types[$cname][4][] = array("showCanvasBase", "Show base canvas", "bool", array("true"=>1, "false"=>0));
		// Limits
		$this->chart_types[$cname][4][] = array("yAxisMinValue", "Lower Limit of y-axis", "number", null);
		$this->chart_types[$cname][4][] = array("yAxisMaxValue", "Upper Limit of y-axis", "number", null);
		// Generic
		$this->chart_types[$cname][4][] = array("shownames", "Show x-axis names", "bool", array("true"=>1, "false"=>0));
		$this->chart_types[$cname][4][] = array("showValues", "Show y-axis values", "bool", array("true"=>1, "false"=>0));
		$this->chart_types[$cname][4][] = array("showLimits", "Display limits", "bool", array("true"=>1, "false"=>0));
		$this->chart_types[$cname][4][] = array("rotateNames", "Rotate category names", "bool", array("true"=>1, "false"=>0));
		$this->chart_types[$cname][4][] = array("animation", "Animate chart", "bool", array("true"=>1, "false"=>0));
		$this->chart_types[$cname][4][] = array("chartLeftMargin", "Chart Left Margin", "number", null);
		$this->chart_types[$cname][4][] = array("chartRightMargin", "Chart Right Margin", "number", null);
		$this->chart_types[$cname][4][] = array("chartTopMargin", "Chart Top Margin", "number", null);
		$this->chart_types[$cname][4][] = array("chartBottomMargin", "Chart Bottom Margin", "number", null);
		// Fonts
		$this->chart_types[$cname][4][] = array("baseFont", "Canvas font", "text", null);
		$this->chart_types[$cname][4][] = array("baseFontSize", "Canvas font size", "text", null);
		$this->chart_types[$cname][4][] = array("baseFontColor", "Canvas font color", "HexColorCode", null);
		$this->chart_types[$cname][4][] = array("outCnvBaseFont", "Outside canvas font", "text", null);
		$this->chart_types[$cname][4][] = array("outCnvBaseFontSze", "Outside canvas font size", "text", null);
		$this->chart_types[$cname][4][] = array("outCnvBaseFontColor", "Outside canvas font color", "HexColorCode", null);
		// Number formatting
		$this->chart_types[$cname][4][] = array("numberPrefix", "Number prefix", "text", null);
		$this->chart_types[$cname][4][] = array("numberSuffix", "Number suffix", "text", null);
		$this->chart_types[$cname][4][] = array("formatNumber", "Format numbers", "bool", array("true"=>1, "false"=>0));
		$this->chart_types[$cname][4][] = array("formatNumberScale", "Scale numbers (1k, 2m)", "bool", array("true"=>1, "false"=>0));
		// Zero Plane
		$this->chart_types[$cname][4][] = array("zeroPlaneShowBorder", "Show zero-plane norder", "bool", array("true"=>1, "false"=>0));
		$this->chart_types[$cname][4][] = array("zeroPlaneBorderColor", "Zero-Plane border color", "HexColorCode", null);
		$this->chart_types[$cname][4][] = array("zeroPlaneColor", "Zero-Plane color", "HexColorCode", null);
		$this->chart_types[$cname][4][] = array("zeroPlaneAlpha", "Zero-Plane alpha", "number", null);

		$cname = "MSColumn2D";
		// Background
		$this->chart_types[$cname][4][] = array("bgColor", "Chart Background Color", "HexColorCode", null);
		$this->chart_types[$cname][4][] = array("bgAlpha", "Chart Alpha Level", "number", null);
		// Canvas properties
		$this->chart_types[$cname][4][] = array("canvasBgColor", "Canvas background color", "HexColorCode", null);
		$this->chart_types[$cname][4][] = array("canvasBgAlpha", "Canvas background alpha", "number", null);
		$this->chart_types[$cname][4][] = array("canvasBorderColor", "Canvas border color", "HexColorCode", null);
		$this->chart_types[$cname][4][] = array("canvasBorderThickness", "Canvas border thickness", "number", null);
		// Limits
		$this->chart_types[$cname][4][] = array("yAxisMinValue", "Lower Limit of y-axis", "number", null);
		$this->chart_types[$cname][4][] = array("yAxisMaxValue", "Upper Limit of y-axis", "number", null);
		// Generic
		$this->chart_types[$cname][4][] = array("shownames", "Show x-axis names", "bool", array("true"=>1, "false"=>0));
		$this->chart_types[$cname][4][] = array("showValues", "Show y-axis values", "bool", array("true"=>1, "false"=>0));
		$this->chart_types[$cname][4][] = array("showLimits", "Display limits", "bool", array("true"=>1, "false"=>0));
		$this->chart_types[$cname][4][] = array("rotateNames", "Rotate category names", "bool", array("true"=>1, "false"=>0));
		$this->chart_types[$cname][4][] = array("animation", "Animate chart", "bool", array("true"=>1, "false"=>0));
		$this->chart_types[$cname][4][] = array("showColumnShadow", "Show Column Shadow", "bool", array("true"=>1, "false"=>0));
		$this->chart_types[$cname][4][] = array("chartLeftMargin", "Chart Left Margin", "number", null);
		$this->chart_types[$cname][4][] = array("chartRightMargin", "Chart Right Margin", "number", null);
		$this->chart_types[$cname][4][] = array("chartTopMargin", "Chart Top Margin", "number", null);
		$this->chart_types[$cname][4][] = array("chartBottomMargin", "Chart Bottom Margin", "number", null);
		// Fonts
		$this->chart_types[$cname][4][] = array("baseFont", "Canvas font", "text", null);
		$this->chart_types[$cname][4][] = array("baseFontSize", "Canvas font size", "text", null);
		$this->chart_types[$cname][4][] = array("baseFontColor", "Canvas font color", "HexColorCode", null);
		$this->chart_types[$cname][4][] = array("outCnvBaseFont", "Outside canvas font", "text", null);
		$this->chart_types[$cname][4][] = array("outCnvBaseFontSze", "Outside canvas font size", "text", null);
		$this->chart_types[$cname][4][] = array("outCnvBaseFontColor", "Outside canvas font color", "HexColorCode", null);
		// Number formatting
		$this->chart_types[$cname][4][] = array("numberPrefix", "Number prefix", "text", null);
		$this->chart_types[$cname][4][] = array("numberSuffix", "Number suffix", "text", null);
		$this->chart_types[$cname][4][] = array("formatNumber", "Format numbers", "bool", array("true"=>1, "false"=>0));
		$this->chart_types[$cname][4][] = array("formatNumberScale", "Scale numbers (1k, 2m)", "bool", array("true"=>1, "false"=>0));
		// Zero Plane
		$this->chart_types[$cname][4][] = array("zeroPlaneThickness", "Zero-plane thickness", "number", null);
		$this->chart_types[$cname][4][] = array("zeroPlaneColor", "Zero-Plane color", "HexColorCode", null);
		$this->chart_types[$cname][4][] = array("zeroPlaneAlpha", "Zero-Plane alpha", "number", null);

		$cname = "MSBar2D";
		// Background
		$this->chart_types[$cname][4][] = array("bgColor", "Chart Background Color", "HexColorCode", null);
		$this->chart_types[$cname][4][] = array("bgAlpha", "Chart Alpha Level", "number", null);
		// Canvas properties
		$this->chart_types[$cname][4][] = array("canvasBgColor", "Canvas background color", "HexColorCode", null);
		$this->chart_types[$cname][4][] = array("canvasBgAlpha", "Canvas background alpha", "number", null);
		$this->chart_types[$cname][4][] = array("canvasBorderColor", "Canvas border color", "HexColorCode", null);
		$this->chart_types[$cname][4][] = array("canvasBorderThickness", "Canvas border thickness", "number", null);
		// Limits
		$this->chart_types[$cname][4][] = array("yAxisMinValue", "Lower Limit of y-axis", "number", null);
		$this->chart_types[$cname][4][] = array("yAxisMaxValue", "Upper Limit of y-axis", "number", null);
		// Generic
		$this->chart_types[$cname][4][] = array("shownames", "Show x-axis names", "bool", array("true"=>1, "false"=>0));
		$this->chart_types[$cname][4][] = array("showValues", "Show y-axis values", "bool", array("true"=>1, "false"=>0));
		$this->chart_types[$cname][4][] = array("showLimits", "Display limits", "bool", array("true"=>1, "false"=>0));
		$this->chart_types[$cname][4][] = array("rotateNames", "Rotate category names", "bool", array("true"=>1, "false"=>0));
		$this->chart_types[$cname][4][] = array("animation", "Animate chart", "bool", array("true"=>1, "false"=>0));
		$this->chart_types[$cname][4][] = array("showColumnShadow", "Show Column Shadow", "bool", array("true"=>1, "false"=>0));
		$this->chart_types[$cname][4][] = array("chartLeftMargin", "Chart Left Margin", "number", null);
		$this->chart_types[$cname][4][] = array("chartRightMargin", "Chart Right Margin", "number", null);
		$this->chart_types[$cname][4][] = array("chartTopMargin", "Chart Top Margin", "number", null);
		$this->chart_types[$cname][4][] = array("chartBottomMargin", "Chart Bottom Margin", "number", null);
		// Fonts
		$this->chart_types[$cname][4][] = array("baseFont", "Canvas font", "text", null);
		$this->chart_types[$cname][4][] = array("baseFontSize", "Canvas font size", "text", null);
		$this->chart_types[$cname][4][] = array("baseFontColor", "Canvas font color", "HexColorCode", null);
		$this->chart_types[$cname][4][] = array("outCnvBaseFont", "Outside canvas font", "text", null);
		$this->chart_types[$cname][4][] = array("outCnvBaseFontSze", "Outside canvas font size", "text", null);
		$this->chart_types[$cname][4][] = array("outCnvBaseFontColor", "Outside canvas font color", "HexColorCode", null);
		// Number formatting
		$this->chart_types[$cname][4][] = array("numberPrefix", "Number prefix", "text", null);
		$this->chart_types[$cname][4][] = array("numberSuffix", "Number suffix", "text", null);
		$this->chart_types[$cname][4][] = array("formatNumber", "Format numbers", "bool", array("true"=>1, "false"=>0));
		$this->chart_types[$cname][4][] = array("formatNumberScale", "Scale numbers (1k, 2m)", "bool", array("true"=>1, "false"=>0));
		// Zero Plane
		$this->chart_types[$cname][4][] = array("zeroPlaneThickness", "Zero-plane thickness", "number", null);
		$this->chart_types[$cname][4][] = array("zeroPlaneColor", "Zero-Plane color", "HexColorCode", null);
		$this->chart_types[$cname][4][] = array("zeroPlaneAlpha", "Zero-Plane alpha", "number", null);

		$cname = "MSLine";
		// Background
		$this->chart_types[$cname][4][] = array("bgColor", "Chart Background Color", "HexColorCode", null);
		$this->chart_types[$cname][4][] = array("bgAlpha", "Chart Alpha Level", "number", null);
		// Canvas properties
		$this->chart_types[$cname][4][] = array("canvasBgColor", "Canvas background color", "HexColorCode", null);
		$this->chart_types[$cname][4][] = array("canvasBgAlpha", "Canvas background alpha", "number", null);
		$this->chart_types[$cname][4][] = array("canvasBorderColor", "Canvas border color", "HexColorCode", null);
		$this->chart_types[$cname][4][] = array("canvasBorderThickness", "Canvas border thickness", "number", null);
		// Limits
		$this->chart_types[$cname][4][] = array("yAxisMinValue", "Lower Limit of y-axis", "number", null);
		$this->chart_types[$cname][4][] = array("yAxisMaxValue", "Upper Limit of y-axis", "number", null);
		// Generic
		$this->chart_types[$cname][4][] = array("shownames", "Show x-axis names", "bool", array("true"=>1, "false"=>0));
		$this->chart_types[$cname][4][] = array("showValues", "Show y-axis values", "bool", array("true"=>1, "false"=>0));
		$this->chart_types[$cname][4][] = array("showLimits", "Display limits", "bool", array("true"=>1, "false"=>0));
		$this->chart_types[$cname][4][] = array("rotateNames", "Rotate category names", "bool", array("true"=>1, "false"=>0));
		$this->chart_types[$cname][4][] = array("animation", "Animate chart", "bool", array("true"=>1, "false"=>0));
		$this->chart_types[$cname][4][] = array("chartLeftMargin", "Chart Left Margin", "number", null);
		$this->chart_types[$cname][4][] = array("chartRightMargin", "Chart Right Margin", "number", null);
		$this->chart_types[$cname][4][] = array("chartTopMargin", "Chart Top Margin", "number", null);
		$this->chart_types[$cname][4][] = array("chartBottomMargin", "Chart Bottom Margin", "number", null);
		// Line Properties
		$this->chart_types[$cname][4][] = array("lineColor", "Line color", "HexColorCode", null);
		$this->chart_types[$cname][4][] = array("lineThickness", "Line thickness", "number", null);
		$this->chart_types[$cname][4][] = array("lineAlpha", "Line alpha", "number", null);
		// Line Shadow Properties
		$this->chart_types[$cname][4][] = array("showShadow", "Show line shadow", "bool", array("true"=>1, "false"=>0));
		$this->chart_types[$cname][4][] = array("shadowColor", "Line shadow color", "HexColorCode", null);
		$this->chart_types[$cname][4][] = array("shadowThickness", "Line shadow thickness", "number", null);
		$this->chart_types[$cname][4][] = array("shadowAlpha", "Line shadow alpha", "number", null);
		// Anchor properties	
		$this->chart_types[$cname][4][] = array("showAnchors", "Show anchors", "bool", array("true"=>1, "false"=>0));
		$this->chart_types[$cname][4][] = array("anchorSides", "Anchor sides", "number", array("three"=>3, "four"=>4));
		$this->chart_types[$cname][4][] = array("anchorBorderColor", "Anchor border color", "HexColorCode", null);
		$this->chart_types[$cname][4][] = array("anchorBgColor", "Anchor background color", "HexColorCode", null);
		$this->chart_types[$cname][4][] = array("anchorBgAlpha", "Anchor background alpha", "number", null);
		$this->chart_types[$cname][4][] = array("anchorAlpha", "Anchor alpha", "number", null);
		// Fonts
		$this->chart_types[$cname][4][] = array("baseFont", "Canvas font", "text", null);
		$this->chart_types[$cname][4][] = array("baseFontSize", "Canvas font size", "text", null);
		$this->chart_types[$cname][4][] = array("baseFontColor", "Canvas font color", "HexColorCode", null);
		$this->chart_types[$cname][4][] = array("outCnvBaseFont", "Outside canvas font", "text", null);
		$this->chart_types[$cname][4][] = array("outCnvBaseFontSze", "Outside canvas font size", "text", null);
		$this->chart_types[$cname][4][] = array("outCnvBaseFontColor", "Outside canvas font color", "HexColorCode", null);
		// Number formatting
		$this->chart_types[$cname][4][] = array("numberPrefix", "Number prefix", "text", null);
		$this->chart_types[$cname][4][] = array("numberSuffix", "Number suffix", "text", null);
		$this->chart_types[$cname][4][] = array("formatNumber", "Format numbers", "bool", array("true"=>1, "false"=>0));
		$this->chart_types[$cname][4][] = array("formatNumberScale", "Scale numbers (1k, 2m)", "bool", array("true"=>1, "false"=>0));
		// Zero Plane
		$this->chart_types[$cname][4][] = array("zeroPlaneThickness", "Zero-plane thickness", "number", null);
		$this->chart_types[$cname][4][] = array("zeroPlaneColor", "Zero-Plane color", "HexColorCode", null);
		$this->chart_types[$cname][4][] = array("zeroPlaneAlpha", "Zero-Plane alpha", "number", null);

		$cname = "MSArea2D";
		// Background
		$this->chart_types[$cname][4][] = array("bgColor", "Chart Background Color", "HexColorCode", null);
		$this->chart_types[$cname][4][] = array("bgAlpha", "Chart Alpha Level", "number", null);
		// Canvas properties
		$this->chart_types[$cname][4][] = array("canvasBgColor", "Canvas background color", "HexColorCode", null);
		$this->chart_types[$cname][4][] = array("canvasBgAlpha", "Canvas background alpha", "number", null);
		$this->chart_types[$cname][4][] = array("canvasBorderColor", "Canvas border color", "HexColorCode", null);
		$this->chart_types[$cname][4][] = array("canvasBorderThickness", "Canvas border thickness", "number", null);
		// Limits
		$this->chart_types[$cname][4][] = array("yAxisMinValue", "Lower Limit of y-axis", "number", null);
		$this->chart_types[$cname][4][] = array("yAxisMaxValue", "Upper Limit of y-axis", "number", null);
		// Generic
		$this->chart_types[$cname][4][] = array("shownames", "Show x-axis names", "bool", array("true"=>1, "false"=>0));
		$this->chart_types[$cname][4][] = array("showValues", "Show y-axis values", "bool", array("true"=>1, "false"=>0));
		$this->chart_types[$cname][4][] = array("showLimits", "Display limits", "bool", array("true"=>1, "false"=>0));
		$this->chart_types[$cname][4][] = array("rotateNames", "Rotate category names", "bool", array("true"=>1, "false"=>0));
		$this->chart_types[$cname][4][] = array("animation", "Animate chart", "bool", array("true"=>1, "false"=>0));
		$this->chart_types[$cname][4][] = array("showColumnShadow", "Show Column Shadow", "bool", array("true"=>1, "false"=>0));
		$this->chart_types[$cname][4][] = array("chartLeftMargin", "Chart Left Margin", "number", null);
		$this->chart_types[$cname][4][] = array("chartRightMargin", "Chart Right Margin", "number", null);
		$this->chart_types[$cname][4][] = array("chartTopMargin", "Chart Top Margin", "number", null);
		$this->chart_types[$cname][4][] = array("chartBottomMargin", "Chart Bottom Margin", "number", null);
		// Area
		$this->chart_types[$cname][4][] = array("showAreaBorder", "Show area border", "bool", array("true"=>1, "false"=>0));
		$this->chart_types[$cname][4][] = array("areaBorderThickness", "Area border thickness", "number", null);
		$this->chart_types[$cname][4][] = array("areaBorderColor", "Area border color", "HexColorCode", null);
		$this->chart_types[$cname][4][] = array("areaBgColor", "Area background color", "HexColorCode", null);
		$this->chart_types[$cname][4][] = array("areaAlpha", "Area alpha", "number", null);
		// Fonts
		$this->chart_types[$cname][4][] = array("baseFont", "Canvas font", "text", null);
		$this->chart_types[$cname][4][] = array("baseFontSize", "Canvas font size", "text", null);
		$this->chart_types[$cname][4][] = array("baseFontColor", "Canvas font color", "HexColorCode", null);
		$this->chart_types[$cname][4][] = array("outCnvBaseFont", "Outside canvas font", "text", null);
		$this->chart_types[$cname][4][] = array("outCnvBaseFontSze", "Outside canvas font size", "text", null);
		$this->chart_types[$cname][4][] = array("outCnvBaseFontColor", "Outside canvas font color", "HexColorCode", null);
		// Number formatting
		$this->chart_types[$cname][4][] = array("numberPrefix", "Number prefix", "text", null);
		$this->chart_types[$cname][4][] = array("numberSuffix", "Number suffix", "text", null);
		$this->chart_types[$cname][4][] = array("formatNumber", "Format numbers", "bool", array("true"=>1, "false"=>0));
		$this->chart_types[$cname][4][] = array("formatNumberScale", "Scale numbers (1k, 2m)", "bool", array("true"=>1, "false"=>0));
		// Zero Plane
		$this->chart_types[$cname][4][] = array("zeroPlaneThickness", "Zero-plane thickness", "number", null);
		$this->chart_types[$cname][4][] = array("zeroPlaneColor", "Zero-Plane color", "HexColorCode", null);
		$this->chart_types[$cname][4][] = array("zeroPlaneAlpha", "Zero-Plane alpha", "number", null);
	}
}

if (isset($_GET['test']))
{
	$chart = new CChart("Column3D", "/datacenter/xml_chartdata.awp?gid=6");
	echo $chart->getChart();
}
?>
