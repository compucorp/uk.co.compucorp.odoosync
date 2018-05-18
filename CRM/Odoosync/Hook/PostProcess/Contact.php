<?php

/**
 * Updates contact sync information custom fields
 */
class CRM_Odoosync_Hook_PostProcess_Contact {

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
   * CRM_Odoosync_Hook_PostProcess_Contact constructor.
   *
   * @param $formName
   * @param $form
   *
   */
  public function __construct($formName, &$form) {
    $this->form = $form;
    $this->formName = $formName;
    $this->setSyncAction();
  }

  /**
   * Sets sync action from form object
   */
  private function setSyncAction() {
    $isInlineContactForm = preg_match('/^CRM_Contact_Form_Inline_/', $this->formName);
    if ($isInlineContactForm && ($this->form->getAction() == CRM_Core_Action::UPDATE ||
        CRM_Core_Action::ADD || CRM_Core_Action::DELETE)) {
      $this->syncAction = 'update';
      $this->setContactIDs();
    }

    if ($this->formName == "CRM_Contact_Form_Contact") {
      if ($this->form->getAction() == CRM_Core_Action::ADD) {
        $this->syncAction = 'create';
      }

      if ($this->form->getAction() == CRM_Core_Action::UPDATE) {
        $this->syncAction = 'update';
      }
      $this->setContactIDs();
    }

    if ($this->formName == "CRM_Contact_Form_Task_Delete" && $this->form->getAction() ==
      CRM_Core_Action::NONE) {
      $skipUnDelete = $this->form->getVar('_skipUndelete');
      if (!$skipUnDelete) {
        $this->syncAction = 'update';
        $this->setContactIDs();
      }
    }

    if ($this->formName == "CRM_Profile_Form_Edit" && $this->form->getAction() == CRM_Core_Action::ADD) {
      $this->syncAction = 'create';
      $this->setContactIDsFromMiniForm();
    }
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
   * Sets contact IDs from form mini form
   */
  private function setContactIDsFromMiniForm() {
    if (!empty($this->form->getVar('_id'))) {
      $this->contactIDs = [$this->form->getVar('_id')];
    }
  }

  /**
   * Updates contact sync information
   */
  public function process() {
    if (empty($this->syncAction)) {
      return;
    }

    $currentDate = (new DateTime())->format('Y-m-d H:i:s');
    foreach ($this->contactIDs as $contactID) {
      $param['contact_id'] = $contactID;
      $syncInformation = new CRM_Odoosync_Contact_SyncInformationUpdater(
        $contactID,
        'awaiting_sync',
        $this->syncAction,
        $currentDate
      );

      $syncInformation->updateSyncInfo();
    }
  }

}
