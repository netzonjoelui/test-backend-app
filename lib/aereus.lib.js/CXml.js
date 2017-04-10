/*======================================================================================
	
	Module:		CXml

	Purpose:	Handle working with xml documents

	Author:		joe, sky.stebnicki@aereus.com
				Copyright (c) 2011 Aereus Corporation. All rights reserved.
	
	Usage:		

======================================================================================*/

/***********************************************************************************
 *
 *	Class: 		CXml
 *
 *	Purpose:	This is the node linked list
 *
 *	Arguements:	name	- string: the name of the node
 *				text	- string: the value of the node
 *
 ***********************************************************************************/
function CXml(name, text)
{
	this.m_name = name;
	this.m_text = (text)?text:"";
	this.m_attributes = new Array();
	this.m_children = new Array();
	this.m_xmlcld = null; // Reference to DOM xml node
}

/***********************************************************************************
 *
 *	Function: 	getNumChildren
 *
 *	Purpose:	Get number of children (try not to access vars directly)
 *
 ***********************************************************************************/
CXml.prototype.getNumChildren = function ()
{
	if (this.m_children)
		return this.m_children.length;
	else
		return 0;
}

/***********************************************************************************
 *
 *	Function: 	getChildNode
 *
 *	Purpose:	Retrieve a node at a specific index
 *
 *	Arguements:	iIndex	- integer: index of node to retrieve
 *
 ***********************************************************************************/
CXml.prototype.getChildNode = function(iIndex)
{
	return this.m_children[iIndex];
}

/***********************************************************************************
 *
 *	Function: 	getChildNodesByName
 *
 *	Purpose:	Retrieve nodes by name
 *
 *	Arguements:	name	- string: name of nodes to retrieve
 *
 ***********************************************************************************/
CXml.prototype.getChildNodesByName = function(name)
{
	if (this.m_query_res && this.m_query_res.length)
		delete this.m_query_res;
	
	// mres is used as a temporary storage array of node references
	this.m_query_res = new Array();
	
	// Loop through children looking for 'name'
	var num = this.getNumChildren();
	var iFound = 0;
	for (i = 0; i < num; i++)
	{
		if (this.getChildNode(i).m_name == name)
		{
			this.m_query_res[iFound] = this.getChildNode(i);
			iFound++;
		}
	}

	return this.m_query_res;
}

/***********************************************************************************
 *
 *	Function: 	getChildNodeByName
 *
 *	Purpose:	Retrieve a single node by name
 *
 *	Arguements:	name	- string: name of nodes to retrieve
 *
 ***********************************************************************************/
CXml.prototype.getChildNodeByName = function (name)
{
	var val = null;

	// Loop through children looking for 'name'
	for (var p = 0; p < this.getNumChildren(); p++)
	{
		if (this.getChildNode(p).m_name == name)
		{
			val = this.getChildNode(p);
			break;
		}
	}

	return val;
}

/***********************************************************************************
 *
 *	Function: 	getChildNodesValByName
 *
 *	Purpose:	Retrieve node value by name. If more than one node with that name
 *				is found it will return the first value. This is best used where
 *				you know for sure there will only be one child node with that name.
 *
 *	Arguements:	name	- string: name of nodes to retrieve
 *
 ***********************************************************************************/
CXml.prototype.getChildNodeValByName = function(name)
{
	var val = "";

	// Loop through children looking for 'name'
	for (var p = 0; p < this.getNumChildren(); p++)
	{
		if (this.getChildNode(p).m_name == name)
		{
			val = this.getChildNode(p).m_text;
			break;
		}
	}

	return val;
}

/***********************************************************************************
 *
 *	Function: 	getAttribute
 *
 *	Purpose:	Get node attribute by name
 *
 *	Arguements:	name	- string: name of attribute to retrieve
 *
 ***********************************************************************************/
CXml.prototype.getAttribute = function(name)
{
	var val = this.m_xmlcld.getAttribute(name);
	if (!val)
		val = "";
	return val;
}

/***********************************************************************************
 *
 *	Function: 	getNodeValue
 *
 *	Purpose:	Get the text of a node
 *
 ***********************************************************************************/
CXml.prototype.getValue = function()
{
	//return this.m_xmlcld.nodeValue;
	return this.m_text;
}

/***********************************************************************************
 *
 *	Function: 	text
 *
 *	Purpose:	Get the text of a node
 *
 ***********************************************************************************/
CXml.prototype.text = function()
{
	return this.getValue();
}

/***********************************************************************************
 *
 *	Function: 	name
 *
 *	Purpose:	Get the name of a node
 *
 ***********************************************************************************/
CXml.prototype.name = function()
{
	return this.m_name;
}
