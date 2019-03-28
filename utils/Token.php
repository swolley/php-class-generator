<?php
namespace ClassGenerator\Utils;

class Token
{
	/**
	 * @var	int		$type		token code
	 * @var	string	$contents	token content
	 */
	public
		$type,
		$contents;

	/**
	 * @param array	$rawToken	recognized token data
	 */
	public function __construct($rawToken)
	{
		if (is_array($rawToken)) {
			$this->type = $rawToken[0];
			$this->contents = $rawToken[1];
		} else {
			$this->type = -1;
			$this->contents = $rawToken;
		}
	}
}