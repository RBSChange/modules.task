<?php
/**
 * commands_task_Unlock
 * @package modules.task.command
 */
class commands_task_Unlock extends commands_AbstractChangeCommand
{
	/**
	 * @return String
	 */
	function getUsage()
	{
		return "<taskToUnlock> [nodeName] [--reset]";
	}

	/**
	 * @return String
	 */
	function getDescription()
	{
		return "Unlock a task, add nodeName if your task is on multiple nodes  --reset to unlock and rerun task";
	}
	
	/**
	 * This method is used to handle auto-completion for this command.
	 * @param Integer $completeParamCount the parameters that are already complete in the command line
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @return String[] or null
	 */
	function getParameters($completeParamCount, $params, $options, $current)
	{
		$components = array();
		if ($completeParamCount == 0)
		{
			$components = task_PlannedtaskService::getInstance()->createQuery()->add(Restrictions::eq('isrunning', '1'))
				->setProjection(Projections::groupProperty('systemtaskclassname'))->findColumn('systemtaskclassname');		
		}
		else if ($completeParamCount == 1)
		{
			$components = task_PlannedtaskService::getInstance()->createQuery()->add(Restrictions::eq('isrunning', '1'))
				->add(Restrictions::eq('systemtaskclassname', $params[0]))
				->setProjection(Projections::groupProperty('node'))->findColumn('node');	
		}
			
		return $components;
	}
	
	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @return boolean
	 */
	protected function validateArgs($params, $options)
	{
		if (f_util_ArrayUtils::isEmpty($params))
		{
			$result = false;
		}
		else 
		{
			$taskName = task_PlannedtaskService::getInstance()->createQuery()->add(Restrictions::eq('isrunning', '1'))
					->setProjection(Projections::groupProperty('systemtaskclassname'))->findColumn('systemtaskclassname');
	
			$nodeName = task_PlannedtaskService::getInstance()->createQuery()->add(Restrictions::eq('isrunning', '1'))
					->add(Restrictions::eq('systemtaskclassname', $params[0]))
					->setProjection(Projections::groupProperty('node'))->findColumn('node');			
					
			if (in_array($params[0], $taskName))
			{
				$result = true; 
			}
			else
			{
				$result = false;
			}
		}
		return $result;
	}

	/**
	 * @return String[]
	 */
	function getOptions()
	{
		$options = array();
		$options[] = "--reset";
		return $options;
	}

	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		$this->message("== Unlock ==");

		$taskName = array_key_exists("0",$params) ? $params[0] : NULL;
		
		$nodeName = array_key_exists("1",$params) ? $params[1] : NULL;
		
		$doReset = array_key_exists("reset", $options);
		
		$tasks = task_PlannedtaskService::getInstance()->createQuery()->add(Restrictions::eq('isrunning','1'))
				->add(Restrictions::eq('systemtaskclassname',$taskName))->find();
		$unlockCount = 0;
		if (count($tasks) > 0)
		{
			foreach ($tasks as $task) 
			{
				if ($task instanceof task_persistentdocument_plannedtask) 
				{
					if ($nodeName === null || $task->getNode() == $nodeName)
					{
						$unlockCount++;
						$this->unlockTask($task, $doReset);
					}
				}
				else 
				{
					$this->quitError("\"".$taskName."\" is corrupt");
				}
			}
		}
		
		
		if (!$unlockCount)
		{
			if ($nodeName === null)
			{
				$this->quitError("\"".$taskName."\" is not running or doesn't exist");
			}
			else
			{
				$this->quitError("\"".$taskName."\" is not running or doesn't exist for \"".$nodeName."\" node name");
			}
		}
	}
	
	/**
	 * @param task_persistentdocument_plannedtask $task
	 * @param Boolean $doReset
	 */
	private function unlockTask($task, $doReset = false)
	{
		if ($task->isLocked())
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
			$this->quitError("Task \"".$task->getSystemtaskclassname()."\" is not locked, the duration given to its work is set to ".$task->getMaxduration()." minutes");
		}
	}
}