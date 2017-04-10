/*======================================================================================
	
	Module:		CVideoPlayer

	Purpose:	Handle playing video files from AntFs

	Author:		joe, sky.stebnicki@aereus.com
				Copyright (c) 2010 Aereus Corporation. All rights reserved.
	
	Deps:		/lib/js/CFlashObj.js

	Usage:		

======================================================================================*/

function CVideoPlayer(fid, width, height, play)
{
	var flash = new CFlashObj("tutorial", "/flash/video_player");
	flash.setAttribute("FlashVars", "fwdFileId="+fid+((play)?"&fwdPlay=1":"&fwdPlay=0"));
	flash.setAttribute("width", width);
	flash.setAttribute("height", height);
	this.m_flash = flash;
}

CVideoPlayer.prototype.getObjHtml = function()
{
	return this.m_flash.getObjHtml();
}
