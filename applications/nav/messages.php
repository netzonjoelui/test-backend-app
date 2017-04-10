<navigation default="inbox">
	<section title='Messages'>
		<item type='object' name='new_email' title='Compose New Email' obj_type='email_message' icon="/images/icons/email_compose_16.png" />
		<item type='wizard' name='bulk_wizard' title='Send Mass Email' wiz_type='EmailCampaign' icon="/images/icons/email_bulk_16.png" />
	</section>
	<section title=' '>
		<item type='browse' title="Inbox" name='inbox' obj_type='email_thread' icon="/images/icons/email_16.png" viewmode="preview">
			<filter>
				<condition blogic='and' field='owner_id' operator='is_equal' value='-3' />
				<condition blogic='and' field='mailbox_id' operator='is_equal' value='Inbox' />
			</filter>
		</item>
		<item type='browse' name='flagged' title='Starred Messages' obj_type='email_thread' icon="/images/icons/flag_16.png">
			<filter>
				<condition blogic='and' field='owner_id' operator='is_equal' value='-3' />
				<condition blogic='and' field='f_flagged' operator='is_equal' value='t' />
			</filter>
		</item>
		<item type='browse' title="Drafts" name='drafts' obj_type='email_message' icon="/images/icons/draft_16.png" viewmode="preview">
			<filter>
				<condition blogic='and' field='owner_id' operator='is_equal' value='-3' />
				<condition blogic='and' field='flag_draft' operator='is_equal' value='t' />
				<condition blogic='or' field='mailbox_id' operator='is_equal' value='Drafts' />
			</filter>
		</item>
		<item type='browse' title="Sent" name='sent' obj_type='email_thread' icon="/images/icons/sent_16.png" viewmode="preview">
			<filter>
				<condition blogic='and' field='owner_id' operator='is_equal' value='-3' />
				<condition blogic='and' field='mailbox_id' operator='is_equal' value='Sent' />
			</filter>
		</item>
		<item type='browse' title="Deleted Items" name='trash' obj_type='email_thread' icon="/images/icons/trash_16.png" viewmode="preview">
			<filter>
				<condition blogic='and' field='owner_id' operator='is_equal' value='-3' />
				<condition blogic='and' field='f_deleted' operator='is_equal' value='t' />
			</filter>
		</item>
		<item type='browse' title="Junk Mail" name='junk' obj_type='email_thread' icon="/images/icons/junk_16.png" viewmode="preview">
			<filter>
				<condition blogic='and' field='owner_id' operator='is_equal' value='-3' />
				<condition blogic='and' field='mailbox_id' operator='is_equal' value='Junk Mail' />
			</filter>
		</item>
		<item type='browse' name='notification' title='Notifications' obj_type='notification' icon="/images/icons/notification_16.png">
			<filter>
				<condition blogic='and' field='owner_id' operator='is_equal' value='-3' />
			</filter>
		</item>
		<item type='browse' name='approvals' title='Approval Requests' obj_type='approval' icon="/images/icons/approval_16.png">
			<filter>
				<condition blogic='and' field='owner_id' operator='is_equal' value='-3' />
			</filter>
		</item>
	</section>
	<section title=' '>
		<item type='browse' title="All Mail" name='all-messages' obj_type='email_thread' browseby='mailbox_id' icon="/images/icons/folder_16.png" viewmode="preview">
			<filter>
				<condition blogic='and' field='owner_id' operator='is_equal' value='-3' />
			</filter>
		</item>
	</section>
</navigation>
