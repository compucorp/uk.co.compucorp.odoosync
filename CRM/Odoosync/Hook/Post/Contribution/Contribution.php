<?php

/**
 * Handle Contribution post processes
 */
class CRM_Odoosync_Hook_Post_Contribution_Contribution extends CRM_Odoosync_Hook_Post_Contribution_Base {

  /**
   * Updates contribution sync information
   *
   * @throws \CiviCRM_API3_Exception
   */
  public function process() {
    $contributionId = $this->objectRef->id;
    if ($this->isSyncStatusSynced($contributionId)) {
      return;
    }

    $syncStatus = 'awaiting_sync';
    $actionToSync = 'create';
    $currentDate = $this->getCreateContributionDate($contributionId);
    $syncInformation = new CRM_Odoosync_Contribution_SyncInformationUpdater(
      $contributionId,
      $syncStatus,
      $actionToSync,
      $currentDate
    );
    $syncInformation->updateSyncInfo();
  }

  /**
   * Gets contribution receive date
   *
   * @param $contributionId
   *
   * @return array
   * @throws \CiviCRM_API3_Exception
   */
  private function getCreateContributionDate($contributionId) {
    return civicrm_api3('Contribution', 'getvalue', [
      'return' => "receive_date",
      'id' => $contributionId
    ]);
  }

}
