/****************************************************************************
*	
*	Class:		CWidWeather
*
*	Purpose:	Main application for the weather center
*
*	Author:		joe, sky.stebnicki@aereus.com
*				Copyright (c) 2006 Aereus Corporation. All rights reserved.
*
*****************************************************************************/
function CWidWeather()
{
	this.title = "Weather";
	this.m_container = null;	// Set by calling process
}

/*************************************************************************
*	Function:	main
*
*	Purpose:	Entry point for application
**************************************************************************/
CWidWeather.prototype.main = function()
{
	this.m_container.innerHTML = "Loading, please wait...";

	// Create context menu
	// ----------------------------------------------------------------------------
	var cls = this;
	this.m_dm.addEntry('Change Location', function (clsref) { clsref.setZip(); }, null, null, [cls]);

	this.loadWeather();
}

/**
 * Used for redrawing - this will be depricated soon
 */
CWidWeather.prototype.exit = function()
{
	this.m_container.innerHTML = "";
}

/**
 * Update user zipcode
 */
CWidWeather.prototype.setZip = function()
{
	var show = (this.m_zip) ? this.m_zip : '97477';
	var zcode = prompt('Please enter a symbol to add', show);
	this.m_container.innerHTML = "Setting zip, please wait";
    
    ajax = new CAjax('json');
    ajax.cbData.cls = this;
    ajax.onload = function(ret)
    {
        this.cbData.cls.m_zip = ret; 
        this.cbData.cls.loadWeather(); 
    };
    ajax.exec("/controller/Application/setZipcode",
                [["zipcode", zcode]]);
}

/**
 * Load weather based on zip code
 */
CWidWeather.prototype.loadWeatherOld = function()
{
	this.ajax = new CAjax();
	this.ajax.m_con = this.m_container;
	this.ajax.m_appcls = this;
	this.ajax.onload = function(root)
	{
		// Create addzip div if none
		var dv_zip = null;
		var city = "";
		var state = "";

		// Build Table
		var table = alib.dom.createElement("table");
		table.style.width = "100%";
		var tbody = alib.dom.createElement("tbody");
		table.appendChild(tbody);
		var tr_header = alib.dom.createElement("tr");
		tbody.appendChild(tr_header);
		var tr_icon = alib.dom.createElement("tr");
		tbody.appendChild(tr_icon);
		var tr_forecast = alib.dom.createElement("tr");
		tbody.appendChild(tr_forecast);
		var tr_temp = alib.dom.createElement("tr");
		tbody.appendChild(tr_temp);
		
		// Get contents of XML document
		if (root)
		{
			var num = root.getNumChildren();
			for (i = 0; i < num; i++)
			{
				var day = root.getChildNode(i);

				// Check for error
				if (day.m_name == "error")
				{
					if (day.m_text == "nozip")
					{
						dv_zip = alib.dom.createElement("div");
						dv_zip.style.paddingTop = "6px";
						dv_zip.style.paddingLeft = "3px";
						dv_zip.style.cursor = "pointer";
						dv_zip.m_appcls = this.m_appcls;
						dv_zip.onclick = function () { this.m_appcls.setZip(); }
						dv_zip.innerHTML = "Click here to enter your location!";
					}
				}
				
				if (day.m_name == "zip")
					this.m_appcls.m_zip = day.m_text;

				if (day.m_name == "city")
					city = day.m_text;
				
				if (day.m_name == "state")
					state = day.m_text;

				// Check for days
				if (day.m_name == "day")
				{
					// Populate vars
					var dayvar_num = day.getNumChildren();
					for (j = 0; j < dayvar_num; j++)
					{
						dayvar = day.getChildNode(j);
						
						switch (dayvar.m_name)
						{
						case "name":
							var day_name = dayvar.m_text;
							break;
						case "icon":
							var day_icon = dayvar.m_text;
							break;
						case "forecast":
							var day_forecast = dayvar.m_text;
							break;
						case "tempMax":
							var day_max = dayvar.m_text;
							break;
						case "tempMin":
							var day_min = dayvar.m_text;
							break;
						}
					}

					// Add day name to table
					var td = alib.dom.createElement("td");
					td.style.width = "20%";
					td.style.textAlign = "center";
					td.innerHTML = day_name;
					tr_header.appendChild(td);
					td = null;

					// Add icon to table
					var td = alib.dom.createElement("td");
					td.style.textAlign = "center";
					td.style.width = "20%";
					img = alib.dom.createElement("img");
					img.src = day_icon;
					img.border = "0";
					td.appendChild(img);
					tr_icon.appendChild(td);
					td = null;
					
					// Add description to table
					var td = alib.dom.createElement("td");
					td.style.textAlign = "center";
					td.style.width = "20%";
					td.innerHTML = day_forecast;
					tr_forecast.appendChild(td);
					td = null;
					
					// Add temerature to table
					var td = alib.dom.createElement("td");
					td.style.textAlign = "center";
					td.style.width = "20%";
					td.innerHTML = day_max + " | " + day_min;
					tr_temp.appendChild(td);
					td = null;

				}

			if (state && city)
				this.m_appcls.m_cct.setTitle(city + " " + state);
			}
		}

		// Add table to div
		this.m_con.innerHTML = "";
		if (dv_zip) // No zip
			this.m_con.appendChild(dv_zip);
		else
			this.m_con.appendChild(table);
	};

	this.ajax.exec("/widgets/xml_weather.awp");
}

/**
 * Load weather based on zip code using new controller
 */
CWidWeather.prototype.loadWeather = function()
{
	this.ajax = new CAjax("json");
	this.ajax.m_con = this.m_container;
	this.ajax.m_appcls = this;
	this.ajax.onload = function(data)
	{
		if (!data)
			return;

		this.m_con.innerHTML = "";

		if (data.error)
		{
			this.m_con.innerHTML = "";
			var dv_zip = alib.dom.createElement("div", this.m_con);
			dv_zip.style.paddingTop = "6px";
			dv_zip.style.paddingLeft = "3px";
			dv_zip.style.cursor = "pointer";
			dv_zip.m_appcls = this.m_appcls;
			dv_zip.onclick = function () { this.m_appcls.setZip(); }
			dv_zip.innerHTML = "Click here to enter your location!";
			return;
		}

		// TODO: Set the title
		//if (data.state && data.city)
			//this.m_appcls.m_cct.setTitle(data.city + " " + data.state);

		// Build Table
		var table = alib.dom.createElement("table", this.m_con);
		table.style.width = "100%";
		var tbody = alib.dom.createElement("tbody");
		table.appendChild(tbody);
		var tr_header = alib.dom.createElement("tr");
		tbody.appendChild(tr_header);
		var tr_icon = alib.dom.createElement("tr");
		tbody.appendChild(tr_icon);
		var tr_forecast = alib.dom.createElement("tr");
		tbody.appendChild(tr_forecast);
		var tr_temp = alib.dom.createElement("tr");
		tbody.appendChild(tr_temp);
		
		// Loop through weather objects
		for (var i in data.days)
		{
			this.m_appcls.m_zip = data.days[i].zip;
			var day_name = data.days[i].name;
			var day_icon = data.days[i].icon;
			var day_forecast = data.days[i].forecast;
			var day_max = data.days[i].tempMax;
			var day_min = data.days[i].tempMin;

			// Add day name to table
			var td = alib.dom.createElement("td");
			td.style.width = "20%";
			td.style.textAlign = "center";
			td.innerHTML = day_name;
			tr_header.appendChild(td);
			td = null;

			// Add icon to table
			var td = alib.dom.createElement("td");
			td.style.textAlign = "center";
			td.style.width = "20%";
			img = alib.dom.createElement("img");
			img.src = day_icon;
			img.border = "0";
			td.appendChild(img);
			tr_icon.appendChild(td);
			td = null;
			
			// Add description to table
			var td = alib.dom.createElement("td");
			td.style.textAlign = "center";
			td.style.width = "20%";
			td.innerHTML = day_forecast;
			tr_forecast.appendChild(td);
			td = null;
			
			// Add temerature to table
			var td = alib.dom.createElement("td");
			td.style.textAlign = "center";
			td.style.width = "20%";
			td.innerHTML = day_max + " | " + day_min;
			tr_temp.appendChild(td);
			td = null;
		}
	};

	this.ajax.exec("/controller/Application/getWeather");
}
