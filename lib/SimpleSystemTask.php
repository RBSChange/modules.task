<?php
class task_SimpleSystemTask implements task_SystemTask
{
	private $parametersArray = array();
	
	
	/**
	 * @var task_persistentdocument_plannedtask
	 */
	protected  $plannedTask;
	
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
	 * @see task_SystemTask::setPlannedTask()
	 *
	 * @param task_persistentdocument_plannedtask $plannedTask
	 */
	function setPlannedTask($plannedTask)
	{
		$this->plannedTask = $plannedTask;
	}

	/**
	 * @see task_SystemTask::setParameterString()
	 * This implementation assumes parameter string is stored as a serialized array;
	 * 
	 * @param string $parameterString
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
	 * @param string $name
	 * @return boolean
	 */
	final function hasParameter($name)
	{
		return isset($this->parametersArray[$name]);
	}
	
	/**
	 * @param string $name
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