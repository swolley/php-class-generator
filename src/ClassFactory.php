<?php
namespace Swolley\ClassGenerator;

use 
	Swolley\ClassGenerator\Components\CClass,
	Swolley\ClassGenerator\Components\CConstructor,
	Swolley\ClassGenerator\Components\CMethod,
	Swolley\ClassGenerator\Components\CNamespace,
	Swolley\ClassGenerator\Components\LMethods,
	Swolley\ClassGenerator\Components\LParents,
	Swolley\ClassGenerator\Components\LTraits,
	Swolley\ClassGenerator\Components\LUses,
	Swolley\ClassGenerator\Utils\Formatter;


/**
 * class generator
 * @uses	TraitValidator	name parsers and validators
 */
final class ClassFactory
{
	/**
	 * @var	CClass				$_pClass		class name and modifier definition
	 * @var CNamespace			$_pNamespace	namespace definition
	 * @var	LUses				$_pUses			list of uses
	 * @var	LTraits				$_pTraits		list of traits to use
	 * @var	LParents			$_pParents		list of parent classes
	 * @var	LMethods			$_pMethods		list of	inherited methods to implement
	 * @var	\ReflectionClass	$_fReflection	reflection class created from generated code
	 */
	private
		/*pre eval*/
		$_pClass,
		$_pNamespace,
		$_pUses,
		$_pTraits,
		$_pParents,
		$_pMethods,
		/*post eval*/
		$_fReflection;

	/**
	 * create class factory with new class method
	 * @param	string	$name	defining class name
	 */
	public function __construct(string $name)
	{
		$this->_pClass = new CClass($name);
		$this->_pParents = new LParents();
		$this->_pUses = new LUses();
		$this->_pTraits = new LTraits();
		$this->_pMethods = new LMethods();
	}

	/**
	 * adds namespace to defining class
	 * @param	string			$name	namespace name
	 * @return	ClassFactory	$this	self class (used to chain defining methods)
	 */
	public function namespace(string $name)
	{
		$this->_pNamespace = new CNamespace($name);
		return $this;
	}

	/**
	 * add a use voice to defining class
	 * @param	string			$name	use name
	 * @return	ClassFactory	$this	self class (used to chain defining methods)
	 */
	public function use(string $name)
	{
		$created = $this->_pUses->add($name);
		if($created['isTrait']) {
			$this->_pTraits->add($created['name']);
		}
		return $this;
	}

	/**
	 * adds a class to be inherited to the defining class
	 * @param	string			$name	parent class name
	 * @return	ClassFactory	$this	self class (used to chain defining methods)
	 */
	public function inherit(string $name)
	{
		$new_found = $this->_pParents->add($name);
		$this->_pUses->add($new_found['use']);
		foreach($new_found['methods'] as $method){
			$new_method = $method->isConstructor() ? new CConstructor($method, $this->_pClass) : new CMethod($method, $this->_pClass);
			$this->_pMethods->add($new_method);
		}
		return $this;
	}

	/**
	 * set defining class final modifier
	 * @param	bool			$isFinal	set class final attribute
	 * @return	ClassFactory	$this		self class (used to chain defining methods)
	 */
	public function final(bool $isFinal = false)
	{
		$this->_pClass->setFinal($isFinal);
		return $this;
	}

	/**
	 * set defining class abstract modifier
	 * @param	bool			$isAbstract	set class abstract attribute
	 * @return	ClassFactory	$this		self class (used to chain defining methods)
	 */
	public function abstract(bool $isAbstract = false)
	{
		$this->pClass->setAbstract($isAbstract);
		return $this;
	}

	/**
	 * write code into specified file path or extract it from namespace
	 * @param 	string	$path	(optional) path where new file'll be saved
	 * @return	bool			if file creation ended correctly
	 */
	public function toFile(string $path = null): bool
	{
		$separator = addcslashes(DIRECTORY_SEPARATOR, '\/\\');
		//parse directory separators, does lowercase file path and set final slash if not exists
		$file_path = preg_replace('/.*(?<!' . $separator . ')$/', $separator, $path ?: str_replace('\\', $separator, strtolower($this->_pNamespace))) . $this->_pClass->getName() . '.php';
		$new_file = fopen($file_path, 'w');
		$formatted_code = (new Formatter)((string)$this);
		$written_bytes = fwrite($new_file, $formatted_code);
		fclose($new_file);

		return is_int($written_bytes);
	}

	/**
	 * eval written code and instantiate a reflection class
	 */
	/*private function eval()
	{
		//
		if(!$this->_fReflection) {
			eval((string)$this);
			$this->_fClass = new \ReflectionClass($this->_pNamespace->getName() . '\\' . $this->_pClass->getName());
		}
	}*/

	/*public function getInstance(...$constructorParams): object
	{
		if($this->_pIsAbstract) {
			throw new \BadMethodCallException('Cannot instantiate an object from an abstract class');
		}
		
		return ($this->evalDefinition())->newInstance(...$constructorParams);
	}*/

	/*public function getInstanceWhitoutConstructor(): object
	{
		if($this->_pIsAbstract) {
			throw new \BadMethodCallException('Cannot instantiate an object from an abstract class');
		}

		return ($this->evalDefinition())->newInstanceWithoutConstructor();
	}*/

	/**
	 * custom toString used for final code creation
	 */
	public function __toString()
	{
		return 
			$this->_pNamespace .
			$this->_pUses .
			$this->_pClass . 
			$this->_pParents .
			'{' .
			$this->_pTraits .
			$this->_pMethods .
			'}';
	}
}