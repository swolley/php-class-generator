<?php
namespace ClassGenerator\Components;

use ClassGenerator\Utils\TraitValidator;

/**
 * @uses	TraitValidator	name parser and validator;
 */
abstract class AbstractComponent
{
	use TraitValidator;

	/**
	 * @var	string	$_name	component name
	 */
	protected $_name;

	/**
	 * @param	string	$name			component name
	 * @param 	bool	$checkExistence	(optional) check class existence
	 */
	public function __construct(string $name, bool $checkExistence = false)
	{
		$parsed = self::parseName($name);

		if ($checkExistence && !class_exists($parsed) && !interface_exists($parsed) && !trait_exists($parsed, false)) {
			throw new \UnexpectedValueException("$parsed not exists");
		}

		$this->_name = $parsed;
	}

	/**
	 * name getter
	 * @return	string	component name
	 */
	public function getName(): string
	{
		return $this->_name;
	}

	abstract public function __toString();
}
