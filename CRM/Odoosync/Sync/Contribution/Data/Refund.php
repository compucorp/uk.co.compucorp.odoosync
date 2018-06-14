<?php

class CRM_Odoosync_Sync_Contribution_Data_Refund extends CRM_Odoosync_Sync_Contribution_Data {

  /**
   * Returns refund data
   *
   * @return mixed
   */
  public function retrieve() {
    $refundItems = $this->generateItemsData();

    return $refundItems;
  }

  /**
   * Gets refund data
   *
   * @return array
   */
  private function generateItemsData() {
    $refundedStatusValueId = CRM_Odoosync_Sync_Contribution_Data_Status::getRefundedValueId();
    $cancelledStatusValueId = CRM_Odoosync_Sync_Contribution_Data_Status::getCancelledValueId();

    $query = "
      SELECT 
        financial_trxn.trxn_date AS payment_date,
        financial_trxn.status_id AS status_id
      FROM civicrm_entity_financial_trxn AS entity_financial_trxn
      LEFT JOIN civicrm_financial_trxn AS financial_trxn
        ON entity_financial_trxn.financial_trxn_id = financial_trxn.id
      WHERE (financial_trxn.status_id = %2 OR financial_trxn.status_id = %3)
        AND entity_financial_trxn.entity_table = 'civicrm_contribution'
        AND entity_financial_trxn.entity_id = %1
    ";

    $dao = CRM_Core_DAO::executeQuery($query, [
      1 => [$this->contributionId, 'Integer'],
      2 => [$refundedStatusValueId, 'String'],
      3 => [$cancelledStatusValueId, 'String']
    ]);

    $refundItems = [];

    while ($dao->fetch()) {
      $date = CRM_Odoosync_Common_Date::convertDateToTimestamp($dao->payment_date);
      $dateWithTimezone = $date + (new DateTime())->getOffset();
      $refundItems[] = [
        [
          'name' => 'date',
          'type' => 'int',
          'value' => $dateWithTimezone
        ],
        [
          'name' => 'description',
          'type' => 'string',
          'value' => $this->contributionId
        ],
        [
          'name' => 'status_id',
          'type' => 'string',
          'value' => $dao->status_id
        ]
      ];
    }

    return $refundItems;
  }

}
