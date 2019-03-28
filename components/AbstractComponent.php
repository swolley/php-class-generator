<?php
namespace ClassGenerator\Components;
use ClassGenerator\Utils\TraitValidator;

abstract class AbstractComponent
{
	use TraitValidator;

	protected $_name;

	public function __construct(string $name, bool $checkExistence = false)
	{
		$parsed = self::parseName($name);

		if($checkExistence && !class_exists($parsed) && !interface_exists($parsed)){
			throw new \UnexpectedValueException("class $parsed not exists");
		}

		$this->_name = $parsed;
	}

	public function getName(): string
	{
		return $this->_name;
	}

	abstract public function __toString();
}