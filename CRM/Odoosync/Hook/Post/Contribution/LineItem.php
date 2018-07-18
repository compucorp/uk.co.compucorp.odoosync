<?php

/**
 * Handle LineItem post processes
 */
class CRM_Odoosync_Hook_Post_Contribution_LineItem extends CRM_Odoosync_Hook_Post_Contribution_Base {

  /**
   * Updates contribution sync information
   *
   * @throws \CiviCRM_API3_Exception
   */
  public function process() {
    $contributionId = $this->objectRef->contribution_id;
    if (!$contributionId) {
      return;
    }

    if (!$this->isSyncStatusSynced($contributionId)) {
      return;
    }

    $syncStatus = 'awaiting_sync';
    $currentDate = (new DateTime())->format('Y-m-d H:i:s');
    $actionToSync = CRM_Odoosync_Contribution_StatusToSyncActionMapper::getActionByContributionId($contributionId);
    $syncInformation = new CRM_Odoosync_Contribution_SyncInformationUpdater(
      $contributionId,
      $syncStatus,
      $actionToSync,
      $currentDate
    );
    $syncInformation->updateSyncInfo();
  }

}
