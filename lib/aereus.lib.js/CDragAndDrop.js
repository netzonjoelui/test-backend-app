/****************************************************************************
*	
*	Class:		CDragAndDrop
*
*	Purpose:	Add Drag&Drop functionality
*
*	Author:		joe, sky.stebnicki@aereus.com
*				Copyright (c) 2006 Aereus Corporation. All rights reserved.
*
*****************************************************************************/

/*
*	Notes
* 	
*	1.	If there is no drop zone defined then just drag the entire if (if absolute positioned)
*		Otherwise, if there are dropzones, create a dummy div(absoutle) with a copy of source to drag.
*		That way if the user does not drop into a drop zone then we can just return to our previous position.
*/
var drag_id = 0;
var DragAndDrop = {

    obj : null,
	dropzones : new Array,
	
	registerDragable : function (o, oRoot, dzgroup, minX, maxX, minY, maxY)
	{
		o.onmousedown	= DragAndDrop.init;
		o.onselectstart	= function() { return false; } // Disable text selection
		o.startdrag		= DragAndDrop.start;
        o.hmode			= true ;
        o.vmode			= true ;

        o.root = (oRoot && oRoot != null) ? oRoot : o ;
		
		// We can restrict this drag item to a groups of dropzones with dzgroup
		if (dzgroup)
			o.m_groupName = dzgroup;

		// Create new element that will hold copy of orig
		o.m_dragCon = ALib.m_document.createElement("div");
		ALib.m_document.body.appendChild(o.m_dragCon);
		o.m_dragCon.style.position = "absolute";
		o.m_dragCon.style.top = "0px";
		o.m_dragCon.style.left = "0px";
		o.m_dragCon.style.display = "none";

        o.minX = typeof minX != 'undefined' ? minX : null;
        o.minY = typeof minY != 'undefined' ? minY : null;
        o.maxX = typeof maxX != 'undefined' ? maxX : null;
        o.maxY = typeof maxY != 'undefined' ? maxY : null;

        o.root.onDragStart	= new Function();
        o.root.onDragEnd	= new Function();
        o.root.onDrag		= new Function();
	},	

	/***********************************************************************************
	 *
	 *	Function: 	registerDragableChild
	 *
	 *	Purpose:	Allows for nestled dragable objects
	 *
	 *	Arguements:	p = parent of o, p must be o.parentNode 
	 *				o = child object to be set as dragable
	 *
	 ***********************************************************************************/
	registerDragableChild : function (p, o, oRoot, dzgroup, minX, maxX, minY, maxY)
	{
		// If child has a parent
		if(p)
		{
			// set drag_id, initialize object as dragable
			drag_id++;
			o.drag_id = drag_id;
			o.dragable = true;

			// parent is only dragable when child is not being dragged 
			o.parent = p;
			
			DragAndDrop.registerDragable(o, oRoot, dzgroup, minX, maxX, minY, maxY);
		}
		// Else, parent is child of root
		else
		{
			// set drag_id, initialize object as dragable
			drag_id++;
			o.drag_id = drag_id;			
			o.dragable = true;
			
			// parent is only dragable when child is not being dragged 
			o.parent = null;

			DragAndDrop.registerDragable(o, oRoot, dzgroup, minX, maxX, minY, maxY);
		}
	},
	
	init : function(e)
    {
        var o = DragAndDrop.obj = this;
		//e = DragAndDrop.fixE(e);
		var ev = e | window.event;
        var ey = ev.clientY;
        var ex = ev.clientX;
		var dz = DragAndDrop.inDropZone(o, ex, ey);
		
		// If object has a drag_id, disable parent nodes from being dragged
		if(o.drag_id)
		{
			// make sure parent has not changed
			if(dz && o.parent)
				o.parent = dz.parentNode;
			
			// disable parent nodes
			if(o.parent)
				o.parent.dragable = false;
			
			if(o.dragable == true)
			{
				DragAndDrop.startCoords = alib.dom.getMouseCoords();

				ALib.m_document.onmousemove = function(e)
				{
					var cur_pos = alib.dom.getMouseCoords();

					if (cur_pos.x >(DragAndDrop.startCoords.x+1) || cur_pos.x <(DragAndDrop.startCoords.x-1)
						|| cur_pos.y >(DragAndDrop.startCoords.y+1) || cur_pos.y <(DragAndDrop.startCoords.y-1))
					{
						o.startdrag(e);
					}
				}

				ALib.m_document.onmouseup = function(e)
				{
					ALib.m_document.onmousemove = function() {}
				}

				return false;
			}
		}
		else
		{
			DragAndDrop.startCoords = alib.dom.getMouseCoords();

			ALib.m_document.onmousemove = function(e)
			{
				var cur_pos = alib.dom.getMouseCoords();

				if (cur_pos.x >(DragAndDrop.startCoords.x+1) || cur_pos.x <(DragAndDrop.startCoords.x-1)
					|| cur_pos.y >(DragAndDrop.startCoords.y+1) || cur_pos.y <(DragAndDrop.startCoords.y-1))
				{
					o.startdrag(e);
				}
			}

			ALib.m_document.onmouseup = function(e)
			{
				ALib.m_document.onmousemove = function() {}
			}

			return false;
		}
    },

    start : function(e)
    {
        var o = DragAndDrop.obj = this;
        e = DragAndDrop.fixE(e);
		var pos = alib.dom.getElementPosition(DragAndDrop.obj.root);
        var y = pos.y;
        var x = pos.x;
        o.root.onDragStart(x, y);
		
		// Now create a default setDragGuiCon if not already called	
		if (typeof o.m_dragConSet == "undefined")
		{
			var icon = ALib.m_document.createElement("div");
			alib.dom.styleSet(icon, "border", o.style.border);
			alib.dom.styleSet(icon, "width", (pos.r - pos.x) + "px");
			alib.dom.styleSet(icon, "height", (pos.b - pos.y) + "px");
			icon.innerHTML = o.root.innerHTML;
			DragAndDrop.setDragGuiCon(o, icon);
		}

		o.m_dragCon.style.display = "block";
		o.m_dragCon.style.top = y + "px";
		o.m_dragCon.style.left = x+ "px";

        o.lastMouseX = e.clientX;
        o.lastMouseY = e.clientY;

		if (o.minX != null) o.minMouseX = e.clientX - x + o.minX;
		if (o.maxX != null) o.maxMouseX = o.minMouseX + o.maxX - o.minX;

		if (o.minY != null) o.minMouseY = e.clientY - y + o.minY;
		if (o.maxY != null) o.maxMouseY = o.minMouseY + o.maxY - o.minY;

        ALib.m_document.onmousemove = DragAndDrop.drag;
        ALib.m_document.onmouseup	 = DragAndDrop.end;

        return false;
    },

    drag : function(e)
    {
        e = DragAndDrop.fixE(e);
        var o = DragAndDrop.obj;

        var ey = e.clientY;
        var ex = e.clientX;
        var y = parseInt(o.m_dragCon.style.top);
        var x = parseInt(o.m_dragCon.style.left);
        var nx, ny;

        if (o.minX != null) ex = Math.max(ex, o.minMouseX);
        if (o.maxX != null) ex = Math.min(ex, o.maxMouseX);
        if (o.minY != null) ey = Math.max(ey, o.minMouseY);
        if (o.maxY != null) ey = Math.min(ey, o.maxMouseY);

        nx = x + (ex - o.lastMouseX);
        ny = y + (ey - o.lastMouseY);
	
		// Check if we are over a drop zone
		var dz = DragAndDrop.inDropZone(o, ex, ey);
		if (o.m_dz)
		{
			if (dz)
			{
				if (dz != o.m_dz)
				{
					DragAndDrop.dzDragExit(o.m_dz, o);
					o.m_dz = dz;
					DragAndDrop.dzDragEnter(dz, o, ex, ey);
				}
			}
			else
			{
				DragAndDrop.dzDragExit(o.m_dz, o);
				o.m_dz = null;
			}
		}
		else
		{
			if (dz)
			{
				o.m_dz = dz;
				DragAndDrop.dzDragEnter(dz, o, ex, ey);
			}
		}

		// Check sortable
		if (dz)
		{
			if (dz.m_isSortable)
			{
				// Find out what element the dragged element is over
				for (var i = 0; i < dz.childNodes.length; i++)
				{
					var node = dz.childNodes.item(i);
					var objPos = alib.dom.getElementPosition(node, true);

					// test to see if x and y are in object region
					if (ex >= objPos.x && ey >= objPos.y
						&& ey <= objPos.b && ex <= objPos.r)
					{
						if (node != o)
						{
							var before = (ey < (objPos.y+((objPos.b-objPos.y)/2))) ? true : false;
							if (before)
								dz.insertBefore(o, node);
							else
								insertAfter(dz, o, node);
						}
					}
				}
			}
		}

		if (DragAndDrop.obj.m_dragOffsetX)
	        DragAndDrop.obj.m_dragCon.style["left"] = (ex + DragAndDrop.obj.m_dragOffsetX) + "px";
		else
	        DragAndDrop.obj.m_dragCon.style["left"] = nx + "px";
		
		if (DragAndDrop.obj.m_dragOffsetY)
        	DragAndDrop.obj.m_dragCon.style["top"] = (ey + DragAndDrop.obj.m_dragOffsetY) + "px";
		else
			DragAndDrop.obj.m_dragCon.style["top"] = ny + "px";

        DragAndDrop.obj.lastMouseX    = ex;
        DragAndDrop.obj.lastMouseY    = ey;
		
		// If we are offsetting the container then report mouse pos
		if (DragAndDrop.obj.m_dragOffsetX)
			nx = ex;
		if (DragAndDrop.obj.m_dragOffsetY)
			ny = ey;
        DragAndDrop.obj.root.onDrag(nx, ny);
        return false;	
    },

    end : function()
    {
        var o = DragAndDrop.obj;
		if(o.drag_id)
		{
			// Restore all parents to dragable
			while(o.parent)
			{
				o.parent.dragable = true;
				o = o.parent;
			}
		}

        ALib.m_document.onmousemove = null;
        ALib.m_document.onmouseup   = null;
		var x = DragAndDrop.obj.lastMouseX;
		var y = DragAndDrop.obj.lastMouseY;
		var pos = alib.dom.getElementPosition(DragAndDrop.obj.m_dragCon);
		var reportX = pos.x;
		var reportY = pos.y;
		// If we are offsetting the container then report mouse pos
		if (DragAndDrop.obj.m_dragOffsetX)
			reportX = x;
		if (DragAndDrop.obj.m_dragOffsetY)
			reportY = y;

		if (!DragAndDrop.dropzones.length || (DragAndDrop.dropzones.length 
			&& DragAndDrop.obj.m_dz) || !DragAndDrop.obj.m_groupName)
		{
			DragAndDrop.obj.root.onDragEnd(reportX, reportY);
		}

		// Move original object
		DragAndDrop.obj.m_dragCon.style.display = "none";

		if(DragAndDrop.obj.m_dz)
		{
			DragAndDrop.dzDragDrop(DragAndDrop.obj.m_dz, DragAndDrop.obj);
		}

        DragAndDrop.obj = null;
    },

    fixE : function(e)
    {
        if (typeof e == 'undefined') 
		{
			if (ALib.m_evwnd)
				e = ALib.m_evwnd.event;
			else
				e = window.event;
		}
        if (typeof e.layerX == 'undefined') e.layerX = e.offsetX;
        if (typeof e.layerY == 'undefined') e.layerY = e.offsetY;
        return e;
    },

	// The below function will set the icon/container under the cursor when dragged (can be object)
	setDragGuiCon : function(obj, con, offsetX, offsetY)
	{
		DragAndDrop.clearDragGuiCon(obj.m_dragCon);
		obj.m_dragCon.appendChild(con);
		obj.m_dragConSet = true;

		// offsetX and offsetY are used to position div relative to mouse
		obj.m_dragOffsetX = null;
		obj.m_dragOffsetY = null;

		if (offsetX)
			obj.m_dragOffsetX = offsetX;
		
		if (offsetY)
			obj.m_dragOffsetY = offsetY;
	},

	clearDragGuiCon : function(con)
	{
		con.innerHTML = "";
	},

	inDropZone : function(e, x, y)
	{
		var objPos = [];

		for (var i = 0; i < this.dropzones.length; i++)
		{
			if (this.dropzones[i])
			{
				// IE never clears dropsozes if new pages are reloaded
				// so we have to skip over null dropzones
				// These should eventually be purged
				try 
				{
					objPos = alib.dom.getElementPosition(this.dropzones[i], true);	

					// test to see if x and y are in object region
					if (x >= objPos.x && y >= objPos.y
						&& y <= objPos.b && x <= objPos.r)
					{
						if (e.m_groupName)
						{
							if (e.m_groupName == this.dropzones[i].m_groupName)
								return this.dropzones[i];
						}
						//else
						//	return this.dropzones[i];
					}
				}
				catch (e) { }
			}
		}
		
		return null;
	},

	mouseCoords : function(ev)
	{
		if(ev.pageX || ev.pageY)
			return {x:ev.pageX, y:ev.pageY};
		
		return {
			x:ev.clientX + ALib.m_document.body.scrollLeft - ALib.m_document.body.clientLeft,
			y:ev.clientY + ALib.m_document.body.scrollTop  - ALib.m_document.body.clientTop
		};
	},

	/* Dropzone functions */
	registerDropzone : function (o, groupname)
	{
		var ind = this.dropzones.length;
		this.dropzones[ind] = o;

		// set group name
		o.m_groupName = groupname;

		o.onDragEnter = new Function;
		o.onDragExit = new Function;
		o.onDragDrop = new Function;
	},

	registerSortable : function (o)
	{
		o.m_isSortable = true;

		o.dzGetSortOrder = function()
		{
			var sorder = new Array();

			for (var i = 0; i < this.childNodes.length; i++)
			{
				sorder[sorder.length] = this.childNodes.item(i).id;
			}

			return sorder;
		}
		/*
		for (var i = 0; i < o.childNodes.length; i++)
		{
			var node = o.childNodes.item(i);
			DragAndDrop.registerDragable(node);
		}
		*/
	},

	dzDragEnter : function(dz, e, ex, ey)
	{
		// Call in dropzone when item is dragged over it
		dz.onDragEnter(e);
	},

	dzDragExit : function(dz, e)
	{
		// Call in dropzone when item leaves drop zone
		dz.onDragExit(e);
	},
	
	dzDragDrop : function(dz, e)
	{
		/*
		if (dz.m_isSortable)
		{
			if (dz.m_overNode)
			{
				insertAfter(dz, e, dz.m_overNode);
			}
		}
		*/

		// Call in dropzone when item leaves drop zone
		dz.onDragDrop(e);
	}
};

