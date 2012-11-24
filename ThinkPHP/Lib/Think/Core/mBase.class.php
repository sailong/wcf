<?php
abstract class mBase extends Think {
	/**
	 * 调用__call给对象属性赋值或取值.
	 *
	 * @param string $func
	 * @param array  $args
	 * @return mixed
	 */
	public function __call($func, $args) {
		if(strtolower(substr($func, 0, 3)) === 'set') {
			$var = strtolower(substr($func, 3, 1)).substr($func, 4);
			if($var == '') return;
			$this->$var = $args[0];
		} elseif(strtolower(substr($func, 0, 3)) === 'get') {
			$var = strtolower(substr($func, 3, 1)).substr($func, 4);
			if($var == '') return;
			return $this->$var;
		}
		return;
	}

}
