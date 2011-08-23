<?php
/**
 * @package modules.task.lib.services
 */
class task_ModuleService extends ModuleBaseService
{
	/**
	 * Singleton
	 * @var task_ModuleService
	 */
	private static $instance = null;

	/**
	 * @return task_ModuleService
	 */
	public static function getInstance()
	{
		if (is_null(self::$instance))
		{
			self::$instance = new self();
		}
		return self::$instance;
	}
}