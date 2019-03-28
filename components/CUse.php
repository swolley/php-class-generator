<?php
namespace ClassGenerator\Components;

class CUse extends AbstractComponent
{
	public function __construct(string $name)
	{
		parent::__construct($name, true);
	}

	public function split()
	{
		return explode('\\',$this->_name);
	}

	public function __toString()
	{
		return $this->_name;
	}
}