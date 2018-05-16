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
   * Awaiting 'sync status' value id
   *
   * @var int
   */
  private $syncStatusValue;

  /**
   * CRM_Odoosync_Sync_Contact_PendingContacts constructor.
   *
   * @throws \CiviCRM_API3_Exception
   */
  public function __construct() {
    $this->syncStatusFieldId = CRM_Odoosync_Common_CustomField::getCustomFieldId(
      'odoo_partner_sync_information',
      'sync_status'
    );
    $this->syncStatusValue = CRM_Odoosync_Common_OptionValue::getOptionValueID(
      'odoo_sync_status',
      'awaiting_sync'
    );
  }

  /**
   * Gets non-synchronized contact Ids
   *
   * @return array
   */
  public function getPendingContacts() {
    try {
      $contactList = civicrm_api3('Contact', 'get', [
        'return' => ["id"],
        'is_deleted' => ['IS NOT NULL' => 1],
        'contact_type' => ["Individual", "Organization"],
        'options' => [
          'sort' => "contact_type DESC",
          'limit' => CRM_Odoosync_Sync_BatchSize::getCurrentButchSize()
        ],
        'custom_' . $this->syncStatusFieldId => $this->syncStatusValue,
      ]);

      $contactListId = [];
      foreach ($contactList['values'] as $contact) {
        $contactListId[] = $contact['contact_id'];
      }

      CRM_Odoosync_Sync_BatchSize::setUsedSize(count($contactListId));

      return $contactListId;
    }
    catch (CiviCRM_API3_Exception $e) {
      return [];
    }
  }

}
