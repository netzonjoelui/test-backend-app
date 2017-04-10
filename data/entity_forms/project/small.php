<field name='name' hidelabel="t" class='headline'></field>
<field name='priority'></field>
<field name='parent'
       tooltip='A project can be a child of much larger projects which allows for smaller teams working on massive projects. This is not a commonly used feature, few projects are of that scale; but if you find a project has too much noise from all the people and activity it might be helpful to split out subprojects and make smaller teams.'></field>
<field name='user_id'
       tooltip='Each project must have one responisble owner even though many members may be working on the project.'></field>
<field name='customer_id'></field>
<field name='date_started'></field>
<field name='date_deadline'
       tooltip='If no deadline is set, this will be considered an ongoing project.'></field>
<field name='date_completed'
       tooltip='Once the project has been completed, enter the date here.'></field>
<field name='groups' hidelabel='t'></field>
<field name='notes' multiline='t'></field>
<objectsref obj_type='task' ref_field='project'></objectsref>
<objectsref obj_type='project_story' ref_field='project_id'></objectsref>
<objectsref obj_type='case' ref_field='project_id'></objectsref>
<objectsref obj_type='discussion'></objectsref>
<attachments></attachments>
<field name='activity'></field>