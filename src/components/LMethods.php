<?php
namespace Swolley\ClassGenerator\Components;

class LMethods extends AbstractList
{
	/**
	 * @param	mixed	$item	new method to add in list
	 */
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

	public function getMethod(string $methodName): \ReflectionMethod
	{
		foreach ($this->_elements as $method) {
			if($method->getName === $methodName) {
				return $method;
			}
		}

		return null;
	}

	/**
	 * custom toString	used for code export
	 */
	public function __toString()
	{
		if($this->count() === 0) {
			return '';
		}

		return implode(PHP_EOL, $this->_elements);
	}
}