
<html>
<!--
Pungo Spell Copyright (c) 2003 Billy Cook, Barry Johnson

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
-->

<head>
<link rel="STYLESHEET" type="text/css" href="/css/<?php echo UserGetTheme(&$dbh, $USERID, 'css'); ?>">
<style type="text/css">
   .hilight 
   {
  		color: #ff0000;
        font-weight: bold;
   }

   .editorWindow {
                  border-style: outset; 
                  padding: 5px; 
                  width: 640px; 
                  height: 200px; 
                  overflow: auto;
                 }

   .editorTable {
					width:450px;
                }

   .topCells {
              background-color: #EAC679;
             }

   .bottomCell {
                 background-color: lightgrey;
                 text-align: right;
                }

   .spButton {
              background-color: #ffffff;
			  width:80px;
             }

   .suggestionsBox {
                    width: 200px;
                   }

   .changeToBox {
                 width: 200px;
                }
</style>
<script>

var iFrameBody;
<?php
// assign a global js var for fieldname
print "var spell_formname='".$_POST['spell_formname']."';\n";
print "var spell_fieldname='".$_POST['spell_fieldname']."';\n";
?>
</script>
<script src="spellcheck.js"></script>
<script>
<?php
// print out the misspelled words JavaScript
print $js;
?>
</script>
</head>

<body onLoad="startsp();" class='whitepage'>
<form name="fm1" onSubmit="return false;">
<div style="border:1px solid #CCCCCC;">
<iframe style="width: 450px; height: 300px" src="iframedoc.html" frameborder="0"></iframe>
</div>
<div style="border:1px solid #CCCCCC;background-color: #EEEEEE;">
<table border="0" cellpadding="3" cellspacing="0" class="editorTable">
   <tr>
     <td valign="top">
	 	Change to:<br>
        <input type="text" name="changeto" class="changeToBox"><br>
        <table border="0">
			<tr>
				<td><?php echo ButtonCreate("Change", "replaceWord()"); ?></td>
				<td><?php echo ButtonCreate("Change All", "replaceAll()"); ?></td>
			</tr>
			<tr>
				<td><?php echo ButtonCreate("Ignore", "nextWord(false)"); ?></td>
				<td><?php echo ButtonCreate("Ignore All", "nextWord(true)"); ?></td>
			</tr>
			<tr>
				<td><?php echo ButtonCreate("Finished", "nextWord(false, true)"); ?></td>
				<td><?php echo ButtonCreate("Add To Dictionary", "xmlSpellAddWord()"); ?></td>
			</tr>
			
		</table>
     </td>
     <td>
       Suggestions:<br>
       <select name="suggestions"  class="suggestionsBox" size="5" onClick="this.form.changeto.value = this.options[ this.selectedIndex ].text">
       </select>
     </td>
   </tr>
</table>
</div>
</form>
</body>
</html>
