This directory contains all scripts for creating and updating the schema of
each ANT account database and the antsystem database.

Philosophy: Managing schemas has two main functions: (1) create the initial schema and (2) apply updates appropriately. The main idea behind our schema management strategy is that the data in ./create.php and ./data/* should always be the most current and the queries/scripts in the ./updates and ./always directories should be used to catch existing databases up with the ./create.php and ./data/* scripts.

All versioned updates to schemas are found in the ./updates directory. 
The initial schema, including the latest schema revision number is found in ./create.php
And scripts in the ./always directory will run every time schema_updates.php is run.


# TO UPDATE ANT ACCOUNT DATABASE
#############################################################

Note: If you alter, delete, or create any tables be sure and update the ./create.php schema to reflect the change and set the schema revision at the top to match the schema revision of the update script (below). In addition any default data that has been loaded or added to the system must also be modifed in ./data/tablename.php so that the initial import of the database is the most recent dataset.

Applying updates to an ANT schema is very simple. We use a three part revision number [major].[minor].[point]. This version string is mapped to the file system in ./updates. So an update the the schema that included renaming a column could be found in ./updates/1/1/2.sql. The system, upon running the query will update the revision of the schema locally to "1.1.2" and next time schema_update is run, it will skip over that file and look for 1.1.3.sql.

To add an update simply go to ./updates, selected the latest major directory (largest number), then the latest minor directory (largest number), locate the last update (largest number.sql) and then make a new file with the next incremental number and place your query in that file.

For instance, if the last file in ./updates/1/1/ was 14.sql, then you would make a new file ./updates/1/1/15.sql.


# TO UPDATE create.php SCRIPT
#############################################################

The create.php script is used to build all tables. We define the tables in our own custom data structure.

Here is an example:

// The name of the table is the association name. In this case, the table will be called 'customers'
$schema['customers'] = array(
	"COLUMNS" => array(
		'id'			=> array('type'=>'bigint', 'default'=>'auto_increment'), // auto_increment will create a sequence for this field
		'time_changed'	=> array('type'=>'timestamp with time zone'),
		'email_default'	=> array('type'=>'character varying(16)'),
		'birthday_spouse'=> array('type'=>'date'),
		'notes'			=> array('type'=>'text'),
		'type_id'		=> array('type'=>'integer'),
		'stage_id'		=> array('type'=>'integer'),
		'user_id'		=> array('type'=>'integer'),
	),
	'PRIMARY_KEY'		=> 'id',	// Primay key can be an array if more than one column
	"KEYS" => array(
		'stage_id'		=> array("INDEX", "stage_id", "customer_types", "id"), 	
		'type_id'		=> array("INDEX", "type_id", "customer_types", "id"),					 
		'user_id'		=> array("INDEX", "user_id", "customer_types", "id"),					 
	)																							
);

KEYS: 'key/index name' => array(definition)

//	key definition
0: type - can be "INDEX", "UNIQUE" or "FKEY"
	Both INDEX and UNIQUE can also be FKEYS by defining referenced (described below) but are indexed
	FKEY is not indexed but maintains a foreign key
1: column - the name of the column
2: optional referenced table
3: referenced table column (if referened table is defined)

# Directory Layout
#############################################################

./updates = all versioned updates including *.sql and *.php files needed to bring old versions current with ./create.php and ./data/* scripts

./always = queries and routines (.php) that run every time schema_updates.php is run.

./data = Default data loaded initially into newly created accounts

./legacy = old schema update manager used to bring legacy accounts to schema version 3.1.1 which is when we started using the new system.

./antsystem - a create.php and an updates.php script used to manage the antsystem database.
