<?php
/**
 * @package modules.task
 */
class commands_task_UnlockAll extends c_ChangescriptCommand
{
	/**
	 * @return string
	 */
	public function getUsage()
	{
		return "[--show]";
	}

	/**
	 * @return string
	 */
	public function getDescription()
	{
		return "Unlock all tasks --show to only view which tasks is locked";
	}

	/**
	 * @return string[]
	 */
	public function getOptions()
	{
		return array("--show");
	}

	/**
	 * @param string[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	public function _execute($params, $options)
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