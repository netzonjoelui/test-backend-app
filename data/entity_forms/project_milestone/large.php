<row>
    <column>
        <tabs>
            <tab name='General'>
                <row>
                    <column>
                        <field name='name'></field>
                    </column>
                </row>
                <row>
                    <column type='quarter'>
                        <field name='date_start'></field>
                        <field name='deadline'></field>
                        <field name='project_id'></field>
                        <field name='user_id'></field>
                        <field name='f_completed'></field>
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

            <tab name='Stories'>
                <objectsref name='Sprint Stories' obj_type='project_story' ref_field='milestone_id'></objectsref>
            </tab>

            <tab name='Tasks'>
                <objectsref name='Tasks' obj_type='task' ref_field='milestone_id'></objectsref>
            </tab>

            <tab name='Discussions'>
                <objectsref obj_type='discussion'></objectsref>
            </tab>
        </tabs>
    </column>
</row>