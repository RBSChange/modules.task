<?php
class task_ViewUserTaskErrorView extends generic_DashboardSuccessView
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		die("This task or its associated document does not exist.");
	}
}