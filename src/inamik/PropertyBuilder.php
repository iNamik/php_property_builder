<?php
/*
 * iNamik Property Builder for PHP
 * Copyright (C) 2012 David Farrell (davidpfarrell@yahoo.com)
 *
 * Licensed under MIT or GPLv3, see LICENSE.txt
 */
namespace inamik;

use \Exception;

/**
 * PropertyBuilder is an API that helps you build an array of properties from
 * composite property sets.
 *
 * Think of PropertyBuilder as array_merge with some extra features:
 *
 * * Variable Substitution
 *
 *   You can reference other properties within your property value:
 *
 *     'subject' => 'world',
 *     'message' => 'Hello, {{subject}}'
 *
 *   You can also reference elements of an array:
 *
 *     'array'   => array( 'salutation' => 'Hello', 'subject' => 'world' ),
 *     'message' => '{{array[salutation]}}, {{array[subject]}}'
 *
 * * Nested Assignments
 *
 *   You can reassign the value of a nested array element:
 *
 *     'array'          => array( 'salutation' => 'Hello', 'subject' => 'world' ),
 *     'array[subject]' => 'Newman',
 *     'message'        => '{{array[salutation]}}, {{array[subject]}}'
 *
 *   You can create new nested array elements:
 *
 *     'array'             => array( 'salutation' => 'Hello', 'subject' => 'world' ),
 *     'array[salutation]' => 'Goodbye',
 *     'array[adjective]'  => 'cruel',
 *     'message'           => '{{array[salutation]}}, {{array[adjective]} {{array[subject]}}'
 *
 * * Prototypes
 *
 *   You can create arrays and use them as prototypes to reduce redundancy in your
 *   property definitions:
 *
 *     'prototype'          => array( 'salutation' => 'Hello', 'subject' => 'world'),
 *     'array1'             => '{{prototype}}',
 *     'array1[subject]'    => 'Newman',
 *     'array2'             => '{{prototype}}',
 *     'array2[salutation]' => 'Goodbye',
 *     'array2[adjective]'  => 'cruel'
 */
final class PropertyBuilder
{
	/**
	 * The final array
	 * @var array
	 */
	private $data = array();

	/**
	 * To keep track of circular references
	 * @var array
	 */
	private $refs = array();

	/**
	 * To store any errors during build()
	 * @var array
	 */
	private $errors = array();

	/**
	 * Constructor
	 *
	 * Empty for now, but I can foresee the ability to pass configuration
	 * options to the builder.
	 */
	public function __construct() { }

	/**
	 * addProperties adds an array of properties to the build.
	 *
	 * If there is a null key, an exception will be thrown.
	 * All keys are cast to (string)
	 * @param array $properties
	 * @throws Exception if $properties is null or not an array
	 *         or if there are null keys
	 */
	public function addProperties($properties)
	{
		if (null === $properties)
		{
			throw new Exception("Null Exception: properties");
		}

		if (!is_array($properties))
		{
			throw new Exception("Illegal Argument Exception: properties is not of type array");
		}

		if (count($properties) > 0)
		{
			foreach ($properties as $key => $value)
			{
				if (null === $key)
				{
					// Not sure we can reach here as null comes through as ''
					throw new Exception('Illegal Argument Exception: properties cannot contain a null key');
				}

				if (!is_string($key))
				{
					throw new Exception('Illegal Argument Exception: properties cannot contain a non-string key');
				}

				if (strlen($key) == 0)
				{
					throw new Exception('Illegal Argument Exception: properties cannot contain an empty key');
				}

				if (!$this->validateType($value))
				{
					throw new Exception("Illegal Argument Exception: Invalid value for key '{$key}'");
				}
				$this->data[$key] = $value;
			}
		}
	}

	/**
	 * addProperty adds a single key/value pair to the build.
	 *
	 * @param string $key the key to add. It will be cast to a string
	 * @param mixed $value
	 * @throws Exception if $key is null
	 * @return void
	 */
	public function addProperty($key, $value)
	{
		if (null === $key)
		{
			throw new Exception('Null Exception: key');
		}

		if (!is_string($key))
		{
			throw new Exception('Illegal Argument Exception: key must be a string');
		}

		if (strlen($key) == 0)
		{
			throw new Exception('Illegal Argument Exception: key cannot be empty');
		}

		if (!$this->validateType($value))
		{
			throw new Exception("Illegal Argument Exception: Invalid value");
		}

		$this->data[$key] = $value;
	}

	/**
	 * build builds the 'compiled' properties.
	 *
	 * @return array|bool array if there are no errors, false otherwise.
	 *         Use getErrors() to retrieve errors.
	 */
	public function build()
	{
		$this->resolveReferences();

		return (count($this->errors) > 0) ? false : $this->data;
	}

	/**
	 * getErrors retreives errors generated from build(), if any
	 *
	 * @return array array of error messages, or empty if none.
	 */
	public function getErrors()
	{
		return $this->errors;
	}

	/**
	 * setNestedValue (private)
	 *
	 * @param string $key
	 * @param array $indexes
	 * @param mixed|NULL $value
	 * @return void
	 */
	private function setNestedValue($key, $indexes, $value)
	{
		assert(null !== $key && is_string($key) && strlen($key) > 0);
		assert(null !== $indexes && is_array($indexes) && count($indexes) > 0);

		if (!$this->validateType($value))
		{
			// Don't think we can get here due to other checks in code
			throw new Exception("Invalid value");
		}

		// If key doesn't exist, init to empty array
		if (!array_key_exists($key, $this->data))
		{
			$this->data[$key] = array();
		}

		$tmpArray = &$this->data[$key];
		$tmpKey   = null;

		$errKey = $key; // Used for error reporting

		foreach ($indexes as $index)
		{
			assert(null !== $index);
			assert(is_string($index));

			// Are we tracking an array?
			if (!is_array($tmpArray))
			{
				throw new Exception("'{$errKey}' is not an array");
			}

			$errKey .= "[{$index}]"; // Used for error reporting

			// Did we have a previous key?
			if (null !== $tmpKey)
			{
				// Previous key needs to be an array
				// If key doesn' exist, create as empty array
				if (!array_key_exists($tmpKey, $tmpArray))
				{
					$tmpArray[$tmpKey] = array();
				}
				else
				// If key does exist but is not an array
				if (!is_array($tmpArray[$tmpKey]))
				{
					throw new Exception("'{$tmpKey}' is not an array");
				}
				$tmpArray = &$tmpArray[$tmpKey];
			}
			$tmpKey = $index;
		}
		assert(null !== $tmpArray);
		assert(is_array($tmpArray));
		assert(null !== $tmpKey);
		assert(is_string($tmpKey));
		$tmpArray[$tmpKey] = $value;
	}

	/**
	 * get (private)
	 *
	 * @param string $key
	 * @return mixed
	 */
	private function get($key)
	{
		assert(null !== $key && is_string($key) && strlen($key) > 0);

		list($sKey, $indexes) = $this->parseKey($key);

		// If key doesn't exist
		if (!array_key_exists($sKey, $this->data))
		{
			throw new Exception("Error getting '{$key}': '{$sKey}' is not defined");
		}

		$this->resolveKey($sKey);

		$value = $this->data[$sKey];

		// If key references indexes
		if (null !== $indexes)
		{
			assert(is_array($indexes) && count($indexes) > 0);

			$errKey = $sKey; // Used for error reporting
			foreach ($indexes as $index)
			{
				if (!is_array($value))
				{
					throw new Exception("Error getting '{$key}': '{$errKey}' is not an array");
				}
				$errKey .= "[{$index}]"; // Used for error reporting
				if (!array_key_exists($index, $value))
				{
					throw new Exception("Error getting '{$key}': '{$errKey}' is not defined");
				}
				$value = $value[$index];
			}
		}
		return $value;
	}

	/**
	 * validateType (private) Checks to see if the value contains any invalid types.
	 *
	 * Valid types are: bool, int, float, string, array
	 * Arrays may have only valid types as keys and values
	 * @param mixedtype $value
	 * @return bool true if the value is valid, false otherwise
	 */
	private function validateType($value)
	{
		if (is_scalar($value) || null === $value)
		{
			return true;
		}
		if (is_array($value))
		{
			foreach ($value as $k => $v)
			{
				// If key is null/empty or if value is invalid
				if (null === $k || (is_string($k) && strlen($k) == 0) || !$this->validateType($v))
				{
					return false;
				}
			}
			return true;
		}
		return false;
	}

	/**
	 * resolveReferences (private)
	 *
	 * @return void
	 * @throws exception if reference cannot be resolved
	 */
	private function resolveReferences()
	{
		$keys = array_keys($this->data);
		$done = false;
		while (!$done)
		{
			$errors = array(); // We only keep errors generated on the last run-through
			$done   = true;    // Stays true if we don't resolve any keys
			foreach ($keys as $i => $key)
			{
				assert(null !== $key);
				try
				{
					list($sKey, $indexes) = $this->parseKey($key);

					// If no references in key
					if (null === $indexes)
					{
						$this->resolveKey($key);
						$done = false;    // We're in for another round
						unset($keys[$i]); // Mark key as resolved
					}
					else
					{
						// If we're creating the key, then don't try to
						// resolve it.
						if (array_key_exists($sKey, $this->data))
						{
							$this->resolveKey($sKey);
						}
						try
						{
							$this->setNestedValue($sKey, $indexes, $this->data[$key]);
						}
						catch (Exception $e)
						{
							throw new Exception("Error setting '{$key}': {$e->getMessage()}");
						}
						unset($this->data[$key]); // This isn't a real key
						$this->resolveKey($sKey); // Our updated value may need resolving
						unset($keys[$i]);         // Mark key as resolved
					}
				}
				catch (Exception $e)
				{
					$errors[] = $e->getMessage();
				}
			}
		}

		// If any errors to report
		if (count($errors) > 0)
		{
			$this->errors = array_merge($this->errors, $errors);
		}
	}

	/**
	 * resolveKey (private)
	 *
	 * @param string $key
	 * @return void
	 * @throws Exception if $key cannot be resolved
	 */
	private function resolveKey($key)
	{
		assert(null !== $key && is_string($key) && strlen($key) > 0);

		if (!array_key_exists($key, $this->data))
		{
			// Don't think we can get here due to other checks in code
			throw new Exception("Key '{$key}' not defined");
		}

		if (in_array($key, $this->refs))
		{
			throw new Exception("Circular reference for key '{$key}'");
		}

		array_push($this->refs, $key);

		/* @var $err Exception */
		$err = null;
		try
		{
			$value = &$this->data[$key];
			$this->resolveValue($value);
		}
		// For lack of finally, we store the exception and throw it later
		catch (Exception $e)
		{
			$err = $e;
		}

		array_pop($this->refs);

		if (null !== $err)
		{
			throw $err;
		}
	}

	/**
	 * resolveValue (private)
	 *
	 * @param mixed $value
	 * @throws Exception
	 */
	private function resolveValue(&$value)
	{
		if (null !== $value)
		{
			if (is_string($value))
			{
				// If whole string is reference to a key, the set value to
				// resolved key value - This lets you assign an array
				$matches=array();
				if (preg_match('/^\\{\\{([^{}]*)\}\\}$/', $value, $matches) === 1)
				{
					if (isset($matches[1]) && strlen($matches[1]) > 0)
					{
						$value = $this->get($matches[1]);
					}
					else
					{
						throw new Exception('Error: Substitution with empty key "{{}}"');
					}
				}
				else
				{
					$value = preg_replace_callback('/\\{\\{([^{}]*)\}\\}/',
						function($matches)
						{
							if (isset($matches[1]) && strlen($matches[1]) > 0)
							{
								$k = $matches[1];
								$v = $this->get($k);

								if (null === $v)
								{
									throw new Exception("Error using '{$k}' in substitution: value is NULL");
								}
								if (!is_scalar($v))
								{
									throw new Exception("Error using '{$k}' in substitution: value is not scalar");
								}
								return (string)$v;
							}
							else
							{
								throw new Exception('Error: Substitution with empty key "{{}}"');
							}
						}
					, $value);
				}
			}
			else
			if (is_array($value))
			{
				foreach ($value as $k => &$v)
				{
					$this->resolveValue($v);
				}
			}
		}
	}

	/**
	 * parseKey (private) parses a key that may contain index references.
	 *
	 * @param string $key
	 * @return array (string $key, array|NULL $indexes)
	 */
	private static function parseKey($key)
	{
		assert(null !== $key && is_string($key) && strlen($key) > 0);

		$matches = array();
//		if (preg_match('/^([0-9a-zA-Z_.]+)((?:\\[[0-9a-zA-Z_.]+\\])+)*$/', $key, $matches) !== 1)
		if (preg_match('/^([^\\[\\]]+)((?:\\[[^\\[\\]]+\\])+)*$/', $key, $matches) !== 1)
		{
			throw new Exception("Invalid key: '{$key}'");
		}

		$sKey = $matches[1];
		assert(null !== $sKey && strlen($sKey) > 0);

		$indexes = isset($matches[2]) ? (preg_split('/\[|\[\]|\]/', $matches[2], null, PREG_SPLIT_NO_EMPTY) ) : null;
		assert(null === $indexes || (is_array($indexes) && count($indexes) > 0));

		return array($sKey, $indexes);
	}

}
