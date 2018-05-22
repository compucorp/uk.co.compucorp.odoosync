<?php

/**
 * Handle EntityFinancialTrxn post processes
 */
class CRM_Odoosync_Hook_Post_Contribution_EntityFinancialTrxn extends CRM_Odoosync_Hook_Post_Contribution_Base {

  /**
   * Updates contribution sync information
   *
   * @throws \CiviCRM_API3_Exception
   */
  public function process() {
    $civicrmFinancialItemId = $this->objectRef->entity_id;
    $contributionId = $this->getContributionId($civicrmFinancialItemId);

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

  /**
   * Gets contribution id by financial item id
   *
   * @param $civicrmFinancialItemId
   *
   * @return int|null
   */
  private function getContributionId($civicrmFinancialItemId) {
    try {
      $contributionId = civicrm_api3('FinancialItem', 'getsingle', [
        'return' => ["entity_id"],
        'id' => $civicrmFinancialItemId,
        'options' => ['limit' => 1],
      ]);

      return (int) $contributionId['entity_id'];
    }
    catch (CiviCRM_API3_Exception $e) {
      return NULL;
    }
  }

}
