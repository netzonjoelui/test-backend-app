Pungo Spell Copyright (c) 2003 Billy Cook, Barry Johnson

Permission is hereby granted, free of charge, to any person obtaining a copy of 
this software and associated documentation files (the "Software"), to deal in the 
Software without restriction, including without limitation the rights to use, 
copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the 
Software, and to permit persons to whom the Software is furnished to do so, 
subject to the following conditions:

The above copyright notice and this permission notice shall be included in all 
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, 
INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A 
PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT 
HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION 
OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE 
SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.


Pungo Spell
-----------

What is it?
-----------
Pungo spell is an easy, and more importantly, a FREE way to add a studly spell
checker to just about ANY web application.  

What does it require?
---------------------

PHP 4.3.1 (http://www.php.net) or greater with Pspell support enabled.  
If your system doesn't have pspell/aspell installed, see the pspell source page at
http://pspell.sourceforge.net to download it.

What browsers does it work with?
-------------------------------

Only "modern" browsers that support DOM level 2
(http://www.w3.org/TR/DOM-Level-2-Core/) are supported.  
MSIE 5.5+, Opera 7.2+, NS 6+ should work fine for you.
Your milage may vary.

How do I use it?
----------------

See example.html for details, but here is gist:  

In the <head> section of your HTML, include the following line:

<script src="spellcheck.js"></script>

This includes the javascript methods needed to instantiate the spell checking
window.

In your form that contains the text box that you want to check, include the
code for a spell check button like this:

  <input type="button" value="Spell Check" onClick="spellCheck( 'form_one',
  'text_one');">

When your user clicks on the Spell Check button, the spellCheck javascript
function is called with two parameters.  The first one, 'form_one' in our
example, is the name of the form, the second param, 'text_one' in our example,
is the name of the field you want to spell check and update.

If you'd like to have spell checks for multiple forms on a page, just
reference the correct form and text box element names and you should be fine.

In addition you MUST ALSO include this hidden form somewhere in your page:

   <form name="spell_form" id="spell_form" method="POST" target="spellWindow"
         action="checkspelling.php">
     <input type="hidden" name="spell_formname" value="">
     <input type="hidden" name="spell_fieldname" value="">
     <input type="hidden" name="spellstring" value="">
  </form>






