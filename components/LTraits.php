<?php
namespace ClassGenerator\Components;

class LTraits extends AbstractList
{
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

	public function __toString()
	{
		if($this->count() === 0) {
			return '';
		}

		return 'use ' . implode(', ', $this->_elements) . ';' . PHP_EOL;
	}
}