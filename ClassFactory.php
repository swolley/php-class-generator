<?php
namespace ClassGenerator;

use 
	ClassGenerator\Components\CClass,
	ClassGenerator\Components\CConstructor,
	ClassGenerator\Components\CMethod,
	ClassGenerator\Components\CNamespace,
	ClassGenerator\Components\LMethods,
	ClassGenerator\Components\LParents,
	ClassGenerator\Components\LTraits,
	ClassGenerator\Components\LUses;


/**
 * class generator
 * @uses	TraitValidator	name parsers and validators
 */
final class ClassFactory
{
	/**
	 * @var	CClass	$_pClass	class name and modifier definition
	 * @var CNamespace	$_pNamespace	namespace definition
	 * @var	
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

	public function __construct(string $name)
	{
		$this->_pClass = new CClass($name);
		$this->_pParents = new LParents();
		$this->_pUses = new LUses();
		$this->_pTraits = new LTraits();
		$this->_pMethods = new LMethods();
	}

	public function namespace(string $name)
	{
		$this->_pNamespace = new CNamespace($name);
		return $this;
	}

	public function use(string $name)
	{
		$created = $this->_pUses->add($name);
		if($created['isTrait']) {
			$this->_pTraits->add($created['name']);
		}
		return $this;
	}

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

	public function final(bool $isFinal = false)
	{
		$this->_pClass->setFinal($isFinal);
		return $this;
	}

	public function abstract(bool $isAbstract = false)
	{
		$this->pClass->setAbstract($isAbstract);
		return $this;
	}

	private function eval()
	{
		if(!$this->_fReflection) {
			eval((string)$this);
			$this->_fClass = new \ReflectionClass($this->_pNamespace->getName() . '\\' . $this->_pClass->getName());
		}
	}

	/////////////////////////////////////////////// output ////////////////////////////////////////////////////
	/*public function getDefinition(bool $formatted = true): string
	{
		$complete_string = $this->_fDefinition['header'] 
			. str_replace(
				self::PLACEHOLDER, 
				$this->_fDefinition['traits'] . $this->_fDefinition['methods'],
				$this->_fDefinition['class']
			);

		return $formatted ? (new Formatter)($complete_string) : $complete_string;
	}*/

	public function toFile($path = null): bool
	{
		$separator = addcslashes(DIRECTORY_SEPARATOR, '\/\\');
		//parse directory separators, does lowercase file path and set final slash if not exists
		$file_path = preg_replace('/.*(?<!' . $separator . ')$/', $separator, $path ?: str_replace('\\', $separator, strtolower($this->_pNamespace))) . $this->_pClass->getName() . '.php';
		$new_file = fopen($file_path, 'w');
		$written_bytes = fwrite($new_file, $this);
		fclose($new_file);

		return is_int($written_bytes);
	}

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

	public function __toString()
	{
		return 
			$this->_pNamespace .
			$this->_pUses .
			$this->_pClass . 
			$this->_pParents .
			'{' . PHP_EOL .
			$this->_pTraits .
			$this->_pMethods .
			'}' . PHP_EOL;
	}
}