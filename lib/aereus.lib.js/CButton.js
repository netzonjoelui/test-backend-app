function CButton(title, funct, args, scheme, width, mouseover, mouseout, type)
{
	var opts = new Object();
	opts.onclick = funct;
	opts.onmouseover = (mouseover) ? mouseover : null;
	opts.onmouseout = (mouseout) ? mouseout : null;
	
    opts.className = (scheme) ? scheme :  null;

	opts.m_funct = funct;
	opts.m_args = args;
	opts.onclick = function ()
	{
		if (typeof this.m_funct == "string")
			eval(this.m_funct);
		else
		{
			if (this.m_args)
			{
				switch(this.m_args.length)
				{
				case 1:
					this.m_funct(this.m_args[0]);
					break;
				case 2:
					this.m_funct(this.m_args[0], this.m_args[1]);
					break;
				case 3:
					this.m_funct(this.m_args[0], this.m_args[1], this.m_args[2]);
					break;
				case 4:
					this.m_funct(this.m_args[0], this.m_args[1], this.m_args[2], this.m_args[3]);
					break;
				case 5:
					this.m_funct(this.m_args[0], this.m_args[1], this.m_args[2], this.m_args[3], this.m_args[4]);
					break;
				case 6:
					this.m_funct(this.m_args[0], this.m_args[1], this.m_args[2], this.m_args[3], this.m_args[4], this.m_args[5]);
					break;
				default:
					alert("Too many arguments");
					break;
				}
			}
			else if (this.m_funct)
				this.m_funct();
		}
	}

	var button = alib.ui.Button(title, opts, type);
	return button;

	scheme = (scheme) ? scheme : 'b1';

	this.m_scheme = scheme;
	/*
	switch (scheme)
	{
	case 'b1-pill-l':
		this.m_scheme = "b1";
		this.m_subscheme = scheme;
		break;
	case 'b1-pill-c':
		this.m_scheme = "b1";
		this.m_subscheme = scheme;
		break;
	case 'b1-pill-r':
		this.m_scheme = "b1";
		this.m_subscheme = scheme;
		break;
	default:
		this.m_scheme = scheme;
	}
	*/

	this.m_main = ALib.m_document.createElement("button");
	this.m_main.setAttribute("type", "button");
	/*alib.dom.styleSetClass(this.m_main, "CButton");*/
	alib.dom.styleAddClass(this.m_main, this.m_scheme);
	if (this.m_subscheme)
		alib.dom.styleAddClass(this.m_main, this.m_subscheme);
	/* Immediately below is a temporary hack to serve the 
	following margin values only to Gecko browsers
	Gecko browsers add an extra 3px of left/right 
	padding to button elements which can't be overriden.
	Thus, we use -3px of left/right margin to overcome this. */
	//if (alib.userAgent.gecko)
		//alib.dom.styleSet(this.m_main, "margin", "0 -3px");

	var table = alib.dom.createElement("span", this.m_main);
	var lbl = alib.dom.createElement("span", table);
	if (typeof title == "string")
		lbl.innerHTML = title;
	else
		lbl.appendChild(title);
	this.m_titleCon = lbl;

	// Set actions for button
	this.m_main.m_btnh = this;
	this.m_main.onmouseover = function ()
	{
		this.m_btnh.changeState("over");
	}
	this.m_main.onmouseout = function ()
	{
		this.m_btnh.changeState("out");
	}
	this.m_main.m_funct = funct;
	this.m_main.m_args = args;
	this.m_main.onclick = function ()
	{
		if (typeof this.m_funct == "string")
			eval(this.m_funct);
		else
		{
			if (this.m_args)
			{
				switch(this.m_args.length)
				{
				case 1:
					this.m_funct(this.m_args[0]);
					break;
				case 2:
					this.m_funct(this.m_args[0], this.m_args[1]);
					break;
				case 3:
					this.m_funct(this.m_args[0], this.m_args[1], this.m_args[2]);
					break;
				case 4:
					this.m_funct(this.m_args[0], this.m_args[1], this.m_args[2], this.m_args[3]);
					break;
				case 5:
					this.m_funct(this.m_args[0], this.m_args[1], this.m_args[2], this.m_args[3], this.m_args[4]);
					break;
				case 6:
					this.m_funct(this.m_args[0], this.m_args[1], this.m_args[2], this.m_args[3], this.m_args[4], this.m_args[5]);
					break;
				default:
					alert("Too many arguments");
					break;
				}
			}
			else
				this.m_funct();
		}
	}

	/* Set actions for div
	this.m_c.onmouseover = function ()
	{
		this.m_btnh.changeState("over");
	}
	
	this.m_c.onmouseout = function ()
	{
		this.m_btnh.changeState("out");
	}
	this.m_c.m_funct = funct;
	this.m_c.m_args = args;
	this.m_c.onclick = function ()
	{
		if (typeof this.m_funct == "string")
			eval(this.m_funct);
		else
		{
			if (this.m_args)
			{
				switch(this.m_args.length)
			{
				case 1:
					this.m_funct(this.m_args[0]);
					break;
				case 2:
					this.m_funct(this.m_args[0], this.m_args[1]);
					break;
				case 3:
					this.m_funct(this.m_args[0], this.m_args[1], this.m_args[2]);
					break;
				case 4:
					this.m_funct(this.m_args[0], this.m_args[1], this.m_args[2], this.m_args[3]);
					break;
				}
			}
			else
				this.m_funct();
		}
	}
	*/

	this.m_table = table;
	
	this.changeState("out");
}

CButton.prototype.changeState = function (state)
{
	/*
	switch (state)
	{
	case 'over':
		alib.dom.styleSetClass(this.m_tl, "CButtonTopLeft_"+this.m_scheme+"Over");
		alib.dom.styleSetClass(this.m_tc, "CButtonTopCenter_"+this.m_scheme+"Over");
		alib.dom.styleSetClass(this.m_tr, "CButtonTopRight_"+this.m_scheme+"Over");
		alib.dom.styleSetClass(this.m_l, "CButtonBodyLeft_"+this.m_scheme+"Over");
		alib.dom.styleSetClass(this.m_c, "CButtonBody_"+this.m_scheme+"Over");
		alib.dom.styleSetClass(this.m_r, "CButtonBodyRight_"+this.m_scheme+"Over");
		alib.dom.styleSetClass(this.m_bl, "CButtonBottomLeft_"+this.m_scheme+"Over");
		alib.dom.styleSetClass(this.m_bc, "CButtonBottomCenter_"+this.m_scheme+"Over");
		alib.dom.styleSetClass(this.m_br, "CButtonBottomRight_"+this.m_scheme+"Over");	
		break;
	case 'out':
		alib.dom.styleSetClass(this.m_tl, "CButtonTopLeft_"+this.m_scheme);
		alib.dom.styleSetClass(this.m_tc, "CButtonTopCenter_"+this.m_scheme);
		alib.dom.styleSetClass(this.m_tr, "CButtonTopRight_"+this.m_scheme);
		alib.dom.styleSetClass(this.m_l, "CButtonBodyLeft_"+this.m_scheme);
		alib.dom.styleSetClass(this.m_c, "CButtonBody_"+this.m_scheme);
		alib.dom.styleSetClass(this.m_r, "CButtonBodyRight_"+this.m_scheme);
		alib.dom.styleSetClass(this.m_bl, "CButtonBottomLeft_"+this.m_scheme);
		alib.dom.styleSetClass(this.m_bc, "CButtonBottomCenter_"+this.m_scheme);
		alib.dom.styleSetClass(this.m_br, "CButtonBottomRight_"+this.m_scheme);
		break;
	}
	*/
}

CButton.prototype.disable = function()
{
	this.m_main.disabled = true;
	alib.dom.styleRemoveClass(this.m_main, this.m_scheme);
}
CButton.prototype.enable= function()
{
	this.m_main.disabled = false;
	alib.dom.styleAddClass(this.m_main, this.m_scheme);
}

CButton.prototype.setText = function(text)
{
	this.m_titleCon.innerHTML = text;
}

CButton.prototype.getButton = function ()
{
	return this.m_main;
}

CButton.prototype.print = function(con)
{
	con.appendChild(this.m_main);
}

CButton.prototype.getTable = function ()
{
	return this.m_table;
}
