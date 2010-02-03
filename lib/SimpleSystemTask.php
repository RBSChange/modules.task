<?php
class task_SimpleSystemTask implements task_SystemTask
{
	private $parametersArray = array();
	
	/**
	 * @see task_SystemTask::run()
	 *
	 */
	final function run()
	{		
		$this->execute();
	}
	
	/**
	 * Implement this in subclasses
	 *
	 */
	protected function execute()
	{
	}
	
	/**
	 * @see task_SystemTask::setParameterString()
	 * This implementation assumes parameter string is stored as a serialized array;
	 * 
	 * @param String $parameterString
	 */
	final function setParameterString($parameterString)
	{
		if (is_string($parameterString))
		{
			if (($result = unserialize($parameterString)) !== false)
			{
				$this->parametersArray = $result;
			}
		}
	}
	
	/**
	 * @param String $name
	 * @return Boolean
	 */
	final function hasParameter($name)
	{
		return isset($this->parametersArray[$name]);
	}
	
	/**
	 * @param String $name
	 * @param Mixed $defaultValue
	 * @return Mixed
	 */
	final function getParameter($name, $defaultValue = null)
	{
		if ($this->hasParameter($name))
		{
			return $this->parametersArray[$name];
		}
		return $defaultValue;
	}
}