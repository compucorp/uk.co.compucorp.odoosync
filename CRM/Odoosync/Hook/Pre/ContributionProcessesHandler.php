<?php

/**
 * Handle Contribution pre processes
 */
class CRM_Odoosync_Hook_Pre_ContributionProcessesHandler {

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
   */
  public function handleHook() {
    if (!$this->isContributionEditProcess()) {
      return;
    }

    $oldContributionStatus = $this->params['prevContribution']->contribution_status_id;
    $newContributionStatus = $this->params['contribution_status_id'];
    $isStatusChanged = (int) $oldContributionStatus != (int) $newContributionStatus;

    if ($isStatusChanged) {
      $contributionId = $this->objectId;
      $syncStatus = 'awaiting_sync';
      $actionToSync = (new CRM_Odoosync_Contribution_StatusMapping())->getActionByContributionStatusId($newContributionStatus);
      $syncInformation = new CRM_Odoosync_Contribution_SyncInformation($contributionId, $syncStatus, $actionToSync);
      $syncInformation->updateSyncInfo();
    }

  }

  private function isContributionEditProcess() {
    return ($this->objectName == 'Contribution' && $this->operation == "edit");
  }

}
