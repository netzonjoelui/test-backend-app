/**
 * @fileoverview This class handles validating field inputs
 *
 * @author 	joe, sky.stebnicki@aereus.com.
 * 			Copyright (c) 2011-2013 Aereus Corporation. All rights reserved.
 */

/**
 * Creates an instance of AntObject_FieldValidator
 *
 * @constructor
 * @param {string} name The name of the validator
 * @param {AntObject_FieldInput} The input we are validating against
 */
function AntObject_FieldValidator(name, fieldInput)
{
	/**
	 * Validator name to run
	 *
	 * @public
	 * @var {string}
	 */
	this.name = name;

	/**
	 * Input reference
	 *
	 * @var {AntObject_FieldInput}
	 */
	this.fieldInput = fieldInput;

	/**
	 * Generic callback data object
	 *
	 * @var {Object}
	 */
	this.cbData = new Object();
}

/**
 * Run validation
 */
AntObject_FieldValidator.prototype.validate = function(value) 
{
	switch (this.name)
	{
	case "username":

		// Create please wait div to keep user from saving before validated
		var dlg = new CDialog();
		var dv_load = alib.dom.createElement('div');
		alib.dom.styleSetClass(dv_load, "statusAlert");
		alib.dom.styleSet(dv_load, "text-align", "center");
		dv_load.innerHTML = "Checking values, please wait...";
		dlg.statusDialog(dv_load, 150, 100);

		var ajax = new CAjax('json');
		ajax.cbData.cls = this;    
		ajax.cbData.dlg = dlg;
		ajax.onload = function(ret)
		{
			dlg.hide();
			if (ret == 1)
				this.cbData.cls.onValid();
			else
				this.cbData.cls.onInvalid(ret);
		};
		ajax.exec("/controller/User/checkUserName", [["name", value], ["uid", this.fieldInput.obj.id]]);
		break;
	default:
		this.onValid();
		break;
	}
}

/**
 * Callback called when the field is validated successfully
 */
AntObject_FieldValidator.prototype.onValid = function() { }

/**
 * Callback called when the field is invalid
 */
AntObject_FieldValidator.prototype.onInvalid = function(message) { }
