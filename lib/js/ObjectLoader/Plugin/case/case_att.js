{
    name:"attachments",
    title:"Attachments",
    mainObject:null,
    toolbar:null,

    main:function(con)
    {
        this.data = new Object();
        this.m_con = con;
        this.loaded = false;
        this.attachments = new Array();

        this.load();
    },

    // This function is called when the object is saved. Use it to rest forms that require an object id
    objectsaved:function()
    {
        //if (this.mainObject.id)
            //this.buildInterface();
    },

    save:function()
    {
        var args = [["project_id", this.mainObject.getValue('project_id')], ["case_id", this.mainObject.id]];

        // Handled uploaded files
        for (var i = 0; i < this.cfupload.getNumUploadedFiles(); i++)
        {
            var file = this.cfupload.getUploadedFile(i);
            args[args.length] = ["uploaded_file[]", file.id];
        }

        /*var funct = function(ret, cls)
        {
            cls.load();
            cls.onsave();
        }
        var rpc = new CAjaxRpc("/controller/Project/caseSaveAttachments", "caseSaveAttachments", args, funct, [this], AJAX_POST, true, "json");        */
        
        ajax = new CAjax('json');
        ajax.cbData.cls = this;
        ajax.onload = function(ret)
        {
            this.cbData.cls.load();
            this.cbData.cls.onsave();
        };
        ajax.exec("/controller/Project/caseSaveAttachments", args);

        try
        {
            this.cfupload.clearUploadedFiles();
        }
        catch(e)
        {
            alert(e);
        }
    },

    onsave:function()
    {
    },

    load:function()
    {
        if (!this.mainObject.id)
        {
            this.buildInterface();
            return;
        }

        /*var funct = function(ret, cls)
        {
            if (ret)
            {
                try
                {                    
                    if (ret.length)
                    {
                        for(attachment in ret)
                        {
                            var currentAttachment = ret[attachment];
                            cls.attachments[cls.attachments.length] = currentAttachment;
                        }
                    }
                }
                catch(e)
                {
                    alert(e);
                }
            }

            cls.buildInterface();
        }
        var rpc = new CAjaxRpc("/controller/Project/caseGetAttachments", "caseGetAttachments", [["case_id", this.mainObject.id]], funct, [this], AJAX_POST, true, "json");*/
        
        ajax = new CAjax('json');
        ajax.cbData.cls = this;
        ajax.onload = function(ret)
        {
            if (ret)
            {
                try
                {                    
                    if (ret.length)
                    {
                        for(attachment in ret)
                        {
                            var currentAttachment = ret[attachment];
                            this.cbData.cls.attachments[this.cbData.cls.attachments.length] = currentAttachment;
                        }
                    }
                }
                catch(e)
                {
                    alert(e);
                }
            }

            this.cbData.cls.buildInterface();
        };
        ajax.exec("/controller/Project/caseGetAttachments",
                    [["case_id", this.mainObject.id]]);
    },

    buildInterface:function()
    {
        if (!this.loaded)
        {
            this.m_con.innerHTML = "";

            var div_upload_btn = alib.dom.createElement("div", this.m_con);
            var div_upload_res = alib.dom.createElement("div", this.m_con);
            this.cfupload = new AntFsUpload('%tmp%');
            //cfupload.onUploadStarted = function () { g_att_uploading = true; };
            //cfupload.onQueueComplete = function () { g_att_uploading = false; };
            this.cfupload.showTmpUpload(div_upload_btn, div_upload_res);
    
            this.m_relCon = alib.dom.createElement("div", this.m_con);

            this.loaded = true;
        }

        this.m_relCon.innerHTML = "";
        if (this.attachments.length)
        {
            for (var i = 0; i < this.attachments.length; i++)
            {
                this.addAttachment(this.attachments[i].fid, this.attachments[i].name);
            }
        }
    },

    addAttachment:function(fid, name)
    {
        this.m_relTable = new CToolTable();
        /*
        this.m_relTable.addHeader("Name");
        this.m_relTable.addHeader("Remove", "center", "20px");
        */
        this.m_relTable.print(this.m_relCon);

        var rw = this.m_relTable.addRow();

        var a = alib.dom.createElement("a");
        a.innerHTML = "<img src='/images/icons/deleteTask.gif' border='0'>";
        a.href = "javascript:void(0)";
        a.rw = rw;
        a.fid = fid;
        a.cls = this;
        a.onclick = function()
        {
            var dlg = new CDialog("Delete Attachment");
            dlg.cls = this.cls;
            dlg.rw = this.rw;
            dlg.fid = this.fid;
            dlg.confirmBox("Are you sure you want to remove this attachment?", "Remove Attachment");
            dlg.onConfirmOk = function()
            {
                for (var i = 0; i < this.cls.attachments.length; i++)
                {
                    if (this.cls.attachments[i].fid == this.fid)
                        this.cls.attachments.splice(i);
                    
                    ajax = new CAjax('json');                    
                    ajax.exec("/controller/Project/caseRemoveAttachment",
                                [["case_id", this.cls.mainObject.id], ["fid", this.fid]]);
                }

                this.rw.deleteRow();
            }
        }

        // Create name link if id
        var namelnk = alib.dom.createElement("a");
        namelnk.href = "javascript:void(0);";
        namelnk.fid = fid;
        namelnk.onclick = function() { window.open("/files/"+this.fid); }
        namelnk.innerHTML = name;

        rw.addCell(namelnk);
        rw.addCell(a, false, "center");
    }
}
