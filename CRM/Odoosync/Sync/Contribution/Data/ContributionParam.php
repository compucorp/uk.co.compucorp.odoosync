<?php

class CRM_Odoosync_Sync_Contribution_Data_ContributionParam extends CRM_Odoosync_Sync_Contribution_Data {

  /**
   * Account relationship id
   *
   * @var int
   */
  const ACCOUNT_RELATIONSHIP_ID = 3;

  /**
   * Gets the contribution's params
   *
   * @return array
   * @throws \CiviCRM_API3_Exception
   */
  public function retrieve() {
    $contributionData = $this->getContributionData();
    $actionToSyncValueId = $contributionData->action_to_sync;
    $actionDateTimestamp = $this->getActionDateTimestamp($contributionData->action_date);
    $receiveDateTimestamp = $this->getActionDateTimestamp($contributionData->receive_date);
    $actionToSyncName = CRM_Odoosync_Common_OptionValue::getOptionName(
      'odoo_invoice_action_to_sync',
      $actionToSyncValueId
    );
    $contactId = $contributionData->contact_id;
    $purchaseOrderNumber = $contributionData->purchase_order_number;
    $accountCode = $this->getAccountCode();
    $currencyCode = $contributionData->currency;

    $contributionParams = [
      [
        'name' => 'journal_code',
        'type' => 'string',
        'value' => ''
      ],
      [
        'name' => 'name',
        'type' => 'string',
        'value' => $purchaseOrderNumber
      ],
      [
        'name' => 'currency_code',
        'type' => 'string',
        'value' => $currencyCode
      ],
      [
        'name' => 'account_code',
        'type' => 'int',
        'value' => $accountCode
      ],
      [
        'name' => 'x_civicrm_id',
        'type' => 'int',
        'value' => $this->contributionId
      ],
      [
        'name' => 'contact_civicrm_id',
        'type' => 'int',
        'value' => $contactId
      ],
      [
        'name' => 'receive_date',
        'type' => 'int',
        'value' => $receiveDateTimestamp
      ],
      [
        'name' => 'action_to_sync',
        'type' => 'string',
        'value' => $actionToSyncName
      ],
      [
        'name' => 'action_date',
        'type' => 'int',
        'value' => $actionDateTimestamp
      ]
    ];

    return $contributionParams;
  }

  /**
   * Gets timestamp 'action date'
   *
   * @param $actionDate
   *
   * @return int
   */
  private function getActionDateTimestamp($actionDate) {
    if (!empty($actionDate)) {
      return CRM_Odoosync_Common_Date::convertDateToTimestamp($actionDate);
    }

    return 0;
  }

  /**
   * Gets the contribution's data
   *
   * @return null|object
   */
  private function getContributionData() {
    $query = "
      SELECT 
        contribution.currency AS currency,
        contribution.contact_id AS contact_id,
        purchase_order.purchase_order_number AS purchase_order_number,
        sync_info.action_to_sync AS action_to_sync, 
        sync_info.action_date AS action_date,
        contribution.receive_date AS receive_date
      FROM civicrm_contribution AS contribution
      LEFT JOIN purchase_order 
        ON contribution.id = purchase_order.entity_id
      LEFT JOIN odoo_invoice_sync_information AS sync_info 
        ON contribution.id = sync_info.entity_id                
      WHERE contribution.id = %1
      LIMIT 1
    ";

    $dao = CRM_Core_DAO::executeQuery($query, [1 => [$this->contributionId, 'Integer']]);

    while ($dao->fetch()) {
      return $dao;
    }

    return NULL;
  }

  /**
   * Gets account code
   *
   * @return int
   */
  public function getAccountCode() {
    $query = "
      SELECT financial_account.accounting_code AS account_code
      FROM civicrm_entity_financial_account AS entity_financial_account
      LEFT JOIN civicrm_contribution AS contribution
        ON contribution.id = %2
      LEFT JOIN civicrm_financial_account AS financial_account
      	ON entity_financial_account.financial_account_id = financial_account.id
      WHERE entity_financial_account.account_relationship = %1
        AND entity_financial_account.entity_id = contribution.financial_type_id 
      LIMIT 1
      ";

    $dao = CRM_Core_DAO::executeQuery($query, [
      1 => [self::ACCOUNT_RELATIONSHIP_ID, 'Integer'],
      2 => [$this->contributionId, 'Integer']
    ]);

    while ($dao->fetch()) {
      return $dao->account_code;
    }

    return 0;
  }

}