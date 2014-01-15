Before run tests you have to:

 1. Make directory `./tmp` writable
 2. Run `composer install`
 3. Edit `./config.php`
 4. Run PHP with `-d auto_prepend_file=./vendor/autoload.php`
 5. Use `./phpunit.xml` as test configuration file
