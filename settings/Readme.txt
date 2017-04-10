# Configuration for binary compilation
***************************************

[freetds] for MSSQL database connection
./configure --with-tdsver=7.0 --enable-msdblib --enable-dbmfix 
	    --with-gnu-ld --enable-shared --enable-static --prefix=/some/dir

[php]
'./configure' '--prefix=/programs/apache/php' '--with-apxs2=/programs/apache/bin/apxs' 
	      '--with-config-file-path=/programs/apache/php' '--with-pgsql=/programs/pgsql/' 
              '--with-xml' '--with-mssql=/path/to/freetds'

[apache]
./configure