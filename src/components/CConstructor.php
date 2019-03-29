<?php
namespace Swolley\ClassGenerator\Components;

class CConstructor extends CMethod
{
	/**
	 * @param	\ReflectionMethod	$method			constructor to set
	 * @param	CClass				$definingClass	defining new class attributes
	 */
	public function __construct(\ReflectionMethod &$method, CClass &$definingClass)
	{
		parent::__construct($method, $definingClass);
	}

	/**
	 * custom toString	used for code export
	 */
	public function __toString()
	{
		$current_contructor = '';
		if (!$this->_rMethod->getDeclaringClass()->isInterface()) {
			//removes abstract from modifier because implemented if not creating another abstract class
			$modifiers = implode(' ', \Reflection::getModifierNames($this->_rMethod->getModifiers()));
			//define parent signature
			$parent_constructor ='parent::__construct(' . $this->defineParams(false, false) . ');';
			//defines method signature
			$current_contructor = $modifiers . ' function __construct(' . $this->defineParams() . '){' . $parent_constructor . '}';
		} else {
			//empty constructor
			$current_contructor = 'public function __construct(){}';
		}

		return $current_contructor;
	}
}