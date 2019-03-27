<?php
namespace ClassGenerator\Components;

class CUse extends AbstractComponent
{
	public function __construct(string $name)
	{
		parent::__construct($name);
	}

	public function __toString()
	{
		return 'use ' . $this->_name . ';';
	}
}