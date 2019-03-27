<?php
namespace Swolley\ClassGenerator\Components;

class CMethod extends AbstractComponent
{
	protected $_reflection;

	public function __construct(\ReflectionClass &$class, \ReflectionMethod &$method)
	{
		parent::__construct($method->name);
		$this->_rClass = $class;
		$this->_rMethod = $method;
	}

	public function __toString()
	{
		//removes abstract from modifier because implemented if not creating another abstract class
		$modifiers = implode(' ', \Reflection::getModifierNames($this->_rMethod->getModifiers()));
		$modifiers =  $this->_pIsAbstract ? $modifiers : str_replace('abstract ', '', $modifiers);
		//defines method signature
		$method_definition = $modifiers . ' function ' . $this->_rMethod->name . '(' . $this->defineParams() . ')';
		//appends return type if exists
		if($this->_reflection->hasReturnType()) {
			$method_definition .= ': ' . $this->_rMethod->getReturnType();
		}
		
		return $method_definition .= $this->_pIsAbstract ? ';' : '{}';
	}

	/**
     * defines specified method's parameters list with default values if found
     * @param   bool              	$defaultValues	optionally add default values to params if found
     * @param   bool              	$declaration	optionally add type and reference symbol to parameters (yes if declaration, no if call)
     * @return  string                              stringified php code of parameters
     **/
	protected function defineParams(bool $defaultValues = true, bool $declaration = true): string
	{
		return trim(implode(', ', array_map(function ($param) use ($defaultValues, $declaration) {
			if ($param->hasType()) {
				$type = $param->getType();
				if (!$type->isBuiltin()) {
					$this->_pUses[] = $type->__toString();
				}
			}

			$is_class_internal = $this->_rClass->isInternal();
			return implode('', [
				'paramType' => $declaration && $param->hasType() ? $param->getType() . ' ' : '',
				'isPassedByReference' => $declaration && $param->isPassedByReference() ? '&' : '',
				'paramName' => '$' . $param->getName(),
				'paramIsOptional' => $declaration && !$is_class_internal && $param->isOptional() ? '=' : '',
				'paramDefaultValue' => $declaration && $defaultValues && !$is_class_internal && $param->isDefaultValueAvailable() ? (self::formatParamDefaultValue($param->getDefaultValue())) : ''
			]);
		}, $this->_rMethod->getParameters())));
	}

	/**
     * parse default value of passed parameter
     * @param   mixed   $param	param default value
     * @return  string          stringified php code of default param's value
     **/
	protected static function formatParamDefaultValue($param): string
	{
		return str_replace(PHP_EOL, '', var_export($param, true));
	}
}