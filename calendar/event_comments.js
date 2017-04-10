var g_commentscon = null;

function tabComments(con)
{
	g_commentscon = alib.dom.createElement("div", con);
	for (var i = 0; i < g_event.comments.length; i++)
	{
		addComment(g_event.comments[i].id, g_event.comments[i].ts_entered_str, g_event.comments[i].entered_by, g_event.comments[i].message);
	}	

	var frm1 = new CWindowFrame("Add Comment");
	var frmcon = frm1.getCon();
	frm1.print(con);

	var ta_comment = alib.dom.createElement("textarea", frmcon);
	alib.dom.styleSet(ta_comment, "width", "98%");
	alib.dom.styleSet(ta_comment, "height", "50px");
	alib.dom.textAreaAutoResizeHeight(ta_comment, 100);
	alib.dom.createElement("br", frmcon);

	var btn = new CButton("Post Comment", function(ta_comment) {  postComment(ta_comment.value); ta_comment.value = "";}, [ta_comment], "b1");
	btn.print(frmcon);

	/*
	var submit_btn = alib.dom.createElement("input");
	submit_btn.ta_comment = ta_comment;
	submit_btn.type = "button";
	submit_btn.value = "Post Comment";
	submit_btn.onclick = function()
	{
		postComment(this.ta_comment.value);
		this.ta_comment.value = "";
	}
	frmcon.appendChild(submit_btn);
	*/
}

function postComment(message)
{
	if (g_eid)
	{
		ajax = new CAjax('json');
        ajax.cls = this;
        ajax.message = message;
        ajax.onload = function(ret)
        {
            this.cls.addComment(ret, "", this.cls.g_username, this.message);      
        };
        ajax.exec("/controller/Calendar/addComment", 
                    [["comment", message], ["eid", g_eid]]);
	}
	else
	{
		g_event.queued_comments[g_event.queued_comments.length] = message;
		addComment("new", "", g_username, message);
	}
}

function addComment(id, ts_entered_str, entered_by, message)
{
	var frm1 = new CWindowFrame(entered_by + " - " + ts_entered_str, null, "3px");
	var frmcon = frm1.getCon();
	frm1.print(g_commentscon);

	frmcon.innerHTML = message;
}
