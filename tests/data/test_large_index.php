<?php
	// ANT Includes 
	require_once('../../lib/AntConfig.php');
	require_once('lib/CDatabase.awp');
	require_once('lib/AntUser.php');
	require_once('lib/CAntObject.php');
	require_once('lib/aereus.lib.php/CAntObjectApi.php');

	$dbh = new CDatabase();

	// Test large index

	// Create table
	$dbh->Query("CREATE TABLE tmp_lrg_idx_test
				(
				  obj_id bigint,
				  val_multi integer[],
				  val_number numeric,
				  val_text text,
				  val_timestamp timestamp with time zone
			    );");

	$dbh->Query("CREATE TABLE tmp_lrg_idx_many_test
				(
					ref_obj bigint,
					int_val integer
				);");

	$dbh->Query("CREATE INDEX tmp_lrg_idx_test_oid_idx
				  ON tmp_lrg_idx_test
				  USING btree
				  (obj_id);");

	$dbh->Query("CREATE INDEX tmp_lrg_idx_test_txt_idx
				  ON tmp_lrg_idx_test
				  USING btree
				  (val_text);");

	$dbh->Query("CREATE INDEX tmp_lrg_idx_test_num_idx
				  ON tmp_lrg_idx_test
				  USING btree
				  (val_number);");

	$dbh->Query("CREATE INDEX tmp_lrg_idx_test_ts_idx
				  ON tmp_lrg_idx_test
				  USING btree
				  (val_timestamp);");

	$dbh->Query("CREATE INDEX tmp_lrg_idx_test_mval_idx
				  ON tmp_lrg_idx_test
				  USING GIN
				  (val_multi);");

	$dbh->Query("CREATE INDEX tmp_lrg_idx_many_test_obj_idx
				  ON tmp_lrg_idx_many_test
				  USING btree
				  (ref_obj);");

	$dbh->Query("CREATE INDEX tmp_lrg_idx_many_test_val_idx
				  ON tmp_lrg_idx_many_test
				  USING btree
				  (int_val);");

	// Outer iterator 100X100k = 10 million records
	echo "Inserting ".number_format(1000*100*100, 0)." records:\n";
	for ($j = 0; $j < 100; $j++)
	{
		// Insert 100k entries and test
		$num = 1000*100;
		for ($i = 0; $i < $num; $i++)
		{
			//$dbh->Query("insert into tmp_lrg_idx_test(obj_id, val_number, val_text, val_timestamp) values('".$i."', '$i', 'Text $i', 'now')");
			$start = rand(1, 500);
			$dbh->Query("INSERT INTO tmp_lrg_idx_test(obj_id, val_multi) 
						 VALUES('$i', '{".$start.",".($start-1).",".($start-2).",".($start-3)."}')");

			$dbh->Query("
						INSERT INTO tmp_lrg_idx_many_test(ref_obj, int_val) VALUES('$i', '".$start."');
						INSERT INTO tmp_lrg_idx_many_test(ref_obj, int_val) VALUES('$i', '".($start-1)."');
						INSERT INTO tmp_lrg_idx_many_test(ref_obj, int_val) VALUES('$i', '".($start-2)."');
						INSERT INTO tmp_lrg_idx_many_test(ref_obj, int_val) VALUES('$i', '".($start-3)."');
						");
		}
		echo "\t".number_format(($j+1)*$num)." entered\n";
	}

	// Test queries
	// --------------------------------------------------------
	
	// Text
	echo "\n";
	$tStart = microtime(true);
	$dbh->Query("select obj_id from tmp_lrg_idx_test where val_text='Text 10000'");
	$tEnd = microtime(true);
	echo "Text Search:\t\t".($tEnd-$tStart)."\n";
	$tStart = microtime(true);
	$dbh->Query("select obj_id from tmp_lrg_idx_test where val_text like '%10000%'");
	$tEnd = microtime(true);
	echo "Text Like Search:\t".($tEnd-$tStart)."\n";

	// Number
	echo "\n";
	$tStart = microtime(true);
	$dbh->Query("select obj_id from tmp_lrg_idx_test where val_number='20000'");
	$tEnd = microtime(true);
	echo "Number Search:\t\t".($tEnd-$tStart)."\n";
	$tStart = microtime(true);
	$dbh->Query("select obj_id from tmp_lrg_idx_test where val_number>='20000' and val_number<='20500'");
	$tEnd = microtime(true);
	echo "Number Range Search:\t".($tEnd-$tStart)."\n";

	// Timestamp
	/*
	$tStart = microtime(true);
	$dbh->Query("select obj_id from tmp_lrg_idx_test where val_number='20000'");
	$tEnd = microtime(true);
	echo "Number Search:\t\t".($tEnd-$tStart)."\n";
	 */
	
	// Cleanup
	$dbh->Query("DROP TABLE tmp_lrg_idx_test;");
	 */
	
	echo "[done]\n";
?>
