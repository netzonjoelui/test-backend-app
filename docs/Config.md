# Config

The new config classes have been created to replace the old Netric\Config concrete class. This module
has been designed to be more modular and easy to work with.

Configuration data are made accessible to Netric\Config\Config‘s constructor with an associative array,
which may be multi-dimensional, so data can be organized from general to specific.

Each value in the configuration data array becomes a property of the Netric\Config\Config object.
The key is used as the property name. If a value is itself an array, then the resulting object
property is created as a new iNetric\Config\Config object, loaded with the array data.
This occurs recursively, such that a hierarchy of configuration data may be created with any number of levels.

By default, configuration data made available through Netric\Config\Config are read-only, and an assignment
(e.g. $config->domian = 'example.com';) results in a thrown exception.

## Using Config

It is possible to use Config by simply constructing a new instance and passing config parameters as an associative array.

    $params = array('database'=>'my_db_name');
    $config = new Netric\Config\Config($params);
    echo $config->database;
    // Output: my_db_name

More often however, you will want to load config params from a file or a set of files.

### Config Files

By default configuration files are simple php arrays.

    return array(
        'property' => 'value',
        'database' => array(
            'host' => 'localhost',
            'username' => 'something'
        )
    );

INI files are also supported via the config loader.

### Loading Config Files

\Netric\Config\ConfigLoader can be used to load configuration from a file or even merge multiple files to allow
 alternative configurations based on environment (such as 'develop' and 'local').

Example:

    // Set the application environment: develop|staging|testing...
    $appEnv = "develop";
    $config = \Netric\Config\ConfigLoader::fromFolder("./config", $appEnv);

The ConfigLoader::fromFolder will construct the configuration from multiple files in the provided path.

It will load the files in the following order, merging and overriding any previously set properties.

#### global.php

The global.php file contains the default common values that get loaded in all environments.

#### {$appEnv}.php

If the second param is passed to ConfigLoader::fromFolder, it will load the array and merge it with global.php. 

If both files contain the same property name, values in {$appEnv}.php will override global.php.

#### local.php

Never check local.php into version control. \Netric\Config\ConfigLoader::fromFolder will load local.php 
(if it exists), allowing the developer to override the values in global.php and {$appEnv}.php