<?php
class task_DayChangeTask extends task_SimpleSystemTask  
{
	/**
	 * @see task_SimpleSystemTask::execute()
	 *
	 */
	protected function execute()
	{
		$previousRunTime = $this->plannedTask->getLastSuccessDate();
		if ($previousRunTime !== null)
		{
			$previousRunTime = date_Calendar::getInstance($previousRunTime)->getTimestamp();
		}
		
		$date = date_Calendar::now()->toString();
		f_event_EventManager::dispatchEvent('dayChange', null, array('date' => $date, 'previousRunTime' => $previousRunTime));
	}
}
