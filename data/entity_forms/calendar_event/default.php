<tab name='General'>
    <column>
        <fieldset name='Details'>
            <row>
                <field name='name'></field>
            </row>
            <row>
                <column style='padding-left:50px'  width='170px'>
                    <field name='ts_start' part='date' label='When'></field>
                </column>
                <column  showif='all_day=f' style='padding-left:0px' width='50px'>
                    <field name='ts_start' part='time' hidelabel='t'></field>
                </column>
                <column style='padding-left:0px'  width='170px'>
                    <field name='ts_end' part='date' label='To'></field>
                </column>
                <column showif='all_day=f' style='padding-left:0px'  width='50px'>
                    <field name='ts_end' part='time' hidelabel='t'></field>
                </column>
                <column style='padding-left:0px'>
                    <field name='all_day'></field>
                </column>
            </row>
			<row>
                <recurrence></recurrence>
			</row>
            <row>
                <field name='location'></field>
            </row>
            <row>
				<column width='95px'><label>Calendar</label></column>
				<column width='150px'><plugin name='calendar_sel'></plugin></column>
				<column width='95px'><label>This event is</label></column>
				<column><field name='sharing' hidelabel='t'></field></column>
			</row>
            <row>
                <all_additional></all_additional>
            </row>
        </fieldset>
        <fieldset name='Reminders'>
			<reminders field_name='ts_start' add_default='t' />
        </fieldset>
        <row>
            <fieldset name='Description'>
                <field name='notes' hidelabel='t' multiline='t'></field>
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
		<fieldset name='References'>
			<field name='customer_id' hidelabel='f'></field>
			<field name='contact_id' hidelabel='f'></field>
		</fieldset>
    </column>
</tab>
<tab name='Activity'>
    <field name='activity'></field>
</tab>
<plugin name='event_timevalidator'></plugin>
