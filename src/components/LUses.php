<?php
namespace Swolley\ClassGenerator\Components;

class LUses extends AbstractList
{
	/**
	 * adds new class full name to element's list
	 * @param	string	$item	class name
	 * @return	array			new added class data for external methods (name, isTrait)
	 * @throws	\UnexpectedValueException	invalid type name is passed
	 */
	public function add($item)
	{
		if (!is_string($item)) {
			throw new \UnexpectedValueException('Invalid use type');
		}

		foreach ($this->_elements as $use) {
			if ($use->getName() === $item) {
				return;
			}
		}

		$new_use = new CUse($item);
		$this->_elements[] = $new_use;

		$reflection = new \ReflectionClass($new_use->getName());
		return [
			'name' => $reflection->getShortName(),
			'isTrait' => $reflection->isTrait()
		];
	}

	/**
	 * custom toString	used for code export
	 */
	public function __toString()
	{
		if ($this->count() === 0) {
			return '';
		}

		$list = array_map(function ($use) {
			return [
				'name' => (string)$use,
				'path' => $use->split()
			];
		}, $this->_elements);
		//orders list
		usort($list, function ($a, $b) {
			return $a['name'] <=> $b['name'];
		});
		//namespace grouping
		$prefix = "use \n\t";
		$groups = [];
		$cur_group = $prefix . $list[0]['name'];
		for ($i = 1; $i < count($list); $i++) {
			if ($list[$i]['path'][0] === $list[$i - 1]['path'][0]) {
				$cur_group .= ",\n\t" . $list[$i]['name'];
			} else {
				$cur_group .= ';';
				$groups[] = $cur_group;
				$cur_group = $prefix . $list[$i]['name'];
			}
		}
		$groups[] = $cur_group . ';';
		return implode("\n", $groups);
	}
}
