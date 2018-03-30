<?php
  use CRM_Odoosync_ExtensionUtil as E;

/**
 * Collection of upgrade steps.
 */
class CRM_Odoosync_Upgrader extends CRM_Odoosync_Upgrader_Base {

  /**
   * Custom groups created by the extension
   *
   * @var array
   */
  private $customGroups = [
    'odoo_invoice_sync_information',
    'odoo_partner_sync_information',
    'purchase_order'
  ];

  /**
   * Option groups created by the extension
   *
   * @var array
   */
  private $optionGroups = [
    'msg_tpl_workflow_odoo_sync',
    'odoo_sync_status',
    'odoo_partner_action_to_sync',
    'odoo_invoice_action_to_sync'
  ];

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
   * Run when a module is enabled.
   *
   * @throws \CiviCRM_API3_Exception
   */
  public function enable() {
    $this->enableExtensionOptionGroups();
    $this->enableExtensionCustomGroups();
  }

  /**
   * Run when a module is disabled.
   *
   * @throws \CiviCRM_API3_Exception
   */
  public function disable() {
    $this->disableExtensionOptionGroups();
    $this->disableExtensionCustomGroups();
  }

  /**
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
    foreach ($this->optionGroups as $optionGroupName) {
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
    foreach ($this->customGroups as $customGroupName) {
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
    $defaultAction = $this->getDefaultOptionValue('odoo_partner_action_to_sync');
    $defaultStatus = $this->getDefaultOptionValue('odoo_sync_status');

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
   * Gets default value in the option group
   *
   * @param $optionGroupName
   *
   * @return int
   * @throws \CiviCRM_API3_Exception
   */
  private function getDefaultOptionValue($optionGroupName) {
    $value = civicrm_api3('OptionValue', 'get', [
      'sequential' => 1,
      'return' => ["value"],
      'option_group_id' => $optionGroupName,
      'is_default' => 1,
    ]);

    return (int) $value['values'][0]['value'];
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

  /**
   * Enables all the option groups created by the extension
   *
   * @throws \CiviCRM_API3_Exception
   */
  private function enableExtensionOptionGroups() {
    foreach ($this->optionGroups as $optionGroupName) {
      $this->toggleOptionGroup($optionGroupName, TRUE);
    }
  }

  /**
   * Enables all the custom groups created by the extension
   *
   * @throws \CiviCRM_API3_Exception
   */
  private function enableExtensionCustomGroups() {
    foreach ($this->customGroups as $customGroupName) {
      $this->toggleCustomGroup($customGroupName, TRUE);
    }
  }

  /**
   * Disables all the custom groups created by the extension
   *
   * @throws \CiviCRM_API3_Exception
   */
  private function disableExtensionCustomGroups() {
    foreach ($this->customGroups as $customGroupName) {
      $this->toggleCustomGroup($customGroupName, FALSE);
    }
  }

  /**
   * Disables all the option groups created by the extension
   *
   * @throws \CiviCRM_API3_Exception
   */
  private function disableExtensionOptionGroups() {
    foreach ($this->optionGroups as $optionGroupName) {
      $this->toggleOptionGroup($optionGroupName, FALSE);
    }
  }

  /**
   * Sets is_active for OptionGroups
   *
   * @param string $name
   * @param bool $isActive
   *
   * @throws \CiviCRM_API3_Exception
   */
  private function toggleOptionGroup($name, $isActive) {
    civicrm_api3('OptionGroup', 'get', [
      'name' => $name,
      'api.OptionGroup.create' => [
        'id' => '$value.id',
        'is_active' => (int) $isActive
      ],
    ]);
  }

  /**
   * Sets is_active for CustomGroups
   *
   * @param string $name
   * @param bool $isActive
   *
   * @throws \CiviCRM_API3_Exception
   */
  private function toggleCustomGroup($name, $isActive) {
    $customGroup = civicrm_api3('CustomGroup', 'getsingle', [
      'name' => $name,
      'return' => ['id']
    ]);

    if (isset($customGroup['id'])) {
      CRM_Core_BAO_CustomGroup::setIsActive((int) $customGroup['id'], $isActive);
    }
  }

}
