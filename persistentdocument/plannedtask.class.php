<?php
/**
 * task_persistentdocument_plannedtask
 * @package task
 */
class task_persistentdocument_plannedtask extends task_persistentdocument_plannedtaskbase
{
	/**
	 * @return integer or null
	 */
	public function getPeriodUnit()
	{
		if ($this->getMinute() === null)
		{
			return date_Calendar::MINUTE;
		}
		else if ($this->getHour() === null)
		{
			return date_Calendar::HOUR;
		}
		else if ($this->getDayofmonth() === null)
		{
			return date_Calendar::DAY;
		}
		else if ($this->getMonthofyear() === null)
		{
			return date_Calendar::MONTH;
		}
		else if ($this->getYear() === null)
		{
			return date_Calendar::YEAR;
		}
		return null;
	}
	
	/**
	 * @return string
	 */
	public function getPeriodUnitLabel()
	{
		switch ($this->getPeriodUnit()) 
		{
			case date_Calendar::MINUTE:
				$key = 'm.task.document.plannedtask.period-minute';
				break;
			case date_Calendar::HOUR:
				$key = 'm.task.document.plannedtask.period-hour';
				break;
			case date_Calendar::DAY:
				$key = 'm.task.document.plannedtask.period-day';
				break;
			case date_Calendar::MONTH:
				$key = 'm.task.document.plannedtask.period-month';
				break;	
			case date_Calendar::YEAR:
				$key = 'm.task.document.plannedtask.period-year';
				break;		
			default:
				$key = 'm.task.document.plannedtask.period-no';
				break;
		}	
		return LocaleService::getInstance()->transBO($key, array('ucf'));
	}
	
	/**
	 * @return string|null
	 */
	public function getUniqueNextDate()
	{
		return $this->getDocumentService()->getUniqueNextDate($this);
	}

	/**
	 * @return Integer
	 */
	public function getPeriodValue()
	{
		return 1;
	}
	
	/**
	 * @param date_Calendar $date
	 */
	public function setUniqueExecutiondate($date)
	{
		$this->setYear($date->getYear());
		$this->setMonthofyear($date->getMonth());
		$this->setDayofmonth($date->getDay());
		$this->setHour($date->getHour());
		$this->setMinute($date->getMinute());
		$this->setNextrundate($date->toString());
	}
		
	/**
	 * @param date_Calendar $date
	 */	
	public function reSchedule($date)
	{
		$this->getDocumentService()->reSchedule($this, $date);
	}
	
	public function ping()
	{
		$this->getDocumentService()->ping($this);
	}

	public function end()
	{
		$this->getDocumentService()->end($this);
	}
	
	/**
	 * @param string $message
	 */
	public function error($message)
	{
		$this->getDocumentService()->error($this, $message);
	}
	
	/**
	 * @return boolean
	 */
	public function isLocked()
	{
		return $this->getExecutionStatus() === task_PlannedtaskService::STATUS_LOCKED;
	}
	
	/**
	 * @return boolean
	 */
	public function getIsrunning()
	{
		return $this->getExecutionStatus() === task_PlannedtaskService::STATUS_RUNNING;
	}	
	
	/**
	 * @return string
	 */
	public function getIsrunningLabel()
	{
		if ($this->getIsrunning())
		{
			return LocaleService::getInstance()->transBO('m.task.document.plannedtask.yes', array('ucf'));
		}
		else 
		{
			return LocaleService::getInstance()->transBO('m.task.document.plannedtask.no', array('ucf'));
		}	
	}
	
	/**
	 * @return string
	 */
	public function getExecutionStatusLabel()
	{
		return LocaleService::getInstance()->transBO('m.task.document.plannedtask.status-' . $this->getExecutionStatus());
	}
	
	/**
	 * @param Integer $minute 0..59 or -1 for random value
	 */
	public function setMinute($minute)
	{
		if ($minute == -1)
		{
			$minute = rand(0, 59);	
		}
		parent::setMinute($minute);
	}
	
	/**
	 * @param Integer $hour 0..23 or -1 for random value
	 */
	public function setHour($hour)
	{
		if ($hour == -1)
		{
			$hour = rand(0, 23);	
		}
		parent::setHour($hour);
	}
	
	/**
	 * @param Integer $dayofmonth 1..31 or -1 for random value
	 */
	public function setDayofmonth($dayofmonth)
	{
		if ($dayofmonth == -1)
		{
			$dayofmonth = rand(1, 28);	
		}
		parent::setDayofmonth($dayofmonth);
	}
	
	/**
	 * @param Integer $month 1..12 or -1 for random value
	 */
	public function setMonth($month)
	{
		if ($month == -1)
		{
			$month = rand(1, 12);	
		}
		parent::setMonth($month);
	}
}