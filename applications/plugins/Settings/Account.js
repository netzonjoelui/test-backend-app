function Plugin_Settings_Account()
{
    this.mainCon = null;
    this.innerCon = null;
    
    this.userId = null;
    this.creditCardNumber = null;
    
    this.accountForm = new Object();
    this.accountData = new Object();
}

Plugin_Settings_Account.prototype.print = function(antView)
{
	this.mainCon = alib.dom.createElement('div', antView.con);
    
    this.titleCon = alib.dom.createElement("div", this.mainCon);
    this.titleCon.className = "aobListHeader";
    this.titleCon.innerHTML = "Account & Billing";
    this.innerCon = alib.dom.createElement("div", this.mainCon);
    this.innerCon.className = "objectLoaderBody";
    
    this.buildInterface();
}

Plugin_Settings_Account.prototype.buildInterface = function()
{   
	this.innerCon.innerHTML = "";
    var toolbar = alib.dom.createElement("div", this.innerCon);
    var tb = new CToolbar();
    
    var btn = new CButton("Save", 
    function(cls)
    {
        cls.saveBilling();
    }, 
    [this], "b1");
    tb.AddItem(btn.getButton(), "left");
    tb.print(toolbar);
    
    this.accountInfo();
    this.billingAddress();
    this.creditCard();
    this.billingHistory();
    
    // user comment settings    
    // commentSettings(this.innerCon);
}

/*************************************************************************
*    Function:    accountInfo
*
*    Purpose:    Creates account info settings
**************************************************************************/
Plugin_Settings_Account.prototype.accountInfo = function()
{    
    var divAccount = alib.dom.createElement("div", this.innerCon);
    alib.dom.styleSet(divAccount, "marginBottom", "5px");
    
    var divLeft = alib.dom.createElement("div", divAccount);
    alib.dom.styleSet(divLeft, "float", "left");
    alib.dom.styleSet(divLeft, "width", "190px");
    
    var lblTitle = alib.dom.createElement("p", divLeft);
    alib.dom.styleSet(lblTitle, "fontWeight", "bold");
    alib.dom.styleSet(lblTitle, "fontSize", "12px");
    lblTitle.innerHTML = "Account Info";
    
    var divRight = alib.dom.createElement("div", divAccount);
    alib.dom.styleSet(divRight, "float", "left");
    
    divRight.innerHTML = "<div class='loading'></div>";
    divClear(divAccount);
    
    ajax = new CAjax('json');
    ajax.cls = this;        
    ajax.divRight = divRight;
    ajax.onload = function(ret)
    {
        this.divRight.innerHTML = "";
        
        var spanEdition = alib.dom.createElement("span", divRight);
        alib.dom.styleSet(spanEdition, "font-weight", "bold");
        alib.dom.styleSet(spanEdition, "margin-right", "15px");
        spanEdition.innerHTML = ret.editionName;
        
        var divChange = alib.dom.createElement("span", divRight);    
		var btnEdition = alib.ui.Button("Change Edition", {
			className:"b1", tooltip:"Upgrade or Downgrade", cls:this.cls, 
			onclick:function() { 
				var wiz = new AntWizard("UpdateEdition"); 
				wiz.cbData.cls = this.cls; 
				wiz.onFinished = function() { this.cbData.cls.buildInterface(); };
			   	wiz.show(); 
			}
		});
		btnEdition.print(divChange);

        //var btnEdition = createInputAttribute(alib.dom.createElement("input", divEdition), "button", "btnEdition", null, null, "Change Edition");
        
        var divDescription = alib.dom.createElement("div", divRight);
        alib.dom.styleSet(divDescription, "width", "350px");
        alib.dom.styleSet(divDescription, "margin", "10px 0");
        divDescription.innerHTML = ret.editionDesc;
        
        var divUsers = alib.dom.createElement("div", divRight);
        var spanUsers = alib.dom.createElement("span", divUsers);
        alib.dom.styleSet(spanUsers, "fontWeight", "bold");
        alib.dom.styleSet(spanUsers, "marginRight", "15px");
        spanUsers.innerHTML = "Current Usage:";
        
        var spanNumUsers = alib.dom.createElement("span", divUsers);
        spanNumUsers.innerHTML = ret.usageDesc;
    }
    ajax.exec("/controller/Admin/getEditionAndUsage");
}

/*************************************************************************
*    Function:    billingAddress
*
*    Purpose:    Creates billing Address settings
**************************************************************************/
Plugin_Settings_Account.prototype.billingAddress = function()
{
    var divBilling = alib.dom.createElement("div", this.innerCon);
    alib.dom.styleSet(divBilling, "borderTop", "1px solid");
    alib.dom.styleSet(divBilling, "paddingTop", "5px");
    
    var divLeft = alib.dom.createElement("div", divBilling);
    alib.dom.styleSet(divLeft, "float", "left");
    alib.dom.styleSet(divLeft, "width", "190px");
    
    var divTitle = alib.dom.createElement("div", divLeft);
    alib.dom.styleSet(divTitle, "fontWeight", "bold");
    alib.dom.styleSet(divTitle, "fontSize", "12px");
    divTitle.innerHTML = "Billing Address";
    
    var divDesc = alib.dom.createElement("div", divLeft);
    alib.dom.styleSet(divDesc, "width", "160px");
    alib.dom.styleSet(divDesc, "marginLeft", "5px");
    divDesc.innerHTML = "This is the address invoice will be billed to and must match the billing address of the debit/credit card on file";
    
    var divRight = alib.dom.createElement("div", divBilling);
    alib.dom.styleSet(divRight, "float", "left");
    
    divRight.innerHTML = "<div class='loading'></div>";
    divClear(divBilling);
    
    ajax = new CAjax('json');
    ajax.cls = this;        
    ajax.divRight = divRight;
    ajax.onload = function(ret)
    {
        this.divRight.innerHTML = "";
        
        var tableForm = alib.dom.createElement("table", divRight);
        var tBody = alib.dom.createElement("tbody", tableForm);
        
        this.cls.accountForm.billing = new Object();
        
        this.cls.accountForm.billing.street = createInputAttribute(alib.dom.createElement("input"), "text", "street", "Street", "300px", ret.street, "92px");
        this.cls.accountForm.billing.street.inputLabel = " (REQUIRED)";
        
        this.cls.accountForm.billing.street2 = createInputAttribute(alib.dom.createElement("input"), "text", "street2", "Street2", "300px", ret.street2, "92px");
        
        this.cls.accountForm.billing.zip = createInputAttribute(alib.dom.createElement("input"), "text", "zip", "Zipcode", "300px", ret.zip, "92px");
        this.cls.accountForm.billing.zip.inputLabel = " (REQUIRED)";
        
        this.cls.accountForm.billing.state = createInputAttribute(alib.dom.createElement("input"), "text", "state", "State", "300px", ret.state, "92px");
        this.cls.accountForm.billing.state.inputLabel = " (REQUIRED)";
        
        this.cls.accountForm.billing.city = createInputAttribute(alib.dom.createElement("input"), "text", "city", "City", "300px", ret.city, "92px");
        this.cls.accountForm.billing.city.inputLabel = " (REQUIRED)";
        
        buildFormInput(this.cls.accountForm.billing, tBody);
    };
    ajax.exec("/controller/Customer/getBillingAddress");
}

/*************************************************************************
*    Function:    creditCard
*
*    Purpose:    Creates credit card settings
**************************************************************************/
Plugin_Settings_Account.prototype.creditCard = function()
{
    var divCredit = alib.dom.createElement("div", this.innerCon);
    alib.dom.styleSet(divCredit, "borderTop", "1px solid");
    alib.dom.styleSet(divCredit, "paddingTop", "5px");
    
    var divLeft = alib.dom.createElement("div", divCredit);
    alib.dom.styleSet(divLeft, "float", "left");
    alib.dom.styleSet(divLeft, "width", "190px");
    
    var divTitle = alib.dom.createElement("div", divLeft);
    alib.dom.styleSet(divTitle, "fontWeight", "bold");
    alib.dom.styleSet(divTitle, "fontSize", "12px");
    divTitle.innerHTML = "Update Credit Card";
    
    var divDesc = alib.dom.createElement("div", divLeft);
    alib.dom.styleSet(divDesc, "width", "160px");
    alib.dom.styleSet(divDesc, "marginLeft", "5px");
    divDesc.innerHTML = "Fill this out to change the credit or debit card used to pay your monthly subscription";
    
    var divRight = alib.dom.createElement("div", divCredit);
    alib.dom.styleSet(divRight, "float", "left");
    
    divRight.innerHTML = "<div class='loading'></div>";
    divClear(divCredit);
    
    ajax = new CAjax('json');
    ajax.cls = this;        
    ajax.divRight = divRight;
    ajax.onload = function(ret)
    {
        this.cls.creditCardNumber = ret[0].number;
        
        this.divRight.innerHTML = "";
        
        var tableForm = alib.dom.createElement("table", divRight);
        var tBody = alib.dom.createElement("tbody", tableForm);
        
        this.cls.accountForm.credit = new Object();
        
        this.cls.accountForm.credit.number = createInputAttribute(alib.dom.createElement("input"), "text", "ccard_number", "Credit Number", "300px", ret[0].maskedCc);
        
        this.cls.accountForm.credit.name = createInputAttribute(alib.dom.createElement("input"), "text", "ccard_name", "Name on Card", "300px", ret[0].ccard_name);
        
        this.cls.accountForm.credit.expMonth = createInputAttribute(alib.dom.createElement("input"), "text", "ccard_exp_month", "Expiration Month", "92px", ret[0].ccard_exp_month);
        this.cls.accountForm.credit.expMonth.inputLabel = " (MM)";
        
        this.cls.accountForm.credit.expYear = createInputAttribute(alib.dom.createElement("input"), "text", "ccard_exp_year", "Expiration Year", "92px", ret[0].ccard_exp_year);
        this.cls.accountForm.credit.expYear.inputLabel = " (YYYY)";
        
        buildFormInput(this.cls.accountForm.credit, tBody);
    }
    ajax.exec("/controller/Customer/getCreditCard");
}

/*************************************************************************
*    Function:    billingHistory
*
*    Purpose:    Creates credit Address settings
**************************************************************************/
Plugin_Settings_Account.prototype.billingHistory = function()
{
    var divHistory = alib.dom.createElement("div", this.innerCon);
    alib.dom.styleSet(divHistory, "borderTop", "1px solid");
    alib.dom.styleSet(divHistory, "paddingTop", "5px");
    
    var divLeft = alib.dom.createElement("div", divHistory);
    alib.dom.styleSet(divLeft, "float", "left");
    alib.dom.styleSet(divLeft, "width", "190px");
    
    var lblTitle = alib.dom.createElement("p", divLeft);
    alib.dom.styleSet(lblTitle, "fontWeight", "bold");
    alib.dom.styleSet(lblTitle, "fontSize", "12px");
    lblTitle.innerHTML = "Billing History";
    
    var divRight = alib.dom.createElement("div", divHistory);
    alib.dom.styleSet(divRight, "float", "left");
    
    divRight.innerHTML = "<div class='loading'></div>";
    divClear(divHistory);
    
    ajax = new CAjax('json');
    ajax.cls = this;    
    ajax.divRight = divRight;
    ajax.onload = function(ret)
    {
        this.divRight.innerHTML = "";
        this.cls.displayHistory(ret, this.divRight);
    }
    ajax.exec("/controller/Customer/accountHistory");
}

/*************************************************************************
*    Function:    saveBilling
*
*    Purpose:    Saves the Billing Address
**************************************************************************/
Plugin_Settings_Account.prototype.displayHistory = function(historyData, con)
{
    // print CToolTable
    var historyTbl = new CToolTable("100%");    
    historyTbl.addHeader("Last 12 Invoices", "left", "300px");
    historyTbl.addHeader("Status", "center", "50px");
    historyTbl.addHeader("Price", "center", "50px");
    
    historyTbl.print(con);
    
    for(data in historyData)
    {   
        var currentHistory = historyData[data];
        var rw = historyTbl.addRow();
        
        rw.addCell(currentHistory.name);
        rw.addCell(currentHistory.status);
        rw.addCell('$ ' + currentHistory.price);
    }
}

/*************************************************************************
*    Function:    saveBilling
*
*    Purpose:    Saves the Billing Address
**************************************************************************/
Plugin_Settings_Account.prototype.saveBilling = function()
{
    var hasError = false;
    var args = new Array();
    for(var billing in this.accountForm.billing)
    {
        var currentBilling = this.accountForm.billing[billing];
        
        var value = "";
        switch(currentBilling.type)
        {
            case "button":                    
            case "checkbox":
                continue;
                break;
            case "select-one":
            case "text":
            default:
                value = currentBilling.value.replace(/^\s+|\s+$/g, ""); 
                break;
        }
        
        switch(currentBilling.id)
        {
            case "street":
            case "state":            
            case "city":            
            case "zip":
                var label = currentBilling.parentNode.lastChild;                
                alib.dom.styleSet(label, "color", "#000000");
                label.innerHTML = " (REQUIRED)";
                
                if(value=="")
                {
                    label.innerHTML = " (REQUIRED) *Please input a valid entry.";
                    alib.dom.styleSet(label, "color", "red");
                    hasError = true;
                }                    
            break;
        }
        
        args[args.length] = [currentBilling.id, value];
    }
    
    if(hasError)    
        ALib.statusShowAlert("Please fill in the required fields!", 3000, "bottom", "right");
    else
    {
        ajax = new CAjax('json');
        ajax.cls = this;    
        ajax.dlg = showDialog("Saving account settings, please wait...");
        ajax.onload = function(ret)
        {
            this.cls.saveCreditCard(this.dlg);
        }
        ajax.exec("/controller/Customer/saveBillingAddress", args);
    }
}

/*************************************************************************
*    Function:    saveCreditCard
*
*    Purpose:    Saves the Credit card info
**************************************************************************/
Plugin_Settings_Account.prototype.saveCreditCard = function(dlg)
{
    var args = new Array();
    for(var credit in this.accountForm.credit)
    {
        var currentCredit = this.accountForm.credit[credit];
        
        var value = "";
        switch(currentCredit.type)
        {
            case "button":                    
            case "checkbox":
                continue;
                break;
            case "select-one":
            case "text":
            default:
                value = currentCredit.value.replace(/^\s+|\s+$/g, ""); 
                break;
        }
        
        switch(currentCredit.id)
        {
            case 'ccard_number':
                if ( isNaN(parseFloat(value)) ) 
                    value = this.creditCardNumber;
                break;
        }
        
        args[args.length] = [currentCredit.id, value];
        
    }
    
    ajax = new CAjax('json');
    ajax.cls = this;    
    ajax.dlg = dlg;    
    ajax.onload = function(ret)
    {
        this.dlg.hide();
        
        if(ret>=0)
            ALib.statusShowAlert("Account settings saved!", 3000, "bottom", "right");
        else
            ALib.statusShowAlert("Error while saving account settings!", 3000, "bottom", "right");        
    }
    ajax.exec("/controller/Customer/saveCreditCard", args);
}
