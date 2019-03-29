<?php
namespace Swolley\ClassGenerator\Components;

class CClass extends AbstractComponent
{
	/**
	 * @var	bool	$_isAbstract	set defining class as abstract
	 * @var	bool	$_isFinal		set defining class as final
	 */
	private
		$_isAbstract = false,
		$_isFinal = false;

	/**
	 * @param	string	class name
	 */
	public function __construct(string $name)
	{
		parent::__construct($name);
	}

	/**
	 * final attribute setter
	 * @param	bool	$isFinal	(optional) attribute status
	 * @throws	\UnexpectedValueException	cannot set abstract class as final
	 */
	public function setFinal(bool $isFinal = false)
	{
		if($isFinal && $this->_isAbstract) {
			throw new \UnexpectedValueException('Abstract class cannot be final');
		}
		$this->_isFinal = $isFinal;
	}

	/**
	 * abstract attribute setter
	 * @param	bool	$isAbstract	(optional) attribute status
	 * @throws	\UnexpectedValueException	cannot set final class as abstract
	 */
	public function setAbstract(bool $isAbstract = false)
	{
		if($isAbstract && $this->_isFinal) {
			throw new \UnexpectedValueException('Final class cannot be abstract');
		}
		$this->_isAbstract = $isAbstract;
	}

	/**
	 * final attribute getter
	 * @return bool	attribute status
	 */
	public function isFinal(): bool
	{
		return $this->_isFinal;
	}

	/**
	 * abstract attribute getter
	 * @return bool	attribute status
	 */
	public function isAbstract(): bool
	{
		return $this->_isAbstract;
	}

	/**
	 * custom toString	used for code export
	 */
	public function __toString()
	{
		return 
			($this->_isAbstract ? 'abstract ' : '') .
			($this->_isFinal ? 'final ' : '') .
			'class ' .
			$this->_name;
	}
}