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
  return (new CRM_Odoosync_Sync())->run($params);
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
  CRM_Odoosync_Mail_Error::collectMessage($params['entity_type'], $params['entity_id'], $params['error_message']);
  $log = (new CRM_Odoosync_Mail_Error())->sendToRecipients();

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

/**
 * This API is used for sync transaction from Odoo
 *
 * @param $params
 *
 * @throws \Exception
 */
function civicrm_api3_odoo_sync_transaction($params) {
  $transactionSync = new CRM_Odoosync_Sync_Inbound_Transaction();
  $transactionSync->run();
}
