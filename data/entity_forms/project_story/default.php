<tab name='General'>
    <fieldset name='Status &amp; Admin'>
        <row>
            <column>
                <field name='name'></field>
            </column>
        </row>
        <row>
            <column>
                <field name='priority_id'></field>
                <field name='type_id'></field>
                <field name='status_id'></field>
                <field name='cost_estimated'></field>
                <field name='cost_actual'></field>
            </column>
            <column>
                <field name='project_id'></field>
                <field name='milestone_id'></field>
                <field name='owner_id'></field>
                <field name='ts_updated'></field>
                <field name='ts_entered'></field>
            </column>
        </row>
        <row>
            <all_additional></all_additional>
        </row>
    </fieldset>
    <row>
        <fieldset name='Attachments'>
            <attachments></attachments>
        </fieldset>
    </row>
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
</tab>

<tab name='Activity'>
    <field name='activity'></field>
</tab>

<tab name='Tasks &amp; Events'>
    <fieldset name='Tasks'>
        <objectsref obj_type='task' ref_field='story_id'></objectsref>
    </fieldset>
</tab>
