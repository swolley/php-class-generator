<?php
namespace ClassGenerator\Components;

class ParentList
{
	private $_classes = [];
	private $_extends = false;

	public function count(): int
	{
		return count($this->_classes);
	}

	public function __toString()
	{
		$extends = '';
		$implements = [];

		foreach($this->_classes as $class) {
			if($class->isInterface()) {
				$implements[] = $class->getName();
			} else {
				$extends = 'extends' . $class->getName() . ' ';
			}
		}

		return $extends . (count($implements) > 0 ? 'implements ' . implode(', ', $implements) : '');
	}

	public function add(string $name)
	{
		foreach($this->_classes as $parent) {
			if($parent->getName() === $name) {
				return false;
			}
		}

		$class = $this->setParentClass($name);
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
		
		$this->_classes[] = $class;
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

		return array_map(function($method) use($class) {
			return $method->isConstructor() ? new CConstructor($class, $method) : new CMethod($class, $method);
		}, $methods_list);
	}
}