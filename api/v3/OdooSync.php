<?php

/**
 * This api calls when run schedule job "Sync CiviCRM changes to Odoo"
 *
 * @param $params
 *
 * @return bool
 */
function civicrm_api3_odoo_sync_run($params) {
  //TODO in next COS
  return TRUE;
}

/**
 * This api for odoo send error message
 *
 * @param $params
 *
 * @return mixed
 */
function civicrm_api3_odoo_sync_send_error_message($params) {
  $errorMail = new CRM_Odoosync_Mail_Error($params['error_message'], $params['entity_type'], $params['entity_id']);
  $errorMail->sendErrorMessage();
  return $errorMail->getReturnData();
}

/**
 * Adjust Metadata for "send_error_message" action
 *
 * The metadata is used for setting defaults, documentation & validation
 * @param array $params array or parameters determined by getfields
 */
function _civicrm_api3_odoo_sync_send_error_message_spec(&$params) {
  $params['error_message']['api.required'] = 1;
  $params['entity_type']['api.required'] = 1;
  $params['entity_id']['api.required'] = 1;
}
