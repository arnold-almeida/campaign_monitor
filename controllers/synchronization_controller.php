<?php
/**
 * @todo Refactor as shell command for Cake 2.0
 */
class SynchronizationController extends AppController {
	
	var $name = 'Synchronization';
	
	var $uses = array();
	
	var $result = array(
		'added'		=> 0,
		'synced'	=> 0,
	);
	
	function beforeFilter() {
		
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
	
/**
 * 
 */	
	function synchronize_new_subscribers() {
		
		$this->autoLayout	= false;
		$this->layout		= 'ajax';
		
		$this->Subscriber->contain();
		$records = $this->Subscriber->find('all', array(
			'conditions' => array(
				"{$this->Subscriber->alias}.{$this->settings['sync_key']} IS NULL",
			),
			'limit'	=> $this->settings['records_per_sync']
		));
		$this->__sync($records);
	}
	
	function __sync($records) {
		foreach($records as $i => $subscriber) {
			// Check if this subscriber exists in CM ?
			$cmRecord = $this->Subscriber->assertCMRecord($subscriber[$this->Subscriber->alias]['email']);
			
			if(false == $cmRecord) {
				$this->Subscriber->addCMRecord($subscriber);
				$this->result['added']++;
			} else {
				$this->Subscriber->syncCMRecord($subscriber);
				$this->result['synced']++;
			}
		}
		
		$found = count($records);
		$this->log("Found	: {$found} records to sync");
		$this->log("Added	: {$this->result['added']}");
		$this->log("Synced	: {$this->result['synced']}");
	}
	
}