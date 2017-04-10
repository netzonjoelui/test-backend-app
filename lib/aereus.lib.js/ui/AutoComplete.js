/**
* @fileOverview alib.ui.button class
*
* This is used simply to abstract common button functions in the DOM
*
* Example:
* <code>
*
* 	// Create basic dropdown with array
* 	var datArr = ["first item", "next item", "last item"];
* 	var ac = alib.dom.AutoComplete(inputElementReference, {data:datArr});
*
*	// Craete dropdown that gathers data from server (json)
* 	var ac = alib.dom.AutoComplete(inputElementReference, {url:"http://server/data.php"});
* </code>
*
* Available Option Properties:
* {string} .url The url to load data from. Search strings will be passed as "get" variable named "search"
* {Array) .data Data to be used for the dropdown manually set (will not query server)
*
* @author:	joe, sky.stebnicki@aereus.com; 
* 			Copyright (c) 2008-2012 Aereus Corporation. All rights reserved.
*/

/**
 * Creates an instance of Alib_Ui_AutoComplete
 *
 * @constructor
 * @param {DOMElement} el The input or textarea to add this autocomplete to
 * @param {Object} options Options to be used
 */
function Alib_Ui_AutoComplete(el, options)
{
	/* ---- Public Variables ---- */
	this.timeOut = -1; // Autocomplete Timeout in ms (-1: autocomplete never time out)
	this.actb_lim = 8;    // Number of elements autocomplete can show (-1: no limit)
	this.actb_firstText = false; // should the auto complete be limited to the beginning of keyword?
	this.enableMouse = true; // Enable Mouse Support
	this.actb_delimiter = new Array(';',',');  // Delimiter for multiple autocomplete. Set it to empty array for single autocomplete
	this.actb_startcheck = 1; // Show widget only after this number of characters is typed in.
	/* ---- Public Variables ---- */

	/* --- Styles --- */
	this.actb_bgColor = '#888888';
	this.actb_textColor = '#FFFFFF';
	this.actb_hColor = '#000000';
	this.actb_fFamily = 'Arial';
	this.actb_fSize = '12px';
	this.actb_hStyle = 'text-decoration:underline;font-weight="bold"';
	/* --- Styles --- */

	/* ---- Private Variables ---- */
	var actb_cdelimword = 0;
	var actb_display = false;
	var actb_pos = 0;
	var actb_total = 0;
	var actb_curr = null;
	var actb_rangeu = 0;
	var actb_ranged = 0;
	var actb_bool = new Array();
	var actb_pre = 0;
	var actb_toid;
	var actb_tomake = false;
	var actb_getpre = "";
	var actb_mouse_on_list = 1;
	var actb_kwcount = 0;
	var actb_caretmove = false;
	this.delimWords = new Array();
	this.delimChar = new Array();
	this.cdelimWord = 0;
	this.data = new Array();
	this.options = options;
	this.currInput = el; // should eventually replace actb_curr
	/* ---- Private Variables---- */
	
	// Initialize data if already set
	if (options.data)
		this.data = options.data;

	var actb_self = this;

	actb_curr = el;
	
	// Set flag that ac is being displayed
	this.currInput.m_inac = false;
	//actb_curr.autocomplete = "off";

	alib.dom.addEvent(this.currInput,"focus",actb_setup);
	function actb_setup()
	{
		alib.dom.addEvent(document,"keydown",actb_checkkey);
		alib.dom.addEvent(actb_curr,"blur",actb_clear);
		alib.dom.addEvent(document,"keypress",actb_keypress);
	}

	function actb_clear(evt)
	{
		if (!evt) evt = event;
		alib.dom.removeEvent(document,"keydown",actb_checkkey);
		alib.dom.removeEvent(actb_curr,"blur",actb_clear);
		alib.dom.removeEvent(document,"keypress",actb_keypress);
		actb_removedisp();
	}

	// Populate the HTML text
	function actb_parse(n)
	{
		// Escape chars
		n = n.replace(/[<]/g,'&lt;');
		n = n.replace(/[>]/g,'&gt;');

		if (actb_self.actb_delimiter.length > 0)
		{
			var t = actb_self.delimWords[actb_self.cdelimWord].trim().addslashes();
			var plen = actb_self.delimWords[actb_self.cdelimWord].trim().length;
		}
		else
		{
			var t = actb_curr.value.addslashes();
			var plen = actb_curr.value.length;
		}

		var tobuild = '';
		var i;

		if (actb_self.actb_firstText)
		{
			var re = new RegExp("^" + t, "i");
		}
		else
		{
			var re = new RegExp(t, "i");
		}

		var p = n.search(re);
				
		for (i=0;i<p;i++)
		{
			tobuild += n.substr(i,1);
		}

		tobuild += "<font style='"+(actb_self.actb_hStyle)+"'>"

		for (i=p;i<plen+p;i++)
		{
			tobuild += n.substr(i,1);
		}

		tobuild += "</font>";

		for (i=plen+p;i<n.length;i++)
		{
			tobuild += n.substr(i,1);
		}

		return tobuild;
	}

	function actb_generate()
	{
		if (document.getElementById('tat_table')){ actb_display = false;document.body.removeChild(document.getElementById('tat_table')); } 
		if (actb_kwcount == 0){
			actb_display = false;
			return;
		}	

		a = document.createElement('table');
		a.cellSpacing='1px';
		a.cellPadding='2px';
		a.style.position='absolute';
		a.style.top = eval(curTop(actb_curr) + actb_curr.offsetHeight) + "px";
		a.style.left = curLeft(actb_curr) + "px";
		a.style.backgroundColor=actb_self.actb_bgColor;
		a.id = 'tat_table';
		a.style.zIndex = "990";
		document.body.appendChild(a);
		var i;
		var first = true;
		var j = 1;
		if (actb_self.enableMouse){
			a.onmouseout = actb_table_unfocus;
			a.onmouseover = actb_table_focus;
		}
		var counter = 0;

		for (i=0;i<actb_self.data.length;i++)
		{
			if (actb_bool[i]){
				counter++;
				r = a.insertRow(-1);

				if (first && !actb_tomake)
				{
					r.style.backgroundColor = actb_self.actb_hColor;
					first = false;
					actb_pos = counter;
				}
				else if(actb_pre == i)
				{
					r.style.backgroundColor = actb_self.actb_hColor;
					first = false;
					actb_pos = counter;
				}
				else
				{
					r.style.backgroundColor = actb_self.actb_bgColor;
				}
				r.id = 'tat_tr'+(j);
				c = r.insertCell(-1);
				c.style.color = actb_self.actb_textColor;
				c.style.fontFamily = actb_self.actb_fFamily;
				c.style.fontSize = actb_self.actb_fSize;
				c.innerHTML = actb_parse(actb_self.data[i]);
				c.id = 'tat_td'+(j);
				c.setAttribute('pos',j);
				if (actb_self.enableMouse){
					c.style.cursor = 'pointer';
					c.onclick=actb_mouseclick;
					c.onmouseover = actb_table_highlight;
				}
				j++;
			}
			if (j - 1 == actb_self.actb_lim && j < actb_total){
				r = a.insertRow(-1);
				r.style.backgroundColor = actb_self.actb_bgColor;
				c = r.insertCell(-1);
				c.style.color = actb_self.actb_textColor;
				c.style.fontFamily = 'arial narrow';
				c.style.fontSize = actb_self.actb_fSize;
				c.align='center';
				replaceHTML(c,'\\/');
				if (actb_self.enableMouse){
					c.style.cursor = 'pointer';
					c.onclick = actb_mouse_down;
				}
				break;
			}
		}
		actb_rangeu = 1;
		actb_ranged = j-1;
		actb_display = true;
		if (actb_pos <= 0) actb_pos = 1;

		// Set flag that ac is being displayed
		actb_curr.m_inac = true;
	}

	function actb_remake()
	{
		document.body.removeChild(document.getElementById('tat_table'));
		a = document.createElement('table');
		a.cellSpacing='1px';
		a.cellPadding='2px';
		a.style.position='absolute';
		a.style.zIndex = "990";
		a.style.top = eval(curTop(actb_curr) + actb_curr.offsetHeight) + "px";
		a.style.left = curLeft(actb_curr) + "px";
		a.style.backgroundColor=actb_self.actb_bgColor;
		a.id = 'tat_table';
		if (actb_self.enableMouse){
			a.onmouseout= actb_table_unfocus;
			a.onmouseover=actb_table_focus;
		}
		document.body.appendChild(a);
		var i;
		var first = true;
		var j = 1;
		if (actb_rangeu > 1){
			r = a.insertRow(-1);
			r.style.backgroundColor = actb_self.actb_bgColor;
			c = r.insertCell(-1);
			c.style.color = actb_self.actb_textColor;
			c.style.fontFamily = 'arial narrow';
			c.style.fontSize = actb_self.actb_fSize;
			c.align='center';
			replaceHTML(c,'/\\');
			if (actb_self.enableMouse){
				c.style.cursor = 'pointer';
				c.onclick = actb_mouse_up;
			}
		}
		for (i=0;i<actb_self.data.length;i++){
			if (actb_bool[i]){
				if (j >= actb_rangeu && j <= actb_ranged){
					r = a.insertRow(-1);
					r.style.backgroundColor = actb_self.actb_bgColor;
					r.id = 'tat_tr'+(j);
					c = r.insertCell(-1);
					c.style.color = actb_self.actb_textColor;
					c.style.fontFamily = actb_self.actb_fFamily;
					c.style.fontSize = actb_self.actb_fSize;
					c.innerHTML = actb_parse(actb_self.data[i]);
					c.id = 'tat_td'+(j);
					c.setAttribute('pos',j);
					if (actb_self.enableMouse){
						c.style.cursor = 'pointer';
						c.onclick=actb_mouseclick;
						c.onmouseover = actb_table_highlight;
					}
					j++;
				}else{
					j++;
				}
			}
			if (j > actb_ranged) break;
		}
		if (j-1 < actb_total){
			r = a.insertRow(-1);
			r.style.backgroundColor = actb_self.actb_bgColor;
			c = r.insertCell(-1);
			c.style.color = actb_self.actb_textColor;
			c.style.fontFamily = 'arial narrow';
			c.style.fontSize = actb_self.actb_fSize;
			c.align='center';
			replaceHTML(c,'\\/');
			if (actb_self.enableMouse){
				c.style.cursor = 'pointer';
				c.onclick = actb_mouse_down;
			}
		}

		// Set flag that ac is being displayed
		actb_curr.m_inac = true;
	}

	function actb_goup()
	{
		if (!actb_display) return;
		if (actb_pos == 1) return;
		document.getElementById('tat_tr'+actb_pos).style.backgroundColor = actb_self.actb_bgColor;
		actb_pos--;
		if (actb_pos < actb_rangeu) actb_moveup();
		document.getElementById('tat_tr'+actb_pos).style.backgroundColor = actb_self.actb_hColor;
		if (actb_toid) clearTimeout(actb_toid);
		if (actb_self.timeOut > 0) actb_toid = setTimeout(function(){actb_mouse_on_list=0;actb_removedisp();},actb_self.timeOut);
	}

	function actb_godown()
	{
		if (!actb_display) return;
		if (actb_pos == actb_total) return;
		document.getElementById('tat_tr'+actb_pos).style.backgroundColor = actb_self.actb_bgColor;
		actb_pos++;
		if (actb_pos > actb_ranged) actb_movedown();
		document.getElementById('tat_tr'+actb_pos).style.backgroundColor = actb_self.actb_hColor;
		if (actb_toid) clearTimeout(actb_toid);
		if (actb_self.timeOut > 0) actb_toid = setTimeout(function(){actb_mouse_on_list=0;actb_removedisp();},actb_self.timeOut);
	}

	function actb_movedown()
	{
		actb_rangeu++;
		actb_ranged++;
		actb_remake();
	}

	function actb_moveup()
	{
		actb_rangeu--;
		actb_ranged--;
		actb_remake();
	}

	/* Mouse */
	function actb_mouse_down()
	{
		document.getElementById('tat_tr'+actb_pos).style.backgroundColor = actb_self.actb_bgColor;
		actb_pos++;
		actb_movedown();
		document.getElementById('tat_tr'+actb_pos).style.backgroundColor = actb_self.actb_hColor;
		actb_curr.focus();
		actb_mouse_on_list = 0;
		if (actb_toid) clearTimeout(actb_toid);
		if (actb_self.timeOut > 0) actb_toid = setTimeout(function(){actb_mouse_on_list=0;actb_removedisp();},actb_self.timeOut);
	}

	function actb_mouse_up(evt)
	{
		if (!evt) evt = event;
		if (evt.stopPropagation){
			evt.stopPropagation();
		}else{
			evt.cancelBubble = true;
		}
		document.getElementById('tat_tr'+actb_pos).style.backgroundColor = actb_self.actb_bgColor;
		actb_pos--;
		actb_moveup();
		document.getElementById('tat_tr'+actb_pos).style.backgroundColor = actb_self.actb_hColor;
		actb_curr.focus();
		actb_mouse_on_list = 0;
		if (actb_toid) clearTimeout(actb_toid);
		if (actb_self.timeOut > 0) actb_toid = setTimeout(function(){actb_mouse_on_list=0;actb_removedisp();},actb_self.timeOut);
	}
	function actb_mouseclick(evt){
		if (!evt) evt = event;
		if (!actb_display) return;
		actb_mouse_on_list = 0;
		actb_pos = this.getAttribute('pos');
		actb_penter();
	}
	function actb_table_focus()
	{
		actb_mouse_on_list = 1;
	}
	function actb_table_unfocus(){
		actb_mouse_on_list = 0;
		if (actb_toid) clearTimeout(actb_toid);
		if (actb_self.timeOut > 0) actb_toid = setTimeout(function(){actb_mouse_on_list = 0;actb_removedisp();},actb_self.timeOut);
	}
	function actb_table_highlight(){
		actb_mouse_on_list = 1;
		document.getElementById('tat_tr'+actb_pos).style.backgroundColor = actb_self.actb_bgColor;
		actb_pos = this.getAttribute('pos');
		while (actb_pos < actb_rangeu) actb_moveup();
		while (actb_pos > actb_ranged) actb_movedown();
		document.getElementById('tat_tr'+actb_pos).style.backgroundColor = actb_self.actb_hColor;
		if (actb_toid) clearTimeout(actb_toid);
		if (actb_self.timeOut > 0) actb_toid = setTimeout(function(){actb_mouse_on_list = 0;actb_removedisp();},actb_self.timeOut);
	}
	/* ---- */

	function actb_insertword(a){
		if (actb_self.actb_delimiter.length > 0){
			str = '';
			l=0;
			for (i=0;i<actb_self.delimWords.length;i++){
				if (actb_self.cdelimWord == i){
					prespace = postspace = '';
					gotbreak = false;
					for (j=0;j<actb_self.delimWords[i].length;++j){
						if (actb_self.delimWords[i].charAt(j) != ' '){
							gotbreak = true;
							break;
						}
						prespace += ' ';
					}
					for (j=actb_self.delimWords[i].length-1;j>=0;--j){
						if (actb_self.delimWords[i].charAt(j) != ' ') break;
						postspace += ' ';
					}
					str += prespace;
					str += a + ",";
					l = str.length;
					if (gotbreak) str += postspace;
				}else{
					str += actb_self.delimWords[i];
				}
				if (i != actb_self.delimWords.length - 1){
					str += actb_self.delimChar[i];
				}
			}
			actb_curr.value = str;
			alib.dom.setCaret(actb_curr,l);
		}else{
			actb_curr.value = a;
		}
		actb_mouse_on_list = 0;
		actb_removedisp();
		try
		{
			actb_curr.onchange();
		}
		catch(e) {}
	}

	function actb_penter()
	{
		if (!actb_display) return;
		actb_display = false;
		var word = '';
		var c = 0;
		for (var i=0;i<=actb_self.data.length;i++){
			if (actb_bool[i]) c++;
			if (c == actb_pos){
				word = actb_self.data[i];
				break;
			}
		}
		actb_insertword(word);
		l = alib.dom.getCaretStart(actb_curr);
	}

	function actb_removedisp()
	{
		if (actb_mouse_on_list==0)
		{
			actb_display = 0;
			if (document.getElementById('tat_table')){ document.body.removeChild(document.getElementById('tat_table')); }
			if (actb_toid) clearTimeout(actb_toid);

			actb_curr.m_inac = false;
		}
	}

	function actb_keypress(e)
	{
		if (actb_caretmove) alib.dom.stopEvent(e);
		return !actb_caretmove;
	}

	function actb_checkkey(evt)
	{
		if (!evt) evt = event;
		a = evt.keyCode;
		caret_pos_start = alib.dom.getCaretStart(actb_curr);
		actb_caretmove = 0;
		
		switch (a)
		{
			case 38:
				actb_goup();
				actb_caretmove = 1;
				return false;
				break;
			case 40:
				actb_godown();
				actb_caretmove = 1;
				return false;
				break;
			case 13: case 9:
				if (actb_display)
				{
					actb_curr.m_inac = false;
					actb_caretmove = 1;
					actb_penter();
					return false;
				}
				else
				{
					return true;
				}
				break;
			default:
				if (actb_self.options.url)
				{
					setTimeout(function(){actb_loadremote(a);},50);
				}
				else if (actb_self.data.length)
				{
					setTimeout(function(){actb_tocomplete(a)},50);
				}
				else
				{
					return true;
				}
				break;
		}
	}

	// TODO: This should be moved to this.loadRemoteData
	function actb_loadremote(kc)
	{
		var keyword = actb_self.getCurrentKeyword();

		if (!actb_self.options.url || !keyword)
			return false;

		var ajax = new CAjax('json');
		ajax.cbData.cls = actb_self;
		ajax.cbData.kc = kc;
		ajax.onload = function(data)
		{
			try
			{
				if (data.length)
				{
					this.cbData.cls.data = data;

					// Now that the data has been populated, create ac dialog
					actb_tocomplete(this.cbData.kc);
				}
			}
			catch(e)
			{
				//alert("There was a problem loading data " + e);
			}
		};
		var args = [["search", keyword]];
		ajax.exec(actb_self.options.url, args);
	}

	/**
	 * Send keycode (char) to the autocomplete buffer
	 *
	 * @param {integer} kc KeyCode/char to process
	 */
	function actb_tocomplete(kc)
	{
		if (kc == 38 || kc == 40 || kc == 13) return;

		var i;

		// TODO: find out what actb_display does
		if (actb_display)
		{ 
			var word = 0;
			var c = 0;

			for (var i=0;i<=actb_self.data.length;i++)
			{
				if (actb_bool[i]) c++;

				if (c == actb_pos)
				{
					word = i;
					break;
				}
			}

			actb_pre = word;
		}
		else
		{ 
			actb_pre = -1
		};
		
		// If the value is empty the remove list of words
		if (actb_curr.value == '')
		{
			actb_mouse_on_list = 0;
			actb_removedisp();
			return;
		}

		var ot = actb_self.getCurrentKeyword();
		var t = ot.addslashes();

		if (ot.length == 0)
		{
			actb_mouse_on_list = 0;
			actb_removedisp();
		}

		if (ot.length < actb_self.actb_startcheck) return this;

		if (actb_self.actb_firstText)
			var re = new RegExp("^" + t, "i");
		else
			var re = new RegExp(t, "i");

		actb_total = 0;
		actb_tomake = false;
		actb_kwcount = 0;

		for (i=0;i<actb_self.data.length;i++)
		{
			actb_bool[i] = false;

			if (re.test(actb_self.data[i]))
			{
				actb_total++;
				actb_bool[i] = true;
				actb_kwcount++;
				if (actb_pre == i) actb_tomake = true;
			}
		}

		if (actb_toid) clearTimeout(actb_toid);
		if (actb_self.timeOut > 0) actb_toid = setTimeout(function(){actb_mouse_on_list = 0;actb_removedisp();},actb_self.timeOut);
		actb_generate();
	}
	
	//return $(el).autocomplete(options);
}

/**
 * Load data from a remote source and populate this.data
 *
 * TODO: this is to replace the inline function actb_loadremote
 *
 * @param {integer} kc The keycode last pressed
 */
Alib_Ui_AutoComplete.prototype.loadRemoteData = function(kc)
{
	var keyword = this.getCurrentKeyword();

	if (!this.options.url || !keyword)
		return false;

	var ajax = new CAjax('json');
	ajax.cbData.cls = this;
	ajax.cbData.kc = kc;
	ajax.onload = function(data)
	{
		try
		{
			if (data.length)
			{
				this.cbData.cls.data = data;

				// Now that the data has been populated, create ac dialog
				this.cbData.cls.toComplete(this.cbData.kc);
			}
		}
		catch(e)
		{
			//alert("There was a problem loading data " + e);
		}
	};
	var args = [["search", keyword]];
	ajax.exec(this.options.url, args);
}

/**
 * Get current keyword from input box
 *
 * @return {string} The current keyword (trimmed)
 */
Alib_Ui_AutoComplete.prototype.getCurrentKeyword = function()
{
	var ret = "";

	// Are we allowing for multiple keywords by using delimiters?
	if (this.actb_delimiter && this.actb_delimiter.length > 0)
	{
		var caret_pos_start = alib.dom.getCaretStart(this.currInput);
		var caret_pos_end = alib.dom.getCaretEnd(this.currInput);
		
		var delim_split = '';
		for (i=0;i<this.actb_delimiter.length;i++)
		{
			delim_split += this.actb_delimiter[i];
		}

		delim_split = delim_split.addslashes();
		var delim_split_rx = new RegExp("(["+delim_split+"])");
		c = 0;
		this.delimWords = new Array();
		this.delimWords[0] = '';

		for (i=0,j=this.currInput.value.length;i<this.currInput.value.length;i++,j--)
		{
			if (this.currInput.value.substr(i,j).search(delim_split_rx) == 0)
			{
				ma = this.currInput.value.substr(i,j).match(delim_split_rx);
				this.delimChar[c] = ma[1];
				c++;
				this.delimWords[c] = '';
			}
			else
			{
				this.delimWords[c] += this.currInput.value.charAt(i);
			}
		}

		var l = 0;
		this.cdelimWord = -1;
		for (i=0;i<this.delimWords.length;i++)
		{
			if (caret_pos_end >= l && caret_pos_end <= l + this.delimWords[i].length)
			{
				this.cdelimWord = i;
			}
			l+=this.delimWords[i].length + 1;
		}

		ret = this.delimWords[this.cdelimWord].trim(); 
	}
	else
	{
		// No keywords, just use input value
		ret = this.currInput.value;
	}

	return ret;
}

/**
 * Initiate autocomplete data and dialog
 *
 * TODO: This is to replace the inline function actb_tocomplete
 *
 * @param {integer} kc The keycode last pressed
 */
Alib_Ui_AutoComplete.prototype.toComplete = function()
{
	/*
	if (kc == 38 || kc == 40 || kc == 13) return;

	var i;

	if (actb_display)
	{ 
		var word = 0;
		var c = 0;

		for (var i=0;i<=actb_self.data.length;i++)
		{
			if (actb_bool[i]) c++;

			if (c == actb_pos)
			{
				word = i;
				break;
			}
		}

		actb_pre = word;
	}
	else
	{ 
		actb_pre = -1
	};
	
	// If the value is empty the remove list of words
	if (actb_curr.value == '')
	{
		actb_mouse_on_list = 0;
		actb_removedisp();
		return;
	}

	var ot = actb_self.getCurrentKeyword();
	var t = ot.addslashes();

	if (ot.length == 0)
	{
		actb_mouse_on_list = 0;
		actb_removedisp();
	}

	if (ot.length < actb_self.actb_startcheck) return this;

	if (actb_self.actb_firstText)
		var re = new RegExp("^" + t, "i");
	else
		var re = new RegExp(t, "i");

	actb_total = 0;
	actb_tomake = false;
	actb_kwcount = 0;

	for (i=0;i<actb_self.data.length;i++)
	{
		actb_bool[i] = false;

		if (re.test(actb_self.data[i]))
		{
			actb_total++;
			actb_bool[i] = true;
			actb_kwcount++;
			if (actb_pre == i) actb_tomake = true;
		}
	}

	if (actb_toid) clearTimeout(actb_toid);
	if (actb_self.timeOut > 0) actb_toid = setTimeout(function(){actb_mouse_on_list = 0;actb_removedisp();},actb_self.timeOut);
	actb_generate();
	*/
}

/**
*  Ajax Autocomplete for jQuery, version 1.1.3
*  (c) 2010 Tomas Kirda
*
*  Ajax Autocomplete for jQuery is freely distributable under the terms of an MIT-style license.
*  For details, see the web site: http://www.devbridge.com/projects/autocomplete/jquery/
*
*  Last Review: 04/19/2010
*/

/*jslint onevar: true, evil: true, nomen: true, eqeqeq: true, bitwise: true, regexp: true, newcap: true, immed: true */
/*global window: true, document: true, clearInterval: true, setInterval: true, jQuery: true */

