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
   * Form name from hook
   *
   * @var string
   */
  private $formName;

  /**
   * Form object from hook
   *
   * @var object
   */
  private $form;

  /**
   * Contact ids
   *
   * @var array
   */
  private $contactIDs;

  /**
   * Sync action
   *
   * @var string
   */
  private $syncAction;

  /**
   * CRM_Odoosync_Hook_PostProcess_ContactSyncInformationUpdater constructor.
   *
   * @param $formName
   * @param $form
   *
   */
  public function __construct($formName, &$form) {
    $this->form = $form;
    $this->formName = $formName;
    $this->setSyncAction();
    if (empty($this->syncAction)) {
      return;
    }

    $this->setContactIDs();
    $this->currentDateTime = (new DateTime())->format('Y-m-d H:i:s');
    $this->syncStatusFieldId = $this->getSyncInfoCustomFieldId('sync_status');
    $this->actionToSyncFieldId = $this->getSyncInfoCustomFieldId('action_to_sync');
    $this->actionDateFieldId = $this->getSyncInfoCustomFieldId('action_date');
    $this->awaitingSyncOptionValueId = $this->getOptionValueID('odoo_sync_status', 'awaiting_sync');
  }

  /**
   * Sets contact IDs from form object
   */
  private function setContactIDs() {
    if (!empty($this->form->getVar('_contactIds'))) {
      $this->contactIDs  = $this->form->getVar('_contactIds');
    }
    elseif (!empty($this->form->getVar('_contactId'))) {
      $this->contactIDs  = [(int) $this->form->getVar('_contactId')];
    }
  }

  /**
   * Sets sync action from form object
   */
  private function setSyncAction() {
    $isInlineContactForm = preg_match('/^CRM_Contact_Form_Inline_/', $this->formName);
    if ($isInlineContactForm && ($this->formgetAction() == CRM_Core_Action::UPDATE ||
        CRM_Core_Action::ADD || CRM_Core_Action::DELETE)) {
      $this->syncAction = 'update';
    }

    if ($this->formName == "CRM_Contact_Form_Contact") {
      if ($this->form->getAction() == CRM_Core_Action::ADD) {
        $this->syncAction = 'create';
      }

      if ($this->form->getAction() == CRM_Core_Action::UPDATE) {
        $this->syncAction = 'update';
      }
    }

    if ($this->formName == "CRM_Contact_Form_Task_Delete" && $this->form->getAction() ==
      CRM_Core_Action::NONE) {
      $skipUnDelete = $this->form->getVar('_skipUndelete');
      if (!$skipUnDelete) {
        $this->syncAction = 'update';
      }
    }
  }

  /**
   * Updates contact sync information
   */
  public function updateSyncInfo() {
    if (empty($this->syncAction)) {
      return;
    }

    $actionToSyncOptionValueId = $this->getOptionValueID('odoo_partner_action_to_sync', $this->syncAction);
    $param = [
      'custom_' . $this->syncStatusFieldId => $this->awaitingSyncOptionValueId,
      'custom_' . $this->actionToSyncFieldId => $actionToSyncOptionValueId,
      'custom_' . $this->actionDateFieldId => $this->currentDateTime,
    ];

    foreach ($this->contactIDs as $contactID) {
      $param['contact_id'] = $contactID;
      civicrm_api3('Contact', 'create', $param);
    }
  }

  /**
   * Gets the custom field id for the specified
   * odoo_partner_sync_information field.
   *
   * @param string $name
   *
   * @return int
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

}
