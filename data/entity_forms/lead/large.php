<row>
    <column>
        <tabs>
            <tab name='General'>
                <row>
                    <column>
                        <field name='status_id'></field>
                        <field name='source_id'></field>
                        <field name='owner_id'></field>
                    </column>
                    <column>
                        <field name='f_converted'></field>
                        <field name='rating_id'></field>
                        <field name='campaign_id'></field>
                    </column>
                    <column>
                        <field name='converted_opportunity_id'></field>
                        <field name='converted_customer_id'></field>
                    </column>
                </row>
                <row>
                    <column>
                        <field name='first_name'></field>
                        <field name='last_name'></field>
                        <field name='company'></field>
                        <field name='title'></field>
                        <field name='website'></field>
                    </column>
                    <column>
                        <field name='email'></field>
                        <field name='phone'></field>
                        <field name='phone2'></field>
                        <field name='phone3'></field>
                        <field name='fax'></field>
                    </column>
                </row>
                <row>
                    <column>
                        <field name='street'></field>
                        <field name='zip'></field>
                        <field name='city'></field>
                    </column>
                    <column>
                        <field name='street2'></field>
                        <field name='state'></field>
                        <field name='country'></field>
                    </column>
                </row>
                <row>
                    <column>
                        <field name='notes' hidelabel='t' multiline='t'></field>
                    </column>
                </row>
            </tab>

            <tab name='Task'>
                <objectsref obj_type='task' ref_field='customer_id'></objectsref>
            </tab>

            <tab name='Events'>
                <objectsref obj_type='calendar_event' ref_field='customer_id'></objectsref>
            </tab>
        </tabs>
    </column>
    <column type="sidebar">
        <header>Activity</header>
        <row>
            <field name='activity'></field>
        </row>
    </column>
</row>