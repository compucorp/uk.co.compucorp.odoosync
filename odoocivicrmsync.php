<?php

require_once 'odoocivicrmsync.civix.php';
use CRM_Odoocivicrmsync_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function odoocivicrmsync_civicrm_config(&$config) {
  _odoocivicrmsync_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function odoocivicrmsync_civicrm_xmlMenu(&$files) {
  _odoocivicrmsync_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function odoocivicrmsync_civicrm_install() {
  _odoocivicrmsync_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_postInstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_postInstall
 */
function odoocivicrmsync_civicrm_postInstall() {
  _odoocivicrmsync_civix_civicrm_postInstall();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function odoocivicrmsync_civicrm_uninstall() {
  _odoocivicrmsync_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function odoocivicrmsync_civicrm_enable() {
  _odoocivicrmsync_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function odoocivicrmsync_civicrm_disable() {
  _odoocivicrmsync_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function odoocivicrmsync_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _odoocivicrmsync_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function odoocivicrmsync_civicrm_managed(&$entities) {
  _odoocivicrmsync_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_caseTypes().
 *
 * Generate a list of case-types.
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function odoocivicrmsync_civicrm_caseTypes(&$caseTypes) {
  _odoocivicrmsync_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Angular modules.
 *
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_angularModules
 */
function odoocivicrmsync_civicrm_angularModules(&$angularModules) {
  _odoocivicrmsync_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function odoocivicrmsync_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _odoocivicrmsync_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Save custom field data to contact
 *
 * @param $contactId
 * @param $customFieldId
 * @param $value
 *
 * @throws \CiviCRM_API3_Exception
 */
function saveCustomFieldDataToContact($contactId, $customFieldId, $value) {
  civicrm_api3('Contact', 'create', [
    'id' => $contactId,
    'custom_' . $customFieldId => $value,
  ]);
}

/**
 * Get custom field id by group name and field name
 *
 * @param $customGroupName
 * @param $name
 *
 * @return int
 * @throws \CiviCRM_API3_Exception
 */
function getCustomFieldId($customGroupName, $name) {
  $result = civicrm_api3('CustomField', 'getvalue', [
    'return' => "id",
    'name' => $name,
    'custom_group_id' => $customGroupName,
  ]);

  return (int) $result;
}
