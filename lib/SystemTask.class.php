<?php
interface task_SystemTask
{
	/**
	 * @throws Exception
	 */
	function run();
	
	/**
	 * Enter description here...
	 *
	 * @param unknown_type $parameterString
	 */
	function setParameterString($parameterString);
	
	/**
	 * @param task_persistentdocument_plannedtask $plannedTask
	 */
	function setPlannedTask($plannedTask);
}