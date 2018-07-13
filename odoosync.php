<?php

require_once 'odoosync.civix.php';
use CRM_Odoosync_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function odoosync_civicrm_config(&$config) {
  _odoosync_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function odoosync_civicrm_xmlMenu(&$files) {
  _odoosync_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function odoosync_civicrm_install() {
  _odoosync_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_postInstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_postInstall
 */
function odoosync_civicrm_postInstall() {
  _odoosync_civix_civicrm_postInstall();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function odoosync_civicrm_uninstall() {
  _odoosync_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function odoosync_civicrm_enable() {
  _odoosync_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function odoosync_civicrm_disable() {
  _odoosync_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function odoosync_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _odoosync_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function odoosync_civicrm_managed(&$entities) {
  _odoosync_civix_civicrm_managed($entities);
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
function odoosync_civicrm_caseTypes(&$caseTypes) {
  _odoosync_civix_civicrm_caseTypes($caseTypes);
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
function odoosync_civicrm_angularModules(&$angularModules) {
  _odoosync_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function odoosync_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _odoosync_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Implements hook_civicrm_navigationMenu().
 *
 */
function odoosync_civicrm_navigationMenu(&$menu) {

  $menuItem = [
    'name'       => ts('CiviCRM Odoo Sync Configuration'),
    'url'        => 'civicrm/admin/odoosync/configuration',
    'permission' => 'administer CiviCRM',
    'operator'   => NULL,
    'separator'  => NULL,
  ];

  _odoosync_civix_insert_navigation_menu($menu, 'Administer/', $menuItem);
}

/**
 * Implements hook_civicrm_pre().
 */
function odoosync_civicrm_pre($op, $objectName, $id, &$params) {
  $contributionSyncInformationUpdater = new CRM_Odoosync_Hook_Pre_Contribution($op, $objectName, $id, $params);
  $contributionSyncInformationUpdater->process();
}

/**
 * Implements hook_civicrm_post().
 */
function odoosync_civicrm_post($op, $objectName, $objectId, &$objectRef) {
  //contribution
  if ($objectName == 'Contribution') {
    if ($op == 'create' || $op == 'edit') {
      $contribution = new CRM_Odoosync_Hook_Post_Contribution_Contribution($op, $objectName, $objectId, $objectRef);
      $contribution->process();
    }
  }

  //entityFinancialTrxn
  if ($objectName == 'EntityFinancialTrxn' && $objectRef->entity_table == "civicrm_financial_item"
    && ($op == 'create' || $op == 'edit' || $op == 'delete')) {
    $entityFinancialTrxn = new CRM_Odoosync_Hook_Post_Contribution_EntityFinancialTrxn($op, $objectName, $objectId, $objectRef);
    $entityFinancialTrxn->process();
  }

  //lineItem
  if ($objectName == 'LineItem' && isset($objectRef->entity_table)
    && $objectRef->entity_table == 'civicrm_contribution'
    && ($op == 'create' || $op == 'edit' || $op == 'delete')) {
    $lineItem = new CRM_Odoosync_Hook_Post_Contribution_LineItem($op, $objectName, $objectId, $objectRef);
    $lineItem->process();
  }

  //Organization, Individual
  $isContactOperation = ($objectName == 'Organization' || $objectName == 'Individual')
    && ($op == 'create' || $op == 'edit' || $op == 'update' ||  $op == 'delete'|| $op == 'trash'|| $op == 'restore');
  if ($isContactOperation) {
    $contact = new CRM_Odoosync_Hook_Post_Contact_Contact($op, $objectName, $objectId, $objectRef);
    $contact->process();
  }

  //Address, Email, IM, Website, Phone
  $isContactSubEntityOperation = ($objectName == 'Address'
      || $objectName == 'Email'
      || $objectName == 'IM'
      || $objectName == 'Phone'
      || $objectName == 'Website')
    && ($op == 'create' || $op == 'edit' || $op == 'delete');
  if ($isContactSubEntityOperation) {
    $contact = new CRM_Odoosync_Hook_Post_Contact_SubEntity($op, $objectName, $objectId, $objectRef);
    $contact->process();
  }

}
