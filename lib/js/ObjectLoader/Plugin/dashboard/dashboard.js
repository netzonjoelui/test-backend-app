{
	name:"dashboard",
	title:"Display dashboard",
	mainObject:null,
	olCls:null, // object loader class reference

	/**
	 * Object loader form
	 *
	 * @var {AntObjectLoader_Form}
	 */
	formObj: null,

	/**
	 * Contianer to print the plugin into
	 *
	 * @var {DOMElement}
	 */
	com: null,

	/**
	 * Layout array
	 *
	 * Structure: [ {width:string, widgets: [ {id:, widget:, data:} ] } ]
	 *
	 * @var {Array}
	 */
	layout: new Array(),

	/**
	 * Current columns array
	 *
	 * @var {Array}
	 */
	columns: new Array(),

	/**
	 * Array of widgets currently loaded
	 *
	 * @var {CWidget[]}
	 */
	dashboardWidgets: new Array(),

	/**
	 * Flag to indicate the dashboard has loaded
	 *
	 * @var {boolean}
	 */
	loaded: false,

	/**
	 * Main function called when form is ready to load the plugin
	 */
	main:function(con)
	{
		this.con = con;

		// Add "Add Widget" button to toolbar
		if (this.olCls)
		{
			this.olCls.pluginAddToolbarEntry("Add Widget ", function(cbData) {
				var widBrowser = new CWidgetBrowser();
				widBrowser.cls = cbData.cls;                                                
				widBrowser.onSelect = function(widgetId)
				{
					this.cls.addWidget(widgetId);
				}
				widBrowser.showDialog();
			}, { cls:this });
		}

		// Render the dashboard
		if (this.mainObject.id)
			this.loadLayout();
		else
			this.con.innerHTML = "<p class='notice'>Please enter a name and save changes above. Once saved you can modify the layout and add widgets to this dashboard.</p>";
			//this.renderDashboard();
	},

	/**
	 * Callback called when the main object is being saved
	 */
	save:function()
	{
		this.saveLayout();
	},

	/**
	 * Internal callback used to let the main form know we have finished loading
	 */
	onsave:function()
	{
	},

	/**
	 * Called after the main object has been saved
	 */
	objectsaved:function()
	{
		if (!this.loaded)
		{
			this.isNew = true;
			this.loadLayout();
		}
	},

	/**
	 * Hook value changed for the main objects
	 */
	onMainObjectValueChange:function(fname, fvalue, fkeyName)
	{
		if ("num_columns" == fname && this.loaded)
		{
			if (fvalue > this.layout.length)
			{
				// extend
				while (this.layout.length < fvalue)
					this.layout.push({});
			}
			else if (fvalue < this.layout.length)
			{
				// trim
				for (var i = this.layout.length; i > fvalue; i--)
				{
					this.layout.splice((i-1), 1);
				}
			}

			// Redraw
			this.renderDashboard();
		}
	},

	/**
	 * Render the dashboard into the DOM
	 *
	 * @private
	 */
	renderDashboard:function()
	{
		this.con.innerHTML = "";

		var spCon = new CSplitContainer("verticle", "100%");
		spCon.resizable = true;
		spCon.print(this.con);
		
		// First add columns
		for (col in this.layout)
		{
			var width = (this.layout[col].width) ? this.layout[col].width : null;
			this.columns[col] = spCon.addPanel(width, "hidden");
		}
			
		// Now add all widgets. We do this separately so that the width is accurately 
		// set after all columns have been added.
		for (col in this.layout)
		{
			if (this.layout[col].widgets)
			{
				for (var i in this.layout[col].widgets)
				{
					var con = alib.dom.createElement("div", this.columns[col]);

					this.renderWidget(col, this.layout[col].widgets[i]);
				}
			}

			// Always add blank resize box at the end of each col
			var widgetBox = new CWidgetBox("dashboardDropZone", "300px");
			widgetBox.wbCls = this;
			widgetBox.onMoved = function(newCon)
			{
				this.wbCls.saveLayout();
			}
			widgetBox.print(this.columns[col]);
		}
		
		spCon.cls = this;
		spCon.onPanelResize = function(sizes)
		{
			this.cls.saveLayout(this.m_columns);
		};

	},

	/**
	 * Render a widget into the specified column
	 *
	 * @private
	 * @param {int} col The column to insert this widget into
	 * @param {Object} widget Widget instance to add
	 */
	renderWidget:function(col, widget)
	{
		// Get last widgetbox to insert before    
		var lastbox = this.columns[col].childNodes[this.columns[col].childNodes.length - 1];

		// Create contianer (handles drag and drop)    
		var wIndex = this.dashboardWidgets.length;
		var widgetBox = new CWidgetBox("dashboardDropZone");
		widgetBox.cls = this;
		widgetBox.wIndex = wIndex
		widgetBox.onMoved = function(newCon) 
		{
			this.cls.saveLayout(); 
			this.cls.dashboardWidgets[this.wIndex].main()
		}
		widgetBox.onBeforeMove = function(from, to) 
		{ 
			//this.cls.dashboardWidgets[this.wIndex].exit();        
		}
		
		var outerCon = widgetBox.getCon();
		 
		// Used to determine what item is in container    
		outerCon.m_id = widget.id;
		widgetBox.printBefore(this.columns[col], lastbox);

		// Create content table
		var widgetCon = alib.dom.createElement("div", outerCon);
		alib.dom.styleSetClass(widgetCon, "dbWidget");

		var widgetConHeader = alib.dom.createElement("div", widgetCon);
		alib.dom.styleSetClass(widgetConHeader, "dbWidgetHeader");

		var widgetConOpt = alib.dom.createElement("div", widgetConHeader);
		alib.dom.styleSetClass(widgetConOpt, "dbWidgetOpt");

		var widgetConTitle = alib.dom.createElement("div", widgetConHeader);
		alib.dom.styleSetClass(widgetConTitle, "dbWidgetTitle");

		var widgetConBody = alib.dom.createElement("div", widgetCon);
		alib.dom.styleSetClass(widgetConBody, "dbWidgetBody");
		
		outerCon.m_con = widgetConBody;

		// Create widget application class
		var widgetApp = eval("new " + widget.widget + "()");
		widgetConTitle.innerHTML = widgetApp.title;
		widgetApp.m_widgetBox = widgetBox;
		this.dashboardWidgets[wIndex] = widgetApp;
		widgetApp.dashboardCls = this;
		widgetApp.m_widTitle = widgetConTitle;
		widgetApp.m_container = widgetConBody;
		widgetApp.m_id = widget.id;    
			
		if (typeof widget.data != "undefined")
			widgetApp.m_data = widget.data;
		
		// Add dropdown context menu
		var dm = new CDropdownMenu();
		dm.handleDuplicates = true;
		//this.dashboardMenus.push(dm);
		widgetApp.m_dm = dm;

		// Add view
		if (this.olCls.antView)
			widgetApp.antView = this.olCls.antView;
		
		// If user has permision to change layout
		if(this.mainObject.security.edit) 
		{
			dm.addEntry('Remove Widget', function(cls, id) { cls.removeWidget(id);}, null, null, [this, widget.id]);        
			widgetConOpt.appendChild(dm.createImageMenu("/images/icons/gear_16.png", "/images/icons/gear_16.png", "/images/icons/gear_16.png"));
			
			DragAndDrop.registerDragable(widgetConTitle, widgetCon, "dashboardDropZone");
			var drag_icon = ALib.m_document.createElement("div");
			drag_icon.innerHTML = "Move: " + widgetConTitle.innerHTML;
			DragAndDrop.setDragGuiCon(widgetConTitle, drag_icon, 15, 15);
		}
		
		// Execute widget
		widgetApp.main();
	},

	/**
	 * Load the dashboard layout
	 */
	loadLayout:function()
	{
		var ajax = new CAjax('json');
		ajax.cbData.cls = this;
		ajax.onload = function(ret)
		{
			// Show add widget browser
			if (this.cbData.cls.isNew)
			{
				var widBrowser = new CWidgetBrowser();
				widBrowser.cls = this.cbData.cls;                                                
				widBrowser.onSelect = function(widgetId)
				{
					this.cls.addWidget(widgetId);
				}
				widBrowser.showDialog();

				this.cbData.cls.isNew = false;
			}

			// Redner the dashboard
			this.cbData.cls.loaded = true;
			this.cbData.cls.layout = ret;
			this.cbData.cls.renderDashboard();

		};
		var args = new Array();
		args[args.length] = ['dbid', this.mainObject.id];
		ajax.exec("/controller/Dashboard/getLayout", args);
	},

	/**
	 * Execute main function for all widgets once loaded
	 */
	executeWidgets:function()
	{
		for(widget in this.dashboardWidgets)
		{
			var currentWidget = this.dashboardWidgets[widget];
			currentWidget.main();
		}
	},

	/**
	 * Execuate exit function for all widgets to close out
	 */
	exitWidgets:function()
	{
		for(widget in this.dashboardWidgets)
		{
			var currentWidget = this.dashboardWidgets[widget];
			currentWidget.exit();
			if (currentWidget.m_dm)
				currentWidget.m_dm.destroyMenu();
		}
	},

	/**
	 * Save the dashboard layout
	 * 
	 * @param {Array} spColumns Array columns of CSplitContainer
	 */
	saveLayout:function(spColumns)
	{
		if (!this.mainObject.id)
		{
			this.onsave();
			return;
		}

		var args = new Array();    
		args[args.length] = ['dashboardId', this.mainObject.id];
		args[args.length] = ["columnCount", this.columns.length];
		
		for(col in this.columns)
		{
			var currentCol = this.columns[col];
			
			var colWidgets = "";

			for (var j = 0; j < currentCol.childNodes.length; j++)
			{
				if (currentCol.childNodes[j].m_id)
				{
					colWidgets += (colWidgets) ? ':' : '';
					colWidgets += currentCol.childNodes[j].m_id;
				}
			}

			args[args.length] = ["col_" + col, colWidgets];
		}
		
		if(typeof spColumns !== "undefined")
		{
			for(column in spColumns)
			{
				var currentColumn = spColumns[column];
				args[args.length] = ["columnWidth_" + column, currentColumn.offsetWidth];
			}
		}
		
		var ajax = new CAjax('json');    
		ajax.exec("/controller/Dashboard/saveLayout", args);

		this.onsave();
	},

	/**
	 * Col has been resized
	 * 
	 * @this {AntObjectLoader_Dashboard} 
	 * @event onColResize
	 */
	onColResize:function()
	{
		this.executeWidgets();
		var args = new Array();
		args[args.length] = ['dashboardId', this.mainObject.id];
		
		for(col in this.columns)
		{
			var currentCol = this.columns[col];
			args[args.length] = ["col_" + col, currentCol.offsetWidth + "px"];
		}
		
		ajax = new CAjax('json');    
		ajax.exec("/controller/Dashboard/saveLayoutResize", args);
	},

	/**
	 * Col is being resized
	 * 
	 * @this {AntObjectLoader_Dashboard} 
	 * @event onColResizeStart
	 */
	onColResizeStart:function()
	{
		this.exitWidgets();
		for(col in this.columns)
		{
			var currentCol = this.columns[col];
			
			for (var j = 0; j < currentCol.childNodes.length; j++)
			{
				if (currentCol.childNodes[j].m_id)
					currentCol.childNodes[j].m_con.innerHTML = "";
			}
		}
	},

	/**
	 * Adds the widget in the database
	 *
	 * @public
	 * @this {CAntObject}
	 * @param {Integer} widgetId  Widget Id
	 */
	addWidget:function(widgetId)
	{
		var ajax = new CAjax('json');
		ajax.cbData.cls = this;
		ajax.onload = function(ret)
		{
			this.cbData.cls.renderWidget(0, ret);
		};
		var args = new Array();
		args[args.length] = ['dashboardId', this.mainObject.id];
		args[args.length] = ['widgetId', widgetId];
		args[args.length] = ['col', 0];
		ajax.exec("/controller/Dashboard/addWidget", args);
	},

	/**
	 * Remove a widget from the dashboard
	 *
	 * @this {AntObjectLoader_Dashboard} 
	 */
	removeWidget:function(dwid)
	{
		ajax = new CAjax('json');
		ajax.cbData.cls = this;
		ajax.onload = function(ret)
		{
			for(widget in this.cbData.cls.dashboardWidgets)
			{
				var currentWidget = this.cbData.cls.dashboardWidgets[widget];
				
				if(currentWidget.m_id == ret)
				{
					var widgetCon = currentWidget.m_widgetBox.getCon();
					widgetCon.parentNode.removeChild(widgetCon);
					currentWidget.exit();
					delete currentWidget;
				}
			}
		};
		var args = new Array();    
		args[args.length] = ['dwid', dwid];
		args[args.length] = ['dashboardId', this.mainObject.id];
		ajax.exec("/controller/Dashboard/removeWidget", args);
	}
}
