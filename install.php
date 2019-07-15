<?php

/**
 * @package Simple Pokes
 * @version 2.0
 * @author Diego Andrés <diegoandres_cortes@outlook.com>
 * @copyright Copyright (c) 2018, Diego Andrés
 * @license http://www.mozilla.org/MPL/MPL-1.1.html
 */

	if (file_exists(dirname(__FILE__) . '/SSI.php') && !defined('SMF'))
		require_once(dirname(__FILE__) . '/SSI.php');

	elseif (!defined('SMF'))
		exit('<b>Error:</b> Cannot install - please verify you put this in the same place as SMF\'s index.php.');

	global $smcFunc, $context;

	db_extend('packages');

	if (empty($context['uninstalling']))
	{
		// Enable the alert by default
		$smcFunc['db_insert'](
			'ignore',
			'{db_prefix}user_alerts_prefs',
			array(
				'id_member' => 'int',
				'alert_pref' => 'string',
				'alert_value' => 'int',
			),
			array(
				array(
					0,
					'poked',
					1,
				),
			),
			array('id_task')
		);

		// Shop items
		$tables[] = array(
			'table_name' => '{db_prefix}pokes',
			'columns' => array(
				array(
					'name' => 'id_member',
					'type' => 'int',
					'size' => 10,
					'null' => false,
				),
				array(
					'name' => 'id_poker',
					'type' => 'int',
					'size' => 10,
					'null' => false,
				),
				array(
					'name' => 'date',
					'type' => 'int',
					'null' => false,
				),
			),
			'indexes' => array(
				array(
					'type' => 'primary',
					'columns' => array('id_member', 'id_poker')
				),
			),
			'if_exists' => 'ignore',
			'error' => 'fatal',
			'parameters' => array(),
		);
		
		// Installing
		foreach ($tables as $table)
			$smcFunc['db_create_table']($table['table_name'], $table['columns'], $table['indexes'], $table['parameters'], $table['if_exists'], $table['error']);
	}