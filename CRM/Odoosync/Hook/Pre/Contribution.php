<?php

/**
 * Handle Contribution pre processes
 */
class CRM_Odoosync_Hook_Pre_Contribution {

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
   * Is the unique identifier for the object if available
   *
   * @var object
   */
  private $objectId;

  /**
   * Are the parameters passed
   *
   * @var object
   */
  private $params;

  /**
   * CRM_Odoosync_Hook_Pre_ContributionProcessesHandler constructor.
   *
   * @param string $op
   * @param string $objectName
   * @param string $id
   * @param object $params
   */
  public function __construct($op, $objectName, $id, &$params) {
    $this->operation = $op;
    $this->objectName = $objectName;
    $this->objectId = $id;
    $this->params = $params;
  }

  /**
   * Updates contribution sync information
   *
   * @throws \CiviCRM_API3_Exception
   */
  public function process() {
    if (!$this->isContributionEdit()) {
      return;
    }

    $contributionId = $this->objectId;
    if (!$this->isContributionStatusChanged() && !$this->isSyncStatusSynced($contributionId)) {
      return;
    }

    $syncStatus = 'awaiting_sync';
    $currentDate = (new DateTime())->format('Y-m-d H:i:s');
    $actionToSync = CRM_Odoosync_Contribution_StatusToSyncActionMapper::getActionByContributionStatusId($this->params['contribution_status_id']);
    $syncInformation = new CRM_Odoosync_Contribution_SyncInformationUpdater(
      $contributionId,
      $syncStatus,
      $actionToSync,
      $currentDate
    );
    $syncInformation->updateSyncInfo();
  }

  /**
   * Checks object name and operation
   *
   * @return bool
   */
  private function isContributionEdit() {
    return ($this->objectName == 'Contribution' && $this->operation == "edit");
  }

  /**
   * Checks if the contribution status was changed
   */
  private function isContributionStatusChanged() {
    $oldContributionStatus = $this->params['prevContribution']->contribution_status_id;
    $newContributionStatus = $this->params['contribution_status_id'];

    return (int) $oldContributionStatus != (int) $newContributionStatus;
  }

  /**
   * Checks if is contribution has "Synced" status
   *
   * @param $contributionId
   *
   * @return bool
   * @throws \CiviCRM_API3_Exception
   */
  protected function isSyncStatusSynced($contributionId) {
    $syncStatusId = CRM_Odoosync_Common_OptionValue::getOptionValueID('odoo_sync_status', 'synced');

    $query = "
      SELECT * FROM odoo_invoice_sync_information AS sync_info 
      WHERE sync_info.entity_id = %1 AND sync_info.sync_status = %2
      ";

    $dao = CRM_Core_DAO::executeQuery($query, [
      1 => [$contributionId, 'Integer'],
      2 => [$syncStatusId, 'Integer'],
    ]);

    return !empty($dao->fetchAll());
  }

}
