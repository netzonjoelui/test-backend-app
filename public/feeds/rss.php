<?php
/**
 * This function takes a content_feed and renders it as an RSS feed
 */
require_once("../../lib/AntConfig.php");
require_once("ant.php");
require_once("lib/CAntObject.php");
require_once("lib/CAntObjectList.php");

$FID = $_GET['fid'];
if (!is_numeric($FID))
	die("Feed not found");

$feed = CAntObject::factory($ANT->dbh, "content_feed", $FID);

/**
 * Function used to escape rss values
 */
function rssEscape($text)
{
	$text = str_replace("&", "&amp;", $text);
	$text = str_replace("<", "&lt;", $text);
	$text = str_replace(">", "&gt;", $text);
	$text = str_replace("\"", "&quot;", $text);

	return $text;
}


/**
 * Set headers and build XML structure
 */
header("Content-type: text/xml");
echo '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'."\n"; 
//echo '<rss xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd" version="2.0">';
echo '<rss xmlns:feedburner="http://rssnamespace.org/feedburner/ext/1.0" version="2.0">';
echo "<channel><title>" . rssEscape($feed->getValue("title")) . "</title>";

$olist = new CAntObjectList($ANT->dbh, "content_feed_post");
$olist->addCondition("and", "f_publish", "is_equal", 't');
$olist->getObjects();
for ($i = 0; $i < $olist->getNumObjects(); $i++)
{
	$post = $olist->getObject($i);

	echo "<item>";
	echo "<title title='Title'>".rssEscape($post->getValue("title"))."</title>";
	echo "<guid>".rssEscape($post->id)."</guid>";
	//echo "<time title='Time'>".rssEscape($post->getValue("time_entered"))."</time>";
	echo "<description title='Desciption'>".rssEscape($post->getValue("data"))."</description>";
	if ($_GET['linkbase'])
		echo "<link>". $_GET['linkbase'] . rssEscape($post->getValue("uname"))."</link>";
	//echo "<pubDate title='Date Entered'>".rssEscape($post->getValue("time_entered"))."</pubDate>";
	echo "</item>";
}

echo "</channel>";
echo "</rss>";
