<?php
/**
 * task_patch_0301
 * @package modules.task
 */
class task_patch_0301 extends patch_BasePatch
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
		try
		{
			$newPath = f_util_FileUtils::buildWebeditPath ( 'modules/task/persistentdocument/plannedtask.xml' );
			$newModel = generator_PersistentModel::loadModelFromString ( f_util_FileUtils::read ( $newPath ), 'task', 'plannedtask' );
			$newProp = $newModel->getPropertyByName ( 'node' );
			f_persistentdocument_PersistentProvider::getInstance ()->addProperty ( 'task', 'plannedtask', $newProp );
		}
		catch ( BaseException $e )
		{
			if ($e->getAttribute ( 'sqlstate' ) != '42S21' || $e->getAttribute ( 'errorcode' ) != '1060')
			{
				throw $e;
			}
		}
		
	// Implement your patch here.
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
		return '0301';
	}
}