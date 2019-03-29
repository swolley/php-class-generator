<?php
namespace Swolley\ClassGenerator\Components;

class LTraits extends AbstractList
{
	/**
	 * adds a new trait to elements list
	 * @param	string	$item	new trait name
	 * @throws	\UnexpectedValueException	invalid name is passed
	 
	 */
	public function add($item)
	{
		if(!is_string($item)) {
			throw new \UnexpectedValueException('Invalid trait type');
		}

		foreach($this->_elements as $trait) {
			if($trait->getName() === $item) {
				return;
			}
		}

		$this->_elements[] = new CTrait($item);
	}

	/**
	 * custom toString	used for code export
	 */
	public function __toString()
	{
		if($this->count() === 0) {
			return '';
		}

		return 'use ' . implode(', ', $this->_elements) . ';';
	}
}