<?php
// Store permission name in then entries table now - reducing joins
if (!$dbh->ColumnExists("security_acle", "pname"))
{
	$dbh->Query("ALTER TABLE security_acle ADD COLUMN pname character varying(128);");
	$dbh->Query("update security_acle set pname=(select security_aclp.name from security_aclp where security_aclp.id=security_acle.aclp_id);");
}

if (!$dbh->ColumnExists("security_acle", "dacl_id"))
{
	$dbh->Query("ALTER TABLE security_acle ADD COLUMN dacl_id bigint;");
	$dbh->Query("update security_acle set dacl_id=(select security_aclp.dacl_id from security_aclp where security_aclp.id=security_acle.aclp_id);");
}
