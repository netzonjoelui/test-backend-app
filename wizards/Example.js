/**
 * @fileoverview This is an example dummy wizard used to demonstrate how to work with wizards in ANT
 *
 * <code>
 * 	var wiz = new AntWizard("Example");
 * 	wiz.onFinished = function() { alert("The wizard is finished); };
 * 	wiz.onCancel = function() { alert("The wizard was canceled"); };
 * 	wiz.show();
 * </code>
 */

/**
 * @constructor
 */
function AntWizard_Example()
{
	/**
	 * Handle to wizard, this MUST be set by the parent class or calling procedure
	 *
	 * @private
	 * @param {AntWizard}
	 */
	this.wizard = null;

	/**
	 * Last error
	 *
	 * @public
	 * @param {string}
	 */
	this.lastErrorMessage = "";
}

/**
 * Setup steps for this wizard
 *
 * This function is called by the AntWizard base class once the wizard has loaded for the first time
 *
 * @param {AntWizard} wizard Required handle to parent wizard class
 */
AntWizard_Example.prototype.setup = function(wizard)
{
	this.wizard = wizard;

	this.wizard.title = "Example Wizard";

	var me = this;

	// Add step 1
	this.wizard.addStep(function(con) { me.stepOne(con); }, "Beginning");

	// Add step 2
	this.wizard.addStep(function(con) { me.stepTwo(con); }, "Middle");

	// Add step 3
	this.wizard.addStep(function(con) { me.stepThree(con); }, "End");
}

/**
 * This function is called every time the user advances a step
 *
 * It may be overridden by each step function below when the step function is called.
 * However, it is reset by the wizard class before each step loads so
 * verification code is limited to that step and must be set each time.
 *
 * If the function returns false, the wizard will not progress to the next step.
 * This function will not be called on the final step where "Finished" is presented.
 * Validation for that step must take place in the onFinished callback.
 *
 * @return {bool} true on success, false on failure. Set this.lastErrorMessage if failed.
 */
AntWizard_Example.prototype.processStep = function() { return true; }

/**
 * Display step 3
 *
 * @public
 */
AntWizard_Example.prototype.stepOne = function(con)
{
	con.innerHTML = "One";

	this.processStep = function()
	{
		return true;
	}
}

/**
 * Display step 2
 *
 * @public
 */
AntWizard_Example.prototype.stepTwo = function(con)
{
	con.innerHTML = "Two";
}

/**
 * Display step 3
 *
 * @public
 */
AntWizard_Example.prototype.stepThree = function(con)
{
	con.innerHTML = "Three";
}
