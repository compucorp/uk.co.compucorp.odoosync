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
   * @return int|null
   */
  private function getContributionId($financialTrxnId) {
    $query = "
      SELECT entity_financial_trxn.entity_id AS contribution_id
      FROM civicrm_entity_financial_trxn AS entity_financial_trxn 
      WHERE entity_financial_trxn.financial_trxn_id = %1 
        AND entity_financial_trxn.entity_table = 'civicrm_contribution'
      LIMIT 1 
      ";

    $dao = CRM_Core_DAO::executeQuery($query, [
      1 => [$financialTrxnId, 'Integer']
    ]);

    while ($dao->fetch()) {
      return $dao->contribution_id;
    }

    return FALSE;
  }

}
