<?php

class CRM_Odoosync_Sync_Contribution_Data_Payment extends CRM_Odoosync_Sync_Contribution_Data {

  /**
   * Returns payment data
   *
   * @return mixed
   * @throws \CiviCRM_API3_Exception
   */
  public function retrieve() {
    $paymentItemsDao = $this->generatePaymentItemsData();
    $paymentItems = $this->mappedItems($paymentItemsDao);

    return $paymentItems;
  }

  /**
   * Gets payment data
   *
   * @return \CRM_Core_DAO|object
   * @throws \CiviCRM_API3_Exception
   */
  private function generatePaymentItemsData() {
    $refundedStatusValueId = CRM_Odoosync_Sync_Contribution_Data_Status::getRefundedValueId();
    $cancelledStatusValueId = CRM_Odoosync_Sync_Contribution_Data_Status::getCancelledValueId();

    $query = "
      SELECT 
        financial_trxn.id AS communication,
        financial_trxn.trxn_date AS payment_date,
        financial_trxn.status_id AS status,
        financial_trxn.currency AS currency_code,
        financial_trxn.is_payment AS is_payment,
        financial_trxn.total_amount AS amount,
        financial_account.name AS journal_name
      FROM civicrm_entity_financial_trxn AS entity_financial_trxn
      LEFT JOIN civicrm_financial_trxn AS financial_trxn
        ON entity_financial_trxn.financial_trxn_id = financial_trxn.id
      LEFT JOIN civicrm_financial_account AS financial_account
        ON financial_trxn.to_financial_account_id = financial_account.id
      WHERE entity_financial_trxn.entity_table = 'civicrm_contribution'
        AND entity_financial_trxn.entity_id = %1
        AND financial_trxn.is_payment = 1
    ";

    $dao = CRM_Core_DAO::executeQuery($query, [
      1 => [$this->contributionId, 'Integer'],
      2 => [$refundedStatusValueId, 'String'],
      3 => [$cancelledStatusValueId, 'String'],
      4 => [CRM_Odoosync_Sync_Contribution_Data_AccountRelationShip::getSalesTaxAccountId(), 'Integer']
    ]);

    return $dao;
  }

  /**
   * @param \CRM_Core_DAO|object $paymentItemsDao
   *
   * @return array
   */
  private function mappedItems($paymentItemsDao) {
    $paymentItems = [];

    while ($paymentItemsDao->fetch()) {
      $paymentDate = CRM_Odoosync_Common_Date::convertDateToTimestamp($paymentItemsDao->payment_date);
      $paymentDateWithTimezone = $paymentDate + (new DateTime())->getOffset();
      $paymentItems[] = [
        [
          'name' => 'status',
          'type' => 'string',
          'value' => $this->generateStatus($paymentItemsDao->status)
        ],
        [
          'name' => 'is_payment',
          'type' => 'int',
          'value' => $paymentItemsDao->is_payment
        ],
        [
          'name' => 'amount',
          'type' => 'double',
          'value' => $paymentItemsDao->amount
        ],
        [
          'name' => 'journal_name',
          'type' => 'string',
          'value' => $paymentItemsDao->journal_name
        ],
        [
          'name' => 'payment_date',
          'type' => 'int',
          'value' => $paymentDateWithTimezone
        ],
        [
          'name' => 'communication',
          'type' => 'string',
          'value' => $paymentItemsDao->communication
        ],
        [
          'name' => 'x_civicrm_id',
          'type' => 'int',
          'value' => $paymentItemsDao->communication
        ],
        [
          'name' => 'currency_code',
          'type' => 'string',
          'value' => $paymentItemsDao->currency_code
        ]
      ];
    }

    return $paymentItems;
  }

  /**
   * Calculates status
   *
   * @param $statusId
   *
   * @return string
   */
  private function generateStatus($statusId) {
    if (
      $statusId == CRM_Odoosync_Sync_Contribution_Data_Status::getRefundedValueId()
      || $statusId == CRM_Odoosync_Sync_Contribution_Data_Status::getCancelledValueId()
    ) {
      return '';
    }

    return $statusId;
  }

}
