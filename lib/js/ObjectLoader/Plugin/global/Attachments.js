/**
 * @fileoverview This class is a global object form plugin for managing attachments
 *
 * @author     Marl Tumulak, marl.tumulak@aereus.com.
 *             Copyright (c) 2011 Aereus Corporation. All rights reserved.
 */

/**
 * Class constructor
 */
function AntObjectLoader_FormAttachments()
{
    this.data = new Object();

    this.name = "attachments";  // should be the same, when calling the plugin
    this.title = "Attachments";
    this.mainObject = null;    
    this.formObj = null;
    
    this.attachedFiles = new Array();    
    this.saveParentObject = false; // Flag used when saving. If set to true, then parent object will need to be saved when all done
    this.attachmentUploading = false;
    this.loaded = false;

    // Containers
    this.mainCon = null;
    this.uploadCon = null;    
    this.resultCon = null;    
    this.filesCon = null;    
}

/**
 * Required plugin main function
 */
AntObjectLoader_FormAttachments.prototype.main = function(con)
{
    this.mainCon = con;    
    this.uploadCon = alib.dom.createElement("div", this.mainCon);
    this.filesCon = alib.dom.createElement("div", this.mainCon);    
    
    this.buildInterface();
}

/**
 * Print form 
 */
AntObjectLoader_FormAttachments.prototype.buildInterface = function()
{
    if (!this.loaded)
    {
        this.uploadCon.innerHTML = "";
        
        // Build Attachments
        var divAttachment = alib.dom.createElement("div", this.uploadCon);
        var divButton = alib.dom.createElement("div", divAttachment);
        this.resultCon = alib.dom.createElement("div", divAttachment);

        var cfupload = new AntFsUpload('%tmp%');
        cfupload.cbData.cls = this;
        
        cfupload.onRemoveUpload = function (fid) 
        {
            this.cbData.cls.attachedFiles = new Array();
            
            for(file in this.m_uploadedFiles)
            {
                var currentFile = this.m_uploadedFiles[file];
                var ind = this.cls.attachedFiles.length;
                var fileId = currentFile['id'];
                
                if(fileId !== fid)
                    this.cbData.cls.attachedFiles[ind] = fileId;
            }
        }

        cfupload.onUploadStarted = function () 
        { 
            this.cls.attachmentUploading = true;         
        }

        cfupload.onQueueComplete = function () 
        { 
            this.cbData.cls.attachmentUploading = false;
            
            this.cbData.cls.attachedFiles = new Array();
            
            for(file in this.m_uploadedFiles)
            {
                var currentFile = this.m_uploadedFiles[file];
                var ind = this.cbData.cls.attachedFiles.length;
                
                this.cbData.cls.attachedFiles[ind] = currentFile['id'];
            }
            
            this.cbData.cls.formObj.toggleEdit(true);
        }
        
        cfupload.showTmpUpload(divButton, this.resultCon, 'Add Attachment');
        
        this.loaded = true;
    }
    
    this.loadSavedFiles();
}

/**
 * Called from object loader when object is saved.
 *
 * This should take care of saving attached file
 */
AntObjectLoader_FormAttachments.prototype.save = function()
{
    
    if(this.attachmentUploading) // uploading files still in progress
    {
        alert('There are still file(s) being uploaded. Only the files that have been finished are saved.')
    }
    
    var args = new Array();    
    args[args.length] = ['attachedFiles', this.attachedFiles];
    args[args.length] = ['id', this.mainObject.id];
    args[args.length] = ['typeName', this.mainObject.obj_type];
    
    ajax = new CAjax('json');
    ajax.cls = this;
    ajax.onload = function(ret)
    {
        this.cls.attachedFiles = new Array();
        this.cls.resultCon.innerHTML = "";
        this.cls.loadSavedFiles();
    };
    ajax.exec("/controller/AntFs/saveAttachment", args);
    
    this.onsave();
}

/**
 * onsave callback - should be overridden by parent form
 */
AntObjectLoader_FormAttachments.prototype.onsave = function()
{
}

/**
 * This will load the saved attachment files
 */
AntObjectLoader_FormAttachments.prototype.loadSavedFiles = function()
{
    this.filesCon.innerHTML = "<div class='loading'></div>";
    
    var args = new Array();    
    args[args.length] = ['id', this.mainObject.id];
    args[args.length] = ['typeName', this.mainObject.obj_type];
    
    ajax = new CAjax('json');
    ajax.cls = this;
    ajax.onload = function(ret)
    {
        this.cls.filesCon.innerHTML = "";
        if(!ret)
            return;
            
        if(!ret.error)
        {
            this.cls.buildFilesRow(ret);
        }        
    };
    ajax.exec("/controller/AntFs/getAttachment", args);
}

/**
 * This will build the attached files table row
 */
AntObjectLoader_FormAttachments.prototype.buildFilesRow = function(files)
{    
    var fileTable = new CToolTable();
    fileTable.print(this.filesCon);
    
    for(file in files)
    {
        var currentFile = files[file];
        
        var rw = fileTable.addRow();
        
        var nameLink = alib.dom.createElement("a");
        nameLink.href = "javascript:void(0);";
        nameLink.id = currentFile.id;
        nameLink.onclick = function() 
        { 
            window.open("/antfs/"+this.id); 
        }
        nameLink.innerHTML = currentFile.name;
        rw.addCell(nameLink);
        
        var removeLink = alib.dom.createElement("a");
        removeLink.href = "javascript:void(0);";
        removeLink.cls = this;
        removeLink.id = currentFile.id;
        removeLink.name = currentFile.name;
        removeLink.rw = rw;
        removeLink.onclick = function() 
        {
            if(confirm("Are you sure to removed " + this.name + "?"))
                this.cls.removeSavedFile(this.rw, this.id, this.name);
        }
        removeLink.innerHTML = "<img src='/images/icons/deleteTask.gif' border='0'>";
        rw.addCell(removeLink);
    }
}

/**
 * This will remove the saved attached file
 */
AntObjectLoader_FormAttachments.prototype.removeSavedFile = function(rw, id, name)
{
    var args = new Array();    
    args[args.length] = ['id', id];
    
    ajax = new CAjax('json');
    ajax.cls = this;
    ajax.name = name;
    ajax.rw = rw;
    ajax.dlg = showDialog("Deleting " + name + ", please wait...");
    ajax.onload = function(ret)
    {
        if(!ret)
            return;
            
        if(ret.error)
            ALib.statusShowAlert(ret.error, 3000, "bottom", "right");
        else
        {
            this.dlg.hide();
            ALib.statusShowAlert(this.name + " has been deleted!", 3000, "bottom", "right");
            this.rw.deleteRow();
        }
    };
    ajax.exec("/controller/AntFs/removeAttachment", args);
}
