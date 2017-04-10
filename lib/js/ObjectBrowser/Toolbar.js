/**
 * @fileoverview TODO: This is the base or default toolbar for browsers
 *
 * Other toolbars may extend this one by object type
 */

/**
 * Class constructor
 *
 * @param {AntObjectBrowser} browser
 */
AntObjectBrowser.Toolbar = function(browser)
{
	/**
	 * Reference to browser instance
	 *
	 * @private
	 * @type {AntObjectBrowser}
	 */
	this.browser = browser;
}

/**
 * Print full toolbar
 *
 * @public
 * @param {DOMElement} con The container to print the toolbar into
 */
AntObjectBrowser.Toolbar.prototype.renderFull = function(con)
{
}

/**
 * Print preview mode toolbar - much smaller
 *
 * @public
 * @param {DOMElement} con The container to print the toolbar into
 */
AntObjectBrowser.Toolbar.prototype.renderPreview = function(con)
{
}

/**
 * Print mobile toolbar
 *
 * @public
 * @param {DOMElement} con The container to print the toolbar into
 */
AntObjectBrowser.Toolbar.prototype.renderMobile = function(con)
{
}
