<?php
	// Populate owner id

	// email_messages
	$result = $dbh_acc->Query("select email_users.user_id, email_threads.id
								FROM
								email_threads, email_mailboxes, email_users
								WHERE
								email_threads.owner_id is null
								and email_threads.mailbox_id=email_mailboxes.id
								and email_mailboxes.email_user=email_users.id");
	$num = $dbh_acc->GetNumberRows($result);
	for ($i = 0; $i < $num; $i++)
	{
		$row = $dbh_acc->GetRow($result, $i);
		$dbh_acc->Query("update email_threads set owner_id='".$row['user_id']."' where id='".$row['id']."';");
	}


	// email_messages
	$result = $dbh_acc->Query("select email_users.user_id, email_messages.id
								FROM
								email_messages, email_mailboxes, email_users
								WHERE
								email_messages.owner_id is null
								and email_messages.mailbox_id=email_mailboxes.id
								and email_mailboxes.email_user=email_users.id");
	$num = $dbh_acc->GetNumberRows($result);
	for ($i = 0; $i < $num; $i++)
	{
		$row = $dbh_acc->GetRow($result, $i);
		$dbh_acc->Query("update email_messages set owner_id='".$row['user_id']."' where id='".$row['id']."';");
	}
?>
