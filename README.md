# Oxrun

[![Build Status](https://travis-ci.org/marcharding/oxrun.svg)](https://travis-ci.org/marcharding/oxrun)
[![Coverage Status](https://coveralls.io/repos/github/marcharding/oxrun/badge.svg?branch=master)](https://coveralls.io/github/marcharding/oxrun?branch=master)

Oxrun provides a cli toolset for the OXID eShop Community Edition.

Thanks to the [netz98 magerun](https://github.com/netz98/n98-magerun) project which heavily inspired oxrun.

## Installation

__Disclaimer:__ This fork is intended for __usage with OXID 6.x__ and up, it will not be compatible with older shop versions. Legacy commands will be removed, e.g. 
"install:shop" which is now handled via Composer.


PHP 5.6 is required, PHP 7 or newer is recommended.

If you are using composer (which you probably are), just add `"smxsm/oxrun": "dev-develop"` to your composer.json and run composer install.

You can then use oxrun by calling `vendor/bin/oxrun` or add `vendor/bin` to your $PATH to be able to just call `oxrun`.

# Usage

To use oxrun just execute `php oxrun.phar` or `./vendor/bin/oxrun` (see above).

Execute oxrun inside your OXID eShop base directory (or subdirectory) if you want to interact with an existing shop. It will automatically try to find the oxid boostrap.php and load it.

If you want to __run it from a different directory__, you have to add the option `"--shopDir=/path/to/your/shop"`.

You can use it e.g. to help you with an OXID 6 installation or deployment, e.g.:

```json
  "scripts": {
    "post-update-cmd": [
      "Incenteev\\ParameterHandler\\ScriptHandler::buildParameters",
      "@oe:ide-helper:generate",
      "@oxrun:activate-modules",
      "@oxrun:set-config"
    ],
    "oxrun:activate-modules": [
      "./vendor/bin/oxrun module:multiactivate configs/modules.yml -c --shopDir=./source"
    ],
    "oxrun:set-config": [
      "./vendor/bin/oxrun config:multiset configs/malls.yml --shopDir=./source"
    ],

```

This could activate some modules in different subshops and set a bunch of config variables e.g.
__Please note:__ since activating modules and updating config values requires a filled database and a valid config.inc.php file, you'd have to do some more voodoo during the OXID setup routine to make the above example work! But it should give you an idea how to use oxrun "multiactivate" and "multiset" :)

# Available commands



misc:phpstorm:metadata
----------------------

* Description: Generate a PhpStorm metadata file for auto-completion.
* Usage:

  * `misc:phpstorm:metadata [-o|--output-dir OUTPUT-DIR]`

Generate a PhpStorm metadata file for auto-completion.

### Options:

**output-dir:**

* Name: `--output-dir`
* Shortcut: `-o`
* Is value required: yes
* Description: Writes the metadata for PhpStorm to the specified directory.

misc:generate:documentation
---------------------------

* Description: Generate a raw command documentation of the available commands
* Usage:

  * `misc:generate:documentation`

Generate a raw command documentation of the available commands

### Arguments:

**command:**

* Name: command
* Description: The command to execute

### Options:

db:query
--------

* Description: Executes a query
* Usage:

  * `db:query [--raw] [--] <query>`

Executes an SQL query on the current shop database. Wrap your SQL in quotes.

If your query produces a result (e.g. a SELECT statement), the output will be returned via the table component. Add the raw option for raw output.

Requires php exec and MySQL CLI tools installed on your system.

### Arguments:

**query:**

* Name: query
* Description: The query which is to be executed

### Options:

**raw:**

* Name: `--raw`
* Accept value: no
* Is value required: no
* Description: Raw output
* Default: `false`

db:anonymize
------------

* Description: Anonymize relevant OXID db tables
* Usage:

  * `db:anonymize [--debug] [-d|--domain [DOMAIN]] [-k|--keepdomain [KEEPDOMAIN]]`

Anonymizes user relevant data in the OXID database.
Relevant tables are:
Array
(
    [0] => oxnewssubscribed
    [1] => oxuser
    [2] => oxvouchers
    [3] => oxaddress
    [4] => oxorder
)


### Options:

**debug:**

* Name: `--debug`
* Accept value: no
* Is value required: no
* Description: Debug SQL queries generated
* Default: `false`

**domain:**

* Name: `--domain`
* Shortcut: `-d`
* Is value required: no
* Description: Domain to use for all anonymized usernames /email addresses, default is "@oxrun.com"

**keepdomain:**

* Name: `--keepdomain`
* Shortcut: `-k`
* Is value required: no
* Description: Domain which should NOT be anonymized, default is "@foobar.com". Data with this domain in the email address will NOT be anonymized.

db:import
---------

* Description: Import a sql file
* Usage:

  * `db:import <file>`

Imports an SQL file on the current shop database.

Requires php exec and MySQL CLI tools installed on your system.

### Arguments:

**file:**

* Name: file
* Description: The sql file which is to be imported

### Options:

db:list
-------

* Description: List of all Tables
* Usage:

  * `db:list [-p|--plain] [-t|--pattern PATTERN]`

List Tables

usage:
    oxrun db:list --pattern oxseo%,oxuser
    - To dump all Tables, but `oxseo`, `oxvoucher`, and `oxvoucherseries` without data.
      possibilities: oxseo%,oxuser,%logs%
      


### Options:

**plain:**

* Name: `--plain`
* Shortcut: `-p`
* Accept value: no
* Is value required: no
* Description: print list as comma separated.
* Default: `false`

**pattern:**

* Name: `--pattern`
* Shortcut: `-t`
* Is value required: yes
* Description: table name pattern test. e.g. oxseo%,oxuser

db:dump
-------

* Description: Dumps the the current shop database
* Usage:

  * `db:dump [--file FILE] [-t|--table TABLE] [-i|--ignoreViews] [-a|--anonymous] [-w|--withoutTableData WITHOUTTABLEDATA]`

Dump the current shop database.

usage:
    oxrun db:dump --withoutTableData oxseo,oxvou%
    - To dump all Tables, but `oxseo`, `oxvoucher`, and `oxvoucherseries` without data.
      possibilities: oxseo%,oxuser,%logs%
      
    oxrun db:dump --table %user%
    - to dump only those tables `oxuser` `oxuserbasketitems` `oxuserbaskets` `oxuserpayments` 

    oxrun db:dump --anonymous # Perfect for Stage Server
    - Those table without data: `oxseo`, `oxseologs`, `oxseohistory`, `oxuser`, `oxuserbasketitems`, `oxuserbaskets`, `oxuserpayments`, `oxnewssubscribed`, `oxremark`, `oxvouchers`, `oxvoucherseries`, `oxaddress`, `oxorder`, `oxorderarticles`, `oxorderfiles`, `oepaypal_order`, `oepaypal_orderpayments`.
    
    oxrun db:dump -v 
    - With verbose mode you will see the mysqldump command
      (`mysqldump -u 'root' -h 'oxid_db' -p ... `)
      
    oxrun db:dump --file dump.sql 
    - Put the Output into a File
    
** Only existing tables will be exported. No matter what was required.
    
Requires php, exec and MySQL CLI tools installed on your system.

### Options:

**file:**

* Name: `--file`
* Is value required: yes
* Description: Dump sql in to this file

**table:**

* Name: `--table`
* Shortcut: `-t`
* Is value required: yes
* Description: name of table to dump only. Default all tables. Use comma separated list and or pattern e.g. %voucher%

**ignoreViews:**

* Name: `--ignoreViews`
* Shortcut: `-i`
* Accept value: no
* Is value required: no
* Description: Ignore views
* Default: `false`

**anonymous:**

* Name: `--anonymous`
* Shortcut: `-a`
* Accept value: no
* Is value required: no
* Description: Do not export table with person related data.
* Default: `false`

**withoutTableData:**

* Name: `--withoutTableData`
* Shortcut: `-w`
* Is value required: yes
* Description: Table name to dump without data. Use comma separated list and or pattern e.g. %voucher%

install:shop __[DEPRECATED]__
-----------------------------

* Description: Installs the shop, for OXID 6 composer is used instead!
* Usage:

  * `install:shop __[DEPRECATED]__ [--oxidVersion [OXIDVERSION]] [--installationFolder [INSTALLATIONFOLDER]] [--dbHost DBHOST] [--dbUser DBUSER] [--dbPwd DBPWD] [--dbName DBNAME] [--dbPort [DBPORT]] [--installSampleData [INSTALLSAMPLEDATA]] [--shopURL SHOPURL] [--adminUser ADMINUSER] [--adminPassword ADMINPASSWORD]`

Installs the shop, for OXID 6 composer is used instead!

### Options:

**oxidVersion:**

* Name: `--oxidVersion`
* Is value required: no
* Description: Oxid version

**installationFolder:**

* Name: `--installationFolder`
* Is value required: no
* Description: Installation folder
* Default: `'/var/www/html/gerstaecker-oxid6/source'`

**dbHost:**

* Name: `--dbHost`
* Is value required: yes
* Description: Database host
* Default: `'localhost'`

**dbUser:**

* Name: `--dbUser`
* Is value required: yes
* Description: Database user
* Default: `'oxid'`

**dbPwd:**

* Name: `--dbPwd`
* Is value required: yes
* Description: Database password
* Default: `''`

**dbName:**

* Name: `--dbName`
* Is value required: yes
* Description: Database name
* Default: `'oxid'`

**dbPort:**

* Name: `--dbPort`
* Is value required: no
* Description: Database port
* Default: `3306`

**installSampleData:**

* Name: `--installSampleData`
* Is value required: no
* Description: Install sample data
* Default: `true`

**shopURL:**

* Name: `--shopURL`
* Is value required: yes
* Description: Installation base url

**adminUser:**

* Name: `--adminUser`
* Is value required: yes
* Description: Admin user email/login
* Default: `'admin@example.com'`

**adminPassword:**

* Name: `--adminPassword`
* Is value required: yes
* Description: Admin password
* Default: `'oxid-123456'`

cache:clear
-----------

* Description: Clears the cache
* Usage:

  * `cache:clear [-f|--force]`

Clears the cache

### Options:

**force:**

* Name: `--force`
* Shortcut: `-f`
* Accept value: no
* Is value required: no
* Description: Try to delete the cache anyway. [danger or permission denied]
* Default: `false`

route:debug
-----------

* Description: Returns the route. Which controller and parameters are called.
* Usage:

  * `route:debug [--shopId [SHOPID]] [-c|--copy] [--] <url>`

Returns the route. Which controller and parameters are called.

### Arguments:

**url:**

* Name: url
* Description: Website URL. Full or Path

### Options:

**shopId:**

* Name: `--shopId`
* Is value required: no
* Description: <none>

**copy:**

* Name: `--copy`
* Shortcut: `-c`
* Accept value: no
* Is value required: no
* Description: Copy file path from the class to the clipboard (only MacOS)
* Default: `false`

config:get
----------

* Description: Gets a config value
* Usage:

  * `config:get [--shopId [SHOPID]] [--moduleId [MODULEID]] [--] <variableName>`

Gets a config value

### Arguments:

**variableName:**

* Name: variableName
* Description: Variable name

### Options:

**shopId:**

* Name: `--shopId`
* Is value required: no
* Description: <none>

**moduleId:**

* Name: `--moduleId`
* Is value required: no
* Description: <none>

config:export
-------------

* Description: Export shop config
* Usage:

  * `config:export [--no-debug] [--env [ENV]] [--force-cleanup [FORCE-CLEANUP]]`

Info:
Exports all config values to yaml files, interacts with the
[Modules Config](https://github.com/OXIDprojects/oxid_modules_config/) module,
[__which currently isn't fully ported to OXID 6 yet!__](https://github.com/OXIDprojects/oxid_modules_config/tree/dev-6.0-wip)
Currently using experimental, [bleeding edge branch](https://github.com/OXIDprojects/oxid_modules_config/tree/PSGEN-282-Config_export_import_module_full_port_to_v6) :)
To try it, use this in your composer.json: 
"oxid-community/oxid_modules_config": "dev-PSGEN-282-Config_export_import_module_full_port_to_v6",

### Options:

**no-debug:**

* Name: `--no-debug`
* Accept value: no
* Is value required: no
* Description: No debug ouput
* Default: `false`

**env:**

* Name: `--env`
* Is value required: no
* Description: set specific environment, corresponds to a specific folder for the yaml files

**force-cleanup:**

* Name: `--force-cleanup`
* Is value required: no
* Description: Force cleanup on error

config:shop:set
---------------

* Description: Sets a shop config value
* Usage:

  * `config:shop:set [--shopId [SHOPID]] [--] <variableName> <variableValue>`

Sets a shop config value

### Arguments:

**variableName:**

* Name: variableName
* Description: Variable name

**variableValue:**

* Name: variableValue
* Description: Variable value

### Options:

**shopId:**

* Name: `--shopId`
* Is value required: no
* Description: oxbaseshop
* Default: `'oxbaseshop'`

config:set
----------

* Description: Sets a config value
* Usage:

  * `config:set [--variableType VARIABLETYPE] [--shopId [SHOPID]] [--moduleId [MODULEID]] [--] <variableName> <variableValue>`

Sets a config value

### Arguments:

**variableName:**

* Name: variableName
* Description: Variable name

**variableValue:**

* Name: variableValue
* Description: Variable value

### Options:

**variableType:**

* Name: `--variableType`
* Is value required: yes
* Description: Variable type

**shopId:**

* Name: `--shopId`
* Is value required: no
* Description: <none>

**moduleId:**

* Name: `--moduleId`
* Is value required: no
* Description: <none>

config:import
-------------

* Description: Import shop config
* Usage:

  * `config:import [--no-debug] [--env [ENV]] [--force-cleanup [FORCE-CLEANUP]]`

Info:
Imports all config values from yaml files, interacts with the
[Modules Config](https://github.com/OXIDprojects/oxid_modules_config/) module,
[__which currently isn't fully ported to OXID 6 yet!__](https://github.com/OXIDprojects/oxid_modules_config/tree/dev-6.0-wip)
Currently using experimental, [bleeding edge branch](https://github.com/OXIDprojects/oxid_modules_config/tree/PSGEN-282-Config_export_import_module_full_port_to_v6) :)
To try it, use this in your composer.json: 
"oxid-community/oxid_modules_config": "dev-PSGEN-282-Config_export_import_module_full_port_to_v6",

### Options:

**no-debug:**

* Name: `--no-debug`
* Accept value: no
* Is value required: no
* Description: No debug ouput
* Default: `false`

**env:**

* Name: `--env`
* Is value required: no
* Description: set specific environment, corresponds to a specific folder for the yaml files

**force-cleanup:**

* Name: `--force-cleanup`
* Is value required: no
* Description: Force cleanup on error

config:multiset
---------------

* Description: Sets multiple config values from yaml file
* Usage:

  * `config:multiset <configfile>`

YAML example:
```yaml
config:
  1:
    blReverseProxyActive: 
      variableType: bool
      variableValue: false
    # simple string type
    sMallShopURL: http://myshop.dev.local
    sMallSSLShopURL: http://myshop.dev.local
    myMultiVal:
      variableType: aarr
      variableValue:
        - /foo/bar/
        - /bar/foo/
      # optional module id
      moduleId: my_module
  2:
    blReverseProxyActive: 
...
```

If you want, you can also specify __a YAML string on the command line instead of a file__, e.g.:

```bash
../vendor/bin/oxrun module:multiset $'config:
  1:
    foobar: barfoo
' --shopId=1
```    

### Arguments:

**configfile:**

* Name: configfile
* Description: The file containing the config values, see malls.yml.dist. The file path is relative to the shop root. You can also pass a YAML string on the command line.

### Options:

config:shop:get
---------------

* Description: Sets a shop config value
* Usage:

  * `config:shop:get [--shopId [SHOPID]] [--] <variableName>`

Sets a shop config value

### Arguments:

**variableName:**

* Name: variableName
* Description: Variable name

### Options:

**shopId:**

* Name: `--shopId`
* Is value required: no
* Description: oxbaseshop
* Default: `'oxbaseshop'`

module:generate
---------------

* Description: Generates a module skeleton __[NOT IMPLEMENTED YET]__
* Usage:

  * `module:generate [--shopId [SHOPID]] [--] <module>`

Generates a module skeleton __[NOT IMPLEMENTED YET]__

### Arguments:

**module:**

* Name: module
* Description: Module name

### Options:

**shopId:**

* Name: `--shopId`
* Is value required: no
* Description: <none>

module:multiactivate
--------------------

* Description: Activates multiple modules, based on a YAML file
* Usage:

  * `module:multiactivate [--shopId SHOPID] [-s|--skipDeactivation] [-c|--skipClear] [--] <module>`

usage:
oxrun module:multiactivate configs/modules.yml
- to activate all modules defined in the file "configs/modules.yml" based
on a white- or blacklist

Example:

```yaml
whitelist:
1:
    - ocb_cleartmp
    - moduleinternals
    #- ddoevisualcms
    #- ddoewysiwyg
2:
    - ocb_cleartmp
```

Supports either a __"whitelist"__ or a __"blacklist"__ entry with multiple shop ids and the desired module ids to activate (whitelist) or to exclude from activation (blacklist).

If you want, you can also specify __a YAML string on the command line instead of a file__, e.g.:

```bash
../vendor/bin/oxrun module:multiactivate $'whitelist:
  1:
    - oepaypal
' --shopId=1
```

### Arguments:

**module:**

* Name: module
* Description: YAML module list filename or YAML string

### Options:

**shopId:**

* Name: `--shopId`
* Is value required: yes
* Description: The shop id.

**skipDeactivation:**

* Name: `--skipDeactivation`
* Shortcut: `-s`
* Accept value: no
* Is value required: no
* Description: Skip deactivation of modules, only activate.
* Default: `false`

**skipClear:**

* Name: `--skipClear`
* Shortcut: `-c`
* Accept value: no
* Is value required: no
* Description: Skip cache clearing.
* Default: `false`

module:list
-----------

* Description: Lists all modules
* Usage:

  * `module:list [--shopId [SHOPID]]`

Lists all modules

### Options:

**shopId:**

* Name: `--shopId`
* Is value required: no
* Description: <none>

module:activate
---------------

* Description: Activates a module
* Usage:

  * `module:activate [--shopId [SHOPID]] [--] <module>`

Activates a module

### Arguments:

**module:**

* Name: module
* Description: Module name

### Options:

**shopId:**

* Name: `--shopId`
* Is value required: no
* Description: <none>

module:deactivate
-----------------

* Description: Deactivates a module
* Usage:

  * `module:deactivate [--shopId [SHOPID]] [--] <module>`

Deactivates a module

### Arguments:

**module:**

* Name: module
* Description: Module name

### Options:

**shopId:**

* Name: `--shopId`
* Is value required: no
* Description: <none>

module:reload
-------------

* Description: Deactivate and activate a module
* Usage:

  * `module:reload [--shopId [SHOPID]] [--] <module>`

Deactivate and activate a module

### Arguments:

**module:**

* Name: module
* Description: Module name

### Options:

**shopId:**

* Name: `--shopId`
* Is value required: no
* Description: <none>

module:fix
----------

* Description: Fixes a module
* Usage:

  * `module:fix [--shopId [SHOPID]] [-b|--base-shop] [-x|--no-debug] [-r|--reset] [-a|--all] [--] [<module>]`

Usage: module:fix [options] <module_id> [<other_module_id>...]
This command fixes information stored in database of modules
Available options:
  -a, --all         Passes all modules
  -b, --base-shop   Fix only on base shop
  -r, --reset   Reset module, remove entries from config arrays
  --shopId=<shop_id>  Specifies in which shop to fix states
  -x, --no-debug    No debug output

### Arguments:

**module:**

* Name: module
* Description: Module name

### Options:

**shopId:**

* Name: `--shopId`
* Is value required: no
* Description: <none>

**base-shop:**

* Name: `--base-shop`
* Shortcut: `-b`
* Accept value: no
* Is value required: no
* Description: <none>
* Default: `false`

**no-debug:**

* Name: `--no-debug`
* Shortcut: `-x`
* Accept value: no
* Is value required: no
* Description: <none>
* Default: `false`

**reset:**

* Name: `--reset`
* Shortcut: `-r`
* Accept value: no
* Is value required: no
* Description: <none>
* Default: `false`

**all:**

* Name: `--all`
* Shortcut: `-a`
* Accept value: no
* Is value required: no
* Description: <none>
* Default: `false`

user:password
-------------

* Description: Sets a new password
* Usage:

  * `user:password <username> <password>`

Sets a new password

### Arguments:

**username:**

* Name: username
* Description: Username

**password:**

* Name: password
* Description: New password

### Options:

cms:update
----------

* Description: Updates a cms page
* Usage:

  * `cms:update [--title [TITLE]] [--content [CONTENT]] [--language LANGUAGE] [--active ACTIVE] [--] <ident>`

Updates a cms page

### Arguments:

**ident:**

* Name: ident
* Description: Content ident

### Options:

**title:**

* Name: `--title`
* Is value required: no
* Description: Content title

**content:**

* Name: `--content`
* Is value required: no
* Description: Content body

**language:**

* Name: `--language`
* Is value required: yes
* Description: Content language

**active:**

* Name: `--active`
* Is value required: yes
* Description: Content active

log:exceptionlog
----------------

* Description: Read EXCEPTION_LOG.txt and display entries.
* Usage:

  * `log:exceptionlog [-l|--lines [LINES]] [-f|--filter [FILTER]] [-r|--raw] [-t|--tail]`

Read EXCEPTION_LOG.txt and display entries.

### Options:

**lines:**

* Name: `--lines`
* Shortcut: `-l`
* Is value required: no
* Description: Number of lines to show

**filter:**

* Name: `--filter`
* Shortcut: `-f`
* Is value required: no
* Description: Filter string to search for

**raw:**

* Name: `--raw`
* Shortcut: `-r`
* Accept value: no
* Is value required: no
* Description: Show raw text, no table
* Default: `false`

**tail:**

* Name: `--tail`
* Shortcut: `-t`
* Accept value: no
* Is value required: no
* Description: Show last lines first
* Default: `false`

views:update
------------

* Description: Updates the views
* Usage:

  * `views:update`

Updates the views

### Options:


# Run the unit tests

The unit tests require a configured shop and a database. To start the tests, run the following command __in the "source" folder of your OXID 6 installation__ and set the correct path to the "oxrun" vendor directory, e.g.:

```bash
../vendor/bin/phpunit /var/www/html/oxid6/vendor/smxsm/oxrun/
```

# Update command documentation in this README file

You can generate the documentation for all available commands with

```bash
../vendor/bin/oxrun misc:generate:documentation > commands.txt
```

and then just copy/paste the contents of "commands.txt" into the README :)

# Build the oxrun.phar file

If you want to build the phar file, you can run

```bash
php build
```

in the base directory. Make sure you set

```bash
phar.readonly = Off
```

in your php.ini file, otherwise PHP will refuse to create the file!