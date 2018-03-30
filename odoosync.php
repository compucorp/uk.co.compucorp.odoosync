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
 * Implements hook_civicrm_postProcess().
 *
 */
function odoosync_civicrm_postProcess($formName, &$form) {
  postProcessContactInlineForms($formName, $form);
  postProcessContactTaskDelete($formName, $form);
  postProcessContactForm($formName, $form);
}

/**
 * Updates contact sync information through editing contact form inline-blocks
 *
 * @param $formName
 * @param $form
 */
function postProcessContactInlineForms($formName, $form) {
  $isInlineContactForm = preg_match('/^CRM_Contact_Form_Inline_/', $formName);
  if ($isInlineContactForm && ($form->getAction() == CRM_Core_Action::UPDATE || CRM_Core_Action::ADD || CRM_Core_Action::DELETE)) {
    $contactId = (int) $form->getVar('_contactId');
    $syncInformationUpdater = new CRM_Odoosync_Hook_PostProcess_ContactSyncInformationUpdater();
    $syncInformationUpdater->updateSyncInfo($contactId, 'update');
  }
}

/**
 * Updates contact sync information within contact deleting process
 *
 * @param $formName
 * @param $form
 */
function postProcessContactTaskDelete($formName, $form) {
  if ($formName == "CRM_Contact_Form_Task_Delete" && $form->getAction() == CRM_Core_Action::NONE) {
    $skipUnDelete = $form->getVar('_skipUndelete');
    if (!$skipUnDelete) {
      $contactIds = $form->getVar('_contactIds');
      $syncInformationUpdater = new CRM_Odoosync_Hook_PostProcess_ContactSyncInformationUpdater();
      foreach ($contactIds as $contactId) {
        $syncInformationUpdater->updateSyncInfo($contactId, 'update');
      }
    }
  }
}

/**
 * Updates contact sync information within contact creating or updating process
 *
 * @param $formName
 * @param $form
 */
function postProcessContactForm($formName, $form) {
  if ($formName == "CRM_Contact_Form_Contact") {
    $contactId = (int) $form->getVar('_contactId');
    $syncInformationUpdater = new CRM_Odoosync_Hook_PostProcess_ContactSyncInformationUpdater();

    if ($form->getAction() == CRM_Core_Action::ADD) {
      $syncInformationUpdater->updateSyncInfo($contactId, 'create');
    }

    if ($form->getAction() == CRM_Core_Action::UPDATE) {
      $syncInformationUpdater->updateSyncInfo($contactId, 'update');
    }
  }
}
