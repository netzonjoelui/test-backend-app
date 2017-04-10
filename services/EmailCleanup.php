<?php
require_once("lib/AntUser.php");		
require_once("email/email_functions.awp");

class EmailCleanup extends AntService
{
	public function main(&$dbh)
	{
		// Delete mail maked as junk that is older than 90 days
		echo "Geting list of spam messages\n";
		$olist = new CAntObjectList($dbh, "email_message");
		$olist->addCondition("and", "flag_spam", "is_equal", "t");
		$olist->addCondition("and", "message_date", "is_less_or_equal", date("m/d/Y", strtotime("-90 days", time())));
		$olist->getObjects(0, 10000);
		for ($i = 0; $i < $olist->getNumObjects(); $i++)
		{
			$obj = $olist->getObject($i);

			echo "Purge spam ".($i+1)." of ".$olist->getNumObjects().": ".$obj->id." delivered ".$obj->getValue("message_date")."\n";
			$obj->removeHard();
			$olist->unsetObject($i);
			unset($obj);
		}
		
		// Delete 'Trash' older than 90 days
		echo "Geting list of deleted messages\n";
		$olist = new CAntObjectList($dbh, "email_message");
		$olist->addCondition("and", "f_deleted", "is_equal", "t");
		$olist->addCondition("and", "ts_updated", "is_less_or_equal", date("m/d/Y", strtotime("-90 days", time())));
		$olist->getObjects(0, 10000);
		for ($i = 0; $i < $olist->getNumObjects(); $i++)
		{
			$obj = $olist->getObject($i);

			echo "Purge trash ".($i+1)." of ".$olist->getNumObjects().": ".$obj->id." delivered ".$obj->getValue("message_date")."\n";
			$obj->removeHard();
			$olist->unsetObject($i);
			unset($obj);
		}
        
        // Cleanup spam learn directories
        // ---------------------------------------------------------
        echo "Deleting temp files\n";
        $paths = array(
            AntConfig::getInstance()->data_path . "/email_spam",
            AntConfig::getInstance()->data_path . "/email_ham",
        );
        foreach ($paths as $path)
        {
            if ($handle = opendir($path)) {

                while (false !== ($file = readdir($handle))) {
                    $filelastmodified = filemtime($file);

                    if(!is_dir($file) && (time() - $filelastmodified) > 24*3600*7) // One week
                    {
                        echo "Deleted $path/$file\n";
                        unlink($path . "/" . $file);
                    }
                }

                closedir($handle); 
            }
        }
	}
}
