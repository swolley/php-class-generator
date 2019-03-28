<?php
namespace ClassGenerator\Components;

abstract class AbstractList
{
	protected $_elements = [];

	public function count(): int
	{
		return count($this->_elements);
	}

	abstract public function __toString();
	abstract public function add($item);
}