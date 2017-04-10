:: Combine all scripts into one
php includes.php > alib_full.js

:: Compess scripts
:: java -jar compiler.jar --mark_as_compiled true --js .\alib_full.js --js_output_file alib_full.cmp.js
java -jar compiler.jar --js .\alib_full.js --js_output_file alib_full.cmp.js
