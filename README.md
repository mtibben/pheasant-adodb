Pheasant-adodb
==============
Implements a subset of the ADOdb API using Pheasant


Example Usage
----------------------------
Set up your pheasant connection, then use the ADOdb API.
```php
$pheasant = \Pheasant::setup('mysql://root@localhost/pheasanttest?charset=utf8&strict=true');
$adodbConnection = new \PheasantAdodb\Connection($pheasant->connection());

$adodbConnection->getAll("SELECT 1,2,3");
```

Or as a drop in replacement for adodb, you can use the compatability include files
```php
require_once('adodb.inc.php');
require_once('adodb-exceptions.inc.php');

$adodbConnection = ADONewConnection('mysql://root@localhost/pheasanttest?charset=utf8&strict=true');
$adodbConnection->setFetchMode(ADODB_FETCH_ASSOC);
$adodbConnection->getAll("SELECT 1,2,3");
```


Limitations and assumptions
----------------------------
 * Tries to match the behavour of ADOdb v4.81 with the adodb-mysqlt driver only
 * Assumes ADODB_FETCH_ASSOC as the only fetchmode
 * Assumes adodb-exceptions.inc.php is used
 * `->Replace(` does not support disabling auto quoting
 * Every field is escaped as if it were a string
 * Magic quotes not supported


Testing
----------------------------
The testsuite uses the real adodb (with mysqlt driver) to compare the results from API calls.

```bash
composer install --dev
mysql -e 'create database pheasantadodb_test1;'
mysql -e 'create database pheasantadodb_test2;'
phpunit
```
