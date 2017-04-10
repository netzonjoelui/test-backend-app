<?php
/**
 * Can be included to render a comment
 */
if ($comment->getForeignValue("sent_by"))
	$sent_by = $comment->getForeignValue("sent_by", $sent_by);
else if ($comment->getValue('sent_by'))
	$sent_by = $comment->getValue('sent_by');
else if ($comment->getForeignValue("owner_id"))
	$sent_by = $comment->getForeignValue('owner_id');
else
	$sent_by = "Anonymous";

// Display name
echo "<div class='g2 inside'>";
echo "<strong>" . $sent_by . "</strong><br />";
echo $comment->getValue("ts_entered");
echo "</div>";

// Display comment
echo "<div class='ml2'>";
echo str_replace("\n", "<br />", $comment->getValue("comment"));
echo "</div>";

echo "<div class='clear'></div>";
