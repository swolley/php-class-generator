<?php
namespace Swolley\ClassGenerator\Utils;

class Formatter
{
	/**
	 * syntax symbols' arrays
	 */
	const
		OPERATORS = ['=', '.', '+', '-', '*', '/', '%', '||', '&&', '+=', '-=', '*=', '/=', '.=', '%=', '==', '!=', '<=', '>=', '<', '>', '===', '!=='],
		IMPORT_STATEMENTS = [T_REQUIRE, T_REQUIRE_ONCE, T_INCLUDE, T_INCLUDE_ONCE],
		CONTROL_STRUCTURES = [T_IF, T_ELSEIF, T_FOREACH, T_FOR, T_WHILE, T_SWITCH, T_ELSE];

	private
		$WHITESPACE_BEFORE = ['?', '{', '=>'],
		$WHITESPACE_AFTER = [',', '?', '=>'],
		$RETURN_BEFORE = [T_NAMESPACE, T_FINAL],
		$RETURN_AFTER = [';', '<?php', '{'];

	/**
	 * @param	string	$code	unformatted code
	 * @return	string			parsed code
	 */
	public function __invoke(string $code): string
	{
		if (mb_substr($code, 0, 5) !== '<?php') {
			$code = '<?php' . PHP_EOL . $code;
		}

		$rawTokens = token_get_all($code);
		$tokens = [];
		foreach ($rawTokens as $rawToken) {
			$tokens[] = new Token($rawToken);
		}

		foreach (self::OPERATORS as $op) {
			$this->WHITESPACE_BEFORE[] = $op;
			$this->WHITESPACE_AFTER[] = $op;
		}

		//First pass - filter out unwanted tokens
		$this->filterUnwantedTokens($tokens);

		// Second pass - add whitespaces and returns
		return $this->parseCode($tokens);
	}

	/**
	 * filter out unwanted tokens
	 * @param	array	$tokens	list of tokens
	 */
	private function filterUnwantedTokens(array &$tokens)
	{
		$matching_ternary = false;
		$filtered_tokens = [];
		for ($i = 0, $n = count($tokens); $i < $n; $i++) {
			$token = $tokens[$i];
			if ($token->contents === '?') {
				$matching_ternary = true;
			}
			if (in_array($token->type, self::IMPORT_STATEMENTS) && self::nextToken($tokens, $i, $j)->contents === '(') {
				$filtered_tokens[] = $token;
				if ($tokens[$i + 1]->type !== T_WHITESPACE) {
					$filtered_tokens[] = new Token([T_WHITESPACE, ' ']);
				}
				$i = $j;
				do {
					$i++;
					$token = $tokens[$i];
					if ($token->contents !== ')') {
						$filtered_tokens[] = $token;
					}
				} while ($token->contents !== ')');
			} elseif ($token->type === T_ELSE && self::nextToken($tokens, $i, $j)->type === T_IF) {
				$i = $j;
				$filtered_tokens[] = new Token([T_ELSEIF, 'elseif']);
			} elseif ($token->contents === ':') {
				if ($matching_ternary) {
					$matching_ternary = false;
				} elseif ($tokens[$i - 1]->type === T_WHITESPACE) {
					array_pop($filtered_tokens); // Remove whitespace before
				}
				$filtered_tokens[] = $token;
			} else {
				$filtered_tokens[] = $token;
			}
		}

		$tokens = $filtered_tokens;
	}

	/**
	 * parses spaces and returns
	 * @param	array	$tokens				list of tokens
	 * @return	string	$final_parsed_code	stringed parsed code
	 */
	private function parseCode(array &$tokens): string
	{
		$tabulators = 0;
		$final_parsed_code = '';
		$matching_ternary = false;
		$double_quote = false;
		for ($i = 0, $n = count($tokens); $i < $n; $i++) {
			$current_parsed_token = '';
			$token = $tokens[$i];
			if ($token->contents === '?') {
				$matching_ternary = true;
			}
			if ($token->contents === '"' && self::isAssocArrayVariable($i, 1) && $tokens[$i + 5]->contents === '"') {
				//Handle case where the only thing quoted is the assoc array variable. Eg. "$value[key]"
				$quote = $tokens[$i++]->contents;
				$var = $tokens[$i++]->contents;
				$open_square_bracket = $tokens[$i++]->contents;
				$str = $tokens[$i++]->contents;
				$close_square_bracket = $tokens[$i++]->contents;
				$quote = $tokens[$i]->contents;
				$current_parsed_token .= $var . "['" . $str . "']";
				$double_quote = false;
				continue;
			}

			//puts return before
			if (in_array($token->type, $this->RETURN_BEFORE) 
				|| ($token->contents === '{' && !in_array($tokens[$i - 1]->type, self::CONTROL_STRUCTURES))
				|| ($token->type === T_CLASS && mb_substr($final_parsed_code, -1) !== ' ')
			) {
				$current_parsed_token .= PHP_EOL;
			}

			if ($token->contents === '"') {
				$double_quote = !$double_quote;
			}
			if ($double_quote && $token->contents === '"' && self::isAssocArrayVariable($tokens, $i, 1)) {
				// don't echo "
			} elseif ($double_quote && self::isAssocArrayVariable($tokens, $i)) {
				if ($tokens[$i - 1]->contents !== '"') {
					$current_parsed_token .= '" . ';
				}
				$var = $token->contents;
				$open_square_bracket = $tokens[++$i]->contents;
				$str = $tokens[++$i]->contents;
				$close_square_bracket = $tokens[++$i]->contents;
				$current_parsed_token .= $var . "['" . $str . "']";
				if ($tokens[$i + 1]->contents != '"') {
					$current_parsed_token .= ' . "';
				} else {
					$i++; // process "
					$double_quote = false;
				}
			} elseif ($token->type === T_STRING && $tokens[$i - 1]->contents === '[' && $tokens[$i + 1]->contents === ']') {
				$current_parsed_token .= preg_match('/[a-z_]+/', $token->contents) ? "'" . $token->contents . "'" : $token->contents;
			} elseif ($token->type === T_ENCAPSED_AND_WHITESPACE || $token->type === T_STRING) {
				$current_parsed_token .= $token->contents;
			} elseif ($token->contents === '-' && in_array($tokens[$i + 1]->type, [T_LNUMBER, T_DNUMBER])) {
				$current_parsed_token .= '-';
			} elseif (in_array($token->type, self::CONTROL_STRUCTURES)) {
				$current_parsed_token .= $token->contents;
				if ($tokens[$i + 1]->type !== T_WHITESPACE) {
					$current_parsed_token .= ' ';
				}
			} elseif ($token->contents === '}') {
				$current_parsed_token .= '}';
				//decrease tabulation
				$tabulators --;
				if ($i + 1 < count($tokens) && in_array($tokens[$i + 1]->type, self::CONTROL_STRUCTURES)) {
					$current_parsed_token .= ' ';
				} else {
					$current_parsed_token .= PHP_EOL;
					if ($i + 1 < count($tokens) && $tokens[$i + 1]->contents !== '}') {
						$current_parsed_token .= PHP_EOL;
					}
				}
			} elseif ($token->contents === '=' && $tokens[$i + 1]->contents === '&') {
				if ($tokens[$i - 1]->type != T_WHITESPACE) {
					$current_parsed_token .= ' ';
				}
				$i++; // match &
				$current_parsed_token .= '=&';
				if ($tokens[$i + 1]->type !== T_WHITESPACE) {
					$current_parsed_token .= ' ';
				}
			} elseif ($token->contents === ':' && $matching_ternary) {
				$matching_ternary = false;
				if ($tokens[$i - 1]->type !== T_WHITESPACE) {
					$current_parsed_token .= ' ';
				}
				$current_parsed_token .= ':';
				if ($tokens[$i + 1]->type !== T_WHITESPACE) {
					$current_parsed_token .= ' ';
				}
			} elseif (in_array($token->contents, $this->WHITESPACE_BEFORE) && $tokens[$i - 1]->type !== T_WHITESPACE && in_array($token->contents, $this->WHITESPACE_AFTER) && $tokens[$i + 1]->type !== T_WHITESPACE) {
				$current_parsed_token .= ' ' . $token->contents . ' ';
			} elseif (in_array($token->contents, $this->WHITESPACE_BEFORE) && $tokens[$i - 1]->type !== T_WHITESPACE) {
				if($token->contents !== '{' || ($token->contents === '{' && in_array($tokens[$i - 1]->type, self::CONTROL_STRUCTURES))) {
					$current_parsed_token .= ' ';
				}

				$current_parsed_token .= $token->contents;
			} elseif (in_array($token->contents, $this->WHITESPACE_AFTER) && $tokens[$i + 1]->type !== T_WHITESPACE) {
				$current_parsed_token .= $token->contents . ' ';
			} else {
				$current_parsed_token .= $token->contents;
			}

			if(mb_substr($final_parsed_code, -1) === PHP_EOL) {
				$current_parsed_token = str_repeat("\t", $tabulators) . $current_parsed_token;
			} elseif(mb_substr($current_parsed_token, 0, 1) === PHP_EOL) {
				$current_parsed_token = substr_replace($current_parsed_token, str_repeat("\t", $tabulators), 1, 0);
			}

			//adds return after
			if (in_array($token->contents, $this->RETURN_AFTER) && !mb_strpos($current_parsed_token, PHP_EOL)) {
				$current_parsed_token .= PHP_EOL;
			}

			//if open bracket increase tabulation
			if ($token->contents === '{') {
				$tabulators++;
			}

			$final_parsed_code .= $current_parsed_token;
		}

		return $final_parsed_code;
	}

	/*private function skipWhitespace(&$tokens, int &$i)
	{
		$i++;
		$token = $tokens[$i];
		while ($token->type == T_WHITESPACE) {
			$this->_lineNo += mb_substr($token->contents, "\n");
			$i++;
			$token = $tokens[$i];
		}
	}*/

	private static function nextToken(array &$tokens, int &$i, int &$j): array
	{
		$j = $i;
		do {
			$j++;
			$token = $tokens[$j];
		} while ($token->type === T_WHITESPACE);
		return $token;
	}

	private static function isAssocArrayVariable(array &$tokens, int $i, int $offset = 0): bool
	{
		$j = $i + $offset;
		return $tokens[$j]->type === T_VARIABLE &&
			$tokens[$j + 1]->contents === '[' &&
			$tokens[$j + 2]->type === T_STRING &&
			preg_match('/[a-z_]+/', $tokens[$j + 2]->contents) &&
			$tokens[$j + 3]->contents === ']';
	}
}
