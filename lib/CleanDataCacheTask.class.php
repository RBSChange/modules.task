<?php
class task_CleanDataCacheTask extends task_SimpleSystemTask  
{
	/**
	 * @see task_SimpleSystemTask::execute()
	 */
	protected function execute()
	{
		f_DataCacheService::getInstance()->cleanExpiredCache();
	}
}