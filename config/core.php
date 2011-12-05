<?php
/**
 * Plugin Configuration File
 *
 * In order to make the plugin work you must include this file
 * within either your app’s `core.php` or `bootstrap.php`.
 *
 * To overwrite defaults you'll define constants before including this file,
 * and overwrite other settings selectively with `Configure::write()`
 * calls after including it.
 *
 * PHP version 5.3
 * CakePHP version 1.3.x
 *
 * @package    campaign_monitor
 * @subpackage campaign_monitor.config
 */

# Debug mode ?
Configure::write('CampaignMonitor.debug', false);
Configure::write('CampaignMonitor.settings', array(
	'API_KEY'			=> null,		
	'LIST_ID'			=> null,		
	'CUSTOM_FIELDS'		=> array(),		
	'optin'				=> false,		
	'subcriber_model'	=> 'User',		// User model to iniialize
	'sync_key'			=> 'cm_last_synced',
	'records_per_sync'	=> 500,
));
