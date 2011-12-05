<?php
class SynchronizationController extends AppController {
	
	var $name = 'Synchronization';
	
	var $uses = array();
	
/**
 *	Sync WebApp->CM
 */	
	function admin_synchronize_subscribers() {
		
		$settings			= Configure::read('CampaignMonitor.settings');
		$this->User			= ClassRegistry::init($settings['subcriber_model']);
		
		# Get the records that have not yet been synced or updated in the last day
		$this->User->contain();
		$records = $this->User->find('all', array(
			'conditions' => array(
				'OR' => array(
					"{$this->User->alias}.{$settings['sync_key']}" => date('Y-m-d H:i:s', strtotime('-1 Day', time())),
					"{$this->User->alias}.{$settings['sync_key']} IS NULL",
				)
			),
			'limit'	=> $settings['records_per_sync']
		));
				
		foreach($records as $i => $subscriber) {
			
		}
	}
	
}