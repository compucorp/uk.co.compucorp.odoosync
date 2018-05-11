<?php

class CRM_Odoosync_Sync_Contribution_Data_LineItem extends CRM_Odoosync_Sync_Contribution_Data {

  /**
   * Gets line item params
   *
   * @return array
   */
  public function retrieve() {
    $lineItems = [];
    $lineItemsData = $this->generateItemsData();
    foreach ($lineItemsData as $lineItem) {
      $taxNameList = is_null($lineItem['tax_name']) ? [] : [$lineItem['tax_name']];
      $lineItems[] = [
        [
          'name' => 'tax_name',
          'type' => 'string',
          'value' => $taxNameList
        ],
        [
          'name' => 'account_code',
          'type' => 'int',
          'value' => $lineItem['account_code']
        ],
        [
          'name' => 'name',
          'type' => 'string',
          'value' => $lineItem['label']
        ],
        [
          'name' => 'price_subtotal',
          'type' => 'double',
          'value' => $lineItem['total']
        ],
        [
          'name' => 'price_unit',
          'type' => 'double',
          'value' => $lineItem['unit_price']
        ],
        [
          'name' => 'product_code',
          'type' => 'string',
          'value' => $lineItem['product_code']
        ],
        [
          'name' => 'x_civicrm_id',
          'type' => 'int',
          'value' => $lineItem['line_item_id']
        ],
        [
          'name' => 'quantity',
          'type' => 'double',
          'value' => $lineItem['quantity']
        ]
      ];
    }

    return $lineItems;
  }

  /**
   * Gets line item data
   *
   * @return array
   */
  private function generateItemsData() {
    $query = "
      SELECT 
        line_item.entity_table AS entity_table,
        line_item.label AS label,
        line_item.qty AS quantity,
        contribution.contact_id AS contact_id,
        line_item.unit_price AS unit_price,
        line_item.line_total AS total,
        line_item.id AS line_item_id,
        (
          SELECT financial_account.accounting_code
          FROM civicrm_entity_financial_account AS entity_financial_account
          LEFT JOIN civicrm_financial_account AS financial_account
            ON entity_financial_account.financial_account_id = financial_account.id
          WHERE entity_financial_account.account_relationship = %2
            AND entity_financial_account.entity_id = line_item.financial_type_id 
          LIMIT 1
        ) AS account_code,
        (
          SELECT financial_account.name
          FROM civicrm_entity_financial_account AS entity_financial_account
          LEFT JOIN civicrm_financial_account AS financial_account
            ON entity_financial_account.financial_account_id = financial_account.id
          WHERE entity_financial_account.account_relationship = %3
            AND entity_financial_account.entity_id = line_item.financial_type_id 
          LIMIT 1
        ) AS tax_name
      FROM civicrm_line_item AS line_item
      LEFT JOIN civicrm_contribution AS contribution
          ON contribution.id = %1
      WHERE line_item.contribution_id = %1
        AND 
            line_item.entity_table IN (
            'civicrm_participant',
            'civicrm_membership',
            'civicrm_booking_slot',
            'civicrm_booking_sub_slot',
            'civicrm_booking_adhoc_charges',
            'civicrm_contribution'
          )
    ";

    $dao = CRM_Core_DAO::executeQuery($query, [
      1 => [$this->contributionId, 'Integer'],
      2 => [CRM_Odoosync_Sync_Contribution_Data_AccountRelationShip::getIncomeAccountId(), 'Integer'],
      3 => [CRM_Odoosync_Sync_Contribution_Data_AccountRelationShip::getSalesTaxAccountId(), 'Integer']
    ]);

    $lineItemList = [];

    while ($dao->fetch()) {
      $lineItemList[] = [
        'product_code' => $this->calculateProductCode($dao->entity_table),
        'label' => $dao->label,
        'quantity' => $dao->quantity,
        'total' => $dao->total,
        'account_code' => (!is_null($dao->account_code)) ? $dao->account_code : '',
        'contact_id' => $dao->contact_id,
        'tax_name' => (!is_null($dao->tax_name)) ? $dao->tax_name : NULL,
        'line_item_id' => $dao->line_item_id,
        'unit_price' => $dao->unit_price
      ];
    }

    return $lineItemList;
  }

  /**
   * Calculates product code
   *
   * @param $entityTable
   *
   * @return string
   */
  private function calculateProductCode($entityTable) {
    switch ($entityTable) {
      case "civicrm_participant":
        $productCode = "CVEVT";
        break;

      case "civicrm_membership":
        $productCode = "CVMEM";
        break;

      case "civicrm_booking_slot":
      case "civicrm_booking_sub_slot":
      case "civicrm_booking_adhoc_charges":
        $productCode = "CVBK";
        break;

      case "civicrm_contribution":
        $productCode = "CVCTB";
        break;

      default:
        $productCode = "";
    }

    return $productCode;
  }

}
