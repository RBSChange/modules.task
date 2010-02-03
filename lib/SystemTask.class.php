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
}

