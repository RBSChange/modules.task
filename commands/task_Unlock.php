<?php
/**
 * commands_task_Unlock
 * @package modules.task.command
 */
class commands_task_Unlock extends c_ChangescriptCommand
{
	/**
	 * @return string
	 */
	public function getUsage()
	{
		return "<taskClassName> [nodeName]";
	}
	
	/**
	 * @return string
	 */
	public function getDescription()
	{
		return "Unlock a task by class name. Specify nodeName if you want to unlock it on only one node.";
	}
	
	/**
	 * This method is used to handle auto-completion for this command.
	 * @param integer $completeParamCount the parameters that are already complete in the command line
	 * @param string[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @return string[] or null
	 */
	public function getParameters($completeParamCount, $params, $options, $current)
	{
		$components = array();
		if ($completeParamCount == 0)
		{
			$components = task_PlannedtaskService::getInstance()->createQuery()
				->add(Restrictions::eq('executionStatus', task_PlannedtaskService::STATUS_LOCKED))
				->setProjection(Projections::groupProperty('systemtaskclassname'))->findColumn('systemtaskclassname');		
		}
		else if ($completeParamCount == 1)
		{
			$components = task_PlannedtaskService::getInstance()->createQuery()
				->add(Restrictions::eq('executionStatus', task_PlannedtaskService::STATUS_LOCKED))
				->add(Restrictions::eq('systemtaskclassname', $params[0]))
				->setProjection(Projections::groupProperty('node'))->findColumn('node');	
		}			
		return $components;
	}
	
	/**
	 * @param string[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @return boolean
	 */
	protected function validateArgs($params, $options)
	{
		if (f_util_ArrayUtils::isEmpty($params) || count($params) > 2)
		{
			return false;
		}
		else 
		{
			$nodes = task_PlannedtaskService::getInstance()->createQuery()
				->add(Restrictions::eq('executionStatus', task_PlannedtaskService::STATUS_LOCKED))
				->add(Restrictions::eq('systemtaskclassname', $params[0]))
				->setProjection(Projections::groupProperty('node'))->findColumn('node');
			if (!count($nodes))
			{
				$this->warnMessage('No locked task for ' . $params[0]);
				return false;
			}
			
			if (count($params) == 2 && !in_array($params[1], $nodes))
			{
				$this->warnMessage('No locked task for ' . $params[0] . ' on node ' . $params[1]);
				return false;
			}
		}
		return true;
	}

	/**
	 * @param string[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	public function _execute($params, $options)
	{
		$this->message("== Unlock ==");
		$this->loadFramework();
		
		$taskName = $params[0];
		$nodeName = (count($params) > 1) ? $params[1] : null;
		
		$query = task_PlannedtaskService::getInstance()->createQuery()
			->add(Restrictions::eq('executionStatus', task_PlannedtaskService::STATUS_LOCKED))
			->add(Restrictions::eq('systemtaskclassname', $taskName));
				
		if ($nodeName)
		{
			$query->add(Restrictions::eq('node', $nodeName));
		}
		
		$unlockCount = 0;
		foreach ($query->find() as $task) 
		{
			/* @var $task task_persistentdocument_plannedtask */
			$unlockCount++;
			$task->getDocumentService()->unlock($task);
			$this->okMessage("Task \"".$task->getSystemtaskclassname()."\" unlocked");
		}
		return $this->quitOk($unlockCount . ' tasks unlocked' . ($nodeName ? (' on node ' . $nodeName) : ''));
	}
}