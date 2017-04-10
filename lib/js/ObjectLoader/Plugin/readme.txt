Form Plugins

This directory holds all system plugins used by object forms. Each plugin must be in a subdirectory named after the object type. For instance, a new plugin for priting an email can be found in /objects/fplugins/email_message/print.js. All plugins must have an *.js postfix.

SAMPLE PLUGIN
/////////////////////////////////////

Plugins are defined as objects in the .js file. For instance, the most basic non-functional pluging would look like this:

{
	/**
	 * The unique name of this plugin
	 *
	 * @var {string}
	 */
	name:"report_filter",

	/**
	 * The human title of this plugin
	 *
	 * @var {string}
	 */
	title:"Filters",

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
		con.innerHTML = "Print filter here";
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
    }
}

INTERFACE FUNCTIONS
/////////////////////////////////////
