<?php
/**
 * @author Oliver Blum <blumanski@gmail.com>
 * @date 2016-01-02
 *
 * Outgoing mails
 */

Namespace Bang\Modules\Directory\Models;

Use Bang\Helper;

Class Mail
{
	/**
	 * ErrorLog object
	 * @var object
	 */
	private $ErrorLog;
	
	/**
	 * Session instance
	 * @var object
	 */
	private $Session;
	
	/**
	 * Instance of Language object
	 * @var object
	 */
	private $Lang;

	
	public function __construct(\stdClass $di)
	{
        $this->ErrorLog 		= $di->ErrorLog;
        $this->Session	 		= $di->Session;
        $this->Lang				= $di->View->Lang;
	}
	
	/**
	 * Must be in all classes
	 * @return array
	 */
	public function __debugInfo() {
	
		$reflect	= new \ReflectionObject($this);
		$varArray	= array();
	
		foreach ($reflect->getProperties(\ReflectionProperty::IS_PUBLIC) as $prop) {
			$propName = $prop->getName();
			 
			if($propName !== 'DI') {
				//print '--> '.$propName.'<br />';
				$varArray[$propName] = $this->$propName;
			}
		}
	
		return $varArray;
	}
}