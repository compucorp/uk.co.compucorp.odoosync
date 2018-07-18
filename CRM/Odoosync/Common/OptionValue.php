<?php

class CRM_Odoosync_Common_OptionValue {

  /**
   * Gets the specified option value ID (value)
   * for the specified option group.
   *
   * @param string $optionGroupName
   * @param string $optionValueName
   *
   * @return string
   * @throws \CiviCRM_API3_Exception
   */
  public static function getOptionValueID($optionGroupName, $optionValueName) {
    $optionValue = civicrm_api3('OptionValue', 'getSingle', [
      'sequential' => 1,
      'options' => ['limit' => 1],
      'return' => ["value"],
      'option_group_id' => $optionGroupName,
      'name' => $optionValueName,
    ]);

    return $optionValue['value'];
  }

  /**
   * Gets the 'option value' name
   * for the specified option group and option value ID (value)
   *
   * @param $optionGroupName
   * @param $valueId
   *
   * @return string
   * @throws \CiviCRM_API3_Exception
   */
  public static function getOptionName($optionGroupName, $valueId) {
    $optionName = civicrm_api3('OptionValue', 'getvalue', [
      'sequential' => 1,
      'return' => "name",
      'option_group_id' => $optionGroupName,
      'value' => $valueId,
      'options' => ['limit' => 1]
    ]);

    return $optionName;
  }

}
