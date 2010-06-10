<?php

Abstract Class Kohana_Unittest_TestCase extends PHPUnit_Framework_TestCase
{
	/**
	 * A backup of the environment
	 * @var array
	 */
	protected $environmentBackup = array();

	/**
	 * A default set of environment to be applied before each test
	 * @var array
	 */
	protected $environmentDefault = array();

	/**
	 * Creates a predefined enviroment using the default environment
	 *
	 * Extending classes that have their own setUp() should call
	 * parent::setUp()
	 */
	function setUp()
	{
		$this->setEnvironment($this->environmentDefault);
	}

	/**
	 * Restores the original environment overriden with setEnvironment()
	 *
	 * Extending classes that have their own tearDown()
	 * should call parent::tearDown()
	 */
	function tearDown()
	{
		$this->setEnvironment($this->environmentBackup);
	}

	/**
	 * Allows easy setting & backing up of conviroment config
	 *
	 * Option types are checked in the following order:
	 *
	 * * Server Var
	 * * Static Variable
	 * * Config option
	 *
	 * @param array $environment List of environment to set
	 */ 
	function setEnvironment(array $environment)
	{
		if( ! count($environment))
			return FALSE;

		foreach($environment as $option => $value)
		{
			// Handle changing superglobals
			if(in_array($option, array('_GET', '_POST', '_SERVER')))
			{
				// For some reason we need to do this in order to change the superglobals
				global $$option;

				// PHPUnit makes a backup of superglobals automatically
				$$option = $GLOBALS[$option] = $value;
			}
			// If this is a static property i.e. Html::$windowed_urls
			elseif(strpos($option, '::$') !== FALSE)
			{
				list($class, $var) = explode('::$', $option, 2);

				$class = new ReflectionClass($class);

				if( ! array_key_exists($option, $this->environmentBackup))
				{
					$this->environmentBackup[$option] = $class->getStaticPropertyValue($var);
				}

				$class->setStaticPropertyValue($var, $value);
			}
			// If this is an enviroment variable
			elseif(preg_match('/^[A-Z_-]+$/', $option) OR isset($_SERVER[$option]))
			{
				// We don't need to backup envr. vars, phpunit automatically does it
				$_SERVER[$option] = $value;
			}
			// Else we assume this is a config option
			else
			{
				if( ! array_key_exists($option, $this->environmentBackup))
				{
					$this->environmentBackup[$option] = Kohana::config($option);
				}
				
				list($group, $var) = explode('.', $option, 2);

				Kohana::config($group)->set($var, $value);
			}
		}
	}


}
