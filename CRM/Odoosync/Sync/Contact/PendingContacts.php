<?php

/**
 * Gets appropriate contacts for synchronization with Odoo
 */
class CRM_Odoosync_Sync_Contact_PendingContacts {

  /**
   * Sync contact id
   *
   * @var int
   */
  private $syncStatusFieldId;

  /**
   * Sync contact id
   *
   * @var int
   */
  private $syncStatusValue;

  /**
   * CRM_Odoosync_Sync_Contact_SyncInformation constructor.
   *
   * @throws \CiviCRM_API3_Exception
   */
  public function __construct() {
    $this->setSyncStatusFieldId();
    $this->setAwaitingSyncValue();
  }

  /**
   * Gets non-synchronized contact Ids
   *
   * @return array
   */
  public function getPendingContacts() {
    $syncSetting = CRM_Odoosync_Setting::getInstance()->retrieve();

    try {
      $contacts = civicrm_api3('Contact', 'get', [
        'return' => ["id"],
        'is_deleted' => ['IS NOT NULL' => 1],
        'options' => ['limit' => (int) $syncSetting['odoosync_batch_size']],
        'custom_' . $this->syncStatusFieldId => $this->syncStatusValue,
      ]);

      $contactListId = [];
      foreach ($contacts['values'] as $contact) {
        $contactListId[] = $contact['contact_id'];
      }

      return $contactListId;
    }
    catch (CiviCRM_API3_Exception $e) {
      return [];
    }
  }

  /**
   * Gets custom field id for sync status
   *
   * @throws \CiviCRM_API3_Exception
   */
  private function setSyncStatusFieldId() {
    $result = civicrm_api3('CustomField', 'getvalue', [
      'return' => "id",
      'name' => 'sync_status',
      'custom_group_id' => 'odoo_partner_sync_information',
    ]);

    $this->syncStatusFieldId = (int) $result;
  }

  /**
   * Gets option value by OptionGroup and OptionValue names
   *
   * @throws \CiviCRM_API3_Exception
   */
  private function setAwaitingSyncValue() {
    $value = civicrm_api3('OptionValue', 'get', [
      'sequential' => 1,
      'return' => ["value"],
      'option_group_id' => 'odoo_sync_status',
      'name' => 'awaiting_sync',
    ]);

    $this->syncStatusValue = $value['values'][0]['value'];
  }

}
