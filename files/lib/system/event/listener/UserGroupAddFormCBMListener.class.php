<?php
namespace wcf\system\event\listener;
use wcf\acp\form\UserGroupAddForm;
use wcf\acp\form\UserGroupEditForm;
use wcf\form\IForm;
use wcf\page\IPage;
use wcf\system\WCF;
use wcf\util\StringUtil;
use wcf\system\exception\UserInputException;

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
	protected $messagingAlias = '';
	
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
		if (isset($_POST['canBeMessaged'])) {
			$this->canBeMessaged = $_POST['canBeMessaged'];
		}
		if (isset($_POST['messagingAlias'])) {
			$this->messagingAlias = $_POST['messagingAlias'];
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
		try {
			if ($this->canBeMessaged && $this->messagingAlias == '') {
				throw new UserInputException('messagingAlias', 'empty');
			}
		} catch (UserInputException $e) {
			$this->eventObj->errorType[$e->getField()] = $e->getType();
		}
		
		// Check for invalid string input
		try {
			if (!is_string($this->messagingAlias)) {
				throw new UserInputException('messagingAlias', 'invalid');
			}
			
			if ( filter_var($this->messagingAlias, FILTER_SANITIZE_STRING) !== $this->messagingAlias ) {
				throw new UserInputException('messagingAlias', 'invalid');	
			}
		} catch (UserInputException $e) {
			$this->eventObj->errorType[$e->getField()] = $e->getType();
		}
	}
}