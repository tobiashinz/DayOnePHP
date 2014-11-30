#DayOnePHP
PHP Framework to create DayOne Entry-Files

##Usage

```php
require_once(BASEPATH . 'DayOneEntry.class.php');

$entry = new DayOneEntry();
$entry->setEntryText('My first entry');
$entry->save();

```
