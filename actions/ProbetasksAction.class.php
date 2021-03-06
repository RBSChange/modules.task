<?php
/**
 * task_ProbetasksAction
 * @package modules.task.actions
 */
class task_ProbetasksAction extends f_action_BaseAction
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		controller_ChangeController::setNoCache();
		$taskName = $request->getParameter('taskname');
		$nodeName = $request->getParameter('nodename');
		
		echo $this->getProbe($taskName, $nodeName);
		return View::NONE;
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