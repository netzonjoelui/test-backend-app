<?php
	// ANT Includes 
	require_once('../../lib/AntConfig.php');
	require_once('lib/Ant.php');
	require_once('lib/AntUser.php');
	require_once('lib/CAntObject.php');
	require_once('lib/elastic.php');
	require_once('lib/aereus.lib.php/CAntObjectApi.php');

	$term_text = "uniquetest";
	$term_number = "25000000";
	$term_text_common = "performance";
	$term_number_common = "1001";

	$dbh = new CDatabase();

	$text = "READ UNCOMMITTED: UserA will see the change made by UserB. This isolation level is called dirty reads, which means that read data is not consistent with other parts of the table or the query, and may not yet have been committed. This isolation level ensures the quickest performance, as data is read directly from the table’s blocks with no further processing, verifications or any other validation. The process is quick and the data is asdirty as it can get.
READ COMMITTED: UserA will not see the change made by UserB. This is because in the READ COMMITTED isolation level, the rows returned by a query are the rows that were committed when the query was started. The change made by UserB was not present when the query started, and therefore will not be included in the query result.
REPEATABLE READ: UserA will not see the change made by UserB. This is because in the REPEATABLE READ isolation level, the rows returned by a query are the rows that were committed when the transaction was started. The change made by UserB was not present when the transaction was started, and therefore will not be included in the query result.
This means that “All consistent reads within the same transaction read the snapshot established by the first read” (from MySQL documentation. See http://dev.mysql.com/doc/refman/5.1/en/innodb-consistent-read.html).
SERIALIZABLE: This isolation level specifies that all transactions occur in a completely isolated fashion, meaning as if all transactions in the system were executed serially, one after the other. The DBMS can execute two or more transactions at the same time only if the illusion of serial execution can be maintained.
In practice, SERIALIZABLE is similar to REPEATABLE READ, but uses a different implementation for each database engine. In Oracle, the REPEATABLE READ level is not supported and SERIALIZABLE provides the highest isolation level. This level is similar to REPEATABLE READ, but InnoDB implicitly converts all plain SELECT statements to “SELECT … LOCK IN SHARE MODE.
Since old values of row data are required for current queries, databases use a special segment to store old row values and snapshots. MySQL calls this segment a Rollback Segment. Oracle once called it this as well, but now calls it an Undo Segment. The premise is the same.

During query execution, each row is examined and if it is found to be too new, an older version of this row is extracted from the rollback segment to comprise the query result. This examination‑lookup‑comprise action chain takes time to complete, resulting in a performance penalty. It also produces a snowball effect. Updates occur during a query, which makes that query slower so that it takes more time. During the time it takes to process the query, more updates come in, making query execution time even longer!

This is why a query that executes in 10 seconds in our testing environment may take a full 10 minutes to execute in a much stronger production environment.";
	//for ($i = 0; $i < 300; $i++)
		//$text .= "Message body ";

	$client = new Elastica_Client();    
	$index = $client->getIndex('tmp_lrg_idx_test');
	//$index->create(array(), true);
	$type = $index->getType('test');
	/*
	$mapping = array(
		"val_number"=>array("type"=>"double"),
		"val_text"=>array("type"=>"string"),
		"val_text_idxsort"=>array("type"=>"string", "index"=>"no"),
		"val_timestamp"=>array("type"=>"date")
	);
	$type->setMapping($mapping);
	*/

/*
	// Create table
	if ($dbh->TableExists("tmp_lrg_idx_test"))
		$dbh->Query("DROP TABLE tmp_lrg_idx_test cascade");
	$dbh->Query("CREATE TABLE tmp_lrg_idx_test
				(
				  obj_id bigint,
				  val_number numeric,
				  val_text text,
				  val_tsv tsvector,
				  val_timestamp timestamp with time zone
				)
				WITH (
				  OIDS=FALSE
				);");

	$dbh->Query("CREATE INDEX tmp_lrg_idx_test_oid_idx
				  ON tmp_lrg_idx_test
				  USING btree
				  (obj_id);");

	$dbh->Query("CREATE INDEX tmp_lrg_idx_test_tsv_idx
				  ON tmp_lrg_idx_test
				  USING gin (val_tsv);");

	$dbh->Query("CREATE INDEX tmp_lrg_idx_test_num_idx
				  ON tmp_lrg_idx_test
				  USING btree
				  (val_number);");

	$dbh->Query("CREATE INDEX tmp_lrg_idx_test_ts_idx
				  ON tmp_lrg_idx_test
				  USING btree
				  (val_timestamp);");
*/

	// Outer iterator 100X100k = 10 million records
	$num = 0;
	echo "Inserting ".number_format($num)." records:\n";
	$log = 0;
	for ($i = 0; $i < $num; $i++)
	{
		/*
		$dbh->Query("insert into tmp_lrg_idx_test(obj_id, val_number, val_tsv, val_text, val_timestamp) 
					 values('".$i."', '$i', to_tsvector('english', '$text'), '$text', 'now')");
		 */

		// Adds 1 document to the index
		$doc1 = new Elastica_Document($i, 
			array('val_i' => $i, 'val_txt' => $text, 'val_tsort' => $text, 'val_t' => gmdate("Ymd\\TG:i:s", time()))
		);
		$type->addDocument($doc1);

		//$log++;
		//if ($log>=($num/10))
		//{
			//echo "\t".number_format($i)." of ".number_format($num)." entered\n";
			//$log = 0;
		//}
	}
	// Now insert at the very end something unique
	/*
	if (!$dbh->GetNumberRows($dbh->Query("select obj_id from tmp_lrg_idx_test where obj_id='$term_number'")))
	{

		$dbh->Query("insert into tmp_lrg_idx_test(obj_id, val_number, val_tsv, val_text, val_timestamp) 
					 values('".$term_number."', '$term_number', to_tsvector('english', '$term_text'), '$term_text', 'now')");

		// Adds 1 document to the index
		$doc1 = new Elastica_Document($term_number, 
											array('val_number' => $term_number, 'val_text' => $term_text, 'val_text_idxsort' => $term_text, 
												  'val_timestamp' => gmdate("Ymd\\TG:i:s", time()))
		);
		$type->addDocument($doc1);
	}
	 */

	// Index needs a moment to be updated
	$index->refresh();
	$index->optimize();

	// --------------------------------------------------------
	// Test queries $num_queries X and get average
	// --------------------------------------------------------
	$num_queries = 1000;

	// Elastic
	// ----------------------------------
	$total_txt = 0;
	$total_txt_nf = 0;
	$total_txt_like = 0;
	$total_txt_like_nf = 0;
	$total_num = 0;
	$total_num_nf = 0;
	$total_num_range = 0;
	$total_num_range_nf = 0;
	echo "Testing Elastic...\n\n";
	for ($i = 0; $i < $num_queries; $i++)
	{
		//echo "\t running ".($i+1)." of $num_queries...\n";

		// Text
		$tStart = microtime(true);
		$queryObject = new Elastica_Query();
		$queryObject->setFrom(0);
		$queryObject->setLimit(10);
		$queryObject->setRawQuery(array('constant_score' => array( 'filter' => array("term"=>array("val_text"=>$term_text)) )));
		$type->search($queryObject);
		$tEnd = microtime(true);
		$total_txt += $tEnd-$tStart;

		$tStart = microtime(true);
		$queryObject = new Elastica_Query();
		$queryObject->setFrom(0);
		$queryObject->setLimit(10);
		$queryObject->setRawQuery(array('constant_score' => array( 'filter' => array("term"=>array("val_text"=>substr($term_text, 0, strlen($term_text)-3)."*")) )));
		//$type->search($queryObject);
		$tEnd = microtime(true);
		$total_txt_like += $tEnd-$tStart;

		// Number
		$tStart = microtime(true);
		$queryObject = new Elastica_Query();
		$queryObject->setFrom(0);
		$queryObject->setLimit(10);
		$queryObject->setRawQuery(array('constant_score' => array( 'filter' => array("term"=>array("val_number"=>$term_number)) )));
		$type->search($queryObject);
		$tEnd = microtime(true);
		$total_num += $tEnd-$tStart;

		$tStart = microtime(true);
		$queryObject = new Elastica_Query();
		$queryObject->setFrom(0);
		$queryObject->setLimit(10);
		$queryObject->setRawQuery(array('constant_score' => array( 'filter' => array("range"=>array("val_number"=>array("from"=>($term_number-100), "to"=>($term_number+100)))) )));
		$type->search($queryObject);
		$tEnd = microtime(true);
		$total_num_range += $tEnd-$tStart;
	}

	echo "ELASTIC:\n-----------------------------\n";
	echo "Text:\t\t\t".number_format($total_txt*1000, 2)."\n";
	echo "Text Like:\t\t".number_format($total_txt_like*1000, 2)."\n";
	echo "Number:\t\t\t".number_format($total_num*1000, 2)."\n";
	echo "Number Range:\t\t".number_format($total_num_range*1000, 2)."\n";

	// DB
	// ----------------------------------
	/*
	$total_txt = 0;
	$total_txt_like = 0;
	$total_num = 0;
	$total_num_range = 0;
	echo "Testing DB...\n\n";
	for ($i = 0; $i < $num_queries; $i++)
	{
		//echo "\t running ".($i+1)." of $num_queries...\n";
		// Text
		$tStart = microtime(true);
		$dbh->Query("select * from tmp_lrg_idx_test where val_tsv @@ to_tsquery('$term_text') limit 10");
		$tEnd = microtime(true);
		$total_txt += $tEnd-$tStart;

		$tStart = microtime(true);
		$dbh->Query("select * from tmp_lrg_idx_test where val_tsv @@ to_tsquery('".substr($term_text, 0, strlen($term_text)-3).":*') limit 10");
		$tEnd = microtime(true);
		$total_txt_like += $tEnd-$tStart;

		// Number
		$tStart = microtime(true);
		$dbh->Query("select * from tmp_lrg_idx_test where val_number='".($term_number-100)."' limit 10");
		$tEnd = microtime(true);
		$total_num += $tEnd-$tStart;
		$tStart = microtime(true);
		$dbh->Query("select * from tmp_lrg_idx_test where val_number>='".($term_number-100)."' and val_number<='".($term_number+100)."' limit 10");
		$tEnd = microtime(true);
		$total_num_range += $tEnd-$tStart;
	}

	echo "DB:\n-----------------------------\n";
	echo "Text:\t\t".number_format($total_txt*1000, 2)."\n";
	echo "Text Like:\t".number_format($total_txt_like*1000, 2)."\n";
	echo "Number:\t\t".number_format($total_num*1000, 2)."\n";
	echo "Number Range:\t".number_format($total_num_range*1000, 2)."\n";

	 */
	
	// Cleanup
	/*
	$dbh->Query("DROP INDEX tmp_lrg_idx_test_oid_idx;");
	$dbh->Query("DROP INDEX tmp_lrg_idx_test_num_idx;");
	$dbh->Query("DROP INDEX tmp_lrg_idx_test_ts_idx;");
	$dbh->Query("DROP INDEX tmp_lrg_idx_test_tsv_idx;");
	$dbh->Query("DROP TABLE tmp_lrg_idx_test;");
	$index->delete();
	 */
	
	echo "[done]\n";
?>
