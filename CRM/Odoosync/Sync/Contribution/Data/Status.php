<?php

class CRM_Odoosync_Sync_Contribution_Data_Status {

  /**
   * Contribution Refunded status 'value ID'
   *
   * @return NULL|string
   */
  private static $refundedValueId = NULL;

  /**
   * Contribution cancelled status 'value ID'
   *
   * @return NULL|string
   */
  private static $cancelledValueId = NULL;

  /**
   * Contribution completed status 'value ID'
   *
   * @return NULL|string
   */
  private static $completedValueId = NULL;

  /**
   * Gets contribution refunded status 'value ID'
   *
   * @return string
   */
  public static function getRefundedValueId() {
    if (is_null(self::$refundedValueId)) {
      self::$refundedValueId = CRM_Odoosync_Common_OptionValue::getOptionValueID('contribution_status', 'Refunded');
    }

    return self::$refundedValueId;
  }

  /**
   * Gets contribution cancelled status 'value ID'
   *
   * @return string
   */
  public static function getCancelledValueId() {
    if (is_null(self::$cancelledValueId)) {
      self::$cancelledValueId = CRM_Odoosync_Common_OptionValue::getOptionValueID('contribution_status', 'Cancelled');
    }

    return self::$cancelledValueId;
  }

  /**
   * Gets contribution completed status 'value ID'
   *
   * @return string
   */
  public static function getCompletedValueId() {
    if (is_null(self::$completedValueId)) {
      self::$completedValueId = CRM_Odoosync_Common_OptionValue::getOptionValueID('contribution_status', 'Completed');
    }

    return self::$completedValueId;
  }

}
