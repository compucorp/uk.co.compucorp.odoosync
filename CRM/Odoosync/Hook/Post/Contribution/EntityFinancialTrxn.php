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
    $financialTrxnId = $this->objectRef->financial_trxn_id;
    $contributionId = $this->getContributionId($financialTrxnId);

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
   * Gets contribution id by 'financial trxn id'
   *
   * @param $financialTrxnId
   *
   * @return int|false
   */
  private function getContributionId($financialTrxnId) {
    try {
      $contributionId = civicrm_api3('EntityFinancialTrxn', 'getvalue', [
        'return' => "entity_id",
        'entity_table' => "civicrm_contribution",
        'financial_trxn_id' => $financialTrxnId
      ]);

      return (int) $contributionId;
    } catch (CiviCRM_API3_Exception $e) {
      return FALSE;
    }
  }

}
