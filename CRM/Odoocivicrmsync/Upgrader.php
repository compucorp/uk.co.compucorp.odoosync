<?php
use CRM_Odoocivicrmsync_ExtensionUtil as E;

/**
 * Collection of upgrade steps.
 */
class CRM_Odoocivicrmsync_Upgrader extends CRM_Odoocivicrmsync_Upgrader_Base {

  /**
   * This hook call when extension uninstall
   *
   * @throws \CiviCRM_API3_Exception
   */
  public function uninstall() {
    $this->deleteGroup('OptionGroup', 'odoo_sync_status');
    $this->deleteGroup('OptionGroup', 'odoo_partner_action_to_sync');
    $this->deleteGroup('OptionGroup', 'odoo_invoice_action_to_sync');
    $this->deleteGroup('CustomGroup', 'odoo_invoice_sync_information');
    $this->deleteGroup('CustomGroup', 'odoo_partner_sync_information');
    $this->deleteGroup('CustomGroup', 'purchase_order');
  }

  /**
   * This hook is called immediately after an extension is installed.
   *
   * @throws \CiviCRM_API3_Exception
   */
  public function postInstall() {
    $contactIds = $this->getAllContactId();

    $syncStatusFieldId = getCustomFieldId('odoo_partner_sync_information', 'sync_status');
    $actionToSyncFieldId = getCustomFieldId('odoo_partner_sync_information', 'action_to_sync');
    foreach ($contactIds as $contactId) {
      saveCustomFieldDataToContact($contactId, $syncStatusFieldId, 1);
      saveCustomFieldDataToContact($contactId, $actionToSyncFieldId, 1);
    }
  }

  /**
   * Get all contact ids
   *
   * @return array
   * @throws \CiviCRM_API3_Exception
   */
  public static function getAllContactId() {
    $contactIds = [];

    $result = civicrm_api3('Contact', 'get', [
      'return' => ["id"],
      'options' => ['limit' => 0],
    ])['values'];

    foreach ($result as $contact) {
      $contactIds[] = $contact['id'];
    }

    return $contactIds;
  }

  /**
   * Delete group function
   *
   * @param string $groupType specify group entity,
   * @param string $fieldIdentifier help to find id of field,
   * @param string $searchBy specify type of $fieldIdentifier
   *
   * @throws \CiviCRM_API3_Exception
   */
  private function deleteGroup($groupType, $fieldIdentifier, $searchBy = 'name') {
    civicrm_api3($groupType, 'delete', [
      'id' => $this->getIdByName($groupType, $fieldIdentifier, $searchBy),
    ]);
  }

  /**
   * Find id by $entity field
   *
   * @param string $entity specify group entity,
   * @param string $fieldIdentifier help to find id of field,
   * @param string $searchBy specify type of $fieldIdentifier
   *
   * @return int
   * @throws \CiviCRM_API3_Exception
   */
  private function getIdByName($entity, $fieldIdentifier, $searchBy) {
    $id = civicrm_api3($entity, 'getvalue', [
      'return' => "id",
      $searchBy => $fieldIdentifier,
    ]);

    return (int) $id;
  }

}
