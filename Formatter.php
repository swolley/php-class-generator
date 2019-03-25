<?php
namespace Swolley\ClassGenerator;

class Token
{
	public $type;
	public $contents;

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

class Formatter
{
	const OPERATORS = ['=', '.', '+', '-', '*', '/', '%', '||', '&&', '+=', '-=', '*=', '/=', '.=', '%=', '==', '!=', '<=', '>=', '<', '>', '===', '!=='];
	const IMPORT_STATEMENTS = [T_REQUIRE, T_REQUIRE_ONCE, T_INCLUDE, T_INCLUDE_ONCE];
	const CONTROL_STRUCTURES = [T_IF, T_ELSEIF, T_FOREACH, T_FOR, T_WHILE, T_SWITCH, T_ELSE];
	const MODIFIERS = [T_PRIVATE, T_PUBLIC, T_PROTECTED, T_FINAL, T_STATIC];

	private $WHITESPACE_BEFORE = ['?', '{', T_DOUBLE_ARROW];
	private $WHITESPACE_AFTER = [',', '?', T_DOUBLE_ARROW];
	private $RETURN_BEFORE = [T_NAMESPACE];
	private $RETURN_AFTER = [';', T_OPEN_TAG];

	public function __invoke(string $code) : string
	{
		if (mb_substr($code, 0, 5) !== T_OPEN_TAG) {
			$code = '<?php ' . $code;
		}

		$rawTokens = token_get_all($code);
		$tokens = [];
		foreach ($rawTokens as $rawToken) {
			$tokens[] = new Token($rawToken);
		}

		$this->RETURN_BEFORE = array_merge($this->RETURN_BEFORE, self::MODIFIERS);
		foreach (self::OPERATORS as $op) {
			$this->WHITESPACE_BEFORE[] = $op;
			$this->WHITESPACE_AFTER[] = $op;
		}

		//First pass - filter out unwanted tokens
		self::filterUnwantedTokens($tokens);

		// Second pass - add whitespace
		return $this->addWhitespaces($tokens);
	}

	/**
	 * filter out unwanted tokens
	 */
	private static function filterUnwantedTokens(array &$tokens)
	{
		$matching_ternary = false;
		$filtered_tokens = [];
		for ($i = 0, $n = count($tokens); $i < $n; $i++) {
			$token = $tokens[$i];
			if ($token->contents == '?') {
				$matching_ternary = true;
			}
			if (in_array($token->type, self::IMPORT_STATEMENTS) && self::nextToken($tokens, $i, $j)->contents == '(') {
				$filtered_tokens[] = $token;
				if ($tokens[$i + 1]->type != T_WHITESPACE) {
					$filtered_tokens[] = new Token([T_WHITESPACE, ' ']);
				}
				$i = $j;
				do {
					$i++;
					$token = $tokens[$i];
					if ($token->contents != ')') {
						$filtered_tokens[] = $token;
					}
				} while ($token->contents != ')');
			} elseif ($token->type == T_ELSE && self::nextToken($tokens, $i, $j)->type == T_IF) {
				$i = $j;
				$filtered_tokens[] = new Token([T_ELSEIF, 'elseif']);
			} elseif ($token->contents == ':') {
				if ($matching_ternary) {
					$matching_ternary = false;
				} elseif ($tokens[$i - 1]->type == T_WHITESPACE) {
					array_pop($filtered_tokens); // Remove whitespace before
				}
				$filtered_tokens[] = $token;
			} else {
				$filtered_tokens[] = $token;
			}
		}

		$tokens = $filtered_tokens;
	}

	private function addWhitespaces(array &$tokens)
	{
		$finalParsedCode = '';
		$matchingTernary = false;
		$doubleQuote = false;
		for ($i = 0, $n = count($tokens); $i < $n; $i++) {
			$token = $tokens[$i];
			if ($token->contents == '?') {
				$matchingTernary = true;
			}
			if ($token->contents == '"' && self::isAssocArrayVariable($i, 1) && $tokens[$i + 5]->contents == '"') {
				/*
				* Handle case where the only thing quoted is the assoc array variable.
				* Eg. "$value[key]"
				*/
				$quote = $tokens[$i++]->contents;
				$var = $tokens[$i++]->contents;
				$openSquareBracket = $tokens[$i++]->contents;
				$str = $tokens[$i++]->contents;
				$closeSquareBracket = $tokens[$i++]->contents;
				$quote = $tokens[$i]->contents;
				$finalParsedCode .= $var . "['" . $str . "']";
				$doubleQuote = false;
				continue;
			}

			if(in_array($token->contents, $this->RETURN_BEFORE)) {
				$finalParsedCode .= PHP_EOL;
			}

			if ($token->contents == '"') {
				$doubleQuote = !$doubleQuote;
			}
			if ($doubleQuote && $token->contents == '"' && self::isAssocArrayVariable($tokens, $i, 1)) {
				// don't echo "
			} elseif ($doubleQuote && self::isAssocArrayVariable($tokens, $i)) {
				if ($tokens[$i - 1]->contents != '"') {
					$finalParsedCode .= '" . ';
				}
				$var = $token->contents;
				$openSquareBracket = $tokens[++$i]->contents;
				$str = $tokens[++$i]->contents;
				$closeSquareBracket = $tokens[++$i]->contents;
				$finalParsedCode .= $var . "['" . $str . "']";
				if ($tokens[$i + 1]->contents != '"') {
					$finalParsedCode .= ' . "';
				} else {
					$i++; // process "
					$doubleQuote = false;
				}
			} elseif ($token->type == T_STRING && $tokens[$i - 1]->contents == '[' && $tokens[$i + 1]->contents == ']') {
				if (preg_match('/[a-z_]+/', $token->contents)) {
					$finalParsedCode .= "'" . $token->contents . "'";
				} else {
					$finalParsedCode .= $token->contents;
				}
			} elseif ($token->type == T_ENCAPSED_AND_WHITESPACE || $token->type == T_STRING) {
				$finalParsedCode .= $token->contents;
			} elseif ($token->contents == '-' && in_array($tokens[$i + 1]->type, [T_LNUMBER, T_DNUMBER])) {
				$finalParsedCode .= '-';
			} elseif (in_array($token->type, self::CONTROL_STRUCTURES)) {
				$finalParsedCode .= $token->contents;
				if ($tokens[$i + 1]->type != T_WHITESPACE) {
					$finalParsedCode .= ' ';
				}
			} elseif ($token->contents == '}') {
				$finalParsedCode .= '}';
				if($i + 1< count($tokens) && in_array($tokens[$i + 1]->type, self::CONTROL_STRUCTURES)){
					$finalParsedCode .= ' ';
				} elseif($i + 1< count($tokens)) {
					$finalParsedCode .= PHP_EOL;
					if($tokens[$i + 1]->contents !== '}') {
						$finalParsedCode .= PHP_EOL;
					}
				}
			} elseif ($token->contents == '=' && $tokens[$i + 1]->contents == '&') {
				if ($tokens[$i - 1]->type != T_WHITESPACE) {
					$finalParsedCode .= ' ';
				}
				$i++; // match &
				$finalParsedCode .= '=&';
				if ($tokens[$i + 1]->type != T_WHITESPACE) {
					$finalParsedCode .= ' ';
				}
			} elseif ($token->contents == ':' && $matchingTernary) {
				$matchingTernary = false;
				if ($tokens[$i - 1]->type != T_WHITESPACE) {
					$finalParsedCode .= ' ';
				}
				$finalParsedCode .= ':';
				if ($tokens[$i + 1]->type != T_WHITESPACE) {
					$finalParsedCode .= ' ';
				}
			} elseif (in_array($token->contents, $this->WHITESPACE_BEFORE) && $tokens[$i - 1]->type != T_WHITESPACE && in_array($token->contents, $this->WHITESPACE_AFTER) && $tokens[$i + 1]->type != T_WHITESPACE) {
				$finalParsedCode .= ' ' . $token->contents . ' ';
			} elseif (in_array($token->contents, $this->WHITESPACE_BEFORE) && $tokens[$i - 1]->type != T_WHITESPACE) {
				$finalParsedCode .= ' ' . $token->contents;
			} elseif (in_array($token->contents, $this->WHITESPACE_AFTER) && $tokens[$i + 1]->type != T_WHITESPACE) {
				$finalParsedCode .= $token->contents . ' ';
			} else {
				$finalParsedCode .= $token->contents;
			}

			if(in_array($token->contents, $this->RETURN_AFTER)) {
				$finalParsedCode .= PHP_EOL;
			}
		}

		return $finalParsedCode;
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

	private static function nextToken(array &$tokens, int &$i, int &$j) : array
	{
		$j = $i;
		do {
			$j++;
			$token = $tokens[$j];
		} while ($token->type == T_WHITESPACE);
		return $token;
	}

	private static function isAssocArrayVariable(array &$tokens, int $i, int $offset = 0) : bool
	{
		$j = $i + $offset;
		return $tokens[$j]->type == T_VARIABLE &&
			$tokens[$j + 1]->contents == '[' &&
			$tokens[$j + 2]->type == T_STRING &&
			preg_match('/[a-z_]+/', $tokens[$j + 2]->contents) &&
			$tokens[$j + 3]->contents == ']';
	}
}
