<?php

/**
 * Gets appropriate contact for synchronization with Odoo
 */
class CRM_Odoosync_Sync_Contact_SyncInformation {

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
    $this->syncStatusFieldId = $this->getCustomFieldId('odoo_partner_sync_information', 'sync_status');
    $this->syncStatusValue = $this->optionValueName('odoo_sync_status', 'awaiting_sync');
  }

  /**
   * Gets first not synchronized contact's id
   *
   * @return int|null
   */
  public function getFirstNotSyncContactId() {
    try {
      $contact = civicrm_api3('Contact', 'get', [
        'return' => ["id"],
        'options' => ['limit' => 1],
        'custom_' . $this->syncStatusFieldId => $this->syncStatusValue,
      ]);

      return (int) $contact['id'];
    }
    catch (CiviCRM_API3_Exception $e) {
      return NULL;
    }
  }

  /**
   * Gets custom field id by group name and field name
   *
   * @param string $customGroupName
   * @param string $name
   *
   * @return int
   * @throws \CiviCRM_API3_Exception
   */
  public static function getCustomFieldId($customGroupName, $name) {
    $result = civicrm_api3('CustomField', 'getvalue', [
      'return' => "id",
      'name' => $name,
      'custom_group_id' => $customGroupName,
    ]);

    return (int) $result;
  }

  /**
   * Gets option value by OptionGroup and OptionValue names
   *
   * @param string $optionGroupName
   * @param string $name
   *
   * @return string
   * @throws \CiviCRM_API3_Exception
   */
  public function optionValueName($optionGroupName, $name) {
    $value = civicrm_api3('OptionValue', 'get', [
      'sequential' => 1,
      'return' => ["value"],
      'option_group_id' => $optionGroupName,
      'name' => $name,
    ]);

    return $value['values'][0]['value'];
  }

}
