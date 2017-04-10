<fieldset name='Status &amp; Admin'>
    <field name='name'></field>
    <field name='priority_id'></field>
    <field name='type_id'></field>
    <field name='status_id'></field>
    <field name='cost_estimated'></field>
    <field name='cost_actual'></field>
    <field name='project_id'></field>
    <field name='milestone_id'></field>
    <field name='owner_id'></field>
    <field name='ts_updated'></field>
    <field name='ts_entered'></field>
    <all_additional></all_additional>
</fieldset>
<fieldset name='Attachments'>
    <attachments></attachments>
</fieldset>
<fieldset name='Description'>
    <field name='notes' hidelabel='t' multiline='t'></field>
</fieldset>
<objectsref name='Tasks' obj_type='task' ref_field='story_id'></objectsref>
<fieldset name='Comments'>
    <field name='comments'></field>
</fieldset>
