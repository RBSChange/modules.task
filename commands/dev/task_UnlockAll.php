<?php
/**
 * commands_task_UnlockAll
 * @package modules.task.command
 */
class commands_task_UnlockAll extends c_ChangescriptCommand
{
	/**
	 * @return string
	 */
	function getUsage()
	{
		return "[--show]";
	}

	/**
	 * @return string
	 */
	function getDescription()
	{
		return "Unlock all tasks --show to only view which tasks is locked";
	}

	/**
	 * @return string[]
	 */
	function getOptions()
	{
		return array("--show");
	}

	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		$this->message("== Unlock All ==");
		$this->loadFramework();
		
		$doReset = array_key_exists("reset", $options);
		$onlyShow = array_key_exists("show", $options);
		$tasks = task_PlannedtaskService::getInstance()->createQuery()
			->add(Restrictions::eq('executionStatus', task_PlannedtaskService::STATUS_LOCKED))->find();
		
		if ($onlyShow == false)
		{	
			if (count($tasks) > 0)
			{
				foreach ($tasks as $task)
				{
					/* @var $task task_persistentdocument_plannedtask */
					$task->getDocumentService()->unlock($task);
				}
			}
			else
			{
				$this->quitOk("No task is locked");
			}
		}
		else 
		{
			if (count($tasks) > 0)
			{
				foreach ($tasks as $task)
				{
					$nodeName = $task->getNode() != NULL ? $task->getNode() : "";
					$this->log($task->getSystemtaskclassname()." ".$nodeName);
				}
			}
			else 
			{
				$this->message("No task is locked");
			}
		}
	}
}