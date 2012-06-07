<?php
class task_I18nSynchroTask extends task_SimpleSystemTask  
{
	/**
	 * @see task_SimpleSystemTask::execute()
	 */
	protected function execute()
	{
		$scriptPath = 'framework/bin/batchI18nSynchro.php';
		while (true)
		{
			$this->plannedTask->ping();
			$output = f_util_System::execScript($scriptPath, array('synchro'));
			if (!is_numeric($output))
			{
				if (!f_util_StringUtils::endsWith($output, 'OK'))
				{
					throw new Exception($output);
				}
				break;
			}
		}
	}
}