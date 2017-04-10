<column showif='id=null'>
    <field name='type_id'></field>
</column>
<column showif='type_id=1'>
    <field name='first_name'></field>
    <field name='last_name'></field>
    <field name='spouse_name'></field>
    <field ref_field='type_id' ref_value='2' name='primary_account'></field>
    <field name='job_title'></field>
    <field name='salutation'></field>
</column>
<column showif='type_id=2'>
    <field name='name'></field>
    <field ref_field='type_id' ref_value='1' name='primary_contact'></field>
</column>
<column>
    <field name='notes' multiline='t'></field>
</column>
<column>
    <field hidelabel='t' name='activity'></field>
</column>