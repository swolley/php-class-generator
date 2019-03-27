<?php
namespace Swolley\ClassGenerator;

final class ClassFactory
{
	const PLACEHOLDER = '###placeholder###';
	/////////////////////////////////////////////////// init params ////////////////////////////////////////////////////
	private $_pNamespace = null;
	private $_pUses = [];
	private $_pName = null;
	private $_pIsFinal = false;
	private $_pIsAbstract = false;
	private $_pParents = [
		'extends' => null,
		'inherits' => []
	];
	private $_pMethods = [];

	////////////////////////////////////////////////// final fields ////////////////////////////////////////////////////
	private $_fClass;
	private $_fDefinition = [
		'header' => null,
		'class' => null,
		'traits' => null,
		'methods' => null
	];

	//////////////////////////////////////////////// constructor ////////////////////////////////////////////////////
	public function __construct(string $name)
	{
		$name = ucfirst(trim($name));
		if (empty($name) || !self::validateName($name)) {
			throw new \InvalidArgumentException("Invalid class $name");
		}

		$this->_pName = $name;
	}

	//////////////////////////////////////////////////// getter/setter /////////////////////////////////////////////////
	public function namespace(string $name)
	{
		$name = ucwords(trim($name), '\\');
		if (empty($name) || !self::validateName($name)) {
			throw new \InvalidArgumentException("Invalid namespace $name");
		}
		
		$this->_pNamespace = $name;
		return $this;
	}

	public function uses(array $name)
	{
		$name = ucwords(trim($name), '\\');
		if (empty($name) || !self::validateName($name)) {
			throw new \InvalidArgumentException("Invalid name $name");
		}

		if(!class_exists($name)) {
			throw new \UnexpectedValueException("class $name not exists");
		}

		$this->_pUses[] = $name;
		return $this;
	}

	public function inherits(string $name)
	{
		//validations
		$name = ucwords(trim($name), '\\');
		if (empty($name) || !self::validateName($name)) {
			throw new \InvalidArgumentException("Invalid name $name");
		}

		if (!class_exists($name)) {
			throw new \UnexpectedValueException("class $name not exists");
		}

		$this->setParentClass($name);
		return $this;
	}

	public function final(bool $isFinal = false)
	{
		if($isFinal && $this->_pIsAbstract) {
			throw new \UnexpectedValueException('Abstract class cannot be final');
		}
		$this->_pIsFinal = $isFinal;
		return $this;
	}

	public function abstract(bool $isAbstract = false)
	{
		if($isAbstract && $this->_pIsFinal) {
			throw new \UnexpectedValueException('Final class cannot be abstract');
		}
		$this->_pIsAbstract = $isAbstract;
		return $this;
	}

	/////////////////////////////////////////////// output ////////////////////////////////////////////////////
	public function getDefinition(bool $formatted = true): string
	{
		$complete_string = $this->_fDefinition['header'] 
			. str_replace(
				self::PLACEHOLDER, 
				$this->_fDefinition['traits'] . $this->_fDefinition['methods'],
				$this->_fDefinition['class']
			);

		return $formatted ? (new Formatter)($complete_string) : $complete_string;
	}

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

		$header_string = '';
		$class_string = '';
		$constructor_string = '';
		$traits_string = '';
		$methods_list = [];
		$extends = null;

		//CLASS SIGNATURE
		if($this->_pIsFinal) {
			$class_string .= 'final ';
		} elseif($this->_pIsAbstract) {
			$class_string .= 'abstract ';
		}

		$class_string .= 'class ' . $this->_pName;

		foreach($this->_pParents as $key => $value) {
			if($key === 'extends' && $value) {
				$class_string .= ' extends ' . $value->getShortName();
			}

		}

		if (count($this->_pParents['inherits']) > 0) {
			foreach($this->_pParents as $extends) {
				$class_string .= (!$extends->isInterface() ? ' extends ' : ' implements ') . $extends->getShortName();
				//METHODS
				//$this->defineDependentMethods($methods_list, $extends);
			}
		}

		//CONSTRUCTOR
		$this->defineConstructor($constructor_string, $extends);
		array_unshift($methods_list, $constructor_string);
		//HEADER
		$this->defineHeader($header_string, $traits_string);
		
		//defined components
		$this->_fDefinition = [
			'header' => $header_string,
			'class' => $class_string . '{' . self::PLACEHOLDER . '}',
			'traits' => $traits_string,
			'methods' => implode($methods_list)
		];
	}

	///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * compose namespace and uses list
	 * @return string	$header	stringed namespace and uses list
	 */
	private function defineHeader(string &$header, string &$traits)
	{
		if ($this->_pNamespace) {
			$header .= 'namespace ' . $this->_pNamespace . ';';
		}

		if ($this->_pUses) {
			foreach($this->_pUses ? $this->_pUses : [] as $use) {
				$use_class = new \ReflectionClass($use);
				if($use_class->isTrait()) {
					$traits .= 'use' . $use_class->getShortName() . ';';
				}
				$header .= 'use ' . $use . ';';
			}
		}
	}

	private function setParentClass(string $name)
	{
		$class = new \ReflectionClass($name);
		//throws if parent class is final because cannot be inherited
		if ($class->isFinal() || $class->isTrait()) {
			throw new \UnexpectedValueException('Final classes and traits cannot be inherited');
		}
		//adds namespace in use list if not already in
		$class_namespace = $class->getNamespaceName();
		$class_namespace = empty($class_namespace) ? '\\' . $class->name : $class_namespace;
		if($class_namespace !== $this->_pNamespace && !in_array($class_namespace, $this->_pUses)) {
			$this->_pUses[] = $class_namespace;
		}

		//adds class in parent's list
		if($class->isInterface()) {
			$this->_pParents['inherits'][] = $class;
		} elseif(is_null($this->_pParents['extends'])) {
			$this->_pParents['extends'] = $class;
		} else {
			throw new \UnexpectedValueException('Cannot extends more than one interface');
		}

		//adds inheritable methods to list
		$this->setParentMethods($class);
	}

	private function setParentMethods(\ReflectionClass &$class)
	{
		$methods_list = [];
		//define methods only if parent class is abstract or interface
		if ($class->isInterface()) {
			$methods_list = $class->getMethods();
		} elseif ($class->isAbstract()) {
			$methods_list = $class->getMethods(\ReflectionMethod::IS_ABSTRACT);
		}

		$this->_pMethods = array_merge($this->_pMethods, $methods_list);
	}

	/**
     * always calls parent constructor from inherited classes
	 * @param	\ReflectionClass    $inheritedClass		parent class
     * @return  string  			$definition 		stringified php code of constructor
     **/
	private function defineConstructor(string &$constructor, \ReflectionClass &$inheritedClass = null)
	{
		if ($inheritedClass && !$inheritedClass->isInterface()) {	
			$inherited_constructor = $inheritedClass->getConstructor();
			$parent_constructor ='parent::__construct(' . static::defineParams($inheritedClass, $inherited_constructor, false, false) . ');';
			$constructor = 'public function __construct(' . static::defineParams($inheritedClass, $inherited_constructor) . '){' . $parent_constructor . '}';
		} else {
			$constructor = 'public function __construct(){}';
		}
	}

	/**
     * defines all methods contained in the parent class
     * @param   \ReflectionClass     $inheritedClass     parent class name
     * @return  array                                  	list of stringified php code of parent methods
     **/
	private function defineDependentMethods(array &$methods, \ReflectionClass &$inheritedClass)
	{
		
		foreach($this->setParentClassMethods($inheritedClass) as $method) {
			$methods[] = static::defineMethod($inheritedClass, $method);
		}
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
