<?php
/**
 * task_patch_0351
 * @package modules.task
 */
class task_patch_0351 extends patch_BasePatch
{
//  by default, isCodePatch() returns false.
//  decomment the following if your patch modify code instead of the database structure or content.
    /**
     * Returns true if the patch modify code that is versionned.
     * If your patch modify code that is versionned AND database structure or content,
     * you must split it into two different patches.
     * @return Boolean true if the patch modify code that is versionned.
     */
//	public function isCodePatch()
//	{
//		return true;
//	}
 
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		$query = task_PlannedtaskService::getInstance()->createQuery()
			->add(Restrictions::orExp(Restrictions::isNotNull("minute"),
				Restrictions::isNotNull("hour"), Restrictions::isNotNull("dayofmonth"),
				Restrictions::isNotNull("monthofyear"), Restrictions::isNotNull("year")));
		
		foreach ($query->find() as $task)
		{
			if ($task->getMinute() === null)
			{
				$task->setMinute(rand(0, 59));
				if ($task->getHour() === null)
				{
					$task->setHour(rand(0, 23));
					if ($task->getDayofmonth() === null)
					{
						$task->setDayofmonth(rand(1, 28));
						if ($task->getMonthofyear() === null)
						{
							$task->setMonthofyear(rand(1, 12));
						}
					}
				}
				$task->save();
			}
		}
	}

	/**
	 * @return String
	 */
	protected final function getModuleName()
	{
		return 'task';
	}

	/**
	 * @return String
	 */
	protected final function getNumber()
	{
		return '0351';
	}
}