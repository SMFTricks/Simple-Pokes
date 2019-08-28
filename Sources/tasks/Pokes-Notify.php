<?php

/**
 * This task handles notifying users when they are poked.
 *
 * Simple Machines Forum (SMF)
 *
 * @version 2.0
 * @author Diego Andrés <diegoandres_cortes@outlook.com>
 * @copyright Copyright (c) 2019, SMF Tricks
 * @license https://www.mozilla.org/en-US/MPL/2.0/
 *
 */

/**
 * Class Pokes_Notify_Background
 */
class Pokes_Notify_Background extends SMF_BackgroundTask
{
	/**
	 * This executes the task - loads up the information, puts the email in the queue and inserts alerts as needed.
	 * @return bool Always returns true
	 */
	public function execute()
	{
		global $smcFunc, $sourcedir;

		$author = false;
		// We need to figure out who the owner of this is.
		$request = $smcFunc['db_query']('', '
			SELECT mem.id_member, mem.pm_ignore_list
			FROM {db_prefix}members AS mem
			WHERE mem.id_member = {int:poked}
			LIMIT 1',
			array(
				'poked' => $this->_details['poked_id'],
			)
		);
		if ($row = $smcFunc['db_fetch_assoc']($request))
		{
			$author = $row['id_member'];
		
		}
		$smcFunc['db_free_result']($request);

		// If we didn't have a member... leave.
		if (empty($author))
			return true;

		// If the person who sent the notification is the person whose content it is, do nothing.
		if ($author == $this->_details['poker_id'])
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
			array(
				'id_member' => $author,
				'content_type' => 'poke',
				'content_id' => $this->_details['poker_id'],
				'content_action' => 'poked',
			)
		);

		if ($smcFunc['db_num_rows']($request) > 0)
			return true;
		$smcFunc['db_free_result']($request);

		// Issue, update, move on.
		$smcFunc['db_insert']('insert',
			'{db_prefix}user_alerts',
			array(
				'alert_time' => 'int',
				'id_member' => 'int',
				'id_member_started' => 'int',
				'member_name' => 'string',
				'content_type' => 'string',
				'content_id' => 'int',
				'content_action' => 'string',
				'is_read' => 'int',
				'extra' => 'string'
			),
			array(
				$this->_details['time'],
				$author,
				$this->_details['poker_id'],
				$this->_details['poker_name'],
				'poke',
				$this->_details['poker_id'],
				'poked',
				0,
				$smcFunc['json_encode'](array('pokes_link' => '?action=pokes'))
			),
			array('id_alert')
		);

		updateMemberData($author, array('alerts' => '+'));

		return true;

	}
}