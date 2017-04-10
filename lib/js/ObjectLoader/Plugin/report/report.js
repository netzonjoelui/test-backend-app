/**
 * This plugin will handle rendering reports into an object form
 *
 * TODO: this is a work in progress. Currently the report loader is still being used.
 */
{
	/**
	 * The unique name of this plugin
	 *
	 * @var {string}
	 */
	name:"report",

	/**
	 * The human title of this plugin
	 *
	 * @var {string}
	 */
	title:"Report Editor",

	/**
	 * Reference to the main object from the loader
	 *
	 * @var {CAntObject}
	 */
	mainObject:null,

	/**
	 * Object loader class reference set when instantiatied
	 *
	 * @var {AntObjectLoader}
	 */
	olCls:null,

	/**
	 * Object loader form
	 *
	 * @var {AntObjectLoader_Form}
	 */
	formObj: null,

	/**
	 * Main plugin entry function called when loaded
	 *
	 * @param {DOMElement} con The container to print the plugin into
	 */
	main:function(con)
	{
		con.innerHTML = "Print report here";
	},

	/**
	 * Local callback function is required. It must be called when the save operation for this plugin is completed.
	 *
	 * WARNING: If this function is not called by 'save' above, it will cause the application to hang. Make sure it is always called.
	 */
	save:function()
	{
		this.onsave();
	},

	/**
	 * Local callback function is required. It must be called when the save operation for this plugin is completed.
	 *
	 * WARNING: If this function is not called by 'save' above, it will cause the application to hang. Make sure it is always called!
	 */
	onsave:function()
	{
	},

	/**
	 * This function is called by the object form every time the object has been saved
	 */
	objectsaved:function()
	{
	},

	/**
	 * This function is called by the object form every time any field is updated in the mainObject.
	 */
	onMainObjectValueChange:function(fname, fvalue, fkeyName)
	{
	},
    
    /**
     * This function is called by the object form every time the edit mode is changed
     */
    onMainObjectToggleEdit:function(fname, fvalue, fkeyName)
    {
    },
}
