<?php
/**
 * task_patch_0350
 * @package modules.task
 */
class task_patch_0350 extends patch_BasePatch
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
		return '0350';
	}
}