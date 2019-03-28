<?php
namespace ClassGenerator\Components;

class CNamespace extends AbstractComponent
{
	/**
	 * @param	string	$name	namespace name
	 */
	public function __construct(string $name)
	{
		parent::__construct($name);
	}

	/**
	 * custom toString	used for code export
	 */
	public function __toString()
	{
		return "namespace {$this->_name};";
	}
}