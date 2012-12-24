<?php
/**
 * Generic Exception Support for PHPUnit < v3.7
 *
 * These classes help you test generic exceptions in PHPUnit versions less than
 * v3.7, avoiding the 'You must not expect the generic exception class' error.
 *
 * We create a new exception to represent the generic exception, and provide
 * multiple ways to incorporate the functionality into your tests:
 *
 * - Trait : You can use PHPUnit_GenericExceptionTrait
 *
 * - Base Class : Your TestCase can extend PHPUnit_GenericException_TestCase
 *
 * - Global Functions : If you can't use traits or the base class
 *
 * NOTE: Each technique is a full implementation, so you can remove any you
 *       don't want from your version of this file
 *       (i.e. you can delete the trait definition if your PHP version doesn't support it)
 *
 * Examples : See PHPUnit_GenericExceptionTest for examples
 *
 * @author David Farrell <DavidPFarrell@gmail.com>
 */

/**
 * PHPUnit_GenericException Representation of generic exception
 */
class PHPUnit_GenericException extends Exception
{
	/**
	 * Constructor
	 *
	 * We flesh this out as a duplicate of parent constructor to aid in
	 * IDE code-completion.
	 *
	 * @param message[optional]
	 * @param code[optional]
	 * @param previous[optional]
	 */
	public function __construct ($message = null, $code = null, $previous = null)
	{
		parent::__construct($message, $code, $previous);
	}
}

/**
 * PHPUnit_GenericExceptionSupport Interface definition for generic exception support
 *
 * This class serves largely to document the functions, as they are duplicated
 * across several implementations.
 */
interface PHPUnit_GenericExceptionSupport
{
	/**
	 * expectGenericException
	 *
	 * Use this function if your test cases are using setExpectedException()
	 * instead of using annotations.
	 *
	 * @param string $exceptionMessage[optional]
	 * @param int $exceptionCode[optional]
	 */
	function expectGenericException($exceptionMessage = null, $exceptionCode = null);

	/**
	 * replaceGenericException Replace Exception for PHPUnit_GenericException
	 *
	 * Returns an Exception suitable for throwing, swapping an instance of
	 * the generic exception with PHPUnit_GenericException
	 *
	 * @param Exception $e
	 * @return Exception|PHPUnit_GenericException
	 */
	function replaceGenericException(Exception $e);

	/**
	 * checkFunctionForGenericException
	 *
	 * Calls the specified function, replacing any generic exceptions thrown
	 * with PHPUnit_GenericException
	 *
	 * @param function $function
	 */
	function checkFunctionForGenericException($function);
}

/**
 * PHPUnit_GenericExceptionTrait
 *
 * For PHP versions that support traits, you can use this trait to exhibit
 * generic exception support without cluttering your class hierarchy
 *
 * NOTE: If your PHP version supports abstract functions on traits,
 * consider uncommenting the setExpectedException() declaration to help ensure
 * you are using the trait on an appropriate class
 */
trait PHPUnit_GenericExceptionTrait //implements PHPUnit_GenericExceptionSupport
{
	/*
	 * If your PHP version supports abstract functions on traits,
	 * consider uncommenting this, to help ensure you are using the trait
	 * on an appropriate class
	 */
//	public abstract function setExpectedException($exceptionName, $exceptionMessage, $exceptionCode);

	public function expectGenericException($exceptionMessage = null, $exceptionCode = null)
	{
		$this->setExpectedException('PHPUnit_GenericException', $exceptionMessage, $exceptionCode);
	}

	public function replaceGenericException(Exception $e)
	{
		if ('Exception' == get_class($e))
		{
			$e = new PHPUnit_GenericException($e->getMessage(), $e->getCode(), $e->getPrevious());
		}
		return $e;
	}

	public function checkFunctionForGenericException($function)
	{
		try
		{
			$function();
		}
		catch (Exception $e)
		{
			throw replaceGenericException($e);
		}
	}
}

/**
 * PHPUnit_GenericException_TestCase
 *
 * For PHP versions that don't support traits, you can extend this class to
 * exhibit generic exception support.
 */
abstract class PHPUnit_GenericException_TestCase extends PHPUnit_Framework_TestCase implements PHPUnit_GenericExceptionSupport
{
	public function expectGenericException($exceptionMessage = null, $exceptionCode = null)
	{
		$this->setExpectedException('PHPUnit_GenericException', $exceptionMessage, $exceptionCode);
	}

	public function replaceGenericException(Exception $e)
	{
		if ('Exception' == get_class($e))
		{
			$e = new PHPUnit_GenericException($e->getMessage(), $e->getCode(), $e->getPrevious());
		}
		return $e;
	}

	public function checkFunctionForGenericException($function)
	{
		try
		{
			$function();
		}
		catch (Exception $e)
		{
			throw replaceGenericException($e);
		}
	}
}

/******************************************************************************
 * Global Functions
 *
 * If your PHP version does not support traits, and if you don't want to
 * (or can't) extend the abstract class, you can use these global functions
 ******************************************************************************/

function expectGenericException(PHPUnit_Framework_TestCase $test, $exceptionMessage = null, $exceptionCode = null)
{
	$test->setExpectedException('PHPUnit_GenericException', $exceptionMessage, $exceptionCode);
}

function replaceGenericException(Exception $e)
{
	if ('Exception' == get_class($e))
	{
		$e = new PHPUnit_GenericException($e->getMessage(), $e->getCode(), $e->getPrevious());
	}
	return $e;
}

function checkFunctionForGenericException($function)
{
	try
	{
		$function();
	}
	catch (Exception $e)
	{
		throw replaceGenericException($e);
	}
}
