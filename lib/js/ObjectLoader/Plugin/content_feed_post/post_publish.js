{
	name:"feed_post_publish",
	title:"Post Publisher",
	mainObject:null,

	main:function(con)
	{
		this.m_con = con;
	},

	save:function()
	{
		if (this.mainObject.getValue('feed_id') && this.mainObject.getValue('f_publish')!='f')
		{
            ajax = new CAjax('json');
            ajax.cbData.cls = this;
            ajax.onload = function(ret)
            {
                this.cbData.cls.onsave();
            };
            ajax.exec("/controller/Content/feedPostPublish",
                        [["fid", this.mainObject.getValue('feed_id')]]);
		}
		else
		{
			this.onsave();
		}
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
