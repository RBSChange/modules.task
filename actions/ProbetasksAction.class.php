<?php
/**
 * task_ProbetasksAction
 * @package modules.task.actions
 */
class task_ProbetasksAction extends change_Action
{
	/**
	 * @param change_Context $context
	 * @param change_Request $request
	 */
	public function _execute($context, $request)
	{
		change_Controller::setNoCache();
		$taskName = $request->getParameter('taskname');
		$nodeName = $request->getParameter('nodename');
		
		echo $this->getProbe($taskName, $nodeName);
		return change_View::NONE;
	}
	
	public function isSecure()
	{
		return false;
	}	
	
	private function getProbe($taskName, $nodeName = NULL)
	{
		$query = task_PlannedtaskService::getInstance()->createQuery()
			->add(Restrictions::eq('systemtaskclassname',$taskName));
		if (!f_util_StringUtils::isEmpty($nodeName))
		{
			$query = $query->add(Restrictions::eq('node', $nodeName));
		}
		$task = $query->findUnique();
		
		if ($task instanceof task_persistentdocument_plannedtask)
		{
			if ($task->isLocked())
			{
				return 'KO';
			}
			else
			{
				return 'OK';
			}
		}
		else 
		{
			return 'KO';
		}
		
	}
}