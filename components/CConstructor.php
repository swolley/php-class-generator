<?php
namespace Swolley\ClassGenerator\Controller;

class CConstructor extends CMethod
{
	public function __construct(\ReflectionClass &$class)
	{
		parent::__construct($class, $class->getConstructor());
	}

	public function __toString()
	{
		$current_contructor = '';
		if (!$this->_rClass->isInterface()) {
			//removes abstract from modifier because implemented if not creating another abstract class
			$modifiers = implode(' ', \Reflection::getModifierNames($this->_rMethod->getModifiers()));
			//define parent signature
			$parent_constructor ='parent::__construct(' . static::defineParams(false, false) . ');';
			//defines method signature
			$current_contructor = $modifiers . ' function __construct(' . $this->defineParams() . '{' . $parent_constructor . '}';
		} else {
			$current_contructor = 'public function __construct(){}';
		}

		return $current_contructor;
	}
}