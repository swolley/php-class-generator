<?php
namespace Swolley\ClassGenerator;

class ClassFactory
{
	const PLACEHOLDER = '###placeholder###';
	/////////////////////////////////////////////////// init params ////////////////////////////////////////////////////
	private $_pNamespace = null;
	private $_pUses = [];
	private $_pName = null;
	private $_pInherits = null;

	////////////////////////////////////////////////// final fields ////////////////////////////////////////////////////
	private $_fClass;
	private $_fDefinition = [];

	////////////////////////////////////////////////// constructor ////////////////////////////////////////////////////
	public function __construct(string $name)
	{
		if (empty($name)) {
			throw new \Exception('cannot create class without a name');
		}

		$this->_pName = ucfirst($name);
	}
	//////////////////////////////////////////////////// getter/setter /////////////////////////////////////////////////
	public function setNamespace(string $name)
	{
		if (!empty($name)) {
			$this->_pNamespace = ucwords($name, '\\');
		}
	}

	public function setUses(array $usesList)
	{
		if (!empty($usesList)) {
			$this->_pUses = $usesList;
		}
	}

	public function setInherits(string $name)
	{
		if (!empty($name)) {
			$this->_pInherits = $name;
		}
	}

	public function getDefinition(bool $addHeader = true): string
	{
		$complete_string = ($addHeader ? $this->_fDefinition['header'] : '') . str_replace(self::PLACEHOLDER, implode($this->_fDefinition['methods']), $this->_fDefinition['class']);
		$parsed_string = preg_replace('/(\s*\{\s*(?!\n\r?))(\s*\}\s*(?!\n\r?))/m', PHP_EOL.'$1'.PHP_EOL . PHP_EOL.'$2'.PHP_EOL.PHP_EOL, $complete_string);
		//$complete_string = preg_replace('/([;])(?![\n|\r|\n\r])/m', PHP_EOL.'}'.PHP_EOL.PHP_EOL, $complete_string);
		$formatted = (new Formatter)($complete_string);
		return $parsed_string;
	}

	/////////////////////////////////////////////// output ////////////////////////////////////////////////////
	public function __toString()
	{
		return ($this->evalDefinition())->__toString();
	}

	public function writeOnFile($path = null): bool
	{
		$separator = addcslashes(DIRECTORY_SEPARATOR, '\/\\');
		//parse directory separators, does lowercase file path and set final slash if not exists
		$file_path = preg_replace('/.*(?<!' . $separator . ')$/', $separator, $path ?: str_replace('\\', $separator, strtolower($this->_pNamespace))) . $this->_pName . '.php';
		$new_file = fopen($file_path, 'w');
		$written_bytes = fwrite($new_file, '<?php' . PHP_EOL . $this->getDefinition());
		fclose($new_file);

		return is_int($written_bytes);
	}

	public function getInstance(...$constructorParams)
	{
		return ($this->evalDefinition())->newInstance(...$constructorParams);
	}

	public function getInstanceWhitoutConstructor()
	{
		return ($this->evalDefinition())->newInstanceWithoutConstructor();
	}

	///////////////////////////////////////////////// main method //////////////////////////////////////////////////////////////
	/**
     * main creation class
     **/
	public function defineClass()
	{
		$headerString = '';
		$classString = '';
		$constructorString = '';
		$methods_list = [];

		//CLASS SIGNATURE
		$classString = 'final class ' . $this->_pName;

		if ($this->_pInherits) {
			if (!class_exists($this->_pInherits) && !interface_exists($this->_pInherits)) {
				throw new \Exception("{$this->_pInherits} not exists.");
			}

			$extends = new \ReflectionClass($this->_pInherits);
			//throws if parent class is final because cannot be inherited
			if ($extends->isFinal() || $extends->isTrait()) {
				throw new \Exception('Final classes and traits cannot be inherited.');
			}

			if (!$extends->isInterface()) {
				$classString .= " extends ";
				//CONSTRUCTOR
				$constructorString = static::defineConstructor($extends);
			} else {
				$classString .= " implements ";
			}
			$classString .= $extends->name;

			//METHODS
			$methods_list = static::defineDependentMethods($extends);
		}

		if (empty($constructorString)) {
			$constructorString = 'public function __construct(){}';
		}

		array_unshift($methods_list, $constructorString);

		//HEADER
		$headerString = self::defineHeader();

		$this->_fDefinition = [
			'header' => $headerString,
			'class' => $classString . '{' . self::PLACEHOLDER . '}',
			'methods' => $methods_list
		];
	}

	///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * compose namespace and uses list
	 */
	private function defineHeader(): string
	{
		$header = '';
		if ($this->_pNamespace) {
			$header .= 'namespace ' . $this->_pNamespace . ';';
		}

		if ($this->_pUses) {
			$header .= array_reduce($this->_pUses ? $this->_pUses : [], function ($total, $use) {
				return $total += 'use ' . $use . ';';
			}, '');
		}

		return $header;
	}

	/**
     * always calls parent constructor from inherited classes
	 * @param	\ReflectionClass    $inheritedClass		parent class
     * @return  string  			$definition 		stringified php code of constructor
     **/
	private function defineConstructor(\ReflectionClass &$inheritedClass): string
	{
		$inherited_constructor = $inheritedClass->getConstructor();
		$constructor_fDefinition = "\tpublic function __construct(" . static::defineParams($inherited_constructor) . '){';
		$constructor_fDefinition .= "\t\tparent::__construct(" . static::defineParams($inherited_constructor, false, false) . ');';
		$constructor_fDefinition .= "\t}";

		return $constructor_fDefinition;
	}

	/**
     * defines all methods contained in the parent class
     * @param   \ReflectionClass     $inheritedClass     parent class name
     * @return  array                                  stringified php code of parent methods
     **/
	private function defineDependentMethods(\ReflectionClass &$inheritedClass): array
	{
		return array_map(function ($method) {
				//defines single method
				return static::defineMethod($method);
			}, 
			$this->getParentMethods($inheritedClass)
		);
	}

	/**
	 * @param   \ReflectionClass     $inheritedClass     parent class name
	 */
	private function getParentMethods(\ReflectionClass &$inheritedClass): array
	{
		$method_list = [];
		//define methods only if parent class is abstract or interface
		if ($inheritedClass->isInterface()) {
			$method_list = $inheritedClass->getMethods();
		} elseif ($inheritedClass->isAbstract()) {
			$method_list = $inheritedClass->getMethods(\ReflectionMethod::IS_ABSTRACT);
		}

		return $method_list;
	}

	/**
     * defines specified method of the parent class with modifiers and return type if found
     * @param   \ReflectionMethod   $method     parent method
     * @return  string              $definition stringified php code of passed method
     **/
	private function defineMethod(\ReflectionMethod &$method): string
	{
		$reflection_method = (new \ReflectionMethod($method->class, $method->name));

		//removes abstract from modifier because implemented
		$modifiers =  str_replace('abstract ', '', implode(' ', Reflection::getModifierNames($reflection_method->getModifiers())));
		//defines method signature
		$method_definition = "\t" . $modifiers . " function {$method->name}(" . static::defineParams($method) . ')';
		//appends return type if exists
		$method_definition .= $reflection_method->hasReturnType() ? " : {$reflection_method->getReturnType()} " : " \t{\t}";

		return $method_definition;
	}

	/**
     * defines specified method's parameters list with default values if found
     * @param   \ReflectionMethod    $method         parent method
     * @param   bool              	$defaultValues	optionally add default values to params if found
     * @param   bool              	$declaration	optionally add type and reference symbol to parameters (yes if declaration, no if call)
     * @return  string                              stringified php code of parameters
     **/
	private function defineParams(\ReflectionMethod &$method, bool $defaultValues = true, bool $declaration = true): string
	{
		return trim(implode(', ', array_map(function ($param) use ($defaultValues, $declaration) {
			if ($param->hasType()) {
				$type = $param->getType();
				if (!$type->isBuiltin()) {
					$this->_pUses[] = $type->__toString();
				}
			}

			return implode('', [
				'paramType' => $declaration && $param->hasType() ? $param->getType() . ' ' : '',
				'isPassedByReference' => $declaration && $param->isPassedByReference() ? '&' : '',
				'paramName' => '$' . $param->getName(),
				'paramIsOptional' => $declaration && $param->isOptional() ? ' = ' : '',
				'paramDefaultValue' => $declaration && $defaultValues && $param->isDefaultValueAvailable() ? (static::formatParamDefaultValue($param->getDefaultValue())) : ''
			]);
		}, (new \ReflectionMethod($method->class, $method->name))->getParameters())));
	}

	/**
     * parse default value of passed parameter
     * @param   mixed   $params param default value
     * @return  string          stringified php code of default param's value
     **/
	private static function formatParamDefaultValue($param): string
	{
		return str_replace(PHP_EOL, "", var_export($param, true));
	}

	private function evalDefinition()
	{
		if (!class_exists($this->_pName)) {
			eval($this->getDefinition(false));
		}

		if (!$this->_fClass) {
			$this->_fClass = new \ReflectionClass($this->_pName);
		}

		return $this->_fClass;
	}
}
