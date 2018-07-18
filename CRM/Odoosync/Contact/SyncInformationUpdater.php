<?php

/**
 * Updates Contact sync information
 */
class CRM_Odoosync_Contact_SyncInformationUpdater {

  /**
   * Contact id
   *
   * @var int
   */
  private $contactId;

  /**
   * Action to sync
   *
   * @var string
   */
  private $actionToSync;

  /**
   * Sync status
   *
   * @var string
   */
  private $syncStatus;

  /**
   * Action date (MySQL date format 'Y-m-d H:i:s')
   *
   * @var string
   */
  private $actionDate;

  /**
   * CRM_Odoosync_Contact_SyncInformationUpdater constructor.
   *
   * @param $contactId
   * @param $syncStatus
   * @param $actionToSync
   * @param $actionDate
   */
  public function __construct($contactId, $syncStatus, $actionToSync, $actionDate) {
    $this->contactId = $contactId;
    $this->actionToSync = $actionToSync;
    $this->syncStatus = $syncStatus;
    $this->actionDate = $actionDate;
  }

  /**
   * Updates contact sync information
   *
   * @throws \CiviCRM_API3_Exception
   */
  public function updateSyncInfo() {
    $actionToSyncValueId = CRM_Odoosync_Common_OptionValue::getOptionValueID('odoo_partner_action_to_sync', $this->actionToSync);
    $syncStatusValueId = CRM_Odoosync_Common_OptionValue::getOptionValueID('odoo_sync_status', $this->syncStatus);

    if ($this->isContactSyncInfoCreated()) {
      $query = "
        UPDATE odoo_partner_sync_information
        SET action_to_sync = %2, sync_status = %3, action_date = %4
        WHERE entity_id = %1 
      ";
    }
    else {
      $query = "
        INSERT INTO odoo_partner_sync_information (entity_id, action_to_sync, sync_status, action_date) 
        VALUES (%1, %2, %3, %4)
      ";
    }

    CRM_Core_DAO::executeQuery($query, [
      1 => [$this->contactId, 'Integer'],
      2 => [$actionToSyncValueId, 'String'],
      3 => [$syncStatusValueId, 'String'],
      4 => [$this->actionDate, 'String']
    ]);

  }

  /**
   * Checks if contact sync information is already created
   *
   * @return bool
   */
  private function isContactSyncInfoCreated() {
    $query = "SELECT * FROM odoo_partner_sync_information WHERE entity_id = %1 LIMIT 1";

    $dao = CRM_Core_DAO::executeQuery($query, [
      1 => [$this->contactId, 'Integer']
    ]);

    return !empty($dao->fetchAll());
  }

}
