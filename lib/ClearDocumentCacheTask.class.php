<?php
class task_ClearDocumentCacheTask extends task_SimpleSystemTask  
{
	/**
	 * @see task_SimpleSystemTask::execute()
	 */
	protected function execute()
	{
		$cacheService = f_persistentdocument_CacheService::getInstance();
		$cacheService->clearByTTL(Framework::getConfigurationValue("documentcache/ttl", 86400));
	}
}