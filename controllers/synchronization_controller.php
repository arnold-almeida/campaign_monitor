<?php
/**
 * @todo Refactor as shell command
 */
class SynchronizationController extends AppController {
	
	var $name = 'Synchronization';
	
	var $uses = array();
	
	var $result = array(
		'added'		=> 0,
		'synced'	=> 0,
	);
		
/**
 * - Syncs WebApp->CampaignMonitor
 * - Assumes you have already have an existing list you want to sync with...
 */	
	function admin_synchronize_subscribers() {
		
		Configure::write('debug', 2);
		
		$settings			= Configure::read('CampaignMonitor.settings');
		$this->Subscriber	= ClassRegistry::init($settings['subcriber_model']);
		
		# Get the records that have not yet been synced or updated in the last day
		$this->Subscriber->contain();
		$records = $this->Subscriber->find('all', array(
			'conditions' => array(
				'OR' => array(
					//"{$this->Subscriber->alias}.{$settings['sync_key']}" => date('Y-m-d H:i:s', strtotime('-1 Day', time())),
					"{$this->Subscriber->alias}.{$settings['sync_key']} IS NULL",
				)
			),
			'limit'	=> $settings['records_per_sync']
		));

		# Setup the Subscriber record
		$this->Subscriber->Behaviors->attach('CampaignMonitor.Subscriber', array(
			'ApiKey'		=> $settings['API_KEY'],
			'ListId'		=> $settings['LIST_ID'],
			'CustomFields'	=> $settings['CUSTOM_FIELDS'],
			'optin'			=> $settings['optin'],
			'config'		=> $settings
		));

				
		foreach($records as $i => $subscriber) {
			
			// Check if this subscriber exists in CM ?
			$cmRecord = $this->Subscriber->assertRecord($subscriber[$this->Subscriber->alias]['email']);
			
			if(false == $cmRecord) {
				debug('TODO');
				debug($this->result);
				die();exit();
				$this->Subscriber->addRecord();
				$this->result['added']++;
			} else {
				$this->Subscriber->syncRecord($subscriber);
				$this->result['synced']++;
			}
		}
	}
	
}