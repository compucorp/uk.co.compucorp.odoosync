<?php

/**
 * Updates Contribution sync information
 */
class CRM_Odoosync_Contribution_SyncInformation {

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
   * CRM_Odoosync_Odoosync_Contribution_SyncInformation constructor.
   *
   * @param int $contributionId
   * @param $actionToSync
   * @param $syncStatus
   */
  public function __construct($contributionId, $syncStatus, $actionToSync) {
    $this->contributionId = $contributionId;
    $this->actionToSync = $actionToSync;
    $this->syncStatus = $syncStatus;
  }

  /**
   * Updates contribution sync information
   */
  public function updateSyncInfo() {
    if ($this->isContributionCreated()) {
      $this->update();
    }
    else {
      $this->create();
    }
  }

  /**
   * Checks if contribution sync information is already created
   *
   * @return bool
   */
  private function isContributionCreated() {
    $query = "SELECT * FROM odoo_invoice_sync_information WHERE entity_id = %1 LIMIT 1";

    $dao = CRM_Core_DAO::executeQuery($query, [
      1 => [$this->contributionId, 'Integer']
    ]);

    return !empty($dao->fetchAll());
  }

  /**
   * Updates contribution sync information
   */
  private function update() {
    $query = "
      UPDATE odoo_invoice_sync_information
        JOIN civicrm_option_group AS action_option_group ON action_option_group.name = 'odoo_invoice_action_to_sync'
        LEFT JOIN civicrm_option_value AS action_option 
          ON action_option.option_group_id = action_option_group.id
          AND action_option.name = %2
                  
        JOIN civicrm_option_group AS status_option_group ON status_option_group.name = 'odoo_sync_status'
        LEFT JOIN civicrm_option_value AS status_option 
          ON status_option.option_group_id = status_option_group.id
          AND status_option.name = %3
          
        LEFT JOIN civicrm_contribution AS contribution
          ON contribution.id = %1
      SET 
        action_to_sync = action_option.value,
        sync_status = status_option.value,
        action_date = contribution.receive_date
      WHERE entity_id = %1 
    ";

    CRM_Core_DAO::executeQuery($query, [
      1 => [$this->contributionId, 'Integer'],
      2 => [$this->actionToSync, 'String'],
      3 => [$this->syncStatus, 'String'],
    ]);
  }

  /**
   * Creates contribution sync information
   */
  private function create() {
    $query = "
      INSERT INTO odoo_invoice_sync_information (entity_id, action_to_sync, sync_status, action_date) 
        SELECT %1, action_option.value, status_option.value, contribution.receive_date 
        FROM civicrm_option_group AS action_option_group 
        LEFT JOIN civicrm_option_value AS action_option 
          ON action_option.option_group_id = action_option_group.id
          AND action_option.name = %2
                    
        JOIN civicrm_option_group AS status_option_group ON status_option_group.name = 'odoo_sync_status'
        LEFT JOIN civicrm_option_value AS status_option 
          ON status_option.option_group_id = status_option_group.id
          AND status_option.name = %3
          
        LEFT JOIN civicrm_contribution AS contribution
          ON contribution.id = %1
      WHERE action_option_group.name = 'odoo_invoice_action_to_sync'
    ";

    CRM_Core_DAO::executeQuery($query, [
      1 => [$this->contributionId, 'Integer'],
      2 => [$this->actionToSync, 'String'],
      3 => [$this->syncStatus, 'String'],
    ]);
  }

}
