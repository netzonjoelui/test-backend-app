<?php 
	require_once("../lib/AntConfig.php");
	require_once("ant.php");
	require_once("ant_user.php");
	require_once("../lib/aereus.lib.php/CPageCache.php");

	$dbh = $ANT->dbh;
	$USERNAME = $USER->name;
	$USERID =  $USER->id;
	$ACCOUNT = $USER->accountId;
	$url = rawurldecode($_GET['url']);

	// 300 = 5 minutes
	$cache = new CPageCache(1800, "widgets-home-".$_GET['id']);

	header("Content-type: text/xml");			// Returns XML document
	echo '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'; 

	class xItem 
	{
		var $xTitle;
		var $xLink;
		var $xDescription;
		var $xEncUrl;
	}
	
	// general vars
	$sTitle = "";
	$sLink = "";
	$sDate = "";
	$RSSVER = ".92";
	$sDescription = "";
	$arItems = array();
	$arAttrs = array();
	$itemCount = 0;
	
	function startElement($parser, $name, $attrs) 
	{
		global $curTag, $arItems, $itemCount, $RSSVER;
		$curTag .= "^$name";

		if ($name == "RDF:RDF") // Account for namespaces
			$RSSVER = "1.0";
		
		if ($name == "ITEM")
		{
			// make new xItem   
			$arItems[$itemCount] = new xItem();
		}
		
		if ($RSSVER == "1.0")
		{
			$itemEncKey = "^RDF:RDF^ITEM^ENCLOSURE";
		}
		else
		{
			$itemEncKey = "^RSS^CHANNEL^ITEM^ENCLOSURE";
		}
		
		if ($curTag == $itemEncKey) 
		{
			$arItems[$itemCount]->xEncUrl = $attrs['URL'];
		}
	}
	
	function endElement($parser, $name) 
	{
		global $curTag, $itemCount;

		$caret_pos = strrpos($curTag,'^');
		$curTag = substr($curTag,0,$caret_pos);
		if ($name == "ITEM")
		{
			// increment item counter
			$itemCount++;
		}
	}
	
	function characterData($parser, $data) 
	{
		global $curTag, $RSSVER; // get the Channel information first
		global $sTitle, $sLink, $sDescription, $sDate;  
		if ($RSSVER == "1.0")
		{
			$titleKey = "^RDF:RDF^CHANNEL^TITLE";
			$linkKey = "^RDF:RDF^CHANNEL^LINK";
			$descKey = "^RDF:RDF^CHANNEL^DESCRIPTION";
		}
		else
		{
			$titleKey = "^RSS^CHANNEL^TITLE";
			$linkKey = "^RSS^CHANNEL^LINK";
			$descKey = "^RSS^CHANNEL^DESCRIPTION";
		}
		
		if ($curTag == $titleKey)
			$sTitle = $data;
		elseif ($curTag == $linkKey)
			$sLink = $data;
		elseif ($curTag == $descKey)
			$sDescription = $data;
	
		// now get the items 
		global $arItems, $itemCount;
		if ($RSSVER == "1.0")
		{
			$itemTitleKey = "^RDF:RDF^ITEM^TITLE";
			$itemLinkKey = "^RDF:RDF^ITEM^LINK";
			$itemDescKey = "^RDF:RDF^ITEM^DESCRIPTION";
			$itemDateKey = "^RDF:RDF^ITEM^PUBDATE";
			$itemEncKey = "^RDF:RDF^ITEM^ENCLOSURE";
		}
		else
		{
			$itemTitleKey = "^RSS^CHANNEL^ITEM^TITLE";
			$itemLinkKey = "^RSS^CHANNEL^ITEM^LINK";
			$itemDescKey = "^RSS^CHANNEL^ITEM^DESCRIPTION";
			$itemDateKey = "^RSS^CHANNEL^ITEM^PUBDATE";
			$itemEncKey = "^RSS^CHANNEL^ITEM^ENCLOSURE";
		}
		
		if ($curTag == $itemTitleKey) 
		{
			// set new item object's properties    
			$arItems[$itemCount]->xTitle .= $data;
		}
		elseif ($curTag == $itemLinkKey) 
		{
			$arItems[$itemCount]->xLink .= $data;
		}
		elseif ($curTag == $itemDescKey) 
		{
			$arItems[$itemCount]->xDescription .= str_replace("<a href=", "<a target='_blank' href=", $data);
		}
	}
	
	// main loop
	if ($cache->IsExpired() && $url && $url!="null")
	{
		$xml_parser = xml_parser_create();
		xml_set_element_handler($xml_parser, "startElement", "endElement");
		xml_set_character_data_handler($xml_parser, "characterData");
		if (($fp = @fopen($url,"r"))) 
		{
			while ($data = fread($fp, 4096)) 
			{
				if (!xml_parse($xml_parser, $data, feof($fp)))
					die(sprintf("XML error: %s at line %d", xml_error_string(xml_get_error_code($xml_parser)), xml_get_current_line_number($xml_parser)));
			}
			xml_parser_free($xml_parser);
		}
		
		// Print title, link, and description 
		$cache->put("<rss version=\"2.0\">");
		$cache->put("<channel>");
		$cache->put("<title>$sTitle</title>");
		$cache->put("<description>$sDescription</description>");
			  
		foreach ($arItems as $txItem)
		{		
			$link = ($txItem->xLink) ? $txItem->xLink : $txItem->xEncUrl;	
			$cache->put("<item>");
			$cache->put("	<title>".rawurlencode($txItem->xTitle)."</title>");
			$cache->put("	<link>".rawurlencode($link)."</link>");
			$cache->put("	<description>".rawurlencode($txItem->xDescription)."</description>");
			$cache->put("	<author></author>");
			$cache->put("	<pubDate></pubDate>");
			$cache->put("</item>");
		}
		$cache->put("</channel>");
		$cache->put("</rss>");
	}

	$cache->printCache();
?>
