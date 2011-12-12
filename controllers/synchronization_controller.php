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
	
	function beforeFilter() {
		
		Configure::write('debug', 2);
		$this->layout = 'ajax';
		
		$this->settings		= Configure::read('CampaignMonitor.settings');
		$this->Subscriber	= ClassRegistry::init($this->settings['subcriber_model']);
		
		# Setup the Subscriber record
		# BAH - should refactor
		$this->Subscriber->Behaviors->attach('CampaignMonitor.Subscriber', array(
			'ApiKey'		=> $this->settings['API_KEY'],
			'ListId'		=> $this->settings['LIST_ID'],
			'CustomFields'	=> $this->settings['CUSTOM_FIELDS'],
			'optin'			=> $this->settings['optin'],
			'config'		=> $this->settings
		));
		
		parent::beforeFilter();
	}
	
	function synchronize_new_subscribers() {
		$this->Subscriber->contain();
		$records = $this->Subscriber->find('all', array(
			'conditions' => array(
				"{$this->Subscriber->alias}.{$this->settings['sync_key']} IS NULL",
			),
			'limit'	=> $this->settings['records_per_sync']
		));
		$this->__sync($records);
	}
		
/**
 * Syncs WebApp->CampaignMonitor
 * - Only syncs Subscribers that have never been updated...
 */	
	function admin_synchronize_new_subscribers() {
		
		$this->Subscriber->contain();
		$records = $this->Subscriber->find('all', array(
			'conditions' => array(
				"{$this->Subscriber->alias}.{$this->settings['sync_key']} IS NULL",
			),
			'limit'	=> $this->settings['records_per_sync']
		));
					
		$this->__sync($records);
	}
	
/**
 * Syncs WebApp->CampaignMonitor
 * - Syncs Subscribers that have not been synced synced in the last 24 hours ??
 */		
	function admin_synchronize_subscribers() {
		
		# Get the records that have not yet been synced or updated in the last day
		$this->Subscriber->contain();
		$records = $this->Subscriber->find('all', array(
			'conditions' => array(
				"{$this->Subscriber->alias}.{$this->settings['sync_key']}" => date('Y-m-d H:i:s', strtotime('-1 Day', time())),
			),
			'limit'	=> $this->settings['records_per_sync']
		));

		
	}
	
	function __sync($records) {
		
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
		
		debug($this->result);		
	}
	
}