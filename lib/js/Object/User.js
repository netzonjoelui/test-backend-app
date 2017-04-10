/**
* @fileoverview This is a User Class Object
*
* @author    	Marl Tumulak, marl.aereus@aereus.com
* @author    	joe, sky.stebnicki@aereus.com
* @copyright	Copyright (c) 2011 Aereus Corporation. All rights reserved.
*/

/**
* Extends base object
*
* @constructor
* @param {CAntObject} base The base object to extend
*/
function CAntObject_User(base)
{
    base.userId = base.id;
    base.teamId = null;
    base.teamDropdown = null;

	/**
 	 * To be over-ridden by calling process to detect when definition is finished loading.
	 *
	 * @public
	 * @this {CAntObject_User} 
	 * @param {Object} ret   Object that is a result from ajax
	 */
	base.onteamsloaded = function(ret) { }

	/**
	 * Loads the team data
	 *
	 * @public
	 * @this {CAntObject_User} 
	 */
	base.loadTeam = function()
	{
		ajax = new CAjax('json');
		ajax.cbData.cls = this;
		ajax.onload = function(ret)
		{
			this.cbData.cls.onteamsloaded(ret);
		};
		var args = new Array();    
		ajax.exec("/controller/User/getTeams");
	}

	/**
	 * Populate the team dropdown
	 *
	 * @public
	 * @this {CAntObject_User} 
	 * @param {Object} teamData      Contains the team data info
	 * @param {Integer} parentId     The parent Id of the current team
	 */
	base.populateTeam = function(teamData, parentId)
	{    
		if(parentId==null)
		{
			var teamId = teamData[0].id;        
			if(teamId > 0)
			{
				this.addTeamOption(teamId, teamData[0])
				delete teamData[0];
				this.populateTeam(teamData, teamId)            
			}            
			else
				return;
		}
		
		var teamId = 0;
		for(team in teamData)
		{
			var currentTeam = teamData[team];

			if(currentTeam.parentId == parentId)
			{
				teamId = currentTeam.id;
				this.addTeamOption(teamId, currentTeam)
				
				delete teamData[teamId];
				this.populateTeam(teamData, teamId)
			}
		}
	}

	/**
	 * Add an entry in the team dropdown
	 *
	 * @public
	 * @this {CAntObject_User} 
	 * @param {Integer} teamId         The team id of the current team
	 * @param {Object} currentTeam     Contains the team info of the current team
	 */
	base.addTeamOption = function(teamId, currentTeam)
	{
		if(teamId > 0)
		{
			var selected = false;
			if(teamId == this.teamId)
				selected = true;
			
			var optionLength = this.teamDropdown.length;
			this.teamDropdown[optionLength] = new Option(currentTeam.name, teamId, false, selected);
			this.teamDropdown[optionLength].parentId = currentTeam.parentId;
		}    
	}

	/**
	 * Add spaced prefix child teams so we know a team is a sub-team of the parent.
	 *
	 * @public
	 * @this {CAntObject_User} 
	 * @param {Object} teamData     Contains the team data info
	 */
	base.addSpacedPrefix = function(teamData)
	{
		var spacedTeam = new Array;
		var spacedCount = 0;
		for(option in this.teamDropdown)
		{
			if(option > 0)
			{
				var currentOption = this.teamDropdown[option];
				
				if(currentOption.parentId !==null)
				{   
					var spaced = 0;
					
					if(!spacedTeam[currentOption.parentId])
					{
						spacedCount += 1;
						spacedTeam[currentOption.parentId] = spaced = spacedCount;
						
					}
					else
					{
						var prevOption = this.teamDropdown[option-1]
						
						spaced = spacedTeam[currentOption.parentId];
						
						if((prevOption.parentId !== currentOption.parentId))
							spacedCount -= 1;
					}
					
					var spacedTxt = "";
					for(var x = 0; x < spaced; x++)
						spacedTxt += "\u00A0\u00A0\u00A0";
					
					currentOption.text = spacedTxt + currentOption.text;
				}
			}
		}
	}
}
