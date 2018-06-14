<?php

class CRM_Odoosync_Sync_Contribution_Data_ContributionParam extends CRM_Odoosync_Sync_Contribution_Data {

  /**
   * Gets the contribution's params
   *
   * @return array
   * @throws \CiviCRM_API3_Exception
   */
  public function retrieve() {
    $contributionData = $this->getContributionData();
    $actionToSyncValueId = $contributionData->action_to_sync;
    $receiveDateTimestamp = CRM_Odoosync_Common_Date::convertDateToTimestamp($contributionData->receive_date);
    $receiveDateTimestampWithTimezone = $receiveDateTimestamp + (new DateTime())->getOffset();
    $actionDateTimestamp = CRM_Odoosync_Common_Date::convertDateToTimestamp($contributionData->action_date);
    $actionToSyncName = CRM_Odoosync_Common_OptionValue::getOptionName('odoo_invoice_action_to_sync', $actionToSyncValueId);
    $contactId = $contributionData->contact_id;
    $name = empty($contributionData->purchase_order_number) ? "CIVI " . $this->contributionId : $contributionData->purchase_order_number;
    $accountCode = $contributionData->account_code;
    $currencyCode = $contributionData->currency;

    $contributionParams = [
      [
        'name' => 'name',
        'type' => 'string',
        'value' => $name
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
        'name' => 'date_invoice',
        'type' => 'int',
        'value' => $receiveDateTimestampWithTimezone
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
        contribution.receive_date AS receive_date,
        (
          SELECT financial_account.accounting_code 
          FROM civicrm_entity_financial_account AS entity_financial_account
          LEFT JOIN civicrm_financial_account AS financial_account
            ON entity_financial_account.financial_account_id = financial_account.id
          WHERE entity_financial_account.account_relationship = %2
            AND entity_financial_account.entity_id = contribution.financial_type_id 
          LIMIT 1
        ) AS account_code
      FROM civicrm_contribution AS contribution
      LEFT JOIN purchase_order 
        ON contribution.id = purchase_order.entity_id
      LEFT JOIN odoo_invoice_sync_information AS sync_info 
        ON contribution.id = sync_info.entity_id                
      WHERE contribution.id = %1
      LIMIT 1
    ";

    $dao = CRM_Core_DAO::executeQuery($query, [
      1 => [$this->contributionId, 'Integer'],
      2 => [CRM_Odoosync_Sync_Contribution_Data_AccountRelationShip::getAccountsReceivableId(), 'Integer']
    ]);

    while ($dao->fetch()) {
      return $dao;
    }

    return NULL;
  }

}
