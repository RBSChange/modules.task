<?php
/**
 * task_patch_0303
 * @package modules.task
 */
class task_patch_0303 extends patch_BasePatch
{
 
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		$this->log('compile-documents ...');
		$this->execChangeCommand('compile-documents');
		
		$this->log('compile-editors-config ...');
		$this->execChangeCommand('compile-editors-config');
		
		$this->log('compile-locales task ...');
		$this->execChangeCommand('compile-locales', array('task'));
		$this->addMaxduration();
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
	
	public function addMaxduration()
	{
		$this->log('Add maxduration field on planned task...');
		$newPath = f_util_FileUtils::buildWebeditPath('modules/task/persistentdocument/plannedtask.xml');
		$newModel = generator_PersistentModel::loadModelFromString(f_util_FileUtils::read($newPath), 'task', 'plannedtask');
		$newProp = $newModel->getPropertyByName('maxduration');
		f_persistentdocument_PersistentProvider::getInstance()->addProperty('task', 'plannedtask', $newProp);
		
		$query = task_PlannedtaskService::getInstance()->createQuery()->find();
		
		foreach ($query as $task)
		{
			if ($task instanceof task_persistentdocument_plannedtask)
			{
				$task->setMaxduration(60);
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
		return '0303';
	}
}