<?php
namespace ClassGenerator\Components;

class CClass extends AbstractComponent
{
	private $_isAbstract = false;
	private $_isFinal = false;

	public function __construct(string $name)
	{
		parent::__construct($name);
	}

	/**
	 * set new class as final
	 * @param	bool	$isFinal	(optional)final flag value
	 * @return	self				self class (used to chain init methods)
	 * @throws	\UnexpectedValueException	cannot set abstract class as final
	 */
	public function setFinal(bool $isFinal = false)
	{
		if($isFinal && $this->_isAbstract) {
			throw new \UnexpectedValueException('Abstract class cannot be final');
		}
		$this->_isFinal = $isFinal;
		return $this;
	}

	/**
	 * set new class as abstract
	 * @param	bool	$isAbstract	(optional)abstract flag value
	 * @return	self				self class (used to chain init methods)
	 * @throws	\UnexpectedValueException	cannot set final class as abstract
	 */
	public function setAbstract(bool $isAbstract = false)
	{
		if($isAbstract && $this->_isFinal) {
			throw new \UnexpectedValueException('Final class cannot be abstract');
		}
		$this->_isAbstract = $isAbstract;
		return $this;
	}

	public function isFinal(): bool
	{
		return $this->_isFinal;
	}

	public function isAbstract(): bool
	{
		return $this->_isAbstract;
	}

	public function __toString()
	{
		return 
			($this->_isAbstract ? 'abstract ' : '') .
			($this->_isFinal ? 'final ' : '') .
			'class ' .
			$this->_name;
	}
}