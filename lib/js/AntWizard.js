/**
 * @fileoverview This class handles creating wizards in ANT including dynamically loading
 *
 * Example:
 * <code>
 * 	var ob = new AntObjectBrowser("customer");
 *	ob.print(document.body);
 * </code>
 *
 * @author 	joe, sky.stebnicki@aereus.com.
 * 			Copyright (c) 2011 Aereus Corporation. All rights reserved.
 */

/**
 * @constructor
 * @param {string} wizName Optional name of wizard to load dynamically from the /wizards directory
 * @param {Object} data Optional data object to pass to subclass
 */
function AntWizard(wizName, data)
{
	/**
	 * Sublcass of this wizard - actual wizard definition
	 *
	 * @var {AntWizard_}
	 */
	this.subclass = null;

	/**
	 * Array of steps
	 *
	 * @private
	 * @var {Array({title, callback})}
	 */
	this.steps = new Array();

	/**
	 * The next step
	 *
	 * @private
	 * @var {int}
	 */
	this.nStep = 0;

	/**
	 * Store the wizard name
	 *
	 * @private
	 * @var {string}
	 */
	this.wizardClassName = wizName;

	/**
	 * Title, may be set by subclass
	 *
	 * @public
	 * @var {string}
	 */
	this.title = "Wizard";

	/**
	 * Flag used to prevent showing the dialog until the subclass is loaded
	 *
	 * @private
	 * @var {bool}
	 */
	this.loading = false;

	/**
	 * Flag used to fire call this.show after the subclass has been loaded
	 *
	 * @private
	 * @var {bool}
	 */
	this.showOnLoad = false;

	/**
	 * Flag used to determin if a step is async processing and we should wait to move to next step
	 *
	 * @private
	 * @var {bool}
	 */
	this.processing = false;

	/**
	 * Flag set when processing is finished in async mode
	 *
	 * @private
	 * @var {bool}
	 */
	this.processingSuccess = true;

	/**
	 * Timer used for delayed processing
	 *
	 * @private
	 * @var {window timer}
	 */
	this.processingTimer = null;

	/**
	 * Property buffer for callback data
	 *
	 * @var {Object}
	 */
	this.cbData = data || new Object();
    
    /**
     * Flag set when displaying wizard path
     *
     * @private
     * @var {bool}
     */
    this.showPath = true;

	/**
	 * The width of this wizard in pixels
	 *
	 * @var {int}
	 */
	this.width = 820;

	// Load the wizard class
	if (wizName)
		this.loadSubclass(wizName);
}

/**
 * Callback called when the wizard is finished
 *
 * This should be over-ridden by the calling class/process
 *
 * @public
 */
AntWizard.prototype.onFinished = function() { }

/**
 * Callback called when the wizard is finished
 *
 * This should be over-ridden by the calling class/process
 *
 * @public
 */
AntWizard.prototype.onCancel = function() { }

/**
 * Add a step to this wizard instnace
 *
 * @param {callback} cb The callback function used to generate this step
 * @param {string} title The title of this step
 */
AntWizard.prototype.addStep = function(cb, title)
{
	var step = new Object();
	step.callback = cb;
	step.title = title;
	this.steps.push(step);
}

/**
 * Advance to the next step
 */
AntWizard.prototype.nextStep = function()
{
	this.showStep(this.nStep);
}

/**
 * Load the wizard subclass
 */
AntWizard.prototype.setWizardSubclass = function()
{
	this.subclass = eval("new AntWizard_" + this.wizardClassName + "();");
	this.subclass.setup(this);
	this.loading = false;

	if (this.showOnLoad)
	{
		this.showOnLoad = false;
		this.show(this.parentDlg);
	}
}

/**
 * Load a wizard
 *
 * @param string className = the name of the applet
 */
AntWizard.prototype.loadSubclass = function(className)
{
	var classParts = className.split("_"); // Nomenclature is [modulename]_[classname]

	// Module specific
	if (classParts.length == 2)
	{
		var moduleDir = classParts[0];
		var fileName = classParts[1];
	}

	// Global wizard
	if (classParts.length == 1)
	{
		var moduleDir = "";
		var fileName = classParts[0];
	}

	// Set plugin path
	var filepath = "/wizards";
	if (moduleDir)
		filepath += "/" + moduleDir;
	filepath += "/" + fileName + ".js";


	// Check if script is already loaded
	if (!document.getElementById("js_wiz_" + className))
	{
		// Set flag to prevent showing the dialog until it is loaded
		this.loading = true;

		// Load External file into this document
		var fileRef = document.createElement('script');
		fileRef.wizardClassName = className;
		fileRef.wizard = this;

		if (alib.userAgent.ie)
		{
			fileRef.onreadystatechange = function () 
			{ 
				if (this.readyState == "complete" || this.readyState == "loaded") 
				{
					this.wizard.setWizardSubclass();
				}
			};
		}
		else
		{
			fileRef.onload = function () 
			{ 
				this.wizard.setWizardSubclass();
			};
		}

		fileRef.type = "text/javascript";
		fileRef.id = "js_wiz_" + className;
		fileRef.src =  filepath;
		document.getElementsByTagName("head")[0].appendChild(fileRef);

	}
	else
	{
		this.setWizardSubclass();
	}
}

/**
 * Display the dialog for this wizard
 *
 * @param {Alib_Ui_Dialog} parentDlg Optional parent dialog for modal display
 */
AntWizard.prototype.show = function(parentDlg)
{
	this.parentDlg = (parentDlg) ? parentDlg : null;

	if (this.loading)
	{
		this.showOnLoad = true;
		return;
	}

	this.dlg = new CDialog(this.title, this.parentDlg);
	this.dlg.f_close = true;

	this.bodyDiv = alib.dom.createElement("div");

	this.dlg.customDialog(this.bodyDiv, this.width, 555);

	if (this.steps.length > 0)
		this.showStep(0);
}

/**
 * Hide this wizard
 *
 * @public
 */
AntWizard.prototype.hide = function()
{
	this.dlg.hide(); 
}

/**
 * @depricated
 * Cancel and close the wizard
 *
 * @public
AntWizard.prototype.cancel = function()
{
	var close = true;
	if (this.subclass.cancel)
	{
		close = this.subclass.cancel();
	}
	else
	{
		close = confirm("Are you sure you want to exit?");
	}

	if (close)
	{
		this.hide();
		this.onCancel();
	}
}
 */

/**
 * Print a step on the wizard canvas
 *
 * @param {int} step The index of the step to load
 */
AntWizard.prototype.showStep = function(step)
{
	if (!this.subclass)
		return false;

	// Handle delaye advancement if the step processing is waiting on an async process
	if (this.processing)
	{
		// Clear old time if exists
		if (this.processingTimer)
			window.clearTimeout(this.processingTimer);

		var advanceStep = step;
		var me = this;
		this.processingTimer = window.setTimeout(function() { me.showStep(advanceStep); }, 1000); // Check again in one second
		// TODO: set up a timeout
		return true;
	}
	else if (!this.processingSuccess)
	{
		// Last async processing failed
		return false;
	}

    var backStep = false;
    
    if(this.nStep > step) // If backsetp, we need to execute the subclass function for stepping back
        backStep = true;
    
	this.bodyDiv.innerHTML = ""; 
	this.nStep = step+1;

	// Clear subclass processStep and local async processing flags
	this.processing = false;
	this.processingSuccess = true;
    this.subclass.processStep = function() { return true; };
    
    // Process a subclass function if back step    
    if(backStep && typeof this.subclass.processBackStep == "function")
        this.subclass.processBackStep();
	
	// Path
	// ---------------------------------------------------------
	this.pathDiv = alib.dom.createElement("div", this.bodyDiv);
	this.pathDiv.innerHTML = "Step " + (step + 1) + " of " + this.steps.length + " - " + this.steps[step].title;
	alib.dom.styleSetClass(this.pathDiv, "wizardTitle");
    
    if(!this.showPath)
        alib.dom.styleSet(this.pathDiv, "display", "none");

	// Display Step
	// ---------------------------------------------------------
	var div_main = alib.dom.createElement("div", this.bodyDiv);
	alib.dom.styleSetClass(div_main, "wizardBody");
	this.steps[step].callback(div_main);

    // Post process function before the step is showed
    if(typeof this.subclass.processPostStep == "function")
        this.subclass.processPostStep();
    
	// Buttons
	// ---------------------------------------------------------
	var dv_btn = alib.dom.createElement("div", this.bodyDiv);
	alib.dom.styleSet(dv_btn, "margin-top", "8px");
	alib.dom.styleSet(dv_btn, "text-align", "right");

	// Save and finish later can be defined in the wizard implementation file as an optional function
	if (this.saveAndFinishLater)
	{
		var btn = alib.ui.Button("Save & Finish Later", {
				className:"b1", tooltip:"Save changes to finish at a later time", cls:this, step:step, 
				onclick:function() 
				{
					this.cls.saveAndFinishLater(this.step);
					this.cls.hide();
					this.cls.onCancel();
				}
			});
		btn.print(dv_btn);
	}

	if (step > 0)
	{
		var btn = alib.ui.Button("Back", {
				className:"b1", tooltip:"Go back one step", cls:this, step:step, 
				onclick:function() 
				{
					this.cls.showStep(this.step-1);
				}
			});
		btn.print(dv_btn);
	}

	if (step == (this.steps.length - 1))
	{
		var btn = alib.ui.Button("Finished", {
				className:"b2", tooltip:"Finish and close wizard", cls:this,
				onclick:function() {this.cls.hide(); this.cls.onFinished(); }
			});
		btn.print(dv_btn);
	}
	else
	{
		var btn = alib.ui.Button("Continue", {
				className:"b2", tooltip:"Continue to next step", cls:this, step:step, 
				onclick:function() 
				{
					if (this.cls.subclass.processStep())
					{
						this.cls.showStep(this.step+1);
					}
					else
					{
						alib.Dlg.messageBox(this.cls.subclass.lastErrorMessage, this.cls.dlg);
					}
				}
			});
		btn.print(dv_btn);

		var button = alib.ui.Button("Cancel", {
				className:"b1", tooltip:"Cancel Wizard", cls:this, dlg:this.dlg, 
				onclick:function() 
                {
                    if(confirm("Are you sure you want to cancel the wizard?"))
                    {
                        this.cls.hide()
						this.cls.onCancel();
                        if(typeof this.cls.refresh == "function")
                            this.cls.refresh(); 
                    }
                }
			});
		button.print(dv_btn);
	}
}

/**
 * Set processing flag for async processing of steps
 */
AntWizard.prototype.setProcessing = function()
{
	this.processing = true;
}

/**
 * Unset processing flag and determine if it was a success or a failure
 *
 * @var {bool} success If true then continue to next step, if false the do not continue. Default is true;
 */
AntWizard.prototype.setProcessingFinished = function(success)
{
	this.processingSuccess = (typeof success != "undefined") ? success : true;
	this.processing = false;
}


/**
 * Gets the current step
 *
 * @return {Number} Returns the current step
 */
AntWizard.prototype.getCurrentStep = function()
{
    return this.nStep; 
}

/**
 * Optional function that can be defined in the wizard subClass
 *
 * @param {int} step The current step
 */
AntWizard.prototype.saveAndFinishLater = null;
