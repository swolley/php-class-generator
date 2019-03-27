<?php
namespace ClassGenerator\Utils;

class Token
{
	public
		$type,
		$contents;

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