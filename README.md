# php-class-generator
Generates class definition code from specified attributes like namespace, parent classes to inherits and modifiers.
Resulting code can be written on file o instantiated (still workin on).
It's defined to be a composer module.

## usage
```php
$new_class_code = new ClassFactory('NewClassName');
$new_class_code
	->namespace('Namespace\To\Assign')
	->use('Another\Class\Or\Trait')
	->inherit('Parent\Class\To\Extends')	
	->inherit('Parent\Class\To\Implements');

//writes to specified folder
$new_class_code->toFile('/optional/path');
//parse folder from namespace string
$new_class_code->toFile();
```

## structure
ClassFactory uses custom component's classes with specified toString methods that return each portion of php code.
Generated code is automatically beautified by a builtin class.

## todos
php-class-generator is still a work in progress
