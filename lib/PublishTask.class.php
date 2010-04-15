<?php
class task_PublishTask extends task_SimpleSystemTask  
{
	/**
	 * @see task_SimpleSystemTask::execute()
	 *
	 */
	protected function execute()
	{
		$end = date_Calendar::now()->toString();
		
		$start = $this->plannedTask->getLastrundate();
		if ($start == null)
		{
			$start = date_Calendar::getInstance($end)->add(date_Calendar::MINUTE, -5)->toString();
		}
		else
		{
			$start = date_Calendar::getInstance($start)->add(date_Calendar::MINUTE, -1)->toString();
		}
		
		$documentsArray = array_chunk($this->getDocumentIdsToProcess($start, $end), 500);
		$script = 'framework/listener/publishDocumentsBatch.php';
		foreach ($documentsArray as $chunk)
		{
			f_util_System::execHTTPScript($script, $chunk);
		}
		
		$this->plannedTask->reSchedule(date_Calendar::getInstance()->add(date_Calendar::MINUTE, +10));
	}
	
	private function getDocumentIdsToProcess($start, $end)
	{
		if (Framework::isInfoEnabled())
		{
			Framework::info(__METHOD__ ."($start, $end)");
		}
		$toProcess = array();
		$compiledFilePath = f_util_FileUtils::buildChangeBuildPath('publishListenerInfos.ser');
		if (file_exists($compiledFilePath))
		{
			$models = unserialize(file_get_contents($compiledFilePath));
			$rc = RequestContext::getInstance();
			foreach ($models as $modelName => $langs) 
			{
				foreach ($langs as $lang)
				{
					try
					{
						$rc->beginI18nWork($lang);
						$query = f_persistentdocument_PersistentProvider::getInstance()->createQuery($modelName, false);
						$query->add(Restrictions::in('publicationstatus', array('ACTIVE', 'PUBLICATED')))
								->add(Restrictions::orExp(Restrictions::between('startpublicationdate', $start, $end), 
										Restrictions::between('endpublicationdate', $start, $end)))
								->setProjection(Projections::property('id', 'id'));
								
						$results = $query->find();
						foreach ($results as $resultArray)
						{
							$toProcess[] = $resultArray['id'] . '/' . $lang;
						}
						$rc->endI18nWork();
					}
					catch (Exception $e)
					{
						$rc->endI18nWork($e);
					}
				}
			}
		}
		else
		{
			Framework::error(__METHOD__ . ' File not found ' . $compiledFilePath);
		}
		return $toProcess;
	}
}
