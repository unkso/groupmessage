<?php
namespace wcf\system\event\listener;
use wcf\acp\form\UserGroupAddForm;
use wcf\acp\form\UserGroupEditForm;
use wcf\form\IForm;
use wcf\page\IPage;
use wcf\system\WCF;
use wcf\util\StringUtil;

class UserGroupAddFormCBMListener implements IParameterizedEventListener {
	/**
	 * canBeMessaged TINYINT of the created or edited UserGroup
	 * @var	string
	 */
	protected $canBeMessaged = 0;
	
	/**
	 * instance of UserGroupAddForm
	 * @var	\wcf\acp\form\UserGroupAddForm
	 */
	protected $eventObj = null;
	
	/**
	 * @see	IPage::assignVariables()
	 */
	protected function assignVariables() {
		WCF::getTPL()->assign('canBeMessaged', $this->canBeMessaged);
	}
	
	/**
	 * @inheritDoc
	 */
	public function execute($eventObj, $className, $eventName, array &$parameters) {
		$this->eventObj = $eventObj;
		
		if (method_exists($this, $eventName) && $eventName !== 'execute') {
			$this->$eventName($eventObj);
		}
		else {
			throw new \LogicException('Unreachable');
		}
	}
	
	/**
	 * @see	IPage::readData()
	 */
	protected function readData(UserGroupEditForm $form) {
		if (empty($_POST)) {
			$this->canBeMessaged = $form->group->canBeMessaged;
		}
	}
	
	/**
	 * @see	IForm::readFormParameters()
	 */
	protected function readFormParameters() {
		if (isset($_POST['canBeMessaged'])) {
			$this->canBeMessaged = $_POST['canBeMessaged'];
		}
	}
	
	/**
	 * @see	IForm::save()
	 */
	protected function save(UserGroupAddForm $form) {
		$form->group->canBeMessaged = $this->canBeMessaged;
		
		if ($this->canBeMessaged) {
			$form->additionalFields['canBeMessaged'] = $this->canBeMessaged;
		}
		else {
			$form->additionalFields['canBeMessaged'] = 0;
		}
	}
	
	/**
	 * @see	IForm::saved()
	 */
	protected function saved() {
		$this->canBeMessaged = 0;
	}
	
	/**
	 * @see	IForm::validate()
	 */
	protected function validate() {
		if (empty($this->canBeMessaged)) {
			return;
		}
	}
}