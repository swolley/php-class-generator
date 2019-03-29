<?php
namespace Swolley\ClassGenerator\Components;

abstract class AbstractList
{
	/**
	 * @var	array	$_elements	list elements
	 */
	protected $_elements = [];

	/**
	 * @return	int	number of elements in list
	 */
	public function count(): int
	{
		return count($this->_elements);
	}

	/**
	 * forces to custom implements toString
	 */
	abstract public function __toString();

	/**
	 * @param	mixed	item to add
	 */
	abstract public function add($item);
}