<?php
namespace ClassGenerator\Components;

class CMethod extends AbstractComponent
{
	protected $_dClass;
	protected $_rMethod;

	public function __construct(\ReflectionMethod &$method, CClass &$definingClass)
	{
		parent::__construct($method->name);
		$this->_dClass = $definingClass;
		$this->_rMethod = $method;
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
					//FIXME e questo??
					$this->_pUses[] = $type->__toString();
				}
			}

			$is_class_internal = $this->_rMethod->getDeclaringClass()->isInternal();
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

	public function __toString()
	{
		//removes abstract from modifier because implemented if not creating another abstract class
		$modifiers = implode(' ', \Reflection::getModifierNames($this->_rMethod->getModifiers()));
		$modifiers =  $this->_dClass->isAbstract() ? $modifiers : str_replace('abstract ', '', $modifiers);
		//defines method signature
		$method_definition = $modifiers . ' function ' . $this->_rMethod->name . '(' . $this->defineParams() . ')';
		//appends return type if exists
		if($this->_rMethod->hasReturnType()) {
			$method_definition .= ': ' . $this->_rMethod->getReturnType();
		}
		
		return $method_definition .= $this->_dClass->isAbstract() ? ';' : '{}';
	}
}