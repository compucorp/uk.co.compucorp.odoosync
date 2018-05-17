<?php

/**
 * Gets appropriate contribution for synchronization with Odoo
 */
class CRM_Odoosync_Sync_Contribution_PendingContribution {

  /**
   * Sync contribution id
   *
   * @var int
   */
  private $syncStatusFieldId;

  /**
   * Awaiting 'sync status' value id
   *
   * @var int
   */
  private $syncStatusValue;

  /**
   * CRM_Odoosync_Sync_Contribution_PendingContribution constructor.
   *
   * @throws \CiviCRM_API3_Exception
   */
  public function __construct() {
    $this->syncStatusFieldId = CRM_Odoosync_Common_CustomField::getCustomFieldId(
      'odoo_invoice_sync_information',
      'sync_status'
    );
    $this->syncStatusValue = CRM_Odoosync_Common_OptionValue::getOptionValueID(
      'odoo_sync_status',
      'awaiting_sync'
    );
  }

  /**
   * Gets non-synchronized contribution Ids
   *
   * @return array
   */
  public function getIds() {
    try {
      $contributionList = civicrm_api3('Contribution', 'get', [
        'return' => ["id"],
        'is_deleted' => ['IS NOT NULL' => 1],
        'options' => ['limit' => (int) CRM_Odoosync_Sync_BatchSize::getCurrentBatchSize()],
        'custom_' . $this->syncStatusFieldId => $this->syncStatusValue,
      ]);

      $contributionListId = [];
      foreach ($contributionList['values'] as $contribution) {
        $contributionListId[] = $contribution['contribution_id'];
      }

      CRM_Odoosync_Sync_BatchSize::setUsedSize(count($contributionListId));

      return $contributionListId;
    }
    catch (CiviCRM_API3_Exception $e) {
      return [];
    }
  }

}
