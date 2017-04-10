<column>
    <field name='name' class="headline"></field>
</column>
<column>
    <field name='ts_start' part='date' label='When'></field>
</column>
<column showif='all_day=f'>
    <field name='ts_start' part='time' hidelabel='t'></field>
</column>
<column>
    <field name='ts_end' part='time' hidelabel='t'></field>
</column>
<column>
    <field name='all_day'></field>
</column>
<column>
    <recurrence></recurrence>
</column>
<column>
    <field name='location'></field>
</column>
<column>
    <field name='sharing'></field>
</column>
<column>
    <all_additional></all_additional>
</column>
<column>
    <field name='notes' hidelabel='t' multiline='t'></field>
</column>
<column>
    <field name='comments'></field>
</column>