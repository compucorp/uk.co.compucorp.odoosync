<?php

class CRM_Odoosync_Utils_CustomData {

  /**
   * Save custom field data to contact
   *
   * @param int $contactId
   * @param int $customFieldId
   * @param mixed $value
   *
   * @throws \CiviCRM_API3_Exception
   */
  public static function saveCustomFieldDataToContact($contactId, $customFieldId, $value) {
    civicrm_api3('Contact', 'create', [
      'id' => $contactId,
      'custom_' . $customFieldId => $value,
    ]);
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
   * Update contact custom odoo fields
   *
   * @param $contactId
   *
   * @throws \CiviCRM_API3_Exception
   */
  public static function updateContactData($contactId) {
    $date = new DateTime();
    $dateFormat = $date->format("Y-m-d H:i:s");
    $defaultAction = CRM_Odoosync_Utils_CustomData::getDefaultOptionValue('odoo_partner_action_to_sync');
    $defaultStatus = CRM_Odoosync_Utils_CustomData::getDefaultOptionValue('odoo_sync_status');
    $syncStatusFieldId = CRM_Odoosync_Utils_CustomData::getCustomFieldId('odoo_partner_sync_information', 'sync_status');
    $actionToSyncFieldId = CRM_Odoosync_Utils_CustomData::getCustomFieldId('odoo_partner_sync_information', 'action_to_sync');
    $actionDateFieldId = CRM_Odoosync_Utils_CustomData::getCustomFieldId('odoo_partner_sync_information', 'action_date');

    $param = [
      'id' => $contactId,
      'custom_' . $syncStatusFieldId => $defaultStatus,
      'custom_' . $actionToSyncFieldId => $defaultAction,
      'custom_' . $actionDateFieldId => $dateFormat,
    ];

    civicrm_api3('Contact', 'create', $param);
  }

}
