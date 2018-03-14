<?php
  use CRM_Odoosync_ExtensionUtil as E;

/**
 * Collection of upgrade steps.
 */
class CRM_Odoosync_Upgrader extends CRM_Odoosync_Upgrader_Base {

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
    $this->deleteExtensionOptionGroups();
    $this->deleteExtensionCustomGroups();
  }

  /**
   * Deletes all the option groups created by the extension
   *
   * @throws \CiviCRM_API3_Exception
   */
  private function deleteExtensionOptionGroups() {
    $optionGroupsToDelete = ['odoo_sync_status', 'odoo_partner_action_to_sync', 'odoo_invoice_action_to_sync'];

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
   *  Initialize contacts sync information
   *  Set custom field "action_to_sync" and "sync_status" default values
   */
  private function initializeContactsSyncInformation() {
    $query = "
      INSERT INTO odoo_partner_sync_information(entity_id, action_to_sync, sync_status)
      SELECT id, 1, 1 FROM civicrm_contact
      WHERE id NOT IN (SELECT entity_id FROM odoo_partner_sync_information);
      ";

    CRM_Core_DAO::executeQuery($query);
  }

}
