<?php
namespace Swolley\ClassGenerator;

require_once('./vendor/autoload.php');
//require_once('./ClassFactory.php');
//require_once('./Formatter.php');

$handle = fopen ('php://stdin','r');

$new_class_name = "";
$parent = '';
$full_parent_name = '';
$uses = [];
/////////////////////////////////// class name ////////////////////////////////////////

do {
	echo 'Enter route name: ';
	$new_class_name = fgets($handle);
	$new_class_name = preg_replace('/\s+/', '', $new_class_name);
} while(empty($new_class_name));

/////////////////////////////////// namespace ////////////////////////////////////////
echo 'Enter namespace (optional): ';
$namespace = fgets($handle);
$namespace = preg_replace('/\s+/', '', $namespace);

/////////////////////////////////// inherits ////////////////////////////////////////
echo 'Enter parent full name (optional): ';
$full_parent_name = fgets($handle);
$full_parent_name = preg_replace('/\s+/', '', $full_parent_name);

fclose($handle);
unset($handle);

echo PHP_EOL;

try{
	$route_factory = (new ClassFactory)
		->setName($new_class_name)
		->setNamespace($namespace)
		//->setUses($uses)
		->setInherits($full_parent_name);

	$route_factory->defineClass();
	
	unset($parent);
	unset($uses);
	unset($full_parent_name);

	//$to_string = $route_factory->__toString();
	$definition = $route_factory->getDefinition();
	//$instance = $route_factory->getInstanceWhitoutConstructor();
	//$file = $route_factory->writeOnFile('./');
	
	exit("SUCCESS: Class {$new_class_name} created" . PHP_EOL);
} catch(\Exception $ex) {
	exit("ERROR: " . $ex->getMessage() . PHP_EOL);
}