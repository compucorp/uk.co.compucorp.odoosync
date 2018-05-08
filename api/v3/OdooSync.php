<?php

/**
 * This API get called when run schedule job "Sync CiviCRM changes to Odoo"
 *
 * @param $params
 *
 * @return mixed
 * @throws \Exception
 */
function civicrm_api3_odoo_sync_run($params) {
  $log = [];
  $logContact = (new CRM_Odoosync_Sync_Contact($params))->run();
  $logContribution = (new CRM_Odoosync_Sync_Contribution($params))->run();

  $log['is_error'] = ($logContact['is_error'] == 1 || $logContact['is_error'] == 1) ? 1 : 0;

  //more detail log can view in api when debug = 1
  if ($params['debug'] == 1) {
    $log['debugLog']['contacts_debug_log'] = $logContact['debugLog'];
    $log['debugLog']['contribution_debug_log'] = $logContribution['debugLog'];
  }

  //this log can view on schedule job
  $log['values'] = '<br/>' . $logContact['values'] . $logContribution['values'];

  return $log;
}

/**
 * Adjusts Metadata for run action
 *
 * The metadata is used for setting defaults, documentation & validation
 * @param array $params
 * Array or parameters determined by getfields
 */
function _civicrm_api3_odoo_sync_run_spec(&$params) {
  $params['debug']['api.default'] = 0;
}

/**
 * This API is used for sending Odoo error message
 *
 * @param $params
 *
 * @return mixed
 */
function civicrm_api3_odoo_sync_send_error_message($params) {
  $errorMail = new CRM_Odoosync_Mail_Error($params['entity_id'], $params['entity_type'], $params['error_message']);
  $log = $errorMail->sendToRecipients();

  return $log;
}

/**
 * Adjust Metadata for "send_error_message" action
 *
 * The metadata is used for setting defaults, documentation & validation
 * @param array $params
 * Array or parameters determined by getfields
 */
function _civicrm_api3_odoo_sync_send_error_message_spec(&$params) {
  $params['error_message']['api.required'] = 1;
  $params['entity_type']['api.required'] = 1;
  $params['entity_id']['api.required'] = 1;
}
