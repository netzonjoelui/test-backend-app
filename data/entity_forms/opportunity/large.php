<row>
    <column>
        <tabs>
            <tab name='General'>
                <row>
                    <column>
                        <field name='name' hidelabel="t" class='headline'></field>
                        <field name='customer_id'></field>
                    </column>
                </row>
                <row>
                    <column>
                        <field name='amount'></field>
                        <field name='probability_per'></field>
                    </column>
                    <column>
                        <field name='expected_close_date'></field>
                        <field name='objection_id'></field>
                    </column>
                </row>
                <row>
                    <column>
                        <field name='notes' hidelabel='t' multiline='t'></field>
                    </column>
                </row>
                <row>
                    <column>
                        <field name='comments'></field>
                    </column>
                </row>
            </tab>

            <tab name='Activity'>
                <field name='activity'></field>
            </tab>

            <tab name='Task'>
                <objectsref obj_type='task' ref_field='customer_id'></objectsref>
            </tab>

            <tab name='Events'>
                <objectsref obj_type='calendar_event'></objectsref>
            </tab>

            <tab name='Files'>
                <attachments></attachments>
            </tab>
        </tabs>
    </column>
    <column type="sidebar">
        <row>
            <column>
                <field name='stage_id'></field>
                <field name='lead_source_id'></field>
                <field name='type_id'></field>
                <field name='campaign_id'></field>
                <field name='owner_id'></field>
                <field name='ts_entered'></field>
                <field name='ts_updated'></field>
            </column>
        </row>
    </column>
</row>