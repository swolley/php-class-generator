<?php
namespace Swolley\ClassGenerator;

final class ClassFactory
{
	const PLACEHOLDER = '###placeholder###';
	/////////////////////////////////////////////////// init params ////////////////////////////////////////////////////
	private $_pNamespace = null;
	private $_pUses = [];
	private $_pName = null;
	private $_pInherits = null;
	private $_pIsFinal = false;
	private $_pIsAbstract = false;

	////////////////////////////////////////////////// final fields ////////////////////////////////////////////////////
	private $_fClass;
	private $_fDefinition = [];

	//////////////////////////////////////////////////// getter/setter /////////////////////////////////////////////////
	/**
	 * @param	string			$name	class name
	 * @return	ClassFactory	$this	self
	 * @throws	\InvalidArgumentException	if name empty or not valid
	 */
	public function setName(string $name)
	{
		$name = ucfirst(trim($name));
		if (empty($name) || !self::validateName($name)) {
			throw new \InvalidArgumentException("Invalid class $name");
		}

		$this->_pName = $name;
		return $this;
	}

	/**
	 * @param	string			$name	namespace name
	 * @return	ClassFactory	$this	self
	 * @throws	\InvalidArgumentException	if name empty or not valid
	 */
	public function setNamespace(string $name)
	{
		$name = ucwords(trim($name), '\\');
		if (empty($name) || !self::validateName($name)) {
			throw new \InvalidArgumentException("Invalid namespace $name");
		}
		
		$this->_pNamespace = $name;
		return $this;
	}

	/**
	 * @param	array			$usesList	uses list
	 * @return	ClassFactory	$this		self
	 * @throws	\InvalidArgumentException	if name empty or not valid
	 * @throws	\UnexpectedValueException	if class not exists
	 */
	public function setUses(array $usesList)
	{
		if (!empty($usesList)) {
			$mapped = [];
			foreach($usesList as $name) {
				$name = ucwords(trim($name), '\\');
				if (empty($name) || !self::validateName($name)) {
					throw new \InvalidArgumentException("Invalid name $name");
				}

				if(!class_exists($name)) {
					throw new \UnexpectedValueException("class $name not exists");
				}

				$mapped[] = $name;
			}

			$this->_pUses = $mapped;
		}

		return $this;
	}

	/**
	 * @param	string			$name		parent class name
	 * @return	ClassFactory	$this		self
	 * @throws	\InvalidArgumentException	if name empty or not valid
	 * @throws	\UnexpectedValueException	if class not exists
	 */
	public function setInherits(string $name)
	{
		$name = ucwords(trim($name), '\\');
		if (empty($name) || !self::validateName($name)) {
			throw new \InvalidArgumentException("Invalid name $name");
		}

		if (!class_exists($name)) {
			throw new \UnexpectedValueException("class $name not exists");
		}

		$this->_pInherits = $name;
		return $this;
	}

	/**
	 * @param	bool			$isFinal	add final attribute to class
	 * @return	ClassFactory	$this		self
	 * @throws	\UnexpectedValueException	if abstract property already set
	 */
	public function setFinal(bool $isFinal = false)
	{
		if($isFinal && $this->_pIsAbstract) {
			throw new \UnexpectedValueException('Abstract class cannot be final');
		}
		$this->_pIsFinal = $isFinal;
		return $this;
	}

	/**
	 * @param	bool			$isAbstract	add abstract attribute to class
	 * @return	ClassFactory	$this		self
	 * @throws	\UnexpectedValueException	if final property already set
	 */
	public function setAbstract(bool $isAbstract = false)
	{
		if($isAbstract && $this->_pIsFinal) {
			throw new \UnexpectedValueException('Final class cannot be abstract');
		}
		$this->_pIsAbstract = $isAbstract;
		return $this;
	}

	/**
	 * return class definition's code
	 * @param	bool 	$formatted	beautify code before return
	 * @return	string	stringified php code
	 */
	public function getDefinition(bool $formatted = true): string
	{
		$complete_string = $this->_fDefinition['header'] . str_replace(self::PLACEHOLDER, implode($this->_fDefinition['methods']), $this->_fDefinition['class']);
		return $formatted ? (new Formatter)($complete_string) : $complete_string;
	}

	/////////////////////////////////////////////// output ////////////////////////////////////////////////////
	public function __toString(): string
	{
		return ($this->evalDefinition())->__toString();
	}

	public function writeOnFile($path = null): bool
	{
		$separator = addcslashes(DIRECTORY_SEPARATOR, '\/\\');
		//parse directory separators, does lowercase file path and set final slash if not exists
		$file_path = preg_replace('/.*(?<!' . $separator . ')$/', $separator, $path ?: str_replace('\\', $separator, strtolower($this->_pNamespace))) . $this->_pName . '.php';
		$new_file = fopen($file_path, 'w');
		$written_bytes = fwrite($new_file, $this->getDefinition());
		fclose($new_file);

		return is_int($written_bytes);
	}

	/**
	 * @param	mixed	$constructorParams	variable numbers of parameters
	 * @return	object						instance of requested class
	 * @throws	\BadMethodCallException		abstract classes cannot be instantiated
	 */
	public function getInstance(...$constructorParams): object
	{
		if($this->_pIsAbstract) {
			throw new \BadMethodCallException('Cannot instantiate an object from an abstract class');
		}
		
		return ($this->evalDefinition())->newInstance(...$constructorParams);
	}

	/**
	 * @return	object						instance of requested class
	 * @throws	\BadMethodCallException		abstract classes cannot be instantiated
	 */
	public function getInstanceWhitoutConstructor(): object
	{
		if($this->_pIsAbstract) {
			throw new \BadMethodCallException('Cannot instantiate an object from an abstract class');
		}

		return ($this->evalDefinition())->newInstanceWithoutConstructor();
	}

	///////////////////////////////////////////////// main method //////////////////////////////////////////////////////////////
	/**
     * main creation class
	 * @throws	\UnexpectedValueException	if no class name found
	 * @throws	\UnexpectedValueException	if parent class not exists
	 * @throws	\UnexpectedValueException	if requested to inherit from a final class or a trait
     **/
	public function define()
	{
		if(!$this->_pName) {
			throw new \UnexpectedValueException("Can't create a class without a name");
		}

		$headerString = '';
		$classString = '';
		$constructorString = '';
		$methods_list = [];
		$extends = null;

		//CLASS SIGNATURE
		if($this->_pIsFinal) {
			$classString .= 'final ';
		} elseif($this->_pIsAbstract) {
			$classString .= 'abstract ';
		}

		$classString .= 'class ' . $this->_pName;

		if ($this->_pInherits) {
			if (!class_exists($this->_pInherits) && !interface_exists($this->_pInherits)) {
				throw new \UnexpectedValueException("{$this->_pInherits} not exists.");
			}

			$extends = new \ReflectionClass($this->_pInherits);
			$extends_namespace = $extends->getNamespaceName();
			$extends_namespace = empty($extends_namespace) ? '\\' . $extends->name : $extends_namespace;
			if($extends_namespace !== $this->_pNamespace && !in_array($extends_namespace, $this->_pUses)) {
				$this->_pUses[] = $extends_namespace;
			}

			//throws if parent class is final because cannot be inherited
			if ($extends->isFinal() || $extends->isTrait()) {
				throw new \UnexpectedValueException('Final classes and traits cannot be inherited.');
			}

			$classString .= (!$extends->isInterface() ? ' extends ' : ' implements ') . $extends->getShortName();
			//METHODS
			$methods_list = static::defineDependentMethods($extends);
		}

		//CONSTRUCTOR
		$constructorString = static::defineConstructor($extends);
		array_unshift($methods_list, $constructorString);
		//HEADER
		$headerString = self::defineHeader();

		//defined components
		$this->_fDefinition = [
			'header' => $headerString,
			'class' => $classString . '{' . self::PLACEHOLDER . '}',
			'methods' => $methods_list
		];
	}

	///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * compose namespace and uses list
	 * @return string	$header	stringed namespace and uses list
	 */
	private function defineHeader(): string
	{
		$header = '';
		if ($this->_pNamespace) {
			$header .= 'namespace ' . $this->_pNamespace . ';';
		}

		if ($this->_pUses) {
			$header .= array_reduce($this->_pUses ? $this->_pUses : [], function ($total, $use) {
				return $total .= 'use ' . $use . ';';
			}, '');
		}

		return $header;
	}

	/**
     * always calls parent constructor from inherited classes
	 * @param	\ReflectionClass    $inheritedClass		parent class
     * @return  string  			$definition 		stringified php code of constructor
     **/
	private function defineConstructor(\ReflectionClass &$inheritedClass = null): string
	{
		if ($inheritedClass && !$inheritedClass->isInterface()) {	
			$inherited_constructor = $inheritedClass->getConstructor();
			$parent_constructor ='parent::__construct(' . static::defineParams($inheritedClass, $inherited_constructor, false, false) . ');';
			return 'public function __construct(' . static::defineParams($inheritedClass, $inherited_constructor) . '){' . $parent_constructor . '}';
		}

		return 'public function __construct(){}';
	}

	/**
     * defines all methods contained in the parent class
     * @param   \ReflectionClass     $inheritedClass     parent class name
     * @return  array                                  	list of stringified php code of parent methods
     **/
	private function defineDependentMethods(\ReflectionClass &$inheritedClass): array
	{
		return array_map(function ($method) {
				//defines single method
				return static::defineMethod($inheritedClass, $method);
			}, 
			$this->getParentMethods($inheritedClass)
		);
	}

	/**
	 * @param   \ReflectionClass    $inheritedClass     parent class name
	 * @return	array				$method_list		list of inheritable methods
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
	 * @param   \ReflectionClass    $class         	method's class
     * @param   \ReflectionMethod   $method     parent method
     * @return  string              $definition stringified php code of passed method
     **/
	private function defineMethod(\ReflectionClass &$class, \ReflectionMethod &$method): string
	{
		$reflection_method = (new \ReflectionMethod($method->class, $method->name));

		//removes abstract from modifier because implemented if not creating another abstract class
		$modifiers = implode(' ', Reflection::getModifierNames($reflection_method->getModifiers()));
		$modifiers =  $this->_pIsAbstract ? $modifiers : str_replace('abstract ', '', $modifiers);
		//defines method signature
		$method_definition = $modifiers . ' function ' . $method->name . '(' . static::defineParams($class, $method) . ')';
		//appends return type if exists
		if($reflection_method->hasReturnType()) {
			$method_definition .= ': ' . $reflection_method->getReturnType();
		}
		
		$method_definition .= $this->_pIsAbstract ? ';' : '{}';

		return $method_definition;
	}

	/**
     * defines specified method's parameters list with default values if found
     * @param   \ReflectionClass    $class         	method's class
     * @param   \ReflectionMethod   $method         parent method
     * @param   bool              	$defaultValues	optionally add default values to params if found
     * @param   bool              	$declaration	optionally add type and reference symbol to parameters (yes if declaration, no if call)
     * @return  string                              stringified php code of parameters
     **/
	private function defineParams(\ReflectionClass &$class, \ReflectionMethod &$method, bool $defaultValues = true, bool $declaration = true): string
	{
		return trim(implode(', ', array_map(function ($param) use ($class, $defaultValues, $declaration) {
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
				'paramIsOptional' => $declaration && !$class->isInternal() && $param->isOptional() ? '=' : '',
				'paramDefaultValue' => $declaration && $defaultValues && !$class->isInternal() && $param->isDefaultValueAvailable() ? (static::formatParamDefaultValue($param->getDefaultValue())) : ''
			]);
		}, (new \ReflectionMethod($method->class, $method->name))->getParameters())));
	}

	/**
     * parse default value of passed parameter
     * @param   mixed   $param	param default value
     * @return  string          stringified php code of default param's value
     **/
	private static function formatParamDefaultValue($param): string
	{
		return str_replace(PHP_EOL, '', var_export($param, true));
	}

	/**
	 * return reflection class instance of created class
	 * @return	\ReflectionClass	reflection class instance
	 */
	private function evalDefinition(): \ReflectionClass
	{
		if (!class_exists($this->_pName)) {
			eval($this->getDefinition(false));
		}

		if (!$this->_fClass) {
			$this->_fClass = new \ReflectionClass($this->_pNamespace . '\\' . $this->_pName);
		}

		return $this->_fClass;
	}

	/**
	 * @param	string	$name		name to check
	 * @return	bool	$is_valid	name is valid
	 */
	private static function validateName(string $name): bool
	{
		$is_valid = true;
		$to_check = explode('\\', $name);
		foreach($to_check as $word) {
			$is_valid = preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $word) !== false;
			if(!$is_valid) {
				break;
			}
		}

		return $is_valid;
	}
}
