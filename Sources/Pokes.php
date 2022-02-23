<?php

/**
 * @package Simple Pokes
 * @version 2.1
 * @author Diego Andrés <diegoandres_cortes@outlook.com>
 * @copyright Copyright (c) 2022, SMF Tricks
 * @license https://www.mozilla.org/en-US/MPL/2.0/
 */

if (!defined('SMF'))
	die('hacking attempt...');

class Pokes
{
	/**
	 * Pokes::hookButtons()
	 * 
	 * Add a poke button to the main menu
	 * 
	 * @param array $buttons The forum menu
	 * @return void
	 */
	public static function hookButtons(&$buttons)
	{
		global $txt, $scripturl;

		loadLanguage('SimplePokes/');

		$before = 'admin';
		$temp_buttons = [];
		foreach ($buttons as $k => $v) {
			if ($k == $before) {
				$temp_buttons['pokes'] = [
					'title' => $txt['pokes'],
					'href' => $scripturl . '?action=pokes',
					'icon' => 'poke',
					'show' => false,
				];
			}
			$temp_buttons[$k] = $v;
		}
		$buttons = $temp_buttons;
	}

	/**
	 * Pokes::preCSS()
	 * 
	 * Add the icon via CSS
	 * 
	 * @return void
	 */
	public static function preCSS()
	{
		global $settings;

		// Add the icon using inline css
		addInlineCss('
			.main_icons.poke::before {
				background-position: 0;
				background-image: url("' . $settings['default_images_url'] . '/icons/poke.png");
				background-size: contain;
			}
		');
	}

	/**
	 * Pokes::hookActions()
	 * 
	 * Add the action for the list of pokes
	 * 
	 * @param array $actions The forum actions
	 * @return void
	 */
	public static function hookActions(&$actions)
	{
		$actions['pokes'] = [false, 'Pokes::mainActions'];
	}

	/**
	 * Pokes::profileAreas()
	 * 
	 * Add the poke button in the profile
	 * 
	 * @param array $profile_areas The profile areas
	 * @return void
	 */
	public static function profileAreas(&$profile_areas)
	{
		global $txt, $scripturl;

		loadLanguage('SimplePokes/');

		// Profile information
		$before = 'statistics';
		$temp_buttons = [];
		foreach ($profile_areas['info']['areas'] as $k => $v)
		{
			if ($k == $before) {
				$temp_buttons['pokes'] = [
					'label' => $txt['pokes'],
					'custom_url' => $scripturl . '?action=pokes',
					'icon' => 'poke',
					'enabled' => true,
					'permission' => [
						'own' => 'profile_view',
						'any' => [],
					],
				];
			}
			$temp_buttons[$k] = $v;
		}
		$profile_areas['info']['areas'] = $temp_buttons;
	}

	/**
	 * Pokes::profilePopup()
	 * 
	 * Add the poke action in the profile popup
	 * 
	 * @param array $profile_items The profile popup items
	 * @return void
	 */
	public static function profilePopup(&$profile_items)
	{
		global $scripturl;

		$temp_area = [];
		foreach ($profile_items as $k => $item)
		{
			if ($item['area'] == 'showposts')
			{
				$temp_area['pokes'] = [
					'menu' => 'info',
					'url' => $scripturl . '?action=pokes',
					'area' => 'pokes',
				];
			}
			$temp_area[$k] = $item;
		}
		$profile_items = $temp_area;
	}

	/**
	 * Pokes::profileCustomFields()
	 * 
	 * Add the pokes as a custom field in the profile to play as an action button.
	 * 
	 * @param array $memID The member ID
	 * @param array $area The profile area
	 * @return void
	 */
	public static function profileCustomFields($memID, $area)
	{
		global $txt, $context, $scripturl, $user_info;

		// Only show this in the summary
		if ($area !== 'summary')
			return;

		// Add the fake custom field
		if (!empty($context['member']) && $context['member']['id'] != $user_info['id'])
		{
			$context['custom_fields']['pokes'] = [
				'name' => $txt['pokes'],
				'colname' => $txt['pokes'],
				'output_html' => '<a href="'.$scripturl.'?action=pokes;sa=pokeuser;id='.$memID.';'. $context['session_var'] .'='. $context['session_id'] .'">'.$txt['poke_user'].'</a>',
				'placement' => 6,
			];
		}
	}

	/**
	 * Pokes::alertTypes()
	 * 
	 * Add the alert type for the pokes
	 * 
	 * @param array $alert_types The alert types
	 * @return void
	 */
	public static function alertTypes(&$alert_types)
	{
		$alert_types['pokes'] = [
				'poked' => ['alert' => 'yes', 'email' => 'never'],
		];
	}

	/**
	 * Pokes::alertFetch()
	 * 
	 * Add the alert notification
	 * 
	 * @param array $alerts The alerts and their content
	 * @return void
	 */
	public static function alertFetch(&$alerts)
	{
		global $scripturl;

		foreach ($alerts as $alert_id => $alert)
		{
			if ($alert['content_type'] == 'poke')
			{
				$alerts[$alert_id]['icon'] = '<span class="alert_icon  main_icons poke"></span>';
				$alerts[$alert_id]['extra']['content_link'] = $scripturl . $alert['extra']['pokes_link'];
			}
		}
	}

	/**
	 * Pokes::mainActions()
	 * 
	 * It creates the list of subactions available in the pokes page
	 * @return void
	 */
	public static function mainActions()
	{
		global $txt, $context, $scripturl;

		// Load language
		loadLanguage('SimplePokes/');

		// Set all the page stuff
		$context['page_title'] = $txt['pokes'];
		$context['linktree'][] = [
			'url' => $scripturl . '?action=pokes',
			'name' => $txt['pokes'],
		];

		$subactions = [
			'list' => [
				'function' => 'Pokes::mainList',
			],
			'pokeuser' => [
				'function' => 'Pokes::actionPoke',
			],
			'pokeignore' => [
				'function' => 'Pokes::actionIgnore',
			],
		];

		// By default
		$sa = 'list';
		if (isset($_REQUEST['sa']) && array_key_exists($_REQUEST['sa'], $subactions) && ($_REQUEST['sa'] != 'list'))
			$sa = $_REQUEST['sa'];
		$subactions[$sa]['function']();
	}

	/**
	 * Pokes::mainList()
	 * 
	 * Display the list of pokes and the relevant information
	 * @return void
	 */
	public static function mainList()
	{
		global $txt, $context, $scripturl, $sourcedir;

		require_once($sourcedir . '/Subs-List.php');
		$context['sub_template'] = 'show_list';
		$context['default_list'] = 'pokeslist';

		// The entire list
		$listOptions = [
			'id' => 'pokeslist',
			'title' => $txt['pokes_log_title'],
			'items_per_page' => 10,
			'base_href' => '?action=pokes',
			'default_sort_col' => 'date',
			'get_items' => [
				'function' => 'Pokes::getPokes',
			],
			'get_count' => [
				'function' => 'Pokes::countPokes',
			],
			'no_items_label' => $txt['poke_list_empty'],
			'no_items_align' => 'center',
			'columns' => [
				'from_user' => [
					'header' => [
						'value' => $txt['poker'],
						'class' => 'lefttext',
					],
					'data' => [
						'sprintf' => [
							'format' => '<a href="'. $scripturl . '?action=profile;u=%1$d">%2$s</a>',
							'params' => [
								'id_poker' => false,
								'name_poker' => true,
							],
						],
						'style' => 'width: 30%',
					],
					'sort' =>  [
						'default' => 'name_poker DESC',
						'reverse' => 'name_poker',
					],
				],
				'poke_back' => [
					'header' => [
						'value' => $txt['poke_back'],
						'class' => 'centertext',
					],
					'data' => [
						'function' => function($row) use ($scripturl, $txt, $context)
						{
							return '<a href="'.$scripturl.'?action=pokes;sa=pokeuser;id='.$row['id_poker'].';'. $context['session_var'] .'='. $context['session_id'] .'">'.$txt['poke_back'].'</a>';
						},
						'class' => 'centertext',
						'style' => 'width: 15%',
					],
				],
				'date' => [
					'header' => [
						'value' => $txt['poke_time'],
						'class' => ' lefttext',
					],
					'data' => [
						'function' => function($row)
						{
							return timeformat($row['date']);
						},
						'style' => 'width: 25%',
					],
					'sort' =>  [
						'default' => 'date DESC',
						'reverse' => 'date',
					],
				],
				'poke_ignore' => [
					'header' => [
						'value' => $txt['poke_ignore'],
						'class' => 'centertext',
					],
					'data' => [
						'function' => function($row) use ($scripturl, $txt, $context)
						{
							return '<a href="'.$scripturl.'?action=pokes;sa=pokeignore;id='.$row['id_poker'].';'. $context['session_var'] .'='. $context['session_id'] .'">'.$txt['poke_ignore'].'</a>';
						},
						'class' => 'centertext',
						'style' => 'width: 10%',
					],
				],
			],
			'additional_rows' => [
				'updated' => [
					'position' => 'top_of_list',
					'value' => !isset($_REQUEST['success']) ? '' : '<div class="infobox">'. $txt['pokes_action_success']. '</div>',
				],
			],
		];
		// Let's finishem
		createList($listOptions);
	}

	/**
	 * Pokes::countPokes()
	 * 
	 * Count the total of pokes
	 * @return void
	 */
	public static function countPokes()
	{
		global $smcFunc, $user_info;

		// Count the log entries
		$logs = $smcFunc['db_query']('', '
			SELECT p.id_member
			FROM {db_prefix}pokes AS p
			WHERE p.id_member = {int:userid}',
			[
				'userid' => $user_info['id']
			]
		);
		$count = $smcFunc['db_num_rows']($logs);
		$smcFunc['db_free_result']($logs);

		return $count;
	}

	/**
	 * Pokes::getPokes()
	 * 
	 * Get the pokes the user has received
	 * @return void
	 */
	public static function getPokes($start, $items_per_page, $sort)
	{
		global $context, $smcFunc, $user_info;

		// Get a list of all the item
		$result = $smcFunc['db_query']('', '
			SELECT p.id_member, p.id_poker, p.date, m.real_name AS name_poker
			FROM {db_prefix}pokes AS p
				LEFT JOIN {db_prefix}members AS m ON (m.id_member = p.id_poker)
			WHERE p.id_member = {int:userid}
			ORDER by {raw:sort}
			LIMIT {int:start}, {int:maxindex}',
			[
				'start' => $start,
				'maxindex' => $items_per_page,
				'sort' => $sort,
				'userid' => $user_info['id'],
			]
		);

		// Return the data
		$context['pokes_list'] = [];
		while ($row = $smcFunc['db_fetch_assoc']($result))
			$context['pokes_list'][] = $row;
		$smcFunc['db_free_result']($result);

		return $context['pokes_list'];
	}

	/**
	 * Pokes::verifyPoke()
	 * 
	 * Verify information about this poke
	 * 
	 * @param int $memID The ID of the member
	 * @param int $poker The ID of the sender
	 * @return void
	 */
	public static function verifyPoke($memID, $poker)
	{
		global $smcFunc;

		$verify = $smcFunc['db_query']('', '
			SELECT p.id_poker, p.id_member
			FROM {db_prefix}pokes AS p
			WHERE p.id_poker = {int:poker} AND p.id_member = {int:userid}',
			[
				'userid' => $memID,
				'poker' => $poker,
			]
		);
		$count  = $smcFunc['db_num_rows']($verify);
		$smcFunc['db_free_result']($verify);

		return $count;
	}

	/**
	 * Pokes::actionIgnore()
	 * 
	 * Ignores a poke
	 * 
	 * @return void
	 */
	public static function actionIgnore()
	{
		global $smcFunc, $user_info, $context;

		// We got something?
		if (!isset($_REQUEST['id']) || empty($_REQUEST['id']))
			fatal_lang_error('poke_nouser_error', false);

		// You're sending a poke to someone else, right?
		elseif ($user_info['id'] == $_REQUEST['id'])
			fatal_lang_error('poke_yourself_error', false);

		// check the session
		checkSession('get');

		// Make sure it's an int
		$memID = (int) $_REQUEST['id'];

		// Check if there's an active poke for this user.
		$context['poke_active'] = self::verifyPoke($user_info['id'], $memID);

		// Let's see if they can poke the user
		if (empty($context['poke_active']))
			fatal_lang_error('poke_not_there', false);
	
		// Delete this from the log then
		$smcFunc['db_query']('', '
			DELETE FROM {db_prefix}pokes
			WHERE id_poker = {int:poker} AND id_member = {int:userid}',
			[
				'userid' => $user_info['id'],
				'poker' => $memID,
			]
		);
		// go back to the pokes
		redirectexit('action=pokes');
	}

	/**
	 * Pokes::actionPoke()
	 * 
	 * Sends a poke
	 * 
	 * @return void
	 */
	public static function actionPoke()
	{
		global $smcFunc, $user_info, $context;

		// We got something?
		if (!isset($_REQUEST['id']) || empty($_REQUEST['id']))
		fatal_lang_error('poke_nouser_error', false);
		// He's sending a poke to someone else, right?
		elseif ($user_info['id'] == $_REQUEST['id'])
		fatal_lang_error('poke_yourself_error', false);

		checkSession('get');

		$memID = (int) $_REQUEST['id'];

		$memberResult = loadMemberData($memID, false, 'profile');
		// Check if loadMemberData() has returned a valid result.
		if (!$memberResult)
			fatal_lang_error('not_a_user', false);

		$context['poke_active'] = self::verifyPoke($memID, $user_info['id']);
		$context['poke_return'] = self::verifyPoke($user_info['id'], $memID);

		// Already sent a poke
		if (!empty($context['poke_active']))
			fatal_lang_error('poke_already_sent', false);

		// First remove the active poke if there's any
		if (!empty($context['poke_return']))
		{
			$smcFunc['db_query']('', '
				DELETE FROM {db_prefix}pokes
				WHERE id_poker = {int:poker} AND id_member = {int:userid}',
				[
					'userid' => $user_info['id'],
					'poker' => $memID,
				]
			);
		}

		// Poke this user
		$smcFunc['db_insert']('',
			'{db_prefix}pokes',
			[
				'id_member' => 'int',
				'id_poker' => 'int',
				'date' => 'int',
			],
			[
				$memID,
				$user_info['id'],
				time()
			],
			[]
		);

		// Send the alert
		self::deployAlert($memID);

		// Success
		redirectexit('action=pokes;success');
	}

	/**
	 * Pokes::deployAlert()
	 * 
	 * Sends an alert for a poke
	 * 
	 * @param int $memID The ID of the member
	 * @return void
	 */
	public static function deployAlert($memID)
	{
		global $smcFunc, $sourcedir, $user_info;

		require_once($sourcedir . '/Subs-Notify.php');
		$prefs = getNotifyPrefs($memID, 'poked', true);

		// Send alert
		// Check the value. If no value or it's empty, they didn't want alerts, oh well.
		if (empty($prefs[$memID]['poked']))
			return true;

		// Don't spam the alerts: if there is an existing unread alert of the
		// requested type for the target user from the sender, don't make a new one.
		$request = $smcFunc['db_query']('', '
			SELECT id_alert
			FROM {db_prefix}user_alerts
			WHERE id_member = {int:id_member}
				AND is_read = 0
				AND content_type = {string:content_type}
				AND content_id = {int:content_id}
				AND content_action = {string:content_action}',
			[
				'id_member' => $memID,
				'content_type' => 'poke',
				'content_id' => $user_info['id'],
				'content_action' => 'poked',
			]
		);

		if ($smcFunc['db_num_rows']($request) > 0)
			return true;
		$smcFunc['db_free_result']($request);

		// Issue, update, move on.
		$smcFunc['db_insert']('insert',
			'{db_prefix}user_alerts',
			[
				'alert_time' => 'int',
				'id_member' => 'int',
				'id_member_started' => 'int',
				'member_name' => 'string',
				'content_type' => 'string',
				'content_id' => 'int',
				'content_action' => 'string',
				'is_read' => 'int',
				'extra' => 'string'
			],
			[
				time(),
				$memID,
				$user_info['id'],
				$user_info['name'],
				'poke',
				$user_info['id'],
				'poked',
				0,
				$smcFunc['json_encode'](['pokes_link' => '?action=pokes'])
			],
			['id_alert']
		);

		// Add the alert to the counter
		updateMemberData($memID, ['alerts' => '+']);
	}
}