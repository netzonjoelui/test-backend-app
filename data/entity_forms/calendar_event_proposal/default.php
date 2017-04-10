<column>
	<fieldset name='Details'>
		<row>
			<field name='name'></field>
		</row>
		<row>
			<field name='location'></field>
		</row>
		<row>
			<all_additional></all_additional>
		</row>
		<row>
			<field name='notes' multiline='t'></field>
		</row>
	</fieldset>
	<row>
		<fieldset name='Date / Time Options and Availability'>
			<plugin name='availability'></plugin>
		</fieldset>
	</row>
	<row>
		<fieldset name='Comments'>
			<field name='comments'></field>
		</fieldset>
	</row>

</column>
<column width='250px'>        
	<fieldset name='Attendees'>
		<members field='attendees'></members>
	</fieldset>
</column>
<helptour id='objects/calendar_event_proposal/1-introduction' type='dialog' />
