<?php
namespace ClassGenerator\Components;

class CNamespace extends AbstractComponent
{
	public function __construct(string $name)
	{
		parent::__construct($name);
	}

	public function __toString()
	{
		return "namespace {$this->_name};" . PHP_EOL;
	}
}