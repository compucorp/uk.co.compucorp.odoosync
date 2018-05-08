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
        (
          SELECT financial_account.name AS account_code
          FROM civicrm_entity_financial_account AS entity_financial_account
          LEFT JOIN civicrm_financial_account AS financial_account
            ON entity_financial_account.financial_account_id = financial_account.id
          WHERE entity_financial_account.account_relationship = %4
            AND entity_financial_account.entity_id = financial_trxn.to_financial_account_id
          LIMIT 1
        ) AS account_code
      FROM civicrm_entity_financial_trxn AS entity_financial_trxn
      LEFT JOIN civicrm_financial_trxn AS financial_trxn
        ON entity_financial_trxn.financial_trxn_id = financial_trxn.id
      WHERE (financial_trxn.status_id != %2 AND financial_trxn.status_id != %3)
        AND entity_financial_trxn.entity_table = 'civicrm_contribution'
        AND entity_financial_trxn.entity_id = %1
        AND financial_trxn.is_payment = 1
    ";

    $dao = CRM_Core_DAO::executeQuery($query, [
      1 => [$this->contributionId, 'Integer'],
      2 => [$refundedStatusValueId, 'String'],
      3 => [$cancelledStatusValueId, 'String'],
      4 => [CRM_Odoosync_Sync_Contribution_Data_LineItem::SALES_ACCOUNT_RELATIONSHIP_ID, 'Integer']
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
    $accountCode = (new CRM_Odoosync_Sync_Contribution_Data_ContributionParam($this->contributionId))->getAccountCode();

    while ($paymentItemsDao->fetch()) {
      $paymentItems[] = [
        [
          'name' => 'status',
          'type' => 'string',
          'value' => $paymentItemsDao->status
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
          'name' => 'journal_code',
          'type' => 'string',
          'value' => $paymentItemsDao->account_code
        ],
        [
          'name' => 'payment_date',
          'type' => 'int',
          'value' => CRM_Odoosync_Common_Date::convertDateToTimestamp($paymentItemsDao->payment_date)
        ],
        [
          'name' => 'communication',
          'type' => 'string',
          'value' => $paymentItemsDao->communication
        ],
        [
          'name' => 'account_code',
          'type' => 'int',
          'value' => $accountCode
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

}