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

	$options = array
	(
		'hostname' => ANT_INDEX_SOLR_HOST,
		'login'    => "admin",
		'password' => "admin",
		'port'     => 8983,
	);

	$client = new SolrClient($options);

	$query = new SolrQuery();
	$query->setQuery("id:[* TO *]");
	//$query->addFilterQuery("id:".substr($term_text, 0, strlen($term_text)-3)."*");
	$query->setStart(0);
	$query->setRows(10);
	$query->addSortField("oid", SolrQuery::ORDER_DESC);
	$query_response = $client->query($query);
	$response = $query_response->getResponse();
	print_r($response);

/*
	$query_response = $client->deleteByQuery("database:aereus_ant AND type:test");
	$response = $query_response->getResponse();
	$client->commit();
	//$client->optimize();
	print_r($response);
	*/

	exit;

/*
	$doc = new SolrInputDocument();

	$doc->addField('id', "pt334458");
	$doc->addField('database', "aereus_ant");
	$doc->addField('type', "pt334455");
	$doc->addField('cat_smv', 'Software');
	$doc->addField('cat_smv', 'Lucene');

	$updateResponse = $client->addDocument($doc);
	$client->commit();

	print_r($updateResponse);

	$query = new SolrQuery();
	$query->setQuery('cat_smv:Software');
	$query->setStart(0);
	$query->setRows(50);
	//$query->addField('cat')->addField('id')->addField('timestamp');
	$query_response = $client->query($query);
	$response = $query_response->getResponse();
	print_r($response);
	*/


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
		//$dbh->Query("insert into tmp_lrg_idx_test(obj_id, val_number, val_tsv, val_text, val_timestamp) 
		//			 values('".$i."', '$i', to_tsvector('english', '$text'), '$text', 'now')");

		// Adds 1 document to the index
		$doc = new SolrInputDocument();

		$doc->addField('id', "$i");
		$doc->addField('database', "aereus_ant");
		$doc->addField('type', "test");
		$doc->addField('val_number_i', "$i");
		$doc->addField('val_text_s', $text);
		$doc->addField('val_ts_dt', gmdate("Y-m-d\\TG:i:s\\Z", time()));

		$client->addDocument($doc);
	}
	// Now insert at the very end something unique
	//if (!$dbh->GetNumberRows($dbh->Query("select obj_id from tmp_lrg_idx_test where obj_id='$term_number'")))
	//{

		//$dbh->Query("insert into tmp_lrg_idx_test(obj_id, val_number, val_tsv, val_text, val_timestamp) 
					 //values('".$term_number."', '$term_number', to_tsvector('english', '$term_text'), '$term_text', 'now')");

		$doc = new SolrInputDocument();

		$doc->addField('id', "$term_number");
		$doc->addField('database', "aereus_ant");
		$doc->addField('type', "test");
		$doc->addField('val_number_i', "$term_number");
		$doc->addField('val_text_s', $term_text);
		$doc->addField('val_ts_dt', gmdate("Y-m-d\\TG:i:s\\Z", time()));

		$client->addDocument($doc);

		$log++;
		if ($log>=($num/10))
		{
			echo "\t".number_format($i)." of ".number_format($num)." entered\n";
			$log = 0;
		}
	//}

	// Index needs a moment to be updated
	//$client->commit();
	//$client->optimize();

	// --------------------------------------------------------
	// Test queries $num_queries X and get average
	// --------------------------------------------------------
	$num_queries = 1000;

	// Solr
	// ----------------------------------
	$total_txt = 0;
	$total_txt_nf = 0;
	$total_txt_like = 0;
	$total_txt_like_nf = 0;
	$total_num = 0;
	$total_num_nf = 0;
	$total_num_range = 0;
	$total_num_range_nf = 0;
	echo "Testing Solr...\n\n";
	for ($i = 0; $i < $num_queries; $i++)
	{
		//echo "\t running ".($i+1)." of $num_queries...\n";

		// Text
		$tStart = microtime(true);
		$query = new SolrQuery();
		$query->setQuery("val_text_s:$term_text");
		$query->setStart(0);
		$query->setRows(10);
		$query_response = $client->query($query);
		$response = $query_response->getResponse();
		$tEnd = microtime(true);
		$total_txt += $tEnd-$tStart;

		$tStart = microtime(true);
		$query = new SolrQuery();
		$query->setQuery("val_text_s:".substr($term_text, 0, strlen($term_text)-3)."*");
		$query->setStart(0);
		$query->setRows(10);
		$query_response = $client->query($query);
		$response = $query_response->getResponse();
		$tEnd = microtime(true);
		$total_txt += $tEnd-$tStart;

		// Number
		$tStart = microtime(true);

		$query = new SolrQuery();
		$query->setQuery("val_number_i:$term_number");
		$query->setStart(0);
		$query->setRows(10);
		$query_response = $client->query($query);
		$response = $query_response->getResponse();
		$tEnd = microtime(true);
		$total_num += $tEnd-$tStart;

		$tStart = microtime(true);

		$query->setQuery("val_number_i:[".($term_number-100)." TO ".($term_number+100)."]");
		$query->setStart(0);
		$query->setRows(10);
		$query_response = $client->query($query);
		$tEnd = microtime(true);
		$total_num_range += $tEnd-$tStart;
	}

	echo "SOLR:\n-----------------------------\n";
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
