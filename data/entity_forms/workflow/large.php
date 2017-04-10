<row>
    <column>
        <helptour id="help.entity.form.workflow.intro" type="inline">
            Workflows automate both simple and complex tasks like 'if a lead gets updated by someone other than the owner, send the owner an email notifying them of the change' or 'if an opportunity is older than 30 days and has not been contacted, email a manager.' This form will guide you through the steps needed to create powerful automated workflows.
        </helptour>
    </column>
</row>
<row>
    <column>
        <field name='name' class='headline' hidelabel='t'></field>
    </column>
</row>
<row>
    <column>
        <field name='notes' hidelabel='t' multiline='t'></field>
    </column>
</row>
<row>
    <column>
        <field name='object_type'></field>
    </column>
</row>
<row>
    <column>
        <field name='f_active'></field>
    </column>
</row>
<row>
    <column>
        <helptour id="help.entity.form.workflow.conditions" type="inline">
            Use the form below to set any conditions required in order for this workflow to start. For example: if you only want a workflow to launch if a sales opportunity status was changed from 'Open' to 'Lost,' then you would add a condition below ['And' 'Status' 'is equal' 'Lost'] which would make sure the workflow only starts when those conditions are met. You can add multiple conditions if desired. If this workflow should launch on all objects without condition then leave this blank and continue below.
        </helptour>
        <workflow />
    </column>
</row>
