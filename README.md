# php-class-generator
Generates class definition code from specified attributes like namespace, parent classes to inherits and modifiers.
Resulting code can be written on file o instantiated (still workin on).
It's defined to be a composer module.

##usage
(new ClassFactory('NewClassName'))
  ->namespace('Namespace\To\Assign')
  ->use('Another\Class\Or\Trait')
  ->inherit('Parent\Class\To\Extends')
  ->inherit('Parent\Class\To\Implements')
  ->toFile('/optional/path');
  
##structure
ClassFactory uses custom component's classes with specified toString methods that return each portion of php code.
Generated code is automatically beautified by a builtin class.

##todos
php-class-generator is still a work in progress
