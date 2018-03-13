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
    $this->updateContactCustomData();
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
   * Delete all option group from ext
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
   * Delete all custom group from ext
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
   * Delete option group from ext
   *
   * @param $optionGroupName
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
   * Delete custom group from ext
   *
   * @param $customGroupName
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
   *  Update contact custom data
   */
  private function updateContactCustomData() {
    $query = "
      INSERT INTO odoo_partner_sync_information(entity_id, action_to_sync, sync_status)
      SELECT id, 1, 1 FROM civicrm_contact
      WHERE id NOT IN (SELECT entity_id FROM odoo_partner_sync_information);
      ";

    CRM_Core_DAO::executeQuery($query);
  }

}
