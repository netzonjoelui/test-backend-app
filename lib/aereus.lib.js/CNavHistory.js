/*======================================================================================
	
	Module:		CNavHistory	

	Purpose:	Manage history and enable goback especially for ajax/dom applications

	Author:		joe, sky.stebnicki@aereus.com
				Copyright (c) 2010 Aereus Corporation. All rights reserved.
	
	Depends:	

	Usage:		

	Variables:	

======================================================================================*/

function CNavHistory()
{
	this.iCurIndex = 0;
	this.iNextIndex = 0;
	this.history = new Array();
	this.skipRegister = false;

	/*
	var me = this;
	this.lastHash = "";
	function intervalMethod() 
	{
		if ( window.location.hash != me.lastHash )
		{
			//me.lastHash = window.location.hash;
			me.goBack(window.location.hash);
		}
		return true;
	}
	this.initInterval = setInterval(intervalMethod, 1000);
	*/
}

CNavHistory.prototype.goBack = function(ind)
{
	var index = (ind) ? ind : this.iCurIndex-1; // skip over current page 
	var entry = this.history[index];

	// exit if not registered
	if (index<0)
		return;

	this.iCurIndex = index;
	this.skipRegister = true;
	this.lastHash = index;

	// Not sure if there is a better way to do this, but for now limit arguments length to 10
	switch (entry.args.length)
	{
	case 0:
		entry.funct();
		break;
	case 1:
		entry.funct(entry.args[0]);
		break;
	case 2:
		entry.funct(entry.args[0], entry.args[1]);
		break;
	case 3:
		entry.funct(entry.args[0], entry.args[1], entry.args[2]);
		break;
	case 4:
		entry.funct(entry.args[0], entry.args[1], entry.args[2], entry.args[3]);
		break;
	}

}

CNavHistory.prototype.registerBack = function(funct, args)
{
	// Skip over registration of page we just returned to
	if (this.skipRegister)
	{
		this.skipRegister = false;
		return;
	}

	var entry = new Object();
	entry.funct = funct;
	entry.args = (args) ? args : new Array();

	this.history[this.iNextIndex] = entry;
	this.lastHash = this.iNextIndex;
	//window.location.hash = this.iNextIndex;

	this.iCurIndex = this.iNextIndex;
	this.iNextIndex++;
}
