Build Status
------------
[![Build Status](https://travis-ci.org/sugarcrm/shadow.svg?branch=master)](https://travis-ci.org/sugarcrm/shadow)

Git: <https://github.com/sugarcrm/shadow>

PHP module
==========

Shadow is implemented as a PHP extension. Compile it using:

```bash
phpize
./configure --with-php-config=php-config
make
make install
make test
```

Install it with 'make install' or just copy modules/shadow.so to PHP
extensions directory. Add:

```php
extension=shadow.so
```

to the php.ini.

Only Unix-based systems are currently supported, no build for Windows as
of yet.

Shadow function
---------------

Shadow has one main function:

```c
void shadow(string template, string instance[, array instance_only])
```

-   template is the template directory
-   instance is instance directory
-   instance\_only is an array of directories or filenames (relative to
    instance directory) that are instance-only

Other functions:

```php
array shadow_get_config()
void shadow_clear_cache()
```

Configuration parameters
------------------------

php.ini parameters for shadow. Default is fine for most cases.

| Name               | Default | Meaning                                              |
|--------------------|---------|------------------------------------------------------|
| shadow.enabled     | 1       | Shadowing enabled?                                   |
| shadow.mkdir\_mask | 0755    | Mask used when creating new directories on instances |
| shadow.debug       | 0       | Debug level (bitmask)                                |
| shadow.cache\_size | 10000   | Shadow cache size (in bytes, per process)            |

Debug level
-----------

```c
DEBUG_FULLPATH     (1<<0)   1
DEBUG_OPEN         (1<<1)   2
DEBUG_STAT         (1<<2)   4
DEBUG_MKDIR        (1<<3)   8
DEBUG_OPENDIR      (1<<4)   16
DEBUG_RESOLVE      (1<<5)   32
DEBUG_UNLINK       (1<<6)   64
DEBUG_RENAME       (1<<7)   128
DEBUG_PATHCHECK    (1<<8)   256
DEBUG_ENSURE       (1<<9)   512
DEBUG_FAIL         (1<<10)  1024
DEBUG_TOUCH        (1<<11)  2048
DEBUG_CHMOD        (1<<11)  4096
DEBUG_OVERRIDE     (1<<12)  8192
```

For enable all DEBUG message shadow.debug must be equal 8191

Sugar Module
============

sugarcrm directory in shadow repo has implementation of Shadow
auto-config for per-server shadowing using mongodb. Running mongo server
and mongo PHP extension mandatory for using it. It also requires APC
installed.

Apache configuration
--------------------

Set up Apache virtual hosts as follows:

```bash

<VirtualHost *:80>
    ServerName shadow.dev
    ServerAlias *.shadow.dev

    # path to sugar core
    DocumentRoot /var/www/sugar/SugarEnt-Full-7.5.1.0/Pro

    <Directory /var/www/sugar/SugarEnt-Full-7.5.1.0/Pro/>
        Options Indexes FollowSymLinks MultiViews
        AllowOverride All
        Order allow,deny
        allow from all
    </Directory>

    <IfModule mod_headers.c>
        Header always set Strict-Transport-Security "max-age=31536000"
    </IfModule>

    AddType application/x-font-woff .woff
    AddType application/json .json

    RewriteEngine On

    RewriteMap lowercase int:tolower
    RewriteRule [^/]+\.log$ - [L,F]
    RewriteRule /not_imported_.*\.txt$ - [L,F]
    RewriteRule /(soap|cache|xtemplate|data|examples|include|log4php|metadata|modules)/+.*\.(php|tpl)$ - [L,F]
    RewriteRule /upload/ - [L,F]
    RewriteRule /custom/+blowfish - [L,F]
    RewriteRule /cache/+diagnostic - [L,F]
    RewriteRule /files\.md5 - [L,F]

    # /var/www/sugar/shadowed/ - path to instance cache
    RewriteCond /var/www/sugar/shadowed/${lowercase:%{SERVER_NAME}}/%{REQUEST_FILENAME} -f
    RewriteRule ^(.*)$ /var/www/sugar/shadowed/${lowercase:%{SERVER_NAME}}/$1 [L,QSA]

    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^/rest/(.*)$ /api/rest.php?__sugar_url=$1 [L,QSA]
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^/cache/api/metadata/lang_(.._..)_(.*)_public(_ordered)?\.json$ /rest/v10/lang/public/$1?platform=$2&ordered=$3 [N,QSA]
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^/cache/api/metadata/lang_(.._..)_([^_]*)(_ordered)?\.json$ /rest/v10/lang/$1?platform=$2&ordered=$3 [N,QSA]
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^/cache/Expressions/functions_cache(_debug)?.js$ /rest/v10/ExpressionEngine/functions?debug=$1 [N,QSA]
    RewriteRule ^/cache/jsLanguage/(.._..).js$ /index.php?entryPoint=jslang&module=app_strings&lang=$1 [L,QSA]
    RewriteRule ^/cache/jsLanguage/(\w*)/(.._..).js$ /index.php?entryPoint=jslang&module=$1&lang=$2 [L,QSA]

    RewriteRule ^/portal/config\.js$ /var/www/sugar/shadowed/${lowercase:%{SERVER_NAME}}/portal2/config.js [L]

    RewriteRule ^/portal/(.*)$ /portal2/$1 [L,QSA]
    RewriteRule ^/portal$ /portal/? [R=301,L]

    RewriteRule ^/pingdom\.php$ /var/www/sugar/shadowed/${lowercase:%{SERVER_NAME}}/pingdom.php [L]

    php_value auto_prepend_file /var/www/sugar/SugarShadow.php
    php_admin_flag basedir.enabled on

    <FilesMatch "\.(jpg|png|gif|js|css|ico|woff)$">
      <IfModule mod_headers.c>
        Header set ETag ""
        Header set Cache-Control "max-age=2592000"
        Header set Expires "01 Jan 2112 00:00:00 GMT"
      </IfModule>
    </FilesMatch>
    <IfModule mod_expires.c>
      ExpiresByType text/css "access plus 1 month"
      ExpiresByType text/javascript "access plus 1 month"
      ExpiresByType application/x-javascript "access plus 1 month"
      ExpiresByType image/gif "access plus 1 month"
      ExpiresByType image/jpg "access plus 1 month"
      ExpiresByType image/png "access plus 1 month"
      ExpiresByType application/x-font-woff "access plus 1 month"
    </IfModule>

    ErrorLog ${APACHE_LOG_DIR}/shadow.sugar.work.error.log
    LogLevel warn
    CustomLog ${APACHE_LOG_DIR}/shadow.sugar.work.access.log combined
</VirtualHost>

```

Instances should be in /path/to/instances/ in directories matching the
host names.

Template configuration
----------------------

Copy SugarShadow.php and shadow.config.php in the sugarcrm directory.
Add this:

```php
require('SugarShadow.php');
SugarShadow::shadow($_SERVER['SERVER_NAME']);
```

to all entry points (such as index.php, etc.) as early as possible,
before any includes, etc. Most important are index.php and install.php,
but all entry points should be covered for them to work. cron.php is
currently unsupported.

Edit shadow.config.php, following values exist:

For mongodb:

```php
'mongo'=>array('server'=>'127.0.0.1', 'port'=>'27017', 'username'=>null, 'password'=>null),
```

This defines MongoDB connection and is required for both setting up and
running instances. Database named 'exosphere' and collection named
'instances' is used for shadow instances data.

For shadow:

```php
shadow'=>array(
    'instancePath'=>'/path/to/instances',
    'createDir' => true,
    'siTemplate' => '../instances/config_si.php',
    'addHost'=>false,
    'ip' => '127.0.0.1' ),
```


These are required for setting up instances but not used for running instance once created.

* instancePath - is the path (without the instance hostname) where instances are located
* createDir - if instance directory should be created when it does not exist
* siTemplate - file that gets placed into new instance directory as config_si.php for
silent install. setup_db_database_name gets appended '_' and then server name with dots replaced by '_'s
(e.g., sugarcrm to sugarcrm_somename_shadow_com), setup_site_url gets 'SERVER' replaced by current server
name (so it should be like 'http://SERVER/'). If it's not set, nothing happens.
* addHost - adds IP alias for this instance automatically to /etc/hosts when setup (see below)
* ip - the IP used for adding alias above

Full example:

```php
$shadow_config = array(
    'mongo' => array(
        'server' => '127.0.0.1',
        'port' => '27017',
        'username' => null,
        'password' => null
    ),
    'shadow' => array(
        'instancePath' => '/path/to/instances',
        'addHost' => true,
        'createDir' => true,
        'siTemplate' => '/path/to/instances/config_si.php',
        "ip" => "127.0.0.1"
    )
);
```

Note that if instance directory does not exist at runtime and createDir is set,
it will be created automatically (but the instance record should be present in MongoDB for that!).

Creating instance
=================

Use SugarExosphere.php to create new instances - see above for configurations
used by it. Just enter the host name into the field, the rest is done automatically.

Alternatively, adding record to MongoDB database 'exosphere' collection 'instances'
will also produce instance if automatic creation (see above) is enabled:

```json
{
   "key" : "cac6f23b011f8f89eb4b7279322a4431",
   "server" : "some.shadow.com",
   "path" :"/path/to/instances/some.shadow.com"
}
```

key is arbitrary key, server must be the server name and path is full instance path.

If automatic creation is disabled, you will also need to create the instance directory
and then either run it through install or place suitable config_si.php in that directory
and let silent install do it.


Important
========

Extension not work with opcache by default. Change opcache default options to:

```php
opcache.revalidate_freq = 0
opcache.revalidate_path = On
```

Current directory (CWD) should always point to template directory When you use shadow.


