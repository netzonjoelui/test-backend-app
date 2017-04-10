<?php
/**
 * This file is included from /lib/Ant.php and used in Ant::schemaCreate
 *
 * It is imeperative thay any and ALL schema changes be entered both here
 * and in the schema updates. This is applied only for new accounts while
 * updates are used to bring old schema versions current to match this file.
 */

/**
 * Global variable is required. Must be incremented with every change to keep
 * scripts in ./updates from running if not needed
 */
$schema_version = "3.1.147";

$schema = include(dirname(__FILE__).'/schema.php');