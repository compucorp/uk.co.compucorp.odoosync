<?php


class CRM_Odoosync_Common_CustomField {

  /**
   * Gets custom field id
   *
   * @param $customGroupName
   * @param $customFieldName
   *
   * @return int
   * @throws \CiviCRM_API3_Exception
   */
  public static function getCustomFieldId($customGroupName, $customFieldName) {
    $customFieldId = civicrm_api3('CustomField', 'getvalue', [
      'sequential' => 1,
      'options' => ['limit' => 1],
      'return' => "id",
      'name' => $customFieldName,
      'custom_group_id' => $customGroupName,
    ]);

    return (int) $customFieldId;
  }

}
