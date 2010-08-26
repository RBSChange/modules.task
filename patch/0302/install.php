<?php
/**
 * task_patch_0302
 * @package modules.task
 */
class task_patch_0302 extends patch_BasePatch
{

    /**
     * Returns true if the patch modify code that is versionned.
     * If your patch modify code that is versionned AND database structure or content,
     * you must split it into two different patches.
     * @return Boolean true if the patch modify code that is versionned.
     */
	public function isCodePatch()
	{
		return true;
	}
 
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		f_util_System::execChangeCommand('init-webapp', array());
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
		return '0302';
	}
}