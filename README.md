Pheasant-adodb
==============
Implements a subset of the [ADOdb](http://phplens.com/adodb/) API using [Pheasant](http://getpheasant.com/)


Example Usage
----------------------------
```php
# set up Pheasant connection
$pheasant = \Pheasant::setup('mysql://user@localhost/mydb');
$adodbConnection = new \PheasantAdodb\Connection($pheasant->connection());

# start using ADOdb API
$adodbConnection->Execute("SELECT 1,2,3")->GetAll();
```

Or as a drop in replacement for adodb, you can use the compatability include files
```php
# include compatibility files
require_once('adodb/adodb.inc.php');
require_once('adodb/adodb-exceptions.inc.php');

# instantiate ADOdb, set fetch mode
$adodbConnection = ADONewConnection('mysql://user@localhost/mydb');
$adodbConnection->setFetchMode(ADODB_FETCH_ASSOC);

# start using ADOdb API
$adodbConnection->Execute("SELECT 1,2,3")->GetAll();
```


Limitations and assumptions
----------------------------
 * Tries to match the behavour of ADOdb v4.81 with the adodb-mysqlt driver only
 * `->Replace(` does not support disabling auto quoting
 * Every field is escaped as if it were a string
 * Magic quotes not supported
 * If instantiated directly
   * Assumes ADODB_FETCH_ASSOC as the only fetchmode
   * Throws exceptions instead of returning error codes
 * If instantiated via ADONewConnection
   * requires `->setFetchMode(ADODB_FETCH_ASSOC)` to be called
   * returns error codes unless `adodb-exceptions.inc.php` is included

Testing
----------------------------
The testsuite uses the real adodb (with mysqlt driver) to compare the results from API calls.

```bash
composer install --dev
mysql -e 'create database pheasantadodb_test1;'
mysql -e 'create database pheasantadodb_test2;'
phpunit
```


[![Build Status](https://api.travis-ci.org/mtibben/pheasant-adodb.png)](https://travis-ci.org/99designs/pheasant-adodb)
