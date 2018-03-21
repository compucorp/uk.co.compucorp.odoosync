<?php

class CRM_Odoosync_Utils_CustomData {

  /**
   * Gets option value by OptionGroup name and name OptionValue
   *
   * @param $optionGroupName
   * @param $name
   *
   * @return int
   * @throws \CiviCRM_API3_Exception
   */
  public static function getOptionValueByName($optionGroupName, $name) {
    $value = civicrm_api3('OptionValue', 'get', [
      'sequential' => 1,
      'return' => ["value"],
      'option_group_id' => $optionGroupName,
      'name' => $name,
    ]);

    return (int) $value['values'][0]['value'];
  }

  /**
   * Gets custom field id by group name and field name
   *
   * @param string $customGroupName
   * @param string $name
   *
   * @return int
   * @throws \CiviCRM_API3_Exception
   */
  public static function getCustomFieldId($customGroupName, $name) {
    $result = civicrm_api3('CustomField', 'getvalue', [
      'return' => "id",
      'name' => $name,
      'custom_group_id' => $customGroupName,
    ]);

    return (int) $result;
  }

  /**
   * Gets default value in the option group
   *
   * @param $optionGroupName
   *
   * @return int
   * @throws \CiviCRM_API3_Exception
   */
  public static function getDefaultOptionValue($optionGroupName) {
    $value = civicrm_api3('OptionValue', 'get', [
      'sequential' => 1,
      'return' => ["value"],
      'option_group_id' => $optionGroupName,
      'is_default' => 1,
    ]);

    return (int) $value['values'][0]['value'];
  }

  /**
   * Update contact sync information
   *
   * @param $contactId
   *
   * @param $actionToSync
   *
   * @throws \CiviCRM_API3_Exception
   */
  public static function updateSyncInformationContact($contactId, $actionToSync) {
    $date = new DateTime();
    $dateFormat = $date->format("Y-m-d H:i:s");
    $createAction = self::getOptionValueByName('odoo_partner_action_to_sync', $actionToSync);
    $createStatus = self::getOptionValueByName('odoo_sync_status', 'awaiting_sync');
    $syncStatusFieldId = CRM_Odoosync_Utils_CustomData::getCustomFieldId('odoo_partner_sync_information', 'sync_status');
    $actionToSyncFieldId = CRM_Odoosync_Utils_CustomData::getCustomFieldId('odoo_partner_sync_information', 'action_to_sync');
    $actionDateFieldId = CRM_Odoosync_Utils_CustomData::getCustomFieldId('odoo_partner_sync_information', 'action_date');

    $param = [
      'id' => $contactId,
      'custom_' . $syncStatusFieldId => $createStatus,
      'custom_' . $actionToSyncFieldId => $createAction,
      'custom_' . $actionDateFieldId => $dateFormat,
    ];

    civicrm_api3('Contact', 'create', $param);
  }

}
