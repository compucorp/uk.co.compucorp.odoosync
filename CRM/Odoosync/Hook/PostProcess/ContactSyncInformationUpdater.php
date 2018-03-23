<?php

/**
 * Updates contact sync information custom fields: sync_status, action_to_sync, action_date
 */
class CRM_Odoosync_Hook_PostProcess_ContactSyncInformationUpdater {

  /**
   * Id of sync_status custom field
   *
   * @var int
   */
  private $syncStatusFieldId;

  /**
   * Id of action_to_sync custom field
   *
   * @var int
   */
  private $actionToSyncFieldId;

  /**
   * Id of action_date custom field
   *
   * @var int
   */
  private $actionDateFieldId;

  /**
   * Current date time
   *
   * @var string
   */
  private $currentDateTime;

  /**
   * Value id of option awaiting_sync
   *
   * @var int
   */
  private $awaitingSyncOptionValueId;

  /**
   * Sets contact custom fields id: sync_status, action_to_sync, action_date
   * Sets current data time
   *
   * CRM_Odoosync_Hook_PostProcess_ContactSyncInformationUpdater constructor.
   *
   * @throws \CiviCRM_API3_Exception
   */
  public function __construct() {
    $date = new DateTime();
    $this->currentDateTime = $date->format('Y-m-d H:i:s');
    $this->syncStatusFieldId = $this->getSyncInfoCustomFieldId('sync_status');
    $this->actionToSyncFieldId = $this->getSyncInfoCustomFieldId('action_to_sync');
    $this->actionDateFieldId = $this->getSyncInfoCustomFieldId('action_date');
    $this->awaitingSyncOptionValueId = $this->getOptionValueID('odoo_sync_status', 'awaiting_sync');
  }

  /**
   * Gets the custom field id for the specified
   * odoo_partner_sync_information field.
   *
   * @param string $name
   *
   * @return int
   * @throws \CiviCRM_API3_Exception
   */
  private function getSyncInfoCustomFieldId($name) {
    $customFieldId = civicrm_api3('CustomField', 'getvalue', [
      'return' => "id",
      'name' => $name,
      'custom_group_id' => 'odoo_partner_sync_information',
    ]);

    return (int) $customFieldId;
  }

  /**
   * Gets the specified option value ID (value)
   * for the specified option group.
   *
   * @param string $optionGroupName
   * @param string $optionValueName
   *
   * @return int
   * @throws \CiviCRM_API3_Exception
   */
  private function getOptionValueID($optionGroupName, $optionValueName) {
    $value = civicrm_api3('OptionValue', 'get', [
      'sequential' => 1,
      'return' => ["value"],
      'option_group_id' => $optionGroupName,
      'name' => $optionValueName,
    ]);

    return (int) $value['values'][0]['value'];
  }

  /**
   * Updates contact sync information
   *
   * @param int $contactId
   * @param string $actionToSyncOptionValueName
   *
   * @return array
   * @throws \CiviCRM_API3_Exception
   */
  public function updateSyncInfo($contactId, $actionToSyncOptionValueName) {
    $actionToSyncOptionValueId = $this->getOptionValueID('odoo_partner_action_to_sync', $actionToSyncOptionValueName);

    $param = [
      'id' => $contactId,
      'custom_' . $this->syncStatusFieldId => $this->awaitingSyncOptionValueId,
      'custom_' . $this->actionToSyncFieldId => $actionToSyncOptionValueId,
      'custom_' . $this->actionDateFieldId => $this->currentDateTime,
    ];

    return civicrm_api3('Contact', 'create', $param);
  }

}
