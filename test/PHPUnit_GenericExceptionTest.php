<?php
/**
 * Test Classes for PHPUnit_GenericException support
 *
 * @author David Farrell <DavidPFarrell@gmail.com>
 */
require_once 'PHPUnit_GenericException.php';

/**
 * GenericExceptionTraitTest tests generic exception support via trait
 * PHPUnit_GenericExceptionTrait
 */
class GenericExceptionTraitTest extends PHPUnit_Framework_TestCase
{
	use PHPUnit_GenericExceptionTrait;

	/**
	 * Simple example just showing what the annotation looks like.
	 * Your code would not throw PHPUnit_GenericException.
	 *
	 * @expectedException PHPUnit_GenericException
	 */
	public function testAnnotation()
	{
		throw new PHPUnit_GenericException();
	}

	// Simple example showing how to declare exception without annotations.
	public function testExpectedException()
	{
		$this->expectGenericException();
		throw new PHPUnit_GenericException();
	}

	// Example showing how to replace generic exception if you want
	// your function to do the 'throw'
	public function testReplaceException()
	{
		$this->expectGenericException();
		try
		{
			throw new Exception();
		}
		catch (Exception $e)
		{
			throw $this->replaceGenericException($e);
		}
	}

	// Example of wrapping your test in a callback (or closure) and let
	// us do the catch/replace/throw.
	public function testCheckFunction()
	{
		$this->expectGenericException();
		$function = function()
		{
			throw new Exception();
		};
		$this->checkFunctionForGenericException($function);
	}
}

/**
 * GenericExceptionBaseTest Tests generic exception support via extending
 * PHPUnit_GenericException_TestCase base class
 */
class GenericExceptionBaseTest extends PHPUnit_GenericException_TestCase //PHPUnit_Framework_TestCase
{
	/**
	 * Simple example just showing what the annotation looks like.
	 * Your code would not throw PHPUnit_GenericException.
	 *
	 * @expectedException PHPUnit_GenericException
	 */
	public function testAnnotation()
	{
		throw new PHPUnit_GenericException();
	}

	// Simple example showing how to declare exception without annotations.
	public function testExpectedException()
	{
		$this->expectGenericException();
		throw new PHPUnit_GenericException();
	}

	// Example showing how to replace generic exception if you want
	// your function to do the 'throw'
	public function testReplaceException()
	{
		$this->expectGenericException();
		try
		{
			throw new Exception();
		}
		catch (Exception $e)
		{
			throw $this->replaceGenericException($e);
		}
	}

	// Example of wrapping your test in a callback (or closure) and let
	// us do the catch/replace/throw.
	public function testCheckFunction()
	{
		$this->expectGenericException();
		$function = function()
		{
			throw new Exception();
		};
		$this->checkFunctionForGenericException($function);
	}
}

/**
 * GenericExceptionGlobalTest Tests generic exception support via global functions
 */
class GenericExceptionGlobalTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Simple example just showing what the annotation looks like.
	 * Your code would not throw PHPUnit_GenericException.
	 *
	 * @expectedException PHPUnit_GenericException
	 */
	public function testAnnotation()
	{
		throw new PHPUnit_GenericException();
	}

	// Simple example showing how to declare exception without annotations.
	public function testExpectedException()
	{
		expectGenericException($this);
		throw new PHPUnit_GenericException();
	}

	// Example showing how to replace generic exception if you want
	// your function to do the 'throw'
	public function testReplaceException()
	{
		expectGenericException($this);
		try
		{
			throw new Exception();
		}
		catch (Exception $e)
		{
			throw replaceGenericException($e);
		}
	}

	// Example of wrapping your test in a callback (or closure) and let
	// us do the catch/replace/throw.
	public function testCheckFunction()
	{
		expectGenericException($this);
		$function = function()
		{
			throw new Exception();
		};
		checkFunctionForGenericException($function);
	}
}
