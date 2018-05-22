<?php

/**
 * Gets appropriate contribution for synchronization with Odoo
 */
class CRM_Odoosync_Sync_Contribution_PendingContribution {

  /**
   * Custom field id for 'sync_status'
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
   * Custom field id for 'do_not_sync'
   *
   * @var int
   */
  private $doNotSyncFieldId;

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
    $this->doNotSyncFieldId = CRM_Odoosync_Common_CustomField::getCustomFieldId(
      'odoo_invoice_sync_information',
      'do_not_sync'
    );
  }

  /**
   * Gets non-synchronized contribution Ids
   *
   * @return array
   */
  public function getIds() {
    $query = "
      SELECT 
        contribution.id AS id 
      FROM civicrm_contribution AS contribution
      LEFT JOIN odoo_invoice_sync_information AS info 
        ON contribution.id = info.entity_id
      WHERE (info.do_not_sync IS NULL OR info.do_not_sync != 1)
      LIMIT %1
    ";

    $dao = CRM_Core_DAO::executeQuery($query, [
      1 => [(int) CRM_Odoosync_Sync_BatchSize::getCurrentBatchSize(), 'Integer']
    ]);

    $contributionListId = [];
    while ($dao->fetch()) {
      $contributionListId[] = $dao->id;
    }

    return $contributionListId;
  }

}
