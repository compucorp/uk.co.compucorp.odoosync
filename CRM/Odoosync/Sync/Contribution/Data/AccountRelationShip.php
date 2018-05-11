<?php

class CRM_Odoosync_Sync_Contribution_Data_AccountRelationShip {

  /**
   * Relationship ID for Income Account
   *
   * @return NULL|int
   */
  private static $incomeAccountRelationshipId = NULL;

  /**
   * Relationship ID for Accounts Receivable Account
   *
   * @return NULL|int
   */
  private static $accountsReceivableAccountRelationshipId = NULL;

  /**
   * Relationship ID for Sales Tax Account
   *
   * @return NULL|int
   */
  private static $salesTaxAccountRelationshipId = NULL;

  /**
   * Gets relationship ID for Income Account
   *
   * @return string
   */
  public static function getIncomeAccountId() {
    if (is_null(self::$incomeAccountRelationshipId)) {
      self::$incomeAccountRelationshipId = CRM_Odoosync_Common_OptionValue::getOptionValueID(
        'account_relationship',
        'Income Account is'
      );
    }

    return self::$incomeAccountRelationshipId;
  }

  /**
   * Gets relationship ID for Accounts Receivable Account
   *
   * @return string
   */
  public static function getAccountsReceivableId() {
    if (is_null(self::$accountsReceivableAccountRelationshipId)) {
      self::$accountsReceivableAccountRelationshipId = CRM_Odoosync_Common_OptionValue::getOptionValueID(
        'account_relationship',
        'Accounts Receivable Account is'
      );
    }

    return self::$accountsReceivableAccountRelationshipId;
  }

  /**
   * Gets relationship ID for Accounts Receivable Account
   *
   * @return string
   */
  public static function getSalesTaxAccountId() {
    if (is_null(self::$salesTaxAccountRelationshipId)) {
      self::$salesTaxAccountRelationshipId = CRM_Odoosync_Common_OptionValue::getOptionValueID(
        'account_relationship',
        'Sales Tax Account is'
      );
    }

    return self::$salesTaxAccountRelationshipId;
  }

}
