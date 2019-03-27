<?php
namespace Swolley\ClassGenerator\Components;

abstract class AbstractComponent
{
	protected $_name;

	public function __construct(string $name)
	{
		$this->_name = $name;
	}

	public function getName()
	{
		return $this->_name;
	}

	abstract public function __toString();
}