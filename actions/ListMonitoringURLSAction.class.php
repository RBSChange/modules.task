<?php
/**
 * task_ListMonitoringURLSAction
 * @package modules.task.actions
 */
class task_ListMonitoringURLSAction extends change_Action
{
	public function isSecure()
	{
	    return false;
	}
	
	/**
	 * @param change_Context $context
	 * @param change_Request $request
	 */
	public function _execute($context, $request)
	{
	    change_Controller::setNoCache();
	    $format = "JSON";
	    if ($request->hasParameter("format"))
	    {
		$format = $request->getParameter("format");
		if ($format != "XML" && $format != "JSON" && $format != "TXT")
		{
		    Framework::warn("Bad format for list monitoring URLs, JSON was used for this request");
		    $format = "JSON";
		}
	    }
	    $urls = $this->getAllURLOfProbetasks();

	    switch ($format)
	    {
		case("JSON"):
		    $this->setContentType('application/json');
		    echo JsonService::getInstance()->encode($urls);
		    break;
		case("XML"):
			$this->setContentType('text/xml');
			echo $this->formatToXML($urls);
		    break;
		case("TXT"):
		    ob_start();
		    $filename = "list_monitoring_urls.txt";
		    $path = PROJECT_HOME . "/" . $filename;
		    $file = $this->formatToTXT($urls, $path);
		    ob_end_clean();
		
		    $headers = array();
		    $headers[] = 'Content-Description: File Transfer';
		    $headers[] = 'Expires: 0';
		    $headers[] = 'Cache-Control: public, must-revalidate, post-check=0, pre-check=0';
		    $headers[] = 'Pragma: hack';
		    $headers[] = 'Content-type: application/octet-stream';
		    $headers[] = 'Content-Disposition: attachment; filename="' . $filename . '"';
		    $headers[] = 'Content-Transfer-Encoding: binary';
		    $headers[] = 'Content-Length: ' . filesize($path);

		    foreach ($headers as $header)
		    {
			header($header);
		    }
		    readfile($path);

		    @unlink($path);
		    exit;
		    break;
		default:
		    break;		 
	    }
	    return change_View::NONE;
	}
	
	private function getAllURLOfProbetasks()
	{
	    $query = task_PlannedtaskService::getInstance()->createQuery();
	    $tasks = $query->find();
	    $actionURLs = array();
	    foreach ($tasks as $task) 
	    {
		if ($task instanceof task_persistentdocument_plannedtask)
		{
		    $parameters = array(
			"taskname" => $task->getSystemtaskclassname(),
			"nodename" => $task->getNode(),
			);
		    $actionURLs[] = LinkHelper::getActionUrl("task", "Probetasks", $parameters);
		}
		
	    }
	    return $actionURLs;
	}
	
	private function formatToTXT($urls, $filename)
	{
	    
	    if($file = fopen($filename, "w"))
	    {
		foreach ($urls as $url)
		{
		    fwrite($file, $url . "\n");
		}
		fclose($file);
		return $file;
	    }
	    else
	    {
		Framework::warn("couldn't create file for TXT format to provide List Monitoring URLs");
	    }
	}
	
	private function formatToXML($urlsText)
	{
	    $doc = new DOMDocument('1.0', 'UTF-8');
	    $urls = $doc->createElement("urls");
	    
	    foreach ($urlsText as $urlText)
	    {
		$url = $doc->createElement("url");
		$url->appendChild($doc->createTextNode($urlText));
		$urls->appendChild($url);
	    }
	    $doc->appendChild($urls);
	    return $doc->saveXML();
	}
}