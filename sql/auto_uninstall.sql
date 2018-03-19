-- /*******************************************************
-- * Delete Odoo CiviCRM Sync tables
-- *******************************************************/

--SET FOREIGN_KEY_CHECKS=0;

DELETE FROM civicrm_setting WHERE `name` LIKE 'odoosync_%';