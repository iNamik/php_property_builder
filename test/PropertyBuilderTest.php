<?php
/*
 * iNamik Property Builder for PHP
 * Copyright (C) 2012 David Farrell (davidpfarrell@yahoo.com)
 *
 * Licensed under MIT or GPLv3, see LICENSE.txt
 */
namespace inamik;

require_once 'PHPUnit_GenericException.php';
require_once 'inamik/PropertyBuilder.php';

use \stdClass;
use \Exception;
use PHPUnit_Framework_TestCase;

use PHPUnit_GenericExceptionTrait;

use inamik\PropertyBuilder;


class PropertyBuilderTest extends PHPUnit_Framework_TestCase
{
	// Bring in support for generic exceptions
	use PHPUnit_GenericExceptionTrait;

	public function testSimpleProperty()
	{
		$pb = new PropertyBuilder();

		$pb->addProperty('foo', 'bar');

		$p = $pb->build();

		if (false === $p)
		{
			$p = $pb->getErrors();
		}

		$this->assertSame(array('foo' => 'bar'), $p);
	}

	public function testSimpleProperties()
	{
		$pb = new PropertyBuilder();

		$pb->addProperties(array('foo' => 'bar'));

		$p = $pb->build();

		if (false === $p)
		{
			$p = $pb->getErrors();
		}

		$this->assertSame(array('foo' => 'bar'), $p);
	}

	public function testScalarArrayProperty()
	{
		$pb = new PropertyBuilder();

		$pb->addProperty('foo', array('bar', 'baz'));

		$p = $pb->build();

		if (false === $p)
		{
			$p = $pb->getErrors();
		}

		$this->assertSame(array('foo' => array(0 => 'bar', 1 => 'baz')), $p);
	}

	public function testAssociativeArrayProperty()
	{
		$pb = new PropertyBuilder();

		$pb->addProperty('foo', array('bar' => 'bar', 'baz' => 'baz'));

		$p = $pb->build();

		if (false === $p)
		{
			$p = $pb->getErrors();
		}

		$this->assertSame(array('foo' => array('bar' => 'bar', 'baz' => 'baz')), $p);
	}

	public function testSimpleSubstitution()
	{
		$pb = new PropertyBuilder();

		$pb->addProperty('foo', 'bar');
		$pb->addProperty('fooref', '{{foo}}');

		$p = $pb->build();

		if (false === $p)
		{
			$p = $pb->getErrors();
		}

		$this->assertSame(array('foo' => 'bar', 'fooref' => 'bar'), $p);
	}

	public function testNestedSubstitution()
	{
		$pb = new PropertyBuilder();

		$pb->addProperty('foo', array('bar' => 'baz'));
		$pb->addProperty('fooref', '{{foo[bar]}}');

		$p = $pb->build();

		if (false === $p)
		{
			$p = $pb->getErrors();
		}

		$this->assertSame(array('foo' => array('bar' => 'baz'), 'fooref' => 'baz'), $p);
	}

	public function testMultipleSubstitution()
	{
		$pb = new PropertyBuilder();

		$pb->addProperty('foo', 'bar');
		$pb->addProperty('baz', 'bam');
		$pb->addProperty('fooref', '{{foo}} {{baz}}');

		$p = $pb->build();

		if (false === $p)
		{
			$p = $pb->getErrors();
		}

		$this->assertSame(array('foo' => 'bar', 'baz' => 'bam', 'fooref' => 'bar bam'), $p);
	}

	public function testNestedCreateString()
	{
		$pb = new PropertyBuilder();

		$pb->addProperty('foo[bar]', 'baz');

		$p = $pb->build();

		if (false === $p)
		{
			$p = $pb->getErrors();
		}

		$this->assertSame(array('foo' => array('bar' => 'baz')), $p);
	}

	public function testNestedCreateArray()
	{
		$pb = new PropertyBuilder();

		$pb->addProperty('foo[bar]', array('baz' => 'bam'));

		$p = $pb->build();

		if (false === $p)
		{
			$p = $pb->getErrors();
		}

		$this->assertSame(array('foo' => array('bar' => array('baz' => 'bam'))), $p);
	}

	public function testMultiLevelNestedCreateString()
	{
		$pb = new PropertyBuilder();

		$pb->addProperty('foo[bar][baz]', 'bam');

		$p = $pb->build();

		if (false === $p)
		{
			$p = $pb->getErrors();
		}

		$this->assertSame(array('foo' => array('bar' => array('baz' => 'bam'))), $p);
	}

	public function testPrototype()
	{
		$pb = new PropertyBuilder();

		$pb->addProperty('array', array('foo' => 'bar'));
		$pb->addProperty('copy', '{{array}}');

		$p = $pb->build();

		if (false === $p)
		{
			$p = $pb->getErrors();
		}

		$this->assertSame(array('array' => array('foo' => 'bar'), 'copy' => array('foo' => 'bar')), $p);
	}

	public function testNestedAssign()
	{
		$pb = new PropertyBuilder();

		$pb->addProperty('array', array('foo' => 'bar'));
		$pb->addProperty('array[foo]', 'baz');

		$p = $pb->build();

		if (false === $p)
		{
			$p = $pb->getErrors();
		}

		$this->assertSame(array('array' => array('foo' => 'baz')), $p);
	}

	public function testMultiLevelNestedAssign()
	{
		$pb = new PropertyBuilder();

		$pb->addProperty('array', array('foo' => array('bar' => 'baz')));
		$pb->addProperty('array[foo][bar]', 'bam');

		$p = $pb->build();

		if (false === $p)
		{
			$p = $pb->getErrors();
		}

		$this->assertSame(array('array' => array('foo' => array('bar' => 'bam'))), $p);
	}

	public function testNestedAssignFromWithSubstitution()
	{
		$pb = new PropertyBuilder();

		$pb->addProperty('string', 'baz');
		$pb->addProperty('array', array('foo' => 'bar'));
		$pb->addProperty('array[foo]', '{{string}}');

		$p = $pb->build();

		if (false === $p)
		{
			$p = $pb->getErrors();
		}

		$this->assertSame(array('string' => 'baz', 'array' => array('foo' => 'baz')), $p);
	}

	public function testSubstitutionBeforeNestedAssign()
	{
		$pb = new PropertyBuilder();

		$pb->addProperty('array', array('foo' => 'bar'));
		$pb->addProperty('string', '{{array[foo]}}');
		$pb->addProperty('array[foo]', 'baz');

		$p = $pb->build();

		if (false === $p)
		{
			$p = $pb->getErrors();
		}

		$this->assertSame(array('array' => array('foo' => 'baz'), 'string' => 'bar'), $p);
	}

	public function testPrototypeOfNullValue()
	{
		$pb = new PropertyBuilder();

		$pb->addProperty('null', null);
		$pb->addProperty('string', '{{null}}');

		$p = $pb->build();

		if (false === $p)
		{
			$p = $pb->getErrors();
		}

		$this->assertSame(array('null' => null, 'string' => null), $p);
	}

	public function testPrototypeOfNoKey()
	{
		$pb = new PropertyBuilder();

		$pb->addProperty('string', '{{}}');

		$p = $pb->build();

		if (false === $p)
		{
			$p = $pb->getErrors();
		}

		$this->assertSame(array(0 => 'Error: Substitution with empty key "{{}}"'), $p);
	}

	public function testSubstitutionOfNoKey()
	{
		$pb = new PropertyBuilder();

		$pb->addProperty('string', '"{{}}"');

		$p = $pb->build();

		if (false === $p)
		{
			$p = $pb->getErrors();
		}

		$this->assertSame(array(0 => 'Error: Substitution with empty key "{{}}"'), $p);
	}

	public function testSubstitutionOfNullValue()
	{
		$pb = new PropertyBuilder();

		$pb->addProperty('null', null);
		$pb->addProperty('string', '"{{null}}"');

		$p = $pb->build();

		if (false === $p)
		{
			$p = $pb->getErrors();
		}

		$this->assertSame(array(0 => "Error using 'null' in substitution: value is NULL"), $p);
	}

	public function testSubstitutionOfArrayValue()
	{
		$pb = new PropertyBuilder();

		$pb->addProperty('array', array('foo' => 'bar'));
		$pb->addProperty('string', '"{{array}}"');

		$p = $pb->build();

		if (false === $p)
		{
			$p = $pb->getErrors();
		}

		$this->assertSame(array(0 => "Error using 'array' in substitution: value is not scalar"), $p);
	}

	public function testInvalidCharacterKey()
	{
		$pb = new PropertyBuilder();

		$pb->addProperty("foo[", 'bar');

		$p = $pb->build();

		if (false === $p)
		{
			$p = $pb->getErrors();
		}

		$this->assertSame(array(0 => "Invalid key: 'foo['"), $p);
	}

	public function testSingleCircularReference()
	{
		$pb = new PropertyBuilder();

		$pb->addProperty('foo', '{{foo}}');

		$p = $pb->build();

		if (false === $p)
		{
			$p = $pb->getErrors();
		}

		$this->assertSame(array(0 => "Circular reference for key 'foo'"), $p);
	}

	public function testDoubleCircularReference()
	{
		$pb = new PropertyBuilder();

		$pb->addProperty('foo', '{{bar}}');
		$pb->addProperty('bar', '{{foo}}');

		$p = $pb->build();

		if (false === $p)
		{
			$p = $pb->getErrors();
		}

		$this->assertSame(array(0 => "Circular reference for key 'foo'", 1 => "Circular reference for key 'bar'"), $p);
	}

	public function testMissingReference()
	{
		$pb = new PropertyBuilder();

		$pb->addProperty('foo', '{{bar}}');

		$p = $pb->build();

		if (false === $p)
		{
			$p = $pb->getErrors();
		}

		$this->assertSame(array(0 => "Error getting 'bar': 'bar' is not defined"), $p);
	}

	public function testMissingMultiLevelReference()
	{
		$pb = new PropertyBuilder();

		$pb->addProperty('array', array('bar' => 'baz'));
		$pb->addProperty('foo', '{{array[bam]}}');

		$p = $pb->build();

		if (false === $p)
		{
			$p = $pb->getErrors();
		}

		$this->assertSame(array(0 => "Error getting 'array[bam]': 'array[bam]' is not defined"), $p);
	}

	public function testBadNestedAssign()
	{
		$pb = new PropertyBuilder();

		$pb->addProperty('string', 'foo');
		$pb->addProperty('string[bar]', 'baz');

		$p = $pb->build();

		if (false === $p)
		{
			$p = $pb->getErrors();
		}

		$this->assertSame(array(0 => "Error setting 'string[bar]': 'string' is not an array"), $p);
	}

	public function testMultiLevelBadNestedAssign()
	{
		$pb = new PropertyBuilder();

		$pb->addProperty('array', array('foo' => 'bar'));
		$pb->addProperty('array[foo][baz]', 'blah');

		$p = $pb->build();

		if (false === $p)
		{
			$p = $pb->getErrors();
		}

		$this->assertSame(array(0 => "Error setting 'array[foo][baz]': 'foo' is not an array"), $p);
	}

	public function testBadArrayReference()
	{
		$pb = new PropertyBuilder();

		$pb->addProperty('string', 'foo');
		$pb->addProperty('bar', '{{string[foo]}}');

		$p = $pb->build();

		if (false === $p)
		{
			$p = $pb->getErrors();
		}

		$this->assertSame(array(0 => "Error getting 'string[foo]': 'string' is not an array"), $p);
	}

	public function testBadMultiLevelArrayReference()
	{
		$pb = new PropertyBuilder();

		$pb->addProperty('array', array('string' => 'foo'));
		$pb->addProperty('bar', '{{array[string][foo]}}');

		$p = $pb->build();

		if (false === $p)
		{
			$p = $pb->getErrors();
		}

		$this->assertSame(array(0 => "Error getting 'array[string][foo]': 'array[string]' is not an array"), $p);
	}

	/**
	 * @expectedException PHPUnit_GenericException
	 * @expectedExceptionMessage Null Exception: key
	 */
	public function testNullKey()
	{
		$pb = new PropertyBuilder();

		$test = function() use($pb)
		{
			$pb->addProperty(null, 'null');
		};

		$this->checkFunctionForGenericException($test);
	}

	/**
	 * @expectedException PHPUnit_GenericException
	 * @expectedExceptionMessage Illegal Argument Exception: key cannot be empty
	 */
	public function testEmptyKey()
	{
		$pb = new PropertyBuilder();

		$test = function() use($pb)
		{
			$pb->addProperty('', 'empty');
		};

		$this->checkFunctionForGenericException($test);
	}

	/**
	 * @expectedException PHPUnit_GenericException
	 * @expectedExceptionMessage Illegal Argument Exception: key must be a string
	 */
	public function testInvalidKey()
	{
		$pb = new PropertyBuilder();

		$test = function() use($pb)
		{
			$pb->addProperty(0, 'zero');
		};

		$this->checkFunctionForGenericException($test);
	}

	/**
	 * @expectedException PHPUnit_GenericException
	 * @expectedExceptionMessage Null Exception: properties
	 */
	public function testNullProperties()
	{
		$pb = new PropertyBuilder();

		$test = function() use($pb)
		{
			$pb->addProperties(null);
		};

		$this->checkFunctionForGenericException($test);
	}

	/**
	 * @expectedException PHPUnit_GenericException
	 * @expectedExceptionMessage Illegal Argument Exception: properties is not of type array
	 */
	public function testInvalidProperties()
	{
		$pb = new PropertyBuilder();

		$test = function() use($pb)
		{
			$pb->addProperties('not_an_array');
		};

		$this->checkFunctionForGenericException($test);
	}

	/**
	 * @expectedException PHPUnit_GenericException
	 * @expectedExceptionMessage Illegal Argument Exception: properties cannot contain an empty key
	 */
	public function testNullKeyProperties()
	{
		$pb = new PropertyBuilder();

		$test = function() use($pb)
		{
			$pb->addProperties(array(null => 'null'));
		};

		$this->checkFunctionForGenericException($test);
	}

	/**
	 * @expectedException PHPUnit_GenericException
	 * @expectedExceptionMessage Illegal Argument Exception: properties cannot contain an empty key
	 */
	public function testEmptyKeyProperties()
	{
		$pb = new PropertyBuilder();

		$test = function() use($pb)
		{
			$pb->addProperties(array('' => 'empty'));
		};

		$this->checkFunctionForGenericException($test);
	}

	/**
	 * @expectedException PHPUnit_GenericException
	 * @expectedExceptionMessage Illegal Argument Exception: properties cannot contain a non-string key
	 */
	public function testInvalidKeyProperties()
	{
		$pb = new PropertyBuilder();

		$test = function() use($pb)
		{
			$pb->addProperties(array(0 => 'zero'));
		};

		$this->checkFunctionForGenericException($test);
	}

	/**
	 * @expectedException PHPUnit_GenericException
	 * @expectedExceptionMessage Illegal Argument Exception: Invalid value
	 */
	public function testInvalidValue()
	{
		$pb = new PropertyBuilder();

		$test = function() use($pb)
		{
			$pb->addProperty('invalid', new stdClass());
		};

		$this->checkFunctionForGenericException($test);
	}

	/**
	 * @expectedException PHPUnit_GenericException
	 * @expectedExceptionMessage Illegal Argument Exception: Invalid value
	 */
	public function testInvalidArrayValue()
	{
		$pb = new PropertyBuilder();

		$test = function() use($pb)
		{
			$pb->addProperty('invalid', array(new stdClass()));
		};

		$this->checkFunctionForGenericException($test);
	}

	/**
	 * @expectedException PHPUnit_GenericException
	 * @expectedExceptionMessage Illegal Argument Exception: Invalid value for key 'invalid'
	 */
	public function testInvalidValueProperties()
	{
		$pb = new PropertyBuilder();

		$test = function() use($pb)
		{
			$pb->addProperties(array('invalid' => new stdClass()));
		};

		$this->checkFunctionForGenericException($test);
	}

	/**
	 * @expectedException PHPUnit_GenericException
	 * @expectedExceptionMessage Illegal Argument Exception: Invalid value for key 'invalid'
	 */
	public function testInvalidArrayValueProperties()
	{
		$pb = new PropertyBuilder();

		$test = function() use($pb)
		{
			$pb->addProperties(array('invalid' => array(new stdClass())));
		};

		$this->checkFunctionForGenericException($test);
	}

}
