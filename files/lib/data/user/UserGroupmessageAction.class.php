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
				//$groupName = $group->getName();
				$groupName = $group->messagingAlias;
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
							while ($row = $statement->fetchArray()) {
								$groupMember = UserProfile::getUserProfile($row['userID']);
								
								$groupMembers[] = array(
									'userTitle' => $groupMember->userTitle,
									'userID' => $row['userID'],
									'username' => $groupMember->username,
									'icon' => $groupMember->getAvatar()->getImageTag(16)
								);
							}
							
							$sortIndex = array();
							foreach ($groupMembers as $key => $user) {
								$sortorder = (int)$this->getSortOrder($user['userTitle']);
								$sortIndex[$sortorder][] = $user;
							}
							
							krsort($sortIndex, SORT_NUMERIC);
							foreach ($sortIndex as $sortorder => $users) {
								usort($users, array($this,'sortByOrder'));
								$sortIndex[$sortorder] = $users;
							}
							
							$memberList = array();
							foreach ($sortIndex as $sortorder => $users) {
								foreach ($users as $key => $user) {
									$memberList[] = $user;
								}
							}
							
							$memberString = "";
							foreach($memberList as $user) {
								if ($user['userID'] != WCF::getUser()->userID) {
									$memberString .= $user['username'] . ", ";
								}
							}
							
							$list[] = array(
								'label' => $groupName,
								'objectID' => $group->groupID,
								'type' => 'group',
								'members' => $memberList,
								//'members' => $groupMembers,
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
	
	public function getSortOrder($userTitle) {
		if ($userTitle == "" || $userTitle == null) {
			return -1;
		}
	
		preg_match('#\((.*?)\)#', trim($userTitle), $paygrade);
		if( $paygrade === false || count($paygrade)==0 ) {
			return -1;
		}
		
		$removeChars = array("-", "_", "[", "]", " ");
		$paygrade = str_replace($removeChars, "", $paygrade[1]);
		
		$letter = $paygrade[0];
		$number = substr($paygrade, 1, 2);
		
		if ($letter=="O" || $letter=="o") {
			return $number+20;
		}
		
		if ($letter=="W" || $letter=="w") {
			$number = substr($paygrade, 2, 2);
			return $number+10;
		}
		
		return $number;
	}
	
	public function sortByOrder($a, $b) {
		//return $a['username'] - $b['username'];
		
		if ($a['username']==$b['username']) return 0;
		return ($a['username']<$b['username'])?-1:1;
	}
}
