<?php
namespace Swolley\ClassGenerator\Utils;

trait Validator
{
	/**
	 * format name and calls validate method
	 * @param	string	$name	short or full name
	 * @return	string			parsed name
	 * @throws	\InvalidArgumentException	on format error
	 */
	private static function parseName(string $name): string
	{
		$name = ucwords(trim($name), '\\');
		if (!self::validateName($name)) {
			throw new \InvalidArgumentException("Invalid name $name");
		}

		return $name;
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

		return $is_valid && !empty($name);
	}
}