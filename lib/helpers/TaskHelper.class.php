<?php
class TaskHelper
{
	static private $UsertaskService;
	static private $NotificationService;

	/**
	 * @return task_UsertaskService
	 */
	public static function getUsertaskService()
	{
		if (!self::$UsertaskService)
		{
			self::$UsertaskService = ServiceLoader::getServiceByDocumentModelName('modules_task/usertask');
		}
		return self::$UsertaskService;
	}

	/**
	 * @return notification_NotificationService
	 */
	public static function getNotificationService()
	{
		if (!self::$NotificationService)
		{
			self::$NotificationService = ServiceLoader::getServiceByDocumentModelName('modules_notification/notification');
		}
		return self::$NotificationService;
	}

	/**
	 * @return array<task_persistentdocument_usertask>
	 */
	public static function getPendingTasksForCurrentUser()
	{
		$query = f_persistentdocument_PersistentProvider::getInstance()->createQuery('modules_task/usertask');
		$query->add(Restrictions::eq('user', users_UserService::getInstance()->getCurrentUser()));
		$query->add(Restrictions::published());
		$query->addOrder(Order::desc('document_creationdate'));
		$query->setMaxResults(50);
		return $query->find();
	}

	/**
	 * @param Integer $documentId
	 * @return array<task_persistentdocument_usertask>
	 */
	public static function getPendingTasksForCurrentUserByDocumentId($documentId)
	{
		$query = f_persistentdocument_PersistentProvider::getInstance()->createQuery('modules_task/usertask');
		$query->add(Restrictions::eq('user', users_UserService::getInstance()->getCurrentUser()));
		$query->add(Restrictions::published());
		$query->add(Restrictions::eq('workitem.documentid', $documentId));
		$query->addOrder(Order::desc('document_creationdate'));
		$query->setMaxResults(50);
		return $query->find();
	}

	/**
	 * @param users_persistentdocument_user $user
	 * @param Integer $documentId
	 * @return array<task_persistentdocument_usertask>
	 */
	public static function getTaskForUserIdByDocumentId($user, $documentId, $taskId)
	{
		$query = task_UsertaskService::getInstance()->createQuery();
		$query->add(Restrictions::published());
		$query->add(Restrictions::eq('user', $user));
		$criteria = $query->createCriteria('workitem');
		$criteria->add(Restrictions::eq('documentid', $documentId));
		$criteria->add(Restrictions::eq('transition.taskid', $taskId));
		return $query->findUnique();
	}
}