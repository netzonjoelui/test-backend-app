/**
* @fileOverview This is a select input used for selecting an object type
*/

/**
 * Class constructor
 */
function AntObjectTypeSel(value)
{
	/**
	 * Current value
	 *
	 * @var {string}
	 */
	this.value = value || "";

	/**
	 * Data object used for callback
	 *
	 * @var {Object}
	 */
	this.cbData = new Object();
}

/**
 * Callback fired when the user selects an object type
 *
 * @public
 * @param {string} objType Name of the object type selected
 */
AntObjectTypeSel.prototype.onchange = function(objType)
{
}

/**
 * Get value method
 *
 * @public
 */
AntObjectTypeSel.prototype.getValue = function()
{
	return this.value;
}


/**
 * Render the select button in the dom tree
 *
 * @public
 * @param {DOMElement} con The container where the select dropdown will be printed
 */
AntObjectTypeSel.prototype.render = function(con)
{
	var dmcon = new CDropdownMenu();
	var dcon = dmcon.addCon();
	dcon.onclick = function() { };
	var in_con = alib.dom.createElement("div", dcon);
	alib.dom.styleSet(in_con, "padding-left", "5px");
	alib.dom.styleSet(in_con, "width", "180px");
	alib.dom.styleSet(in_con, "max-height", "300px");
	alib.dom.styleSet(in_con, "overflow", "auto");

	var funct = function(in_con, dropDown, cls)
	{
		cls.loadTypes(in_con, dropDown);
	}
	con.appendChild(dmcon.createButtonMenu("Select", funct, [in_con, dmcon, this], "b1"));
}

/**
 * Load object types
 *
 * @private
 */
AntObjectTypeSel.prototype.loadTypes = function(con, dropDown)
{
	con.innerHTML = "<div class='loading'></div>";

	var ajax = new CAjax('json');
    ajax.cbData.cls = this;    
    ajax.cbData.con = con;
    ajax.cbData.dropDown = dropDown;
    ajax.onload = function(ret)
    {
		this.cbData.con.innerHTML = "";

		for (var i in ret)
		{
			var row = alib.dom.createElement("div", this.cbData.con);
			alib.dom.styleSet(row, "padding", "5px 0 5px 0");
			alib.dom.styleSet(row, "cursor", "pointer");

			row.innerHTML = ret[i].title;
			row.otype = ret[i].name;
			row.cbData = this.cbData;
			row.onclick = function() {
				this.cbData.cls.onchange(this.otype);
				this.cbData.dropDown.unloadMe();
			}
		}
    };
    
	ajax.exec("/controller/Object/getObjects");
}
