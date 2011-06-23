<?php
/**
 * Globally keep around instances without polluting the global namespace.
 * Get and set instances with a keyword.
 * Also allows for easy overrides by re-keeping something with the same key.
 * Replaced keys must be an instance of the original.
 */
class Anno_Keeper {
	protected static $instances = array();
	public static function keep($key, $instance) {
		if (isset(self::$instances[$key]) && !($instance instanceof self::$instances[$key])) {
			throw new Exception('If you\'re going to replace an instance that already exists, the new instance must be an instanceof the original class, so that methods may safely be called.', 1);
		}
		self::$instances[$key] = $instance;
		return self::$instances[$key];
	}
	public static function retrieve($key) {
		if (!isset(self::$instances[$key])) {
			throw new Exception($key.' hasn\'t been set yet with ::keep()', 1);
			
		}
		return self::$instances[$key];
	}
	public function discard($key) {
		unset(self::$instances[$key]);
	}
}
?>