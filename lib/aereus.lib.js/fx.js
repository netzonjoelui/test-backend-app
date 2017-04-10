/**
 * @fileOverview alib.ui.fs Is used to add effects using jquery
 *
 * @author:	joe, sky.stebnicki@aereus.com; 
 * 			Copyright (c) 2013 Aereus Corporation. All rights reserved.
 */

/**
 * Create fx namespace
 *
 * @var {Object}
 */
alib.fx = {};

/**
 * SlideUp effect
 *
 * @param {DOMElement} e
 * @param {function} cbFun
 * @param {duration}
 */
alib.fx.slideUp = function(e, cbFun, duration)
{
	var dur = duration || 400;
	var cb = cbFun || null;

	$(e).slideUp(duration, cb);
}

/**
 * SlideDown effect
 *
 * @param {DOMElement} e
 * @param {function} cbFun
 * @param {duration}
 */
alib.fx.slideDown = function(e, cbFun, duration)
{
	var dur = duration || 400;
	var cb = cbFun || null;

	$(e).slideDown(duration, cb);
}

/**
 * Hide by sliding out to the left
 *
 * @param {DOMElement} e
 * @param {function} cbFun
 * @param {duration}
 */
alib.fx.hideLeft = function(e, cbFun, duration)
{
	var dur = duration || 1000;
	var cb = cbFun || null;

	$(e).hide('slide', {direction: 'left'}, duration);
}

/**
 * Show by sliding in from the left
 *
 * @param {DOMElement} e
 * @param {function} cbFun
 * @param {duration}
 */
alib.fx.showLeft = function(e, cbFun, duration)
{
	var dur = duration || 1000;
	var cb = cbFun || null;

	$(e).show('slide', {direction: 'left'}, duration);
}

/**
 * Hide by sliding out to the right
 *
 * @param {DOMElement} e
 * @param {function} cbFun
 * @param {duration}
 */
alib.fx.hideRight = function(e, cbFun, duration)
{
	var dur = duration || 1000;
	var cb = cbFun || null;

	$(e).hide('slide', {direction: 'right'}, duration);
}

/**
 * Show by sliding in from the right
 *
 * @param {DOMElement} e
 * @param {function} cbFun
 * @param {duration}
 */
alib.fx.showRight = function(e, cbFun, duration)
{
	var dur = duration || 1000;
	var cb = cbFun || null;

	$(e).show('slide', {direction: 'right'}, duration);
}

/**
 * Fade in effect
 *
 * @param {DOMElement} e
 * @param {function} cbFun
 * @param {duration}
 */
alib.fx.fadeIn = function(e, cbFun, duration)
{
	var dur = duration || 400;
	var cb = cbFun || null;

	$(e).fadeIn(duration, cb);
}

/**
 * Fade out effect
 *
 * @param {DOMElement} e
 * @param {function} cbFun
 * @param {duration}
 */
alib.fx.fadeOut = function(e, cbFun, duration)
{
	var dur = duration || 400;
	var cb = cbFun || null;

	$(e).fadeOut(duration, cb);
}
