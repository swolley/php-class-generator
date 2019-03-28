<?php
namespace ClassGenerator\Components;

class LParents extends AbstractList
{
	/**
	 * @var	bool	$_extends	show if defining class is extending a class in list
	 */
	private $_extends = false;

	/**
	 * @param	$item	element to add in list
	 * @return			data to be used by external methods (use and methods)
	 */
	public function add($item)
	{
		if(!is_string($item)) {
			throw new \UnexpectedValueException('Invalid parent type');
		}

		foreach($this->_elements as $parent) {
			if($parent->getName() === $item) {
				return false;
			}
		}

		$class = $this->setParentClass($item);
		$methods = $this->getInheritableMethods($class);
		
		//adds namespace in use list if not already in
		$class_namespace = $class->getNamespaceName();
		$class_namespace = empty($class_namespace) ? '\\' . $class->name : $class_namespace;

		return [
			'use' => $class_namespace,
			'methods' => $methods
		];
	}
	
	private function setParentClass(string $name)
	{
		$class = new \ReflectionClass($name);
		//throws if parent class is final because cannot be inherited
		if ($class->isFinal() || $class->isTrait()) {
			throw new \UnexpectedValueException('Final classes and traits cannot be inherited');
		}
		
		//adds class in parent's list
		if(!$class->isInterface() && $this->_extends) {
			throw new \UnexpectedValueException('Cannot extend more than one class');
		} elseif(!$class->isInterface()) {
			$this->_extends = true;
		}
		
		$this->_elements[] = $class;
		return $class;
	}

	private function getInheritableMethods(\ReflectionClass &$class): array
	{
		$methods_list = [];
		//define methods only if parent class is abstract or interface
		if (!$class->isInterface()) {
			$methods_list[] = $class->getConstructor();
			if ($class->isAbstract()){
				$methods_list = array_merge($methods_list, $class->getMethods(\ReflectionMethod::IS_ABSTRACT));
			}
		} else {
			$methods_list = $class->getMethods();
		}

		return $methods_list;
	}

	/**
	 * custom toString	used for code export
	 */
	public function __toString()
	{
		if($this->count() === 0) {
			return '';
		}

		$extends = '';
		$implements = [];
		foreach($this->_elements as $class) {
			if($class->isInterface()) {
				$implements[] = $class->getName();
			} else {
				$extends = ' extends ' . $class->getName();
			}
		}
		return $extends . (count($implements) > 0 ? ' implements ' . implode(', ', $implements) : '');
	}
}