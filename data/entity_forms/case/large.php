<row>
    <column>
        <tabs>
            <tab name='General'>
                <row>
                    <column>
                        <field name='title' hidelabel="t" class='headline'></field>
                    </column>
                </row>
                <row>
                    <column>
                        <field name='severity_id'></field>
                        <field name='type_id'></field>
                        <field name='owner_id'></field>
                    </column>
                    <column>
                        <field name='project_id'></field>
                        <field name='status_id'></field>
                        <field name='customer_id'></field>
                        <field name='ts_entered'></field>
                    </column>
                </row>
                <row>
                    <all_additional></all_additional>
                </row>
                <row>
                    <column>
                        <attachments></attachments>
                    </column>
                </row>
                <row>
                    <column>
                        <field name='description' hidelabel='t' multiline='t'></field>
                    </column>
                </row>
                <row>
                    <column>
                        <field name='comments'></field>
                    </column>
                </row>
            </tab>
            <tab name='Activity'>
                <row>
                    <column>
                        <field name='activity'></field>
                    </column>
                </row>
            </tab>
            <tab name='Tasks'>
                <row>
                    <column>
                        <objectsref obj_type='task' ref_field='case_id'></objectsref>
                    </column>
                </row>
            </tab>
            <tab name='Events'>
                <row>
                    <column>
                        <objectsref obj_type='calendar_event'></objectsref>
                    </column>
                </row>
            </tab>
        </tabs>
    </column>
</row>