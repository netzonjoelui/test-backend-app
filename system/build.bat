:: Combine all javascripts scripts into one
cd ..\lib\js
php includes.php build > ant_full.js

:: Compess scripts
cd ..\..\system
java -jar compiler.jar --js ..\lib\js\ant_full.js --js_output_file ..\lib\js\ant_full.cmp.js


