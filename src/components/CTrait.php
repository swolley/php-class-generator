<?php
namespace ClassGenerator\Components;

class CTrait extends AbstractComponent
{
	/**
	 * @param	string	$name	trait name
	 */
	public function __construct(string $name)
	{
		parent::__construct($name);
	}

	/**
	 * custom toString	used for code export
	 */
	public function __toString()
	{
		return $this->_name;
	}
}