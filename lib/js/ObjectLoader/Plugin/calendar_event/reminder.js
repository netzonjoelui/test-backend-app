{
    name:"reminder",
    title:"Reminders",
    mainObject:null, // will be set by form class, is a reference to edited object    
    
    /**
     * Called once form is fully loaded
     *
     * @param DOMElement con a handle to the parent container for this plugin (where it will be printed)
     */
    main:function(con)
    {
        this.m_con = con;        
        
        // plugin variables
        this.g_recurtbl = null;
        this.g_recurcon = null;        
        
        // variable settings
        this.g_eid = this.mainObject.id;
        this.g_ecid = null;
        this.g_user_email = null;
        this.g_username = null;
        this.g_userMobilePhone = null;
        this.g_user_cell = null;
        this.g_email_id = null;
        this.g_smscarriers = null;
        this.g_defcal = null;
        this.g_event = new Object();
        this.g_event.reminders = new Array();
        
        var divLoading = alib.dom.createElement("div", this.m_con);
        divLoading.innerHTML = "<div class='loading'></div>";
        
        ajax = new CAjax('json');
        ajax.cbData.cls = this;
        ajax.onload = function(ret)
        {
            if(ret)
            {   
                this.cbData.cls.g_user_email = ret['g_user_email'];
                this.cbData.cls.g_username = ret['g_user_email'];
                this.cbData.cls.g_userMobilePhone = ret['g_userMobilePhone'];
                this.cbData.cls.g_user_cell = ret['g_user_cell'];
                this.cbData.cls.g_email_id = ret['g_email_id'];
                this.cbData.cls.g_smscarriers = ret['g_smscarriers'];
                this.cbData.cls.g_defcal = ret['g_defcal'];
            }
            
            this.cbData.cls.buildInterface();
        };
        ajax.exec("/controller/Calendar/getReminderVariables");
    },

    /**
     * Will be called by AntObjectLoader_Form when the user saves changes. 
     * This MUST call this.onsave when finsihed or the browser will hang.
     */
    save:function()
    {        
        if (this.mainObject.id && this.g_event.reminders.length)
        {   
            var args = new Array();
            args[args.length] = ["eid", this.mainObject.id];
            for (var i = 0; i < this.g_event.reminders.length; i++)
            {
                args[args.length] = ["reminders[]", this.g_event.reminders[i].id];
                args[args.length] = ["reminder_type_" + this.g_event.reminders[i].id, this.g_event.reminders[i].type];
                args[args.length] = ["reminder_send_to_" + this.g_event.reminders[i].id, this.g_event.reminders[i].send_to];
                args[args.length] = ["reminder_count_" + this.g_event.reminders[i].id, this.g_event.reminders[i].count];
                args[args.length] = ["reminder_interval_" + this.g_event.reminders[i].id, this.g_event.reminders[i].interval];
                
                // need to make it integer so it wont insert another reminder entry if "Save Changes" button is clicked                
            }
            
            ajax = new CAjax('json');
            ajax.cbData.cls = this;
            ajax.onload = function(ret)
            {
                if(!ret['error'])
                {
                    /*this.cbData.cls.g_recurtbl.clear();
                    for(reminder in ret)
                    {
                        var currentReminder = ret[reminder];                        
                        this.cbData.cls.addReminder(currentReminder.id, currentReminder.type, currentReminder.count, currentReminder.interval, currentReminder.send_to);
                    }*/
                    this.cbData.cls.onsave();
                }
            };
            ajax.exec("/controller/Calendar/saveReminders", args);
        }
        else
        {
            this.onsave();
        }
    },

    /**
     * Inform the AntObjectLoader_Form object that this plugin has finished saving changes
     */
    onsave:function()
    {
    },
    
    /**
     * Will be called by AntObjectLoader_Form when the deletes a reminder.      
     */
    remove:function(id)
    {        
        if(!isNaN(id) && this.mainObject.id)
        {
            ajax = new CAjax('json');
            ajax.cbData.cls = this;
            ajax.onload = function(ret)
            {
                if(!ret['error'])
                    this.cbData.cls.onremove();
            };
            var args = new Array();
            args[args.length] = ["eid", this.mainObject.id];
            args[args.length] = ["id", id];
            ajax.exec("/controller/Calendar/deleteEventReminder", args);
        }
        else
            this.onremove();
    },
    
    /**
     * Inform the AntObjectLoader_Form object that this plugin has finished deleting reminder
     */
    onremove:function()
    {        
    },

    /**
     * Private function for loading interface
     */
    load:function()
    {
    },

    /**
     * Private function for building interface
     */
    buildInterface:function()
    {   
        this.m_con.innerHTML = "";
        var formCon = alib.dom.createElement("div", this.m_con);
        var tableCon = alib.dom.createElement("div", this.m_con);
        this.g_recurcon = tableCon;
        
        var selectCon = alib.dom.createElement("div", formCon);
        var inputCon = alib.dom.createElement("div", formCon);        
        var sel = alib.dom.createElement("select", selectCon);
                
        // Set Styles
        alib.dom.styleSet(selectCon, "margin-bottom", "10px");
        alib.dom.styleSet(selectCon, "float", "left");
        
        alib.dom.styleSet(inputCon, "margin-left", "5px");
        alib.dom.styleSet(inputCon, "margin-bottom", "10px");
        alib.dom.styleSet(inputCon, "float", "left");
        
        divClear(formCon);
        
        sel.m_frmcon = inputCon;
        sel.m_cls = this;
        sel[sel.length] = new Option("Add Reminder", "", false);
        sel[sel.length] = new Option("Send Email", "1", false);
        sel[sel.length] = new Option("Send Text Message (SMS)", "2", false);
        sel[sel.length] = new Option("Pop-up Alert", "3", false);
        sel.onchange = function()
        {
            // Display the right fields
            switch (this.value)
            {
            // popup
            case "3":
                this.m_frmcon.innerHTML = "";

                var formCon = alib.dom.createElement("div", this.m_frmcon);
                var lbl = alib.dom.createElement("span", formCon);
                lbl.innerHTML = " remind me ";
                
                var timeInput = this.m_cls.reminderTimeInput(formCon);

                var lbl = alib.dom.createElement("span", formCon);
                lbl.innerHTML = " before event starts ";

                var btn = alib.dom.createElement("input");
                btn.type = 'button';
                btn.value = "Add";
                btn.m_frmcon = this.m_frmcon;
                btn.m_cb = this;
                btn.m_cls = this.m_cls;
                btn.m_time = timeInput.inp_time;
                btn.m_interval = timeInput.sel;
                btn.onclick = function()
                {
                    if (this.m_time.value)
                    {
                        this.m_cls.addReminder("new"+this.m_cls.g_event.reminders.length, "3", this.m_time.value, this.m_interval.value, this.m_cls.g_username);
                        this.m_frmcon.innerHTML = "";
                        this.m_cb.options[0].selected = true;

                        var reminder = new Object();
                        reminder.id         = "new"+this.m_cls.g_event.reminders.length;
                        reminder.type         = 3;
                        reminder.send_to     = this.m_cls.g_username;
                        reminder.count         = this.m_time.value;
                        reminder.interval    = this.m_interval.value;                        
                            
                        this.m_cls.g_event.reminders[this.m_cls.g_event.reminders.length] = reminder;                        
                    }
                    else
                        alert("Please enter a time");
                }
                formCon.appendChild(btn);
                break;
            // sms
            case "2":
                this.m_frmcon.innerHTML = "";

                var formCon = alib.dom.createElement("div", this.m_frmcon);
                var lbl = alib.dom.createElement("span", formCon);
                lbl.innerHTML = " to ";
                
                var inp_number = alib.dom.createElement("input", formCon);
                inp_number.style.width = "150px";
                inp_number.value = this.m_cls.g_userMobilePhone;
                
                var lbl = alib.dom.createElement("span", formCon);
                lbl.innerHTML = " @ ";

                var sel_carrier = alib.dom.createElement("select", formCon);
                for(carrier in this.m_cls.g_smscarriers)
                {
                    var currentCarrier = this.m_cls.g_smscarriers[carrier];
                    sel_carrier[sel_carrier.length] = new Option(currentCarrier[0], currentCarrier[1], false, (this.m_cls.g_userMobilePhoneCarrier==currentCarrier[1])?true:false);
                }

                alib.dom.styleSet(formCon, "margin-bottom", "5px");
                var formCon = alib.dom.createElement("div", this.m_frmcon);
                var timeInput = this.m_cls.reminderTimeInput(formCon);

                var lbl = alib.dom.createElement("span", formCon);
                lbl.innerHTML = " before event starts ";
                
                var btn = alib.dom.createElement("input");
                btn.type = 'button';
                btn.value = "Add";
                btn.m_frmcon = this.m_frmcon;
                btn.m_cb = this;
                btn.m_cls = this.m_cls;
                btn.m_time = timeInput.inp_time;
                btn.m_interval = timeInput.sel;
                btn.m_number = inp_number;
                btn.m_carrier = sel_carrier;
                btn.onclick = function()
                {
                    if (this.m_number.value)
                    {
                        this.m_cls.addReminder("new"+this.m_cls.g_event.reminders.length, "2", this.m_time.value, this.m_interval.value, this.m_number.value + this.m_carrier.value);
                        this.m_frmcon.innerHTML = "";
                        this.m_cb.options[0].selected = true;

                        var reminder = new Object();
                        reminder.id         = "new"+this.m_cls.g_event.reminders.length;
                        reminder.type         = 2;
                        reminder.send_to     = this.m_number.value + this.m_carrier.value;
                        reminder.count         = this.m_time.value;
                        reminder.interval    = this.m_interval.value;
                            
                        this.m_cls.g_event.reminders[this.m_cls.g_event.reminders.length] = reminder;
                    }
                    else
                        alert("Please enter a phone number");
                }
                formCon.appendChild(btn);
                break;
            // send email
            case "1":
                this.m_frmcon.innerHTML = "";

                var formCon = alib.dom.createElement("div", this.m_frmcon);
                var lbl = alib.dom.createElement("span", formCon);
                lbl.innerHTML = " to ";
                
                var inp_email = alib.dom.createElement("input", formCon);
                inp_email.style.width = "170px";
                inp_email.value = this.m_cls.g_user_email;
                
                alib.dom.styleSet(formCon, "margin-bottom", "5px");
                var formCon = alib.dom.createElement("div", this.m_frmcon);
                var timeInput = this.m_cls.reminderTimeInput(formCon);

                var lbl = alib.dom.createElement("span", formCon);
                lbl.innerHTML = " before event starts ";
                
                var btn = alib.dom.createElement("input");
                btn.type = 'button';
                btn.value = "Add";
                btn.m_frmcon = this.m_frmcon;
                btn.m_cb = this;
                btn.m_cls = this.m_cls;
                btn.m_time = timeInput.inp_time;
                btn.m_interval = timeInput.sel;
                btn.m_email = inp_email;
                btn.onclick = function()
                {
                    if (this.m_email.value)
                    {
                        this.m_cls.addReminder("new"+this.m_cls.g_event.reminders.length, "1", this.m_time.value, this.m_interval.value, this.m_email.value);
                        this.m_frmcon.innerHTML = "";
                        this.m_cb.options[0].selected = true;

                        var reminder = new Object();
                        reminder.id = "new" + this.m_cls.g_event.reminders.length;
                        reminder.type = 1;
                        reminder.send_to = this.m_email.value;
                        reminder.count = this.m_time.value;
                        reminder.interval = this.m_interval.value;
                            
                        this.m_cls.g_event.reminders[this.m_cls.g_event.reminders.length] = reminder;
                    }
                    else
                        alert("Please enter an email address");
                }
                
                formCon.appendChild(btn);

                break;
            default:
                this.m_frmcon.innerHTML = "";
                break;
            }
        }
        
        if (this.g_eid)
            this.getReminders();
        else
            this.reminderTable();
    },
   
    /**
    * Private function for adding calendar reminder entries
    */ 
    addReminder:function(id, type, time, interval, send_to)
    {   
        var rw = this.g_recurtbl.addRow();
        switch (type)
        {
        case "1":
            rw.addCell("Send Email");
            break;
        case "2":
            rw.addCell("Send SMS Text Message");
            break;
        case "3":
            rw.addCell("Pop-up Alert");
            break;
        }
        rw.addCell((typeof send_to != "undefined") ? send_to : "");

        var lbl = time + " ";
        switch (interval)
        {
        case "1":
            lbl += "minutes";
            break;
        case "2":
            lbl += "hours";
            break;
        case "3":
            lbl += "days";
            break;
        case "4":
            lbl += "weeks";
            break;
        }
        lbl += " before event starts";
        rw.addCell(lbl);

        var del_dv = alib.dom.createElement("div");
        rw.addCell(del_dv, true, "center");
        del_dv.innerHTML = "<img border='0' src='/images/themes/" + ((typeof(Ant)=='undefined')?g_theme:Ant.m_theme) + "/icons/deleteTask.gif' />";
        alib.dom.styleSet(del_dv, "cursor", "pointer");
        del_dv.m_rw = rw;
        del_dv.m_id = id;
        del_dv.m_cls = this;
        del_dv.onclick = function()
        {
            ALib.Dlg.confirmBox("Are you sure you want to remove this reminder?", "Remove Reminder", [this.m_rw]);
            ALib.Dlg.m_cls = this.m_cls;
            ALib.Dlg.onConfirmOk = function(row)
            {
                this.m_cls.remove(id);
                row.deleteRow();

                // Remove group from document
                for (var i = 0; i < this.m_cls.g_event.reminders.length; i++)
                {
                    if (this.m_cls.g_event.reminders[i].id == id)
                        this.m_cls.g_event.reminders.splice(i, 1);
                }
            }
        }    
    },
    
    getReminders:function()
    {   
        this.reminderTable();
                
        ajax = new CAjax('json');
        ajax.cbData.cls = this;
        ajax.onload = function(ret)
        {
            if(!ret || ret.length == 0)
                return;
            
            if(ret.error)
            {
                ALib.statusShowAlert(ret.error, 3000, "bottom", "right");
            }
            else
            {
                for(reminder in ret)
                {
                    var currentReminder = ret[reminder];
                    
                    var reminderObject = new Object();
                    reminderObject.id = currentReminder["id"];
                    reminderObject.type = currentReminder["type"];
                    reminderObject.send_to = currentReminder["send_to"];
                    reminderObject.count = currentReminder["count"];
                    reminderObject.interval = currentReminder["interval"];
                    
                    var reminderIndex = this.cbData.cls.g_event.reminders.length;
                    this.cbData.cls.g_event.reminders[reminderIndex] = reminderObject;
                    this.cbData.cls.addReminder(reminderObject.id, reminderObject.type, reminderObject.count, reminderObject.interval, reminderObject.send_to);
                }
            }

            this.cbData.cls.load();
        };
        
        var args = new Array();
        args[args.length] = ['eventId', this.g_eid];        
        ajax.exec("/controller/Calendar/getReminders", args);
        
    },
    
    reminderTable:function()
    {        
        this.g_recurtbl = new CToolTable("100%");
        this.g_recurtbl.addHeader("Type");
        this.g_recurtbl.addHeader("To");
        this.g_recurtbl.addHeader("Time");
        this.g_recurtbl.addHeader("Delete", "center", "50px");
        this.g_recurtbl.print(this.g_recurcon);
    },
    
    reminderTimeInput:function(formCon)
    {
        var timeInput = new Object();
        
        timeInput.inp_time = alib.dom.createElement("input", formCon);
        timeInput.inp_time.style.width = "30px";
        timeInput.inp_time.value = 15;
        var lbl = alib.dom.createElement("span", formCon);
        lbl.innerHTML = "&nbsp;";

        timeInput.sel = alib.dom.createElement("select", formCon);
        timeInput.sel[timeInput.sel.length] = new Option("minute(s)", "1", false);
        timeInput.sel[timeInput.sel.length] = new Option("hour(s)", "2", false);
        timeInput.sel[timeInput.sel.length] = new Option("day(s)", "3", false);
        timeInput.sel[timeInput.sel.length] = new Option("week(s)", "4", false);
        
        return timeInput;
    }

}
