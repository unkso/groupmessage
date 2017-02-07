{if $templateName == 'conversationAdd' || $templateName == 'conversationForward'}
<script data-relocate="true">
	//<![CDATA[
	$(function() {
		/**
		 * Provides quick search for users and user groups.
		 * 
		 * @see	WCF.Search.Base
		 */
		WCF.Search.User = WCF.Search.Base.extend({
			/**
			 * @see	WCF.Search.Base._className
			 */
			_className: 'wcf\\data\\user\\UserGroupmessageAction',
			
			/**
			 * include user groups in search
			 * @var	boolean
			 */
			_includeUserGroups: true,
			
			/**
			 * @see	WCF.Search.Base.init()
			 */
			init: function(searchInput, callback, includeUserGroups, excludedSearchValues, commaSeperated) {
				this._includeUserGroups = 1;
				
				this._super(searchInput, callback, excludedSearchValues, commaSeperated);
			},
	
			/**
			 * @see	WCF.Search.Base._getParameters()
			 */
			_getParameters: function(parameters) {
				parameters.data.includeUserGroups = this._includeUserGroups ? 1 : 0;
				
				return parameters;
			},
			
			/**
			 * @see	WCF.Search.Base._createListItem()
			 */
			_createListItem: function(item) {
				var $listItem = this._super(item);
				
				var $icon = null;
				if (item.icon) {
					$icon = $(item.icon);
				}
				else if (this._includeUserGroups && item.type === 'group') {
					$icon = $('<span class="icon icon16 icon-group" />');
				}
				
				if ($icon) {
					var $label = $listItem.find('span').detach();
					
					var $box16 = $('<div />').addClass('box16').appendTo($listItem);
					
					$box16.append($icon);
					$box16.append($('<div />').append($label));
					
					var memberString = "";
					var $members = $('<div />');
					for (var i in item.members) {
						$member16 = $('<div />').addClass('box16');
						$memberIcon = $('<span class="icon icon16" />').append($(item.members[i].icon)).appendTo($member16);
						$memberUsername = $('<span />').text(item.members[i].username).appendTo($member16);
						$member16.appendTo($members);
						
						memberString += item.members[i].username + ",";
					}
					
					$members.appendTo($box16);
				}
				
				// insert item type
				$listItem.data('type', item.type);
				$listItem.data('memberstring', item.memberstring);
				
				return $listItem;
			},
			
			/**
			 * Executes callback upon result click.
			 * 
			 * @param	object		event
			 */
			_executeCallback: function(event) {
				var $clearSearchInput = false;
				var $listItem = $(event.currentTarget);
				// notify callback
				if (this._commaSeperated) {
					// auto-complete current part
					//var $result = $listItem.data('label');
					if ($listItem.data('type') == 'group') {
						var $result = $listItem.data('memberstring');
					} else {
						var $result = $listItem.data('label');
					}
					this._oldSearchString[this._caretAt] = $result;
					this._searchInput.val(this._oldSearchString.join(', '));
					
					if ($.browser.webkit) {
						// chrome won't display the new value until the textarea is rendered again
						// this quick fix forces chrome to render it again, even though it changes nothing
						this._searchInput.css({ display: 'block' });
					}
					
					// set focus on input field again
					var $position = this._searchInput.val().toLowerCase().indexOf($result.toLowerCase()) + $result.length;
					this._searchInput.focus().setCaret($position);
				}
				else {
					if (this._callback === null) {
						//this._searchInput.val($listItem.data('label'));
						this._searchInput.val($listItem.data('memberstring'));
					}
					else {
						$clearSearchInput = (this._callback($listItem.data()) === true) ? true : false;
					}
				}
				
				// close list and revert input
				this._clearList($clearSearchInput);
			},
			
		});
		
		new WCF.Search.User('#participants', null, false, [ ], true);
	});
	//]]>
</script>
{/if}