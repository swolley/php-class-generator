<?php
namespace Swolley\ClassGenerator\Components;

class CUse extends AbstractComponent
{
	/**
	 * @param	string	$name	use name
	 */
	public function __construct(string $name)
	{
		parent::__construct($name, true);
	}

	public function split()
	{
		return explode('\\',$this->_name);
	}

	/**
	 * custom toString	used for code export
	 */
	public function __toString()
	{
		return $this->_name;
	}
}