<?php
  use CRM_Odoosync_ExtensionUtil as E;

/**
 * Collection of upgrade steps.
 */
class CRM_Odoosync_Upgrader extends CRM_Odoosync_Upgrader_Base {

  /**
   * This hook call when extension install
   *
   * @throws \CiviCRM_API3_Exception
   */
  public function install() {
    $this->installMessageTemplate();
  }

  /**
   * This hook is called immediately after an extension is installed.
   *
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
    $this->deleteMessageTemplate();
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
   * Install default message template
   *
   * @throws \CiviCRM_API3_Exception
   */
  private function installMessageTemplate() {

    $filePath = $this->extensionDir . "/templates/CRM/Odoosync/DefaultMessageTemplates/OdooSyncErrorReport.html";

    $messageHtml = '';
    if (file_exists($filePath)) {
      $messageHtml = file_get_contents($filePath);
    }
    else {
      CRM_Core_Session::setStatus(ts('Creating message template'), ts("Couldn't find default template at '$filePath'"), 'alert');
    }

    $workflowId = $this->getWorkflowId();

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
  private function getWorkflowId() {
    $id = civicrm_api3('OptionValue', 'getvalue', [
      'return' => "id",
      'option_group' => 'msg_tpl_workflow_odoo_sync',
      'name' => 'civi_crm_odoo_sync_error_report',
    ]);

    return (int) $id;
  }

  /**
   * Deletes the message template created by the extension
   *
   * @throws \CiviCRM_API3_Exception
   */
  private function deleteMessageTemplate() {

    $messageTemplateId = $this->getMessageTemplateIdByTitle("CiviCRM Odoo Sync Error Report");

    if ($messageTemplateId) {
      civicrm_api3('MessageTemplate', 'delete', [
        'id' => $messageTemplateId,
      ]);
    }
  }

  /**
   * Gets message template id by title
   *
   * @param string $title
   *
   * @return bool|int
   */
  private function getMessageTemplateIdByTitle($title) {
    try {
      $messageTemplateId = civicrm_api3('MessageTemplate', 'getvalue', [
        'msg_title' => $title,
        'return' => "id"
      ]);

      return (int) $messageTemplateId;
    }
    catch (CiviCRM_API3_Exception $e) {
      return FALSE;
    }
  }

}
