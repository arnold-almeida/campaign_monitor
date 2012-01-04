<?php
/**
 * Campaign Monitor Behavior class file.
 *
 * @filesource
 * @author Craig Morris
 * @author Arnold Almeida <arnold@coppaalmeida.com>
 * @version	0.2
 * @license	http://www.opensource.org/licenses/mit-license.php The MIT License
 * @package app
 * @subpackage app.models.behaviors
 */

/**
 * Model behavior to support synchronisation of member records with Campaign Monitor.
 *
 * Features:
 * 
 * 
 * Todo:
 * - Will add members to campaign monitor after saving.
 * - Will unsubscribe members from campaign monitor after a member is deleted.
 * - Adding subscribers with custom fields from your member model (even if they have different names)
 * - Ability to check a "opt in" field in your model and will unsubscribe or subscribe. This feature can be turned off
 *
 * Usage:
 *
 	var $actsAs = array(
		'CampaignMonitor.Subscriber' => array(
			'ApiKey' => 'YOUR API KEY',
			'ListId' => 'YOUR LIST ID',
			'CustomFields' => array(
				'MODEL FIELD' => 'CAMPAIGN MONITOR FIELD',
				'FIELD' // < ---- ONLY IF YOUR MODEL FIELD AND CAMPAIGN MONITOR CUSTOM FIELD HAVE THE SAME NAME
			),
			'optin' => MODEL FIELD // Checked after save and subscribes / unsubscribes. False for no check.
		)
	);
 *
 *
 * @package app
 * @subpackage app.models.behaviors
 */
class SubscriberBehavior extends ModelBehavior
{
	/**
	* @var CampaignMonitor
	*/
	var $_CM;

/**
 * 
 * @param type $model
 * @param type $settings 
 */	
	function setup(&$model, $settings) {
		if (!isset($this->settings[$model->alias])) {
			$this->settings[$model->alias] = array(
				'ApiKey' => '',
				'ListId' => '',
				'CustomFields' => array(),
				'StaticFields' => array(),
				'email' => 'email',
				'name' => 'name',
				'optin' => 'optin'
			);
		}
		$this->settings[$model->alias] = array_merge($this->settings[$model->alias], (array) $settings);
		
		# Init the object
		App::import('Vendor', 'CampaignMonitor.CS_REST_Subscribers', array('file' => 'CampaignMonitor' . DS . 'csrest_subscribers.php'));
		$settings = $this->settings[$model->alias];
		extract($settings);
		$this->_CM = new CS_REST_Subscribers($ListId, $ApiKey);
	}
	
/**
* Public function to sync the record, used in afterSave or manual sync calls
* 
* @param mixed $model
* @param mixed $created 
*/
	function syncCMRecord(&$model, $data) {
		
		$settings = $this->settings[$model->alias];
		extract($settings);
		
		$cmRecord = array(
			'EmailAddress'	=> $data[$model->alias]['email'],
			'Name'			=> (isset($data[$model->alias]['name'])) ? $data[$model->alias]['email'] : "",
			'CustomFields'	=> $this->_mapCustomFields($CustomFields, $data, array('alias' => $model->alias))
		);
		
		$response = $this->_CM->update($data[$model->alias]['email'], $cmRecord);
		
		if(200 == $response->http_status_code) {
			// Cool! Flag this record as being synced...
			$model->id = $data[$model->alias]['id'];
			$model->saveField($settings['config']['sync_key'], date('Y-m-d H:i:s', time()));
			
			return true;
		}
		
		return false;
	}
	
/**
* Public function to add a new Subscriber record
* 
* @param mixed $model
* @param mixed $created 
*/
	function addCMRecord(&$model, $data) {
		
		$settings = $this->settings[$model->alias];
		extract($settings);
		
		$cmRecord = array(
			'EmailAddress'	=> $data[$model->alias]['email'],
			'Name'			=> (isset($data[$model->alias]['name'])) ? $data[$model->alias]['email'] : "",
			'CustomFields'	=> $this->_mapCustomFields($CustomFields, $data, array('alias' => $model->alias)),
			'Resubscribe'	=> false
		);
		
		$response = $this->_CM->add($cmRecord);
		
		if(201 == $response->http_status_code) {
			// Cool! Flag this record as being synced...
			$model->id = $data[$model->alias]['id'];
			$model->saveField($settings['config']['sync_key'], date('Y-m-d H:i:s', time()));
			
			return true;
		}
		
		return false;
	}
	
	/**
	 *
	 * @param type $customFields
	 * @param type $data 
	 */	
	function _mapCustomFields($customFields, $data, $options=array()) {
		$out  = array();
		foreach($customFields as $model_field => $cm_field) {
			if(isset($data[$options['alias']][$model_field]) && !empty($data[$options['alias']][$model_field])) {
				$out[] = array(
					'Key'	=> $cm_field,
					'Value'	=> $data[$options['alias']][$model_field],
				);
			}
		}
		return $out;
	}

	/**
	* Returns the email, name and optin values from the data based on the settings. hasOpted
	* will be true if there is no optin field set.
	*
	* @param mixed $data
	*/
	function _extract(&$model, $data)
	{
		$settings = $this->settings[$model->alias];
		extract($settings);

		// Get data out for email.
		$alias = $model->alias;
		if ( strpos($email, '.') ) {
			list($alias, $email) = explode('.', $email);
		}
		$email = $data[$alias][$email];

		// Get data out for name.
		$alias = $model->alias;
		$myName = null;
		if ( strpos($name, '.') ) {
			list($alias, $name) = explode('.', $name);
		}
		if ( isset($data[$alias][$name]) ) {
			$myName = $data[$alias][$name];
		}

		// Get data out for optin
		$hasOpted = true;
		if ( $optin ) {
			$alias = $model->alias;
			if ( strpos($optin, '.') ) {
				list($alias, $optin) = explode('.', $optin);
			}
			if ( isset($data[$alias][$optin]) ) {
				$hasOpted = $data[$alias][$optin];
			}
		}

		$arr = array($email, $myName, $hasOpted);
		return $arr;
	}

/**
* Returns an array of all the custom fields to be sent to campaign monitor, this
* comprises of the CustomFields (from the model data) and the static fields (
* these are usually constant flags or something)
*
* @param mixed $data
* @param mixed $CustomFields
* @param mixed $StaticFields
*/
	function _getCustomFields($data, $CustomFields, $StaticFields)
	{
		$myCustomFields = array();
		foreach ($CustomFields as $key => $field) {
			// if key is numeric, use the field as the model field and the CM custom field
			// otherwise, use the key as the model field and the field as the CM custom field.
			if ( is_numeric($key) ) {
				$myCustomFields[$field] = $data[$field];
			}
			else {
				$myCustomFields[$field] = $data[$key];
			}
		}
		foreach ($StaticFields as $field => $value) {
			$myCustomFields[$field] = $value;
		}
		return $myCustomFields;
	}

	
/**
 * Checks if this Subscriber exists in CM returns FALSE when Subscriber is not found in configured API list
 * 
 * @param String $email 
 * @return Array $data
 */	
	function assertCMRecord(&$model, $email) {
		
		$data = $this->_CM->get($email);
		
		if(isset($data->response->Code) && 203 == $data->response->Code) {
			return false;
		}
		
		return $data;
	}
}