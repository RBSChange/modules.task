<?php
/**
 * commands_task_UnlockAll
 * @package modules.task.command
 */
class commands_task_UnlockAll extends commands_AbstractChangeCommand
{
	/**
	 * @return String
	 */
	function getUsage()
	{
		return "(--reset | --show)";
	}

	/**
	 * @return String
	 */
	function getDescription()
	{
		return "Unlock all tasks --reset to unlock and rerun tasks --show to only view which tasks is locked";
	}

	/**
	 * @return String[]
	 */
	function getOptions()
	{
		$options = array();
		$options[] = "--reset";
		$options[] = "--show";
		return $options;
	}

	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		$this->message("== UnlockAll ==");

		$doReset = array_key_exists("reset", $options);
		$onlyShow = array_key_exists("show", $options);
		$tasks = array();
		
		$tasks = task_PlannedtaskService::getInstance()->createQuery()
				->add(Restrictions::in('executionStatus',array(task_PlannedtaskService::STATUS_RUNNING, task_PlannedtaskService::STATUS_LOCKED)))->find();
		if ($onlyShow == false)
		{	
			if (count($tasks) > 0)
			{
				foreach ($tasks as $task)
				{
					if ($task instanceof task_persistentdocument_plannedtask)
					{
                                            if ($task->getExecutionStatus() == task_PlannedtaskService::STATUS_LOCKED)
                                            {
                                                $task->getDocumentService()->unlock($task);
                                            }
                                            else if ($task->getExecutionStatus() == task_PlannedtaskService::STATUS_RUNNING)
                                            {
                                                 $task->getDocumentService()->error($task, LocaleService::getInstance()->transBO('m.task.document.plannedtask.bo-cancel'));
                                            }
                                                 
                                                
					}
				}
			}
			else
			{
				$this->quitOk("No task is running");
			}
		}
		else 
		{
			if (count($tasks) > 0)
			{
				foreach ($tasks as $task)
				{
					$nodeName = $task->getNode() != NULL ? $task->getNode() : "";
					$this->message($task->getSystemtaskclassname()." ".$nodeName, c_Changescript::FG_MAGENTA);
				}
			}
			else 
			{
				$this->message("No task is running");
			}
		}
	}
	
}