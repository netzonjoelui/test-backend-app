==============================================================
Script:     Amazing Draggable Layer

Functions:  This script implements a draggable layer that
            can be used much like a popup window... but with-
            out the usual focus problems that popups often
            imply.  Also included are simple controls to show
            or hide the draggable layer. Compatible with
            NS4-7 & IE.
            
Comments:   The script is in two parts.  A JavaScript
            <script> to be placed in the <head> of the
            page; and a <div> layer that should be placed
            either immediately after the <body> tag or
            immediately before the </body> tag.
            
            There are *no* changes or setups required in the
            JavaScript script.
            
            Positioning, width, height, colors, fonts, etc.,
            as well as initial visibility are set in the
            layer portion of the script, in the body.
            
Notes:      Only a single instance of the script is allowed
            per page.            

Browsers:   NS4-7 & IE4 and later

Author:     etLux
==============================================================



STEP 1.
Inserting the JavaScript <script> In Your Page

Put the following <script> </script> in the head
section of your page.  

There are no setups or changes required.



<script language="JavaScript1.2">

// Script Source: CodeLifter.com
// Copyright 2003
// Do not remove this header

isIE=document.all;
isNN=!document.all&&document.getElementById;
isN4=document.layers;
isHot=false;

function ddInit(e){
  topDog=isIE ? "BODY" : "HTML";
  whichDog=isIE ? document.all.theLayer : document.getElementById("theLayer");  
  hotDog=isIE ? event.srcElement : e.target;  
  while (hotDog.id!="titleBar"&&hotDog.tagName!=topDog){
    hotDog=isIE ? hotDog.parentElement : hotDog.parentNode;
  }  
  if (hotDog.id=="titleBar"){
    offsetx=isIE ? event.clientX : e.clientX;
    offsety=isIE ? event.clientY : e.clientY;
    nowX=parseInt(whichDog.style.left);
    nowY=parseInt(whichDog.style.top);
    ddEnabled=true;
    document.onmousemove=dd;
  }
}

function dd(e){
  if (!ddEnabled) return;
  whichDog.style.left=isIE ? nowX+event.clientX-offsetx : nowX+e.clientX-offsetx; 
  whichDog.style.top=isIE ? nowY+event.clientY-offsety : nowY+e.clientY-offsety;
  return false;  
}

function ddN4(whatDog){
  if (!isN4) return;
  N4=eval(whatDog);
  N4.captureEvents(Event.MOUSEDOWN|Event.MOUSEUP);
  N4.onmousedown=function(e){
    N4.captureEvents(Event.MOUSEMOVE);
    N4x=e.x;
    N4y=e.y;
  }
  N4.onmousemove=function(e){
    if (isHot){
      N4.moveBy(e.x-N4x,e.y-N4y);
      return false;
    }
  }
  N4.onmouseup=function(){
    N4.releaseEvents(Event.MOUSEMOVE);
  }
}

function hideMe(){
  if (isIE||isNN) whichDog.style.visibility="hidden";
  else if (isN4) document.theLayer.visibility="hide";
}

function showMe(){
  if (isIE||isNN) whichDog.style.visibility="visible";
  else if (isN4) document.theLayer.visibility="show";
}

document.onmousedown=ddInit;
document.onmouseup=Function("ddEnabled=false");

</script>



==============================================================



STEP 2.
Inserting The Layer Code In Your Page

Insert the following code in the body of your page.  It may be
placed either immediately after the <body> tag or
immediately before the </body> tag.

This is essentially a couple of nested tables inside a <div>

Colors and spacing are set with the usual table features
(bgcolor, cellpadding).

The width, height, left and top position are set in the style
in the <div> tag.  

Likewise, if you want the layer to be initially invisible, set
visibility:visible instead to visibility:hidden in the style.

(To show or hide the layer from a JavaScript link function
call see the following Step 3.)

Your content goes in the commented area, as shown.  It can be
most any html code or text, though additional div  or table
tags within the designated content area should be done with
care, and checked in all browser versions.

To change the titlebar text, find the words Layer Title and
replace them with your title.



<!-- BEGIN FLOATING LAYER CODE //-->
<div id="theLayer" style="position:absolute;width:250px;left:100;top:100;visibility:visible">
<table border="0" width="250" bgcolor="#424242" cellspacing="0" cellpadding="5">
<tr>
<td width="100%">
  <table border="0" width="100%" cellspacing="0" cellpadding="0" height="36">
  <tr>
  <td id="titleBar" style="cursor:move" width="100%">
  <ilayer width="100%" onSelectStart="return false">
  <layer width="100%" onMouseover="isHot=true;if (isN4) ddN4(theLayer)" onMouseout="isHot=false">
  <font face="Arial" color="#FFFFFF">Layer Title</font>
  </layer>
  </ilayer>
  </td>
  <td style="cursor:hand" valign="top">
  <a href="#" onClick="hideMe();return false"><font color=#ffffff size=2 face=arial  style="text-decoration:none">X</font></a>
  </td>
  </tr>
  <tr>
  <td width="100%" bgcolor="#FFFFFF" style="padding:4px" colspan="2">
<!-- PLACE YOUR CONTENT HERE //-->  
This is where your content goes.<br>
It can be any html code or text.<br>
Remember to feed the reindeer.<br>
Avoid chewable giblet curtains.
<!-- END OF CONTENT AREA //-->
  </td>
  </tr>
  </table> 
</td>
</tr>
</table>
</div>
<!-- END FLOATING LAYER CODE //--> 



==============================================================



STEP 3. (Optional)
Using Show And Hide Controls

The layer can be shown or hidden via simple function calls.

To show the layer:

<a href="javascript:showMe();">show</a>

To hide the layer:

<a href="javascript:hideMe();">hide</a>



============================[end]=============================
