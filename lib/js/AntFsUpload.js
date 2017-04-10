/**
* @fileOverview AntFsUpload is used to upload files to the AntFs
*
* @author:	joe, sky.stebnicki@aereus.com; 
* 			Copyright (c) 2003-2012 Aereus Corporation. All rights reserved.
*/

/**
 * Global array of uplaoders.
 *
 * Used so multiple uplaoders can be run in parallele but needed for the flash callbacks below
 */
var g_AntFsUploadCurrent = null;

/**
 * Callback functions used by the flash file uploader
 */
function ToggleAdd()
{
	if (g_AntFsUploadCurrent != null)
		g_AntFsUploadCurrent.unload();
}
function FlUploadComplete()
{
	if (g_AntFsUploadCurrent != null)
		g_AntFsUploadCurrent.uploadFinished();
}

/**
 * Creates an instance of Upload
 *
 * @constructor
 * @parma {string} path Optional path to load. %userdir% will be loaded by default.
 */
function AntFsUpload(path, parentDlg)
{
	/**
	 * Optional folder id
	 *
	 * @private
	 * @var {int}
	 */
	this.folderId = "";

	/**
	 * Optional file id, if set then upload will update the file rather than create a new one
	 *
	 * @private
	 * @var {int}
	 */
	this.fileId = "";

	/**
	 * The current path relative to root or using a system variable like %tmp%
	 *
	 * @private
	 * @var {string}
	 */
	this.currentPath = (path) ? path : "%userdir%";

	/**
	 * parent dialog used if uplaoder is being called from another dialog so background is not closed
	 *
	 * @private
	 * @var {CDialog}
	 */
	this.parentDlg = (parentDlg) ? parentDlg : null;

	/**
	 * Authentication string used to send files in stateless mode
	 *
	 * @private
	 * @var {string}
	 */
	this.authStr = "";

	/**
	 * Optional processor function tells controller to do something with the file after uploading
	 *
	 * This is usually used for encoding and modifying uploaded files
	 *
	 * @private
	 * @var {string}
	 */
	this.process_function = null;

	/**
	 * Array of uploaded files
	 *
	 * @private
	 * @var {Array}
	 */
	this.m_uploadedFiles = new Array();

	/**
	 * Object used to store callback properties
	 *
	 * @public
	 * @var {Object}
	 */
	this.cbData = new Object();
	
	// Get authentication string so we upload in stateless mode (for flash)
	this.getAuthString();
}

/**
 * Display the upload files dialog
 *
 * @public
 */
AntFsUpload.prototype.showDialog = function()
{
	if (!this.authStr)
	{
		this.getAuthString("showDialog");
		return;
	}

	this.m_dlg = new CDialog("Upload File(s)", this.parentDlg);
	var dlg = this.m_dlg;

	g_AntFsUploadCurrent = this;

	var dv = alib.dom.createElement("div");
	
	// iframe
	var dv_frame = alib.dom.createElement("div", dv);
	dv_frame.style.zIndex = 1000;

	var divFileProgressContainer = alib.dom.createElement("div", dv_frame);

	var btn_con = alib.dom.createElement("div", dv_frame);
	var tbl = alib.dom.createElement("table", btn_con);
	var tbody = alib.dom.createElement("tbody", tbl);
	var row = alib.dom.createElement("tr", tbody);

	var td = alib.dom.createElement("td", row);
	td.valign = "top";
	var spanButtonPlaceholder = alib.dom.createElement("span", td);

	var td = alib.dom.createElement("td", row);
	td.valign = "top";
	var cancel = alib.dom.createElement("div", td);
	alib.dom.styleSet(cancel, "vartical-align", "top");
	alib.dom.styleSet(cancel, "font-family", "Helvetica, Arial, sans-serif");
	alib.dom.styleSet(cancel, "font-size", "12px");
	alib.dom.styleSet(cancel, "cursor", "pointer");
	alib.dom.styleSet(cancel, "height", "16px");327
	alib.dom.styleSet(cancel, "width", "97px");
	alib.dom.styleSet(cancel, "background-image", "url(/images/buttons/flash_bg_100x18.png)");
	alib.dom.styleSet(cancel, "padding", "2px 0px 0px 3px");
	alib.dom.styleSet(cancel, "margin", "-2px 0px 0px 3px");
	alib.dom.styleSet(cancel, "overflow", "hidden");
	cancel.innerHTML = "Cancel";
	cancel.m_cls = this;
	cancel.onclick = function() { this.m_cls.unload(); }

	dlg.customDialog(dv, 460, 85);

	var url = "/controller/AntFs/upload?auth="+this.authStr;
	if (this.process_function)
		url += "&process_function="+this.process_function;
	if (this.folderId)
		url += "&folderid="+this.folderId;
	else
		url += "&path="+escape(this.currentPath);
	if (this.fileId)
		url += "&fileid="+this.fileId;

	var swfu;

	try
	{
		swfu = new SWFUpload({
					// Backend Settings
					upload_url: url,
					//post_params: {"PHPSESSID": "pdopmbhp2faiunsspjr64j14d4"},

					// File Upload Settings
					file_size_limit : "2GB",	// 2MB
					//file_types : "*.jpg",
					//file_types_description : "JPG Images",
					file_upload_limit : "0",
					//assume_success_timeout : 75,

					// Event Handler Settings - these functions as defined in Handlers.js
					//  The handlers are not part of SWFUpload but are part of my website and control how
					//  my website reacts to the SWFUpload events.
					file_queue_error_handler : AntFsUpload_fileQueueError,
					file_dialog_complete_handler : fileDialogComplete,
					upload_progress_handler : AntFsUpload_uploadProgress,
					upload_error_handler : AntFsUpload_uploadError,
					upload_success_handler : AntFsUpload_uploadSuccess,
					upload_complete_handler : AntFsUpload_uploadComplete,
					swfupload_loaded_handler : AntFsUpload_uploadFlashLoaded,

					// Button Settings
					button_image_url : "/images/buttons/flash_bg_100x18.png",
					button_placeholder : spanButtonPlaceholder,
					button_placeholder_id : "",
					button_width: 100,
					button_height: 18,
					button_text : '<span class="button">Select Files</span>',
					button_text_style : '.button { font-family: Helvetica, Arial, sans-serif; font-size: 12pt; }',
					button_text_top_padding: 0,
					button_text_left_padding: 3,
					button_window_mode: SWFUpload.WINDOW_MODE.TRANSPARENT,
					button_cursor: SWFUpload.CURSOR.HAND,

					// Flash Settings
					flash_url : "/lib/SWFUpload/Flash/swfupload.swf",

					custom_settings : {
						upload_target : divFileProgressContainer,
						isMulti : false
					},

					// Debug Settings
					debug: false 
					});
	}
	catch (ex) { alert(ex); }
}

/**
 * Add Ability to upload temp files
 *
 * This will print the files uploaded into the files_con
 *
 * @public
 * @param {DOMElement} button_con The element to house the upload button
 * @param {DOMElement} files_con The element to house the files entries as they upload and once they are finished
 * @param {string} button_text The text label of the upload button
 * @param {int} file_upload_limit Limit the number of files that can be uplaoded
 */
AntFsUpload.prototype.showTmpUpload = function(button_con, files_con, button_text, file_upload_limit)
{
	if (!this.authStr)
	{
		this.m_tmpUpVarsObj = new Object();
		this.m_tmpUpVarsObj.button_con = button_con;
		this.m_tmpUpVarsObj.files_con = files_con;
		this.m_tmpUpVarsObj.button_text = button_text;
		this.m_tmpUpVarsObj.file_upload_limit = file_upload_limit;

		this.getAuthString("showTmpUpload");
		return;
	}

	this.m_resultscon = files_con;

	g_AntFsUploadCurrent = this;

	if (typeof(button_con) == "undefined" || typeof(files_con) == "undefined")
		return;

	if (typeof(button_text) == "undefined")
		button_text = "Upload File(s)";

	if (typeof(file_upload_limit) == "undefined")
		file_upload_limit = "0";

	var swfu;

	// Get current style
	var alnk = alib.dom.createElement("a", document.body);
	alib.dom.styleSet(alnk, "display", "none");
	alib.dom.styleSet(alnk, "position", "absolute");
	alnk.innerHTML = " ";
	var link_font = alib.dom.styleGet(alnk, "font-family");
	var font_size = alib.dom.styleGet(alnk, "font-size");
	var color = alib.dom.styleGet(alnk, "color");
	var decoration = alib.dom.styleGet(alnk, "text-decoration");
	document.body.removeChild(alnk);

	var url = "/controller/AntFs/upload?path="+escape("%tmp%")+"&auth="+this.authStr;
	if (this.process_function)
		url += "&process_function="+this.process_function;

	swfu = new SWFUpload({
				// Backend Settings
				upload_url: url,
				//post_params: {"PHPSESSID": "pdopmbhp2faiunsspjr64j14d4"},

				// File Upload Settings
				file_size_limit : "2GB",	// 2MB
				//file_types : "*.jpg",
				//file_types_description : "JPG Images",
				file_upload_limit : file_upload_limit,
				//assume_success_timeout : 7000,

				// Event Handler Settings - these functions as defined in Handlers.js
				//  The handlers are not part of SWFUpload but are part of my website and control how
				//  my website reacts to the SWFUpload events.
				file_queued_handler : AntFsUpload_fileQueued,
				file_queue_error_handler : AntFsUpload_fileQueueError,
				file_dialog_complete_handler : fileDialogComplete,
				upload_start_handler : AntFsUpload_uploadStart,
				upload_progress_handler : AntFsUpload_uploadProgress,
				upload_error_handler : AntFsUpload_uploadError,
				upload_success_handler : AntFsUpload_uploadSuccess,
				upload_complete_handler : AntFsUpload_uploadComplete,
				queue_complete_handler : AntFsUpload_queueComplete,	// Queue plugin event
				swfupload_loaded_handler : AntFsUpload_uploadFlashLoaded,

				// Button Settings
				button_image_url : "/images/buttons/flash_attachments_14x16.png",
				button_placeholder : button_con,
				button_placeholder_id : "",
				button_width: 100,
				button_height: 16,
				button_text : '<span class="button">'+button_text+'</span>',
				button_text_style : '.button { font-family: '+link_font+'; font-size: '+font_size+'; color: #'+color+';}',
				button_text_top_padding: 0,
				button_text_left_padding: 17,
				button_window_mode: SWFUpload.WINDOW_MODE.TRANSPARENT,
				button_cursor: SWFUpload.CURSOR.HAND,

				// Flash Settings
				flash_url : "/lib/SWFUpload/Flash/swfupload.swf",

				custom_settings : {
					upload_target : files_con,
					isMulti : true 
				},

				// Debug Settings
				debug: false 
	});
}

/**
 * Get auth string for this user
 *
 * Flash is sending the files and may not send the local cookie from the browse
 * so we authenticate manually with an auth string.
 *
 * @private
 * @param {string} functionString The name of the function to call once auth has been obtained
 */
AntFsUpload.prototype.getAuthString = function(functionString)
{
    ajax = new CAjax('json');
    ajax.cbData.cls = this;
    ajax.cbData.functionString = functionString;
    ajax.onload = function(ret)
    {
        if(!ret)
            return;
 
        if(ret)
        {
            this.cbData.cls.authStr = ret;

            switch(functionString)
            {
            case 'showDialog':
                this.cbData.cls.showDialog();
                break;
            case 'showTmpUpload':
                this.cbData.cls.showTmpUpload(this.cbData.cls.m_tmpUpVarsObj.button_con, this.cbData.cls.m_tmpUpVarsObj.files_con, 
                                  this.cbData.cls.m_tmpUpVarsObj.button_text, this.cbData.cls.m_tmpUpVarsObj.file_up);
                break;
            }
        }
    };
    ajax.exec("/controller/User/getAuthString");
}

/**
 * Hide this dialog if we are not inline
 *
 * @public
 */
AntFsUpload.prototype.unload = function()
{
	if (this.m_dlg)
		this.m_dlg.hide();
}


/**
 * This callback is executed once all uploads are finished
 *
 * @private
 */
AntFsUpload.prototype.uploadFinished = function()
{
	this.onUploadFinished();
	if (this.m_dlg)
		this.m_dlg.hide();
}

/**
 * Clear uploaded files queue (usually after processing)
 *
 * @private
 */
AntFsUpload.prototype.clearUploadedFiles = function()
{
	// Clear current results
	if (this.m_resultscon) 
		this.m_resultscon.innerHTML = "";

	if (this.m_uploadedFiles)
		this.m_uploadedFiles = new Array();
}

/**
 * Get number of uploaded files
 *
 * @public
 */
AntFsUpload.prototype.getNumUploadedFiles = function()
{
	return this.m_uploadedFiles.length;
}

/**
 * Get {id, name} of each uplaoded file
 *
 * @public
 * @param {int} ind The index of the file to get in the array of uploaded files
 * @return {id, name}
 */
AntFsUpload.prototype.getUploadedFile = function(ind)
{
	return this.m_uploadedFiles[ind];
}

/**
 * Set folder id to use - this will override the path
 *
 * @param {int} folderId The unique id of the folder to upload files to
 */
AntFsUpload.prototype.setFolderId = function(folderId)
{
	this.folderId = folderId;
}

/**
 * Set the path to upload files to
 *
 * @param {string} path The path to set
 */
AntFsUpload.prototype.setPath = function(path)
{
	this.currentPath = path;
}

/**
 * Set file id to update, if set this will overwrite existing files
 *
 * @param {int} fileId The unique id of the file to update
 */
AntFsUpload.prototype.setFileId = function(fileId)
{
	this.fileId = fileId;
}


/*************************************************************************
* Callbacks
*************************************************************************/

/**
 * Called when flash is ready to receive commands
 */
AntFsUpload.prototype.onFlashLoad = function()
{
}

/**
 * Called when the queue starts being uploaded
 */
AntFsUpload.prototype.onUploadStarted = function()
{
}

/**
 * Called when the queue has finished uploading all files
 */
AntFsUpload.prototype.onUploadFinished = function()
{
}

/**
 * Called if the user cancels the upload
 */
AntFsUpload.prototype.onCancel = function()
{
}

/**
 * Called after each individual file has been uploaded
 *
 * @param {int} fid The file id of the newly uploaded file
 * @param {string} name The file name of the uploaded file
 */
AntFsUpload.prototype.onUploadSuccess = function(fid, name)
{
}

/**
 * Called when a user removes an individual file
 *
 * @param {int} fid The id of the file removed
 */
AntFsUpload.prototype.onRemoveUpload = function(fid)
{
}

/**
 * Called when the queue is completely finished processing everything
 */
AntFsUpload.prototype.onQueueComplete = function()
{
}



function AntFsUpload_fileQueued(file) {
	try {
		var progress = new FileProgress(file, this.customSettings.upload_target, this.customSettings.isMulti);
		progress.setStatus("Pending...");
		progress.toggleCancel(true, this);

	} catch (ex) {
		this.debug(ex);
	}

}

function AntFsUpload_fileQueueError(file, errorCode, message) 
{
	try 
	{
		if (errorCode === SWFUpload.QUEUE_ERROR.QUEUE_LIMIT_EXCEEDED) 
		{
			alert("You have attempted to queue too many files.\n" + (message === 0 ? "You have reached the upload limit." : "You may select " + (message > 1 ? "up to " + message + " files." : "one file.")));
			return;
		}

		var progress = new FileProgress(file, this.customSettings.upload_target, this.customSettings.isMulti);
		progress.setError();
		//progress.toggleCancel(false);
		progress.toggleRemove(true);

		switch (errorCode) 
		{
		case SWFUpload.QUEUE_ERROR.FILE_EXCEEDS_SIZE_LIMIT:
			progress.setStatus("File is too big.");
			this.debug("Error Code: File too big, File name: " + file.name + ", File size: " + file.size + ", Message: " + message);
			break;
		case SWFUpload.QUEUE_ERROR.ZERO_BYTE_FILE:
			progress.setStatus("Cannot upload Zero Byte files.");
			this.debug("Error Code: Zero byte file, File name: " + file.name + ", File size: " + file.size + ", Message: " + message);
			break;
		case SWFUpload.QUEUE_ERROR.INVALID_FILETYPE:
			progress.setStatus("Invalid File Type.");
			this.debug("Error Code: Invalid File Type, File name: " + file.name + ", File size: " + file.size + ", Message: " + message);
			break;
		default:
			if (file !== null) {
				progress.setStatus("Unhandled Error");
			}
			this.debug("Error Code: " + errorCode + ", File name: " + file.name + ", File size: " + file.size + ", Message: " + message);
			break;
		}
	} 
	catch (ex) 
	{
        this.debug(ex);
	}
}

function fileDialogComplete(numFilesSelected, numFilesQueued) {
	try 
	{
		if (numFilesQueued > 0) 
		{
			this.startUpload();
		}
	} 
	catch (ex) 
	{
		this.debug(ex);
	}
}

function AntFsUpload_uploadStart(file) 
{
	try 
	{
		/* I don't want to do any file validation or anything,  I'll just update the UI and
		return true to indicate that the upload should start.
		It's important to update the UI here because in Linux no uploadProgress events are called. The best
		we can do is say we are uploading.
		 */
		var progress = new FileProgress(file, this.customSettings.upload_target, this.customSettings.isMulti);
		progress.setStatus("Uploading...");
		progress.toggleCancel(true, this);

		g_AntFsUploadCurrent.onUploadStarted();
	}
	catch (ex) {}
	
	return true;
}

function AntFsUpload_uploadProgress(file, bytesLoaded) 
{

	try 
	{
		var percent = Math.ceil((bytesLoaded / file.size) * 100);

		var progress = new FileProgress(file,  this.customSettings.upload_target, this.customSettings.isMulti);
		progress.setProgress(percent);
		if (percent === 100) 
		{
			progress.setStatus("Processing...");
			progress.toggleCancel(false, this);
			progress.toggleRemove(true);
		} 
		else 
		{
			progress.setStatus("Uploading...");
			progress.toggleCancel(true, this);
		}
	} 
	catch (ex) 
	{
		this.debug(ex);
	}
}

function AntFsUpload_uploadSuccess(file, serverData) 
{    
	try 
	{
		/*
		var xmlDoc = null;        
		if (window.DOMParser)
		{            
			parser=new DOMParser();            
			xmlDoc=parser.parseFromString(serverData,"text/xml");            
		}        
		else // Internet Explorer
		{
			xmlDoc=new ActiveXObject("Microsoft.XMLDOM");
			xmlDoc.async="false";
			xmlDoc.loadXML(serverData); 
		}         
		*/

		var progress = new FileProgress(file,  this.customSettings.upload_target, this.customSettings.isMulti);
		progress.toggleCancel(false);
		progress.toggleRemove(true);

		var ret = JSON.parse(serverData);

		if (ret.error)
		{
			if (this.customSettings.isMulti)
			{
				progress.setStatus("Error - " + ret.error);
				progress.setComplete();
			}
			else
			{
				alert(ret.error);
			}
			return;
		}
		else if (this.customSettings.isMulti)
		{
			progress.setComplete();
			progress.setStatus("Complete");
		}

		var filename = ret[0].name;
		var fileid = ret[0].id;
		var jobid = "";        
		/*
		var root = xmlDoc.documentElement;        
		var file = root.getElementsByTagName("file");        
		if (file.length)
		{
			if (file[0].getElementsByTagName('id')[0].firstChild)
				fileid = file[0].getElementsByTagName('id')[0].firstChild.nodeValue;
			if (file[0].getElementsByTagName('name')[0].firstChild)
				filename = unescape(file[0].getElementsByTagName('name')[0].firstChild.nodeValue);
			if (file[0].getElementsByTagName('jobid')[0].firstChild)
				jobid = unescape(file[0].getElementsByTagName('jobid')[0].firstChild.nodeValue);
		}
		*/

		if (fileid)
		{
			progress.antFileId = fileid;
			progress.setStatus("Complete - <a href='/antfs/"+fileid+"/"+filename+"' target='_blank'>view file</a>" + "<input type='hidden' name='uploaded_file[]' value='"+fileid+"'>");

			g_AntFsUploadCurrent.onUploadSuccess(fileid, filename, jobid);
			g_AntFsUploadCurrent.m_uploadedFiles[g_AntFsUploadCurrent.m_uploadedFiles.length] = {id:fileid, name: filename};
		}
	
		/*
		if (serverData.substring(0, 7) === "FILEID:") 
		{
		progress.setStatus("Thumbnail Created.");
		progress.toggleCancel(false);
		} 
		else 
		{
		progress.setStatus("Error.");
		alert(serverData);
		}
		*/
	} 
	catch (ex) 
	{
		this.debug(ex);
	}
}

function AntFsUpload_uploadComplete(file) 
{    
	try 
	{
		/*  I want the next upload to continue automatically so I'll call startUpload here */
		if (this.getStats().files_queued > 0) 
		{
			this.startUpload();
		} 
		else 
		{
			if (!this.customSettings.isMulti)
			{
				var progress = new FileProgress(file,  this.customSettings.upload_target, this.customSettings.isMulti);
				progress.setComplete();
				progress.setStatus("All files received.");
				progress.toggleCancel(false);
				g_AntFsUploadCurrent.uploadFinished();
			}
		}
	} 
	catch (ex) 
	{
		this.debug(ex);
	}
}

function AntFsUpload_uploadError(file, errorCode, message) {
	try 
	{
		var progress = new FileProgress(file, this.customSettings.upload_target, this.customSettings.isMulti);
		progress.setError();
		progress.toggleCancel(false);
		progress.toggleRemove(true);

		switch (errorCode) 
		{
		case SWFUpload.UPLOAD_ERROR.HTTP_ERROR:
			progress.setStatus("Upload Error: " + message);
			this.debug("Error Code: HTTP Error, File name: " + file.name + ", Message: " + message);
			break;
		case SWFUpload.UPLOAD_ERROR.UPLOAD_FAILED:
			progress.setStatus("Upload Failed.");
			this.debug("Error Code: Upload Failed, File name: " + file.name + ", File size: " + file.size + ", Message: " + message);
			break;
		case SWFUpload.UPLOAD_ERROR.IO_ERROR:
			progress.setStatus("Server (IO) Error");
			this.debug("Error Code: IO Error, File name: " + file.name + ", Message: " + message);
			break;
		case SWFUpload.UPLOAD_ERROR.SECURITY_ERROR:
			progress.setStatus("Security Error");
			this.debug("Error Code: Security Error, File name: " + file.name + ", Message: " + message);
			break;
		case SWFUpload.UPLOAD_ERROR.UPLOAD_LIMIT_EXCEEDED:
			progress.setStatus("Upload limit exceeded.");
			this.debug("Error Code: Upload Limit Exceeded, File name: " + file.name + ", File size: " + file.size + ", Message: " + message);
			break;
		case SWFUpload.UPLOAD_ERROR.FILE_VALIDATION_FAILED:
			progress.setStatus("Failed Validation.  Upload skipped.");
			this.debug("Error Code: File Validation Failed, File name: " + file.name + ", File size: " + file.size + ", Message: " + message);
			break;
		case SWFUpload.UPLOAD_ERROR.FILE_CANCELLED:
			// If there aren't any files left (they were all cancelled) disable the cancel button
			if (this.getStats().files_queued === 0) {
				ALib.m_document.getElementById(this.customSettings.cancelButtonId).disabled = true;
			}
			progress.setStatus("Cancelled");
			progress.setCancelled();
			break;
		case SWFUpload.UPLOAD_ERROR.UPLOAD_STOPPED:
			progress.setStatus("Stopped");
			break;
		default:
			progress.setStatus("Unhandled Error: " + errorCode);
			this.debug("Error Code: " + errorCode + ", File name: " + file.name + ", File size: " + file.size + ", Message: " + message);
			break;
		}
	} 
	catch (ex) 
	{
        this.debug(ex);
    }
}

function AntFsUpload_uploadFlashLoaded()
{
	g_AntFsUploadCurrent.onFlashLoad();
}

function fadeIn(element, opacity) {
	var reduceOpacityBy = 5;
	var rate = 30;	// 15 fps


	if (opacity < 100) {
		opacity += reduceOpacityBy;
		if (opacity > 100) {
			opacity = 100;
		}

		if (element.filters) {
			try {
				element.filters.item("DXImageTransform.Microsoft.Alpha").opacity = opacity;
			} catch (e) {
				// If it is not set initially, the browser will throw an error.  This will set it if it is not set yet.
				element.style.filter = 'progid:DXImageTransform.Microsoft.Alpha(opacity=' + opacity + ')';
			}
		} else {
			element.style.opacity = opacity / 100;
		}
	}

	if (opacity < 100) {
		setTimeout(function () {
			fadeIn(element, opacity);
		}, rate);
	}
}

// This event comes from the Queue Plugin
function AntFsUpload_queueComplete(numFilesUploaded) 
{    
	//var status = document.getElementById("divStatus");
	//status.innerHTML = numFilesUploaded + " file" + (numFilesUploaded === 1 ? "" : "s") + " uploaded.";
	g_AntFsUploadCurrent.onQueueComplete();
}


/* ******************************************
 *	FileProgress Object
 *	Control object for displaying file info
 * ****************************************** */

function FileProgress(file, targetDiv, isTmpMulti) 
{
	if (isTmpMulti)
		this.isMulti = isTmpMulti;

	if (this.isMulti)
		this.fileProgressID = file.id;
	else
		this.fileProgressID = "divFileProgress";

	this.fileProgressWrapper = ALib.m_document.getElementById(this.fileProgressID);
	if (!this.fileProgressWrapper) 
	{
		this.fileProgressWrapper = ALib.m_document.createElement("div");
		this.fileProgressWrapper.className = "progressWrapper";
		this.fileProgressWrapper.id = this.fileProgressID;

		this.fileProgressElement = ALib.m_document.createElement("div");
		this.fileProgressElement.className = "progressContainer";

		var progressCancel = ALib.m_document.createElement("a");
		progressCancel.className = "progressCancel";
		progressCancel.href = "#";
		progressCancel.style.visibility = "hidden";
		//progressCancel.appendChild(ALib.m_document.createTextNode("CANCEL"));
		progressCancel.innerHTML = "cancel";

		var progressText = ALib.m_document.createElement("div");
		progressText.className = "progressName";
		progressText.appendChild(ALib.m_document.createTextNode(file.name));

		var progressBar = ALib.m_document.createElement("div");
		progressBar.className = "progressBarInProgress";

		var progressStatus = ALib.m_document.createElement("div");
		progressStatus.className = "progressBarStatus";
		progressStatus.innerHTML = "&nbsp;";

		this.fileProgressElement.appendChild(progressCancel);
		this.fileProgressElement.appendChild(progressText);
		this.fileProgressElement.appendChild(progressStatus);
		this.fileProgressElement.appendChild(progressBar);

		this.fileProgressWrapper.appendChild(this.fileProgressElement);

		targetDiv.appendChild(this.fileProgressWrapper);
		//ALib.m_document.getElementById(targetID).appendChild(this.fileProgressWrapper);
		fadeIn(this.fileProgressWrapper, 0);

	} 
	else 
	{
		this.fileProgressElement = this.fileProgressWrapper.firstChild;
		this.fileProgressElement.childNodes[1].firstChild.nodeValue = file.name;
	}

	this.height = this.fileProgressWrapper.offsetHeight;

}
FileProgress.prototype.setProgress = function (percentage) {
	this.fileProgressElement.className = "progressContainer green";
	this.fileProgressElement.childNodes[3].className = "progressBarInProgress";
	this.fileProgressElement.childNodes[3].style.width = percentage + "%";
};
FileProgress.prototype.setComplete = function () {
	this.fileProgressElement.className = "progressContainer blue";
	this.fileProgressElement.childNodes[3].className = "progressBarComplete";
	this.fileProgressElement.childNodes[3].style.width = "";

};
FileProgress.prototype.setError = function () {
	this.fileProgressElement.className = "progressContainer red";
	this.fileProgressElement.childNodes[3].className = "progressBarError";
	this.fileProgressElement.childNodes[3].style.width = "";

};
FileProgress.prototype.setCancelled = function () {
	this.fileProgressElement.className = "progressContainer";
	this.fileProgressElement.childNodes[3].className = "progressBarError";
	this.fileProgressElement.childNodes[3].style.width = "";

};
FileProgress.prototype.setStatus = function (status) {
	this.fileProgressElement.childNodes[2].innerHTML = status;
};

FileProgress.prototype.toggleCancel = function (show, swfuploadInstance) {
	this.fileProgressElement.childNodes[0].style.visibility = show ? "visible" : "hidden";
	if (swfuploadInstance) 
	{
		var fileID = this.fileProgressID;
		this.fileProgressElement.childNodes[0].onclick = function () {
			swfuploadInstance.cancelUpload(fileID);
			return false;
		};
	}
};

FileProgress.prototype.toggleRemove = function(show) 
{
	this.fileProgressElement.childNodes[0].style.visibility = show ? "visible" : "hidden";

	if (show) 
	{
		this.fileProgressElement.childNodes[0].innerHTML = "remove";
		this.fileProgressElement.childNodes[0].progressObj = this;
		this.fileProgressElement.childNodes[0].onclick = function () 
		{
			if (this.progressObj.antFileId)
			{
				this.progressObj.setStatus("Removing..."); // moves the input form element
                                        
                ajax = new CAjax('json');
                ajax.cbData.cls = this;
                ajax.cbData.wrapper = this.progressObj.fileProgressWrapper;
                ajax.cbData.fid = this.progressObj.antFileId;
                ajax.onload = function(ret)
                {
                    this.cbData.wrapper.style.display = "none";
                    g_AntFsUploadCurrent.onRemoveUpload(this.cbData.fid);
                    for (var i = 0; i < g_AntFsUploadCurrent.m_uploadedFiles.length; i++)
                    {
                        if (g_AntFsUploadCurrent.m_uploadedFiles[i].id == this.cbData.fid)
                            g_AntFsUploadCurrent.m_uploadedFiles.splice(i, 1);
                    }
                };
                ajax.exec("/controller/UserFile/deleteFileId",
                            [["fid", this.progressObj.antFileId]]);
			}
			else
			{
				this.progressObj.setStatus("Removed"); // moves the input form element
				this.progressObj.fileProgressWrapper.style.display = "none";
			}
			return false;
		};
	}
};
