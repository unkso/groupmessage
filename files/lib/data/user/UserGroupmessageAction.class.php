<?php
namespace wcf\data\user;
use wcf\data\user\avatar\UserAvatarAction;
use wcf\data\user\group\UserGroup;
use wcf\data\user\UserEditor;
use wcf\data\AbstractDatabaseObjectAction;
use wcf\data\IClipboardAction;
use wcf\data\ISearchAction;
use wcf\system\clipboard\ClipboardHandler;
use wcf\system\comment\CommentHandler;
use wcf\system\database\util\PreparedStatementConditionBuilder;
use wcf\system\event\EventHandler;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\exception\UserInputException;
use wcf\system\mail\Mail;
use wcf\system\request\RequestHandler;
use wcf\system\WCF;
use wcf\util\UserRegistrationUtil;

class UserGroupmessageAction extends UserAction {
	/**
	 * @see	\wcf\data\ISearchAction::validateGetSearchResultList()
	 */
	public function validateGetSearchResultList() {
		$this->readBoolean('includeUserGroups', false, 'data');
		$this->readString('searchString', false, 'data');
		
		if (isset($this->parameters['data']['excludedSearchValues']) && !is_array($this->parameters['data']['excludedSearchValues'])) {
			throw new UserInputException('excludedSearchValues');
		}
	}
	
	/**
	 * @see	\wcf\data\ISearchAction::getSearchResultList()
	 */
	public function getSearchResultList() {
		$searchString = $this->parameters['data']['searchString'];
		$excludedSearchValues = array();
		if (isset($this->parameters['data']['excludedSearchValues'])) {
			$excludedSearchValues = $this->parameters['data']['excludedSearchValues'];
		}
		$list = array();
		
		if ($this->parameters['data']['includeUserGroups']) {
			$accessibleGroups = UserGroup::getAccessibleGroups();
			foreach ($accessibleGroups as $group) {
				$groupName = $group->getName();
				if (!in_array($groupName, $excludedSearchValues)) {
					$pos = mb_strripos($groupName, $searchString);
					if ($pos !== false && $pos == 0) {
						
						// Check if group can be messaged to return search result
						if ($group->canBeMessaged) {
							
							// Get the members and leader of the group
							$sql = "SELECT		user_to_group.userID
								FROM		wcf".WCF_N."_user_to_group user_to_group
								WHERE		user_to_group.groupID = ?";
							$statement = WCF::getDB()->prepareStatement($sql);
							$statement->execute(array($group->groupID));
							$groupMembers = array();
							$memberString = "";
							while ($row = $statement->fetchArray()) {
								$groupMember = UserProfile::getUserProfile($row['userID']);
								
								if ($row['userID'] != WCF::getUser()->userID) {
									$memberString .= $groupMember->username . ", ";
								}
								
								$groupMembers[] = array(
									'username' => $groupMember->username,
									'icon' => $groupMember->getAvatar()->getImageTag(16)
								);
							}
							
							$list[] = array(
								'label' => $groupName,
								'objectID' => $group->groupID,
								'type' => 'group',
								'members' => $groupMembers,
								'memberstring' => $memberString
							);
						}
					}
				}
			}
		}
		
		// find users
		$userProfileList = new UserProfileList();
		$userProfileList->getConditionBuilder()->add("username LIKE ?", array($searchString.'%'));
		if (!empty($excludedSearchValues)) {
			$userProfileList->getConditionBuilder()->add("username NOT IN (?)", array($excludedSearchValues));
		}
		$userProfileList->sqlLimit = 10;
		$userProfileList->readObjects();
		
		foreach ($userProfileList as $userProfile) {
			$list[] = array(
				'icon' => $userProfile->getAvatar()->getImageTag(16),
				'label' => $userProfile->username,
				'objectID' => $userProfile->userID,
				'type' => 'user'
			);
		}
		
		return $list;
	}
}
