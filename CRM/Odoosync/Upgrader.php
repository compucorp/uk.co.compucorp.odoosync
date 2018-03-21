<?php
  use CRM_Odoosync_ExtensionUtil as E;

/**
 * Collection of upgrade steps.
 */
class CRM_Odoosync_Upgrader extends CRM_Odoosync_Upgrader_Base {

  /**
   * @throws \CiviCRM_API3_Exception
   */
  public function install() {
    $this->createSyncErrorMessageTemplate();
  }

  /**
   * This hook is called immediately after an extension is installed.
   *
   * @throws \CiviCRM_API3_Exception
   */
  public function postInstall() {
    $this->initializeContactsSyncInformation();
  }

  /**
   * This hook call when extension uninstall
   *
   * @throws \CiviCRM_API3_Exception
   */
  public function uninstall() {
    $this->deleteSyncErrorMessageTemplate();
    $this->deleteExtensionOptionGroups();
    $this->deleteExtensionCustomGroups();
  }

  /**
   * Deletes all the option groups created by the extension
   *
   * @throws \CiviCRM_API3_Exception
   */
  private function deleteExtensionOptionGroups() {
    $optionGroupsToDelete = ['msg_tpl_workflow_odoo_sync', 'odoo_sync_status', 'odoo_partner_action_to_sync', 'odoo_invoice_action_to_sync'];

    foreach ($optionGroupsToDelete as $optionGroupName) {
      $this->deleteOptionGroup($optionGroupName);
    }
  }

  /**
   * Deletes 'CiviCRM-Odoo Sync Error Report' message template
   *
   * @throws \CiviCRM_API3_Exception
   */
  private function deleteSyncErrorMessageTemplate() {
    civicrm_api3('MessageTemplate', 'get', [
      'msg_title' => "CiviCRM Odoo Sync Error Report",
      'api.MessageTemplate.delete' => ['id' => '$value.id'],
    ]);
  }

  /**
   * Deletes all the custom groups created by the extension
   *
   * @throws \CiviCRM_API3_Exception
   */
  private function deleteExtensionCustomGroups() {
    $customGroupsToDelete = ['odoo_invoice_sync_information', 'odoo_partner_sync_information', 'purchase_order'];

    foreach ($customGroupsToDelete as $customGroupName) {
      $this->deleteCustomGroup($customGroupName);
    }
  }

  /**
   * Deletes the specified option group
   *
   * @param string $optionGroupName
   *
   * @throws \CiviCRM_API3_Exception
   */
  private function deleteOptionGroup($optionGroupName) {
    civicrm_api3('OptionGroup', 'get', [
      'name' => $optionGroupName,
      'api.OptionGroup.delete' => ['id' => '$value.id'],
    ]);
  }

  /**
   * Deletes the specified custom group
   *
   * @param string $customGroupName
   *
   * @throws \CiviCRM_API3_Exception
   */
  private function deleteCustomGroup($customGroupName) {
    civicrm_api3('CustomGroup', 'get', [
      'name' => $customGroupName,
      'api.CustomGroup.delete' => ['id' => '$value.id'],
    ]);
  }

  /**
   *  Initializes contacts sync information
   *  Set custom field "action_to_sync" and "sync_status" default values
   *
   * @throws \CiviCRM_API3_Exception
   */
  private function initializeContactsSyncInformation() {
    $defaultAction = CRM_Odoosync_Utils_CustomData::getDefaultOptionValue('odoo_partner_action_to_sync');
    $defaultStatus = CRM_Odoosync_Utils_CustomData::getDefaultOptionValue('odoo_sync_status');

    $query = "
      INSERT INTO odoo_partner_sync_information(entity_id, action_to_sync, sync_status)
      SELECT id, %1 , %2 FROM civicrm_contact
      WHERE id NOT IN (SELECT entity_id FROM odoo_partner_sync_information);
      ";

    CRM_Core_DAO::executeQuery($query, [
      1 => [$defaultAction, 'Integer'],
      2 => [$defaultStatus, 'Integer'],
    ]);
  }

  /**
   * Creates 'CiviCRM-Odoo Sync Error Report' message template
   *
   * @throws \CiviCRM_API3_Exception
   */
  private function createSyncErrorMessageTemplate() {
    $templateFilePath = $this->extensionDir . "/templates/CRM/Odoosync/DefaultMessageTemplates/OdooSyncErrorReport.html";

    $messageHtml = '';
    if (file_exists($templateFilePath)) {
      $messageHtml = file_get_contents($templateFilePath);
    }
    else {
      CRM_Core_Session::setStatus(ts('Creating message template'), ts("Couldn't find default template at '$templateFilePath'"), 'alert');
    }

    $workflowId = $this->getSyncErrorTemplateWorkflowID();

    civicrm_api3('MessageTemplate', 'create', [
      'msg_title' => "CiviCRM Odoo Sync Error Report",
      'msg_subject' => ts("CiviCRM Odoo Sync Error Report"),
      'is_reserved' => 0,
      'msg_html' => $messageHtml,
      'is_active' => 1,
      'msg_text' => 'N/A',
      'workflow_id' => $workflowId
    ]);
  }

  /**
   * Gets workflow id related to the message template
   *
   * @throws \CiviCRM_API3_Exception
   */
  private function getSyncErrorTemplateWorkflowID() {
    $id = civicrm_api3('OptionValue', 'getvalue', [
      'return' => "id",
      'option_group' => 'msg_tpl_workflow_odoo_sync',
      'name' => 'civicrm_odoo_sync_error_report',
    ]);

    return (int) $id;
  }

}
