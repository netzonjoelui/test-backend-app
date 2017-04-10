/**
 * The presence plugin is used to manage universal presenece
 */
{
	name:"event_presence",
	title:"Event Presence",
	mainObject:null,

	main:function(con)
	{
		var sel = alib.dom.createElement("select", con);

		sel[sel.length] = new Option("Available", "a", false, true);
		sel[sel.length] = new Option("Busy", "b", false, false);
	},

	save:function()
	{
		this.onsave();
	},

	onsave:function()
	{
	},

	objectsaved:function()
	{
	}
}
