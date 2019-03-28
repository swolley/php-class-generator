<?php
namespace ClassGenerator\Components;

class LMethods extends AbstractList
{
	public function add($item)
	{
		if(!($item instanceof CMethod)) {
			throw new \UnexpectedValueException('Invalid method type');
		}

		foreach($this->_elements as $method){
			if($method->getName() === $item->getName()) {
				throw new \UnexpectedValueException('Another method with same name already exists');
			}
		}

		if($item instanceof CConstructor) {
			array_unshift($this->_elements, $item);
		} else {
			$this->_elements[] = $item;
		}
	}

	public function __toString()
	{
		if($this->count() === 0) {
			return '';
		}

		return implode(PHP_EOL, $this->_elements) . PHP_EOL;
	}
}