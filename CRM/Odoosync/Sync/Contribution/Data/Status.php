<?php

class CRM_Odoosync_Sync_Contribution_Data_Status {

  /**
   * Contribution Refunded status 'value ID'
   *
   * @return bool|string
   */
  private static $refundedValueId = FALSE;

  /**
   * Contribution cancelled status 'value ID'
   *
   * @return bool|string
   */
  private static $cancelledValueId = FALSE;

  /**
   * Gets contribution refunded status 'value ID'
   *
   * @return string
   */
  public static function getRefundedValueId() {
    if (!self::$refundedValueId) {
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
    if (!self::$cancelledValueId) {
      self::$cancelledValueId = CRM_Odoosync_Common_OptionValue::getOptionValueID('contribution_status', 'Cancelled');
    }

    return self::$cancelledValueId;
  }

}
