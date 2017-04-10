<?php
	// Get recipients
	// --------------------------------------------------------------------------------
	$to_recipients = array();

	$ALIB_CACHE_DISABLE = true; // disable caching

	if (is_array($data['objects']) && count($data['objects']) && $data['obj_type'])
	{
		foreach ($data['objects'] as $oid)
		{
			$obj = new CAntObject($dbh, $data['obj_type'], $oid);
			if (is_array($data['using']))
			{
				foreach ($data['using'] as $use)
				{
					$fNoBulkMail = 'f';

					if ($data['obj_type'] == "customer")
						$fNoBulkMail = $obj->getValue("f_noemailspam");

					$add_to = $obj->getValue($use, true);

					if ($add_to && strpos($add_to, "@")!==false && $fNoBulkMail!='t')
					{
						// Check for bulk handling
						if ($data['send_method'] == 1)
						{
							$to_recipients[$oid] .= ($to_recipients[$oid]) ? ", $add_to" : $add_to;
						}
						else
						{
							$to_recipients[0] .= ($to_recipients[0]) ? ", $add_to" : $add_to;
						}
					}
				}
			}
		}
		
		$select_from = "object";
	}
	else if ($data['send_method'] == 1 && $data['obj_type'])
	{
		$olist = new CAntObjectList($dbh, $data['obj_type'], $USER);
		$olist->processFormConditions($data);
		$olist->getObjects();
		$num = $olist->getNumObjects();
		for ($i = 0; $i < $num; $i++)
		{
			$obj = $olist->getObject($i);
			$oid = $obj->id;

			if (is_array($data['using']))
			{
				foreach ($data['using'] as $use)
				{
					$fNoBulkMail = 'f';

					if ($data['obj_type'] == "customer")
						$fNoBulkMail = $obj->getValue("f_noemailspam");

					$add_to = $obj->getValue($use, true);

					if ($add_to && strpos($add_to, "@")!==false && $fNoBulkMail!='t')
					{
						// Check for bulk handling
						if ($data['send_method'] == 1)
						{
							$to_recipients[$oid] .= ($to_recipients[$oid]) ? ", $add_to" : $add_to;
						}
						else
						{
							$to_recipients[0] .= ($to_recipients[0]) ? ", $add_to" : $add_to;
						}
					}
				}
			}

			$olist->unsetObject($i);
		}

		$select_from = "object";
	}
	else
	{
		if (strpos($data['cmp_to'], ';') !== false)
			$to_recipients[0] = str_replace(';', ',', $data['cmp_to']);
		else
			$to_recipients[0] = $data['cmp_to'];

		//$to_recipients[0] = EmailCleanAddressList($to_recipients[0]);
		$to_recipients[0] = EmailProcessAddressList($dbh, $USERID, $to_recipients[0]);
	}

	// Process invitations - mostly set permissions if needed
	// --------------------------------------------------------------------------------
	if ($data['calendar_share'])
	{
		$parts = explode(",", $to_recipients[0]);

		foreach ($parts as $addr)
		{
			$addr = EmailAdressGetDisplay($addr, 'address');
			$uid = UserGetUserIdFromAddress($dbh, $addr);
			if ($uid)
			{
				$DACL_CAL = new Dacl($dbh, "calendars/".$data['calendar_share'], true, $CAL_ACLS);
				$DACL_CAL->grantUserAccess($uid, "View Public Events");
			}
		}
	}
	if ($data['contact_group_share'])
	{
		$parts = explode(",", $to_recipients[0]);

		foreach ($parts as $addr)
		{
			$addr = EmailAdressGetDisplay($addr, 'address');
			$uid = UserGetUserIdFromAddress($dbh, $addr);
			if ($uid)
			{
				$DACL_CON_GRP = new Dacl($dbh, "contacts/groups/".$data['contact_group_share'], true, $CONTACT_GROUP_ACLS);
				$DACL_CON_GRP->grantUserAccess($uid, "View Contacts");
			}
		}
	}

	// Send email to each recipient
	// --------------------------------------------------------------------------------
	$CMPBODY = str_replace(" id='ant_signature'", '', $data['cmpbody']);
	foreach ($to_recipients as $DELID=>$DELTO)
	{
		$obj = null;
		$email = new CEmailMessage($dbh, null, $USERID, $ACCOUNT);
		$email->setHeader("Subject", $data['cmp_subject']);
		$email->setBody($CMPBODY, "html");

		// From
		$email->setHeader("From", EmailGetUserName(&$dbh, $USERID, 'full_rep', $data['use_account']));
		$email->setHeader("Reply-to", EmailGetUserName(&$dbh, $USERID, 'reply_to', $data['use_account']));

		// To
		$email->setHeader("To", EmailAdressGetDisplay($DELTO, 'address'));

		// Cc
		if ($data['cmp_cc'])
		{
			if (strpos($data['cmp_cc'], ';') !== false)
				$recipients["Cc"] = str_replace(';', ',', $data['cmp_cc']);
			else
				$recipients["Cc"] = $data['cmp_cc'];
			
			$email->setHeader("Cc", EmailProcessAddressList($dbh, $USERID, $recipients["Cc"]));
		}
		// Bcc
		if ($data['cmp_bcc'])
		{
			if (strpos($data['cmp_bcc'], ';') !== false)
				$recipients["Bcc"] = str_replace(';', ',', $data['cmp_bcc']);
			else
				$recipients["Bcc"] = $data['cmp_bcc'];
			
			$email->setHeader("Bcc", EmailProcessAddressList($dbh, $USERID, $recipients["Bcc"]));
		}

		// Set account and object relationships
		if ($data['calendar_share'])
			$email->setHeader("X-ANT-CAL-SHARE", $data['calendar_share']);
		if ($data['contact_group_share'])
			$email->setHeader("X-ANT-CON-GRP-SHARE", $data['contact_group_share']);

		// In Reply To
		if ($data['in_reply_to'])
			$email->setHeader("In-Reply-To", $data['in_reply_to']);

		// Populate template emails if send method is bulk		
		if ($data['send_method'] == 1)
		{
			if ($select_from == "object")
			{
				$obj = new CAntObject($dbh, $data['obj_type'], $DELID);
				$email->setMergeFields($obj);
			}
		}

		// Add attachments - userfiles
		if (is_array($data['uploaded_file']) && count($data['uploaded_file']))
		{
			foreach ($data['uploaded_file'] as $fid)
			{
				$email->addAttachmentTmp($fid);
			}
		}

		// Add email message attachments that are not yet in userfiles
		if (is_array($data['fid_attachments']) && count($data['fid_attachments']))
		{
			for ($i=0; $i<count($data['fid_attachments']); $i++)
			{
				$email->addAttachmentFwd($data['fid_attachments'][$i]);
			}
		}

		$email->f_saveSent = false; // Do not save a full copy of this message
		$finished = $email->send();
		if ($obj)
			$obj->addActivity("Bulk Email Sent: ".$email->getHeader("subject"), $email->getBody("plain"), null, 'o', 't', $USERID);

		sleep(1);
	}
?>
