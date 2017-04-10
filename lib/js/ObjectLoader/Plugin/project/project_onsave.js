{
	name:"onSaveHooks",
	title:"On Save Hooks",
	mainObject:null,

	main:function(con)
	{
		this.m_con = con;
	},

	save:function()
	{
		if (!this.mainObject.id)
		{
			this.onsave();
			return;
		}
        
		if (this.mainObject.id)
		{
			var args = [["project_id", this.mainObject.id], 
						["template_id", this.mainObject.getValue("template_id")], 
						["date_completed", this.mainObject.getValue("date_completed")]];
            
            ajax = new CAjax('json');
            ajax.cbData.cls = this;
            ajax.onload = function(ret)
            {
                this.cbData.cls.onsave();
            };
            ajax.exec("/controller/Project/saveMembers", args);
		}
		else
			this.onsave();
	},

	onsave:function()
	{
	},

	objectsaved:function()
	{
	},

	load:function()
	{
		
	}
}
