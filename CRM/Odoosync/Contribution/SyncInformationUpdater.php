<?php

/**
 * Updates Contribution sync information
 */
class CRM_Odoosync_Contribution_SyncInformationUpdater {

  /**
   * Contribution id
   *
   * @var int
   */
  private $contributionId;

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
   * CRM_Odoosync_Contribution_SyncInformationUpdater constructor.
   *
   * @param int $contributionId
   * @param $syncStatus
   * @param $actionToSync
   * @param $actionDate
   */
  public function __construct($contributionId, $syncStatus, $actionToSync, $actionDate) {
    $this->contributionId = $contributionId;
    $this->actionToSync = $actionToSync;
    $this->syncStatus = $syncStatus;
    $this->actionDate = $actionDate;
  }

  /**
   * Updates contribution sync information
   *
   * @throws \CiviCRM_API3_Exception
   */
  public function updateSyncInfo() {
    $actionToSyncValueId = CRM_Odoosync_Common_OptionValue::getOptionValueID('odoo_invoice_action_to_sync', $this->actionToSync);
    $syncStatusValueId = CRM_Odoosync_Common_OptionValue::getOptionValueID('odoo_sync_status', $this->syncStatus);

    if ($this->isContributionSyncInfoCreated()) {
      $query = "
        UPDATE odoo_invoice_sync_information
        SET action_to_sync = %2, sync_status = %3, action_date = %4
        WHERE entity_id = %1 
      ";
    }
    else {
      $query = "
        INSERT INTO odoo_invoice_sync_information (entity_id, action_to_sync, sync_status, action_date) 
        VALUES (%1, %2, %3, %4)
      ";
    }

    CRM_Core_DAO::executeQuery($query, [
      1 => [$this->contributionId, 'Integer'],
      2 => [$actionToSyncValueId, 'String'],
      3 => [$syncStatusValueId, 'String'],
      4 => [$this->actionDate, 'String']
    ]);

  }

  /**
   * Checks if contribution sync information is already created
   *
   * @return bool
   */
  private function isContributionSyncInfoCreated() {
    $query = "SELECT * FROM odoo_invoice_sync_information WHERE entity_id = %1 LIMIT 1";

    $dao = CRM_Core_DAO::executeQuery($query, [
      1 => [$this->contributionId, 'Integer']
    ]);

    return !empty($dao->fetchAll());
  }

}
