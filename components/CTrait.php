<?php
namespace ClassGenerator\Components;

class CTrait extends AbstractComponent
{
	public function __construct(string $name)
	{
		parent::__construct($name);
	}

	public function __toString()
	{
		return $this->_name;
	}
}