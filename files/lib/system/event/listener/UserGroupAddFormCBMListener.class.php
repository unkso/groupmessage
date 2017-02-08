<?php
namespace wcf\system\event\listener;
use wcf\acp\form\UserGroupAddForm;
use wcf\acp\form\UserGroupEditForm;
use wcf\form\IForm;
use wcf\page\IPage;
use wcf\system\WCF;
use wcf\util\StringUtil;
use wcf\system\language\I18nHandler;

class UserGroupAddFormCBMListener implements IParameterizedEventListener {
	/**
	 * canBeMessaged TINYINT of the created or edited UserGroup
	 * @var	INT
	 */
	protected $canBeMessaged = 0;
	
	/**
	 * messagingAlias VARCHAR(255)
	 * @var	STRING
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
		WCF::getTPL()->assign(
			array(
				'canBeMessaged', $this->canBeMessaged,
				'messagingAlias', $this->messagingAlias
			)
		);
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
			$this->messagingAlias = $form->group->messagingAlias;
		}
	}
	
	/**
	 * @see	IForm::readFormParameters()
	 */
	protected function readFormParameters() {
		// Read i18n values
		I18nHandler::getInstance()->readValues();
		
		if (isset($_POST['canBeMessaged'])) {
			$this->canBeMessaged = $_POST['canBeMessaged'];
		}
		if (isset($_POST['messagingAlias'])) {
			if (I18nHandler::getInstance()->isPlainValue('messagingAlias')) {
				$this->messagingAlias = I18nHandler::getInstance()->getValue('messagingAlias');
			}
		}
	}
	
	/**
	 * @see	IForm::save()
	 */
	protected function save(UserGroupAddForm $form) {
		$form->group->canBeMessaged = $this->canBeMessaged;
		$form->group->messagingAlias = $this->messagingAlias;
		
		if ($this->canBeMessaged) {
			$form->additionalFields['canBeMessaged'] = $this->canBeMessaged;
		}
		else {
			$form->additionalFields['canBeMessaged'] = 0;
		}
		
		$form->additionalFields['messagingAlias'] = $this->messagingAlias;
	}
	
	/**
	 * @see	IForm::saved()
	 */
	protected function saved() {
		$this->canBeMessaged = 0;
		$this->messagingAlias = '';
	}
	
	/**
	 * @see	IForm::validate()
	 */
	protected function validate() {
		if (empty($this->canBeMessaged)) {
			return;
		}
		
		// Check the case when the option is enabled but no alias given
		if ($this->canBeMessaged && $this->messagingAlias == '') {
			throw new UserInputException('messagingAlias', 'empty');
		}
		
		if (!filter_var($this->messagingAlias, FILTER_VALIDATE_STRING)) {
			throw new UserInputException('messagingAlias', 'invalid');
		}
	}
}