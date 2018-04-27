<?php

/**
 * Handle Contribution post processes
 */
class CRM_Odoosync_Hook_Post_ContributionProcessesHandler {

  /**
   * Operation being performed with CiviCRM object
   *
   * @var object
   */
  private $operation;

  /**
   * Object name
   *
   * @var object
   */
  private $objectName;

  /**
   * The unique identifier for the object
   *
   * @var object
   */
  private $objectId;

  /**
   * The reference to the object if available
   *
   * @var object
   */
  private $objectRef;

  /**
   * CRM_Odoosync_Hook_Post_ContributionProcessesHandler constructor.
   *
   * @param string $op
   * @param string $objectName
   * @param int $objectId
   * @param object $objectRef
   */
  public function __construct($op, $objectName, $objectId, &$objectRef) {
    $this->operation = $op;
    $this->objectName = $objectName;
    $this->objectId = $objectId;
    $this->objectRef = $objectRef;
  }

  /**
   * Updates contribution sync information
   */
  public function handleHook() {
    if ($this->isEntityFinancialTrxn()) {
      $civicrmFinancialItemId = $this->objectRef->entity_id;
      $contributionId = $this->getContributionId($civicrmFinancialItemId);
      $syncStatus = 'awaiting_sync';
      $actionToSync = (new CRM_Odoosync_Contribution_StatusMapping())->getActionByContributionId($contributionId);
      $syncInformation = new CRM_Odoosync_Contribution_SyncInformation($contributionId, $syncStatus, $actionToSync);
      $syncInformation->updateSyncInfo();
    }

    if ($this->isLineItem()) {
      $contributionId = $this->objectRef->contribution_id;
      $syncStatus = 'awaiting_sync';
      $actionToSync = (new CRM_Odoosync_Contribution_StatusMapping())->getActionByContributionId($contributionId);
      $syncInformation = new CRM_Odoosync_Contribution_SyncInformation($contributionId, $syncStatus, $actionToSync);
      $syncInformation->updateSyncInfo();
    }

    if ($this->objectName == 'Contribution') {
      if ($this->operation == 'create') {
        $contributionId = $this->objectRef->id;
        $isSyncStatusSynced = $this->isSyncStatusSynced($contributionId);
        if (!$isSyncStatusSynced) {
          $syncStatus = 'awaiting_sync';
          $actionToSync = 'create';
          $syncInformation = new CRM_Odoosync_Contribution_SyncInformation($contributionId, $syncStatus, $actionToSync);
          $syncInformation->updateSyncInfo();
        }
      }

      if ($this->operation == 'edit') {
        $contributionId = $this->objectRef->id;
        $isSyncStatusSynced = $this->isSyncStatusSynced($contributionId);
        if (!$isSyncStatusSynced) {
          $syncStatus = 'awaiting_sync';
          $actionToSync = 'create';
          $syncInformation = new CRM_Odoosync_Contribution_SyncInformation($contributionId, $syncStatus, $actionToSync);
          $syncInformation->updateSyncInfo();
        }
      }
    }

  }

  /**
   * Checks if objectName a 'EntityFinancialTrxn' with specific param
   *
   * @return bool
   */
  private function isEntityFinancialTrxn() {
    return $this->objectName == 'EntityFinancialTrxn'
    && $this->objectRef->entity_table == "civicrm_financial_item"
    && (
      $this->operation == 'create'
      || $this->operation == 'edit'
      || $this->operation == 'delete'
    );
  }

  /**
   * Checks if objectName a 'LineItem' with specific param
   *
   * @return bool
   */
  private function isLineItem() {
    return $this->objectName = 'LineItem'
      && isset($this->objectRef->entity_table)
      && $this->objectRef->entity_table == 'civicrm_contribution'
      && (
        $this->operation == 'create'
        || $this->operation == 'edit'
        || $this->operation == 'delete'
      );
  }

  /**
   * Checks if is contribution has "Synced" status
   *
   * @param $contributionId
   *
   * @return bool
   */
  private function isSyncStatusSynced($contributionId) {
    $query = "
      SELECT * FROM odoo_invoice_sync_information AS sync_info
        JOIN civicrm_option_group AS status_option_group 
          ON status_option_group.name = 'odoo_sync_status'
        LEFT JOIN civicrm_option_value AS status_option 
          ON status_option.option_group_id = status_option_group.id
          AND status_option.name = 'synced' 
      WHERE sync_info.entity_id = %1
        AND sync_info.sync_status = status_option.value
      ";

    $dao = CRM_Core_DAO::executeQuery($query, [1 => [$contributionId, 'Integer']]);

    return !empty($dao->fetchAll());
  }


  /**
   * Gets contribution id by financial item id
   *
   * @param $civicrmFinancialItemId
   *
   * @return int|null
   */
  private function getContributionId($civicrmFinancialItemId) {
    $query = "
      SELECT civicrm_financial_item.entity_id 
      FROM civicrm_financial_item 
      WHERE civicrm_financial_item.id = %1
      ";

    $dao = CRM_Core_DAO::executeQuery($query, [1 => [$civicrmFinancialItemId, 'Integer']]);

    while ($dao->fetch()) {
      return (int) $dao->entity_id;
    }

    return NULL;
  }

}
