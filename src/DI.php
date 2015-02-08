<?php

namespace xpl\Dependency;

use Closure;

class DI implements \ArrayAccess, \Countable {
	
	protected $keys = array();
	protected $values = array();
	protected $registered = array();
	protected $factories = array();
	
	/**
	 * Register a dependency.
	 * 
	 * Value can be:
	 * 	1) An object instance
	 *  2) A closure that returns the object
	 * 
	 * @param string $key Dependency key.
	 * @param mixed $object
	 */
	public function offsetSet($key, $object) {
		
		$this->keys[$key] = true;
	
		if ($object instanceof Closure) {
			$this->registered[$key] = $object;
		} else {
			$this->values[$key] = $object;
		}
	}
	
	public function register($key, $newval) {
		$this->offsetSet($key, $newval);
	}
	
	/**
	 * Resolves a named dependency to an object.
	 * 
	 * @param string $key Identifier for the object.
	 * @param ... Additional arguments
	 * @return mixed
	 */
	public function resolve($key) {
		
		if (isset($this->values[$key])) {
			return $this->values[$key];
		}
		
		if (func_num_args() !== 1) {
			$args = func_get_args();
			array_shift($args);
			return $this->resolveArray($key, $args);
		}
		
		if (isset($this->registered[$key])) {
			return $this->values[$key] = call_user_func($this->registered[$key], $this);
		}
		
		if (isset($this->factories[$key])) {
			return call_user_func($this->factories[$key], $this);
		}
		
		return null;
	}
	
	/**
	 * Resolves a named dependency to an object using an array of arguments.
	 * 
	 * @param string $key Identifier for the object.
	 * @param array $args Arguments to pass to registration closure or factory.
	 * @return mixed
	 */
	public function resolveArray($key, array $args = array()) {
		
		if (isset($this->values[$key])) {
			return $this->values[$key];
		}
		
		if (isset($this->registered[$key])) {
			return $this->values[$key] = call_user_func_array($this->registered[$key], $args);
		}
		
		if (isset($this->factories[$key])) {
			return call_user_func_array($this->factories[$key], $args);	
		}
		
		return null;
	}
	
	/**
	 * Resolves a dependency or value.
	 * 
	 * @param string $key
	 * @return mixed
	 */
	public function offsetGet($key) {
		return $this->resolve($key);
	}
	
	/**
	 * Adds a factory to the container.
	 * 
	 * @param string $key Identifier for the factory.
	 * @param Closure $factory Factory closure.
	 */
	public function factory($key, Closure $factory) {
		$this->keys[$key] = true;
		$this->factories[$key] = $factory;
	}
	
	/**
	 * Extends an object in the container.
	 * 
	 * @param string $key Identifier for the object.
	 * @param \Closure $callback Extension closure.
	 * @throws \xpl\Dependency\Exception if given an invalid object identifier.
	 */
	public function extend($key, Closure $callback) {
		$this[$key] = call_user_func($callback, $this->resolve($key), $this);
	}
	
	public function has($key) {
		return isset($this->keys[$key]);
	}
	
	public function offsetExists($key) {
		return $this->has($key);
	}
	
	public function remove($key) {
		if (isset($this->keys[$key])) {
			unset($this->registered[$key], $this->values[$key], $this->factories[$key], $this->keys[$key]);
		}
	}
	
	public function offsetUnset($key) {
		$this->remove($key);
	}
	
	public function get($key) {
		return $this->resolve($key);
	}
	
	public function __get($var) {
		return $this->resolve($var);
	}
	
	public function __set($key, $value) {
		$this->register($key, $value);
	}
	
	public function __isset($var) {
		return $this->has($var);
	}
	
	public function __unset($var) {
		$this->remove($var);
	}
	
	public function count() {
		return count($this->keys);
	}
	
	public function toArray() {
		return get_object_vars($this);
	}
	
}
