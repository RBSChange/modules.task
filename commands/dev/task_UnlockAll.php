<?php
/**
 * commands_task_UnlockAll
 * @package modules.task.command
 */
class commands_task_UnlockAll extends c_ChangescriptCommand
{
	/**
	 * @return String
	 * @example "<moduleName> <name>"
	 */
	function getUsage()
	{
		return "(--reset | --show)";
	}

	/**
	 * @return String
	 * @example "initialize a document"
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
				->add(Restrictions::eq('executionStatus', 'running'))->find();
		if ($onlyShow == false)
		{	
			if (count($tasks) > 0)
			{
				foreach ($tasks as $task)
				{
					if ($task instanceof task_persistentdocument_plannedtask)
					{
						$this->unlockTask($task, $doReset);
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
					$this->log($task->getSystemtaskclassname()." ".$nodeName);
				}
			}
			else 
			{
				$this->message("No task is running");
			}
		}
	}
	
	/**
	 * @param task_persistentdocument_plannedtask $task
	 * @param Boolean $doReset
	 */
	private function unlockTask($task, $doReset = false)
	{
		if ($task->getIsrunning())
		{
			$task->setIsrunning(false);
			if ($doReset == true)
			{
				$task->setNextrundate(date_Calendar::now());
			}
			$task->save();
			$action = ($doReset == true ? 'reset' : 'unlock') . '.plannedtask';
			UserActionLoggerService::getInstance()->addUserDocumentEntry('system',$action, $task, array(), 'task');
			$this->quitOk("Task \"".$task->getSystemtaskclassname()."\" ".($doReset == true ? 'reset' : 'unlock')."ed");
		}
		else
		{
			$this->quitError("Task \"".$task->getSystemtaskclassname()."\" is not locked");
		}
	}
}