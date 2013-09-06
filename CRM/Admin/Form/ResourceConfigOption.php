<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.4                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2013
 *
 */

/**
 * This class generates form components for Resource
 *
 */
class CRM_Admin_Form_ResourceConfigOption extends CRM_Admin_Form {
  protected $_id = NULL;
  protected $_sid = NULL;

  function preProcess() {
    parent::preProcess();
    CRM_Utils_System::setTitle(ts('Settings - Resource Configuration Option'));
    $this->_sid = CRM_Utils_Request::retrieve('sid', 'Positive',
      $this, FALSE, 0
    );
    $this->assign('sid', $this->_sid);

  }

  /**
   * Function to build the form
   *
   * @return None
   * @access public
   */
  public function buildQuickForm($check = FALSE) {
    parent::buildQuickForm();

    if ($this->_action & CRM_Core_Action::DELETE) {
      return;
    }

    $this->add('text', 'label', ts('Label'), array('size' => 50, 'maxlength' => 255), TRUE);
    $this->add('text', 'price', ts('Price'), CRM_Core_DAO::getAttribute('CRM_Booking_DAO_ResourceConfigOption', 'price '), TRUE);
    $this->add('text', 'max_size', ts('Max Size'), CRM_Core_DAO::getAttribute('CRM_Booking_DAO_ResourceConfigOption', 'max_size '), TRUE);
    $this->add('text', 'weight', ts('Weight'), CRM_Core_DAO::getAttribute('CRM_Booking_DAO_ResourceConfigOption', 'weight'), TRUE);
    $this->add('checkbox', 'is_active', ts('Enabled?'));


    $units =  CRM_Booking_BAO_ResourceConfigOption::buildOptions('unit_id', 'create');
    $this->add('select', 'unit_id', ts('Unit'),
      array('' => ts('- select -')) + $units,
      TRUE,
      array()
    );

    $this->addFormRule(array('CRM_Admin_Form_ResourceConfigOption', 'formRule'), $this);
    $cancelURL = CRM_Utils_System::url('civicrm/admin/resource/config_option', "&sid=$this->_sid&reset=1");
    $cancelURL = str_replace('&amp;', '&', $cancelURL);
    $this->addButtons(
      array(
        array(
          'type' => 'next',
          'name' => ts('Save'),
          'isDefault' => TRUE,
        ),
        array(
          'type' => 'cancel',
          'name' => ts('Cancel'),
          'js' => array('onclick' => "location.href='{$cancelURL}'; return false;"),
        ),
      )
    );
  }

  static function formRule($fields) {
    if (!empty($errors)) {
      return $errors;
    }

    return empty($errors) ? TRUE : $errors;
  }



  function setDefaultValues() {
    $defaults = parent::setDefaultValues();

    return $defaults;
  }


  /**
   * Function to process the form
   *
   * @access public
   *
   * @return None
   */
  public function postProcess() {
    CRM_Utils_System::flushCache();
    $params = $this->exportValues();
    if ($this->_action & CRM_Core_Action::DELETE) {
      CRM_Booking_BAO_ResourceConfigOption::del($this->_id);
      CRM_Core_Session::setStatus(ts('Selected resource configuration option has been deleted.'), ts('Record Deleted'), 'success');
    }
    else {
      $params = $this->exportValues();
      $params['set_id'] = $this->_sid;
      if($this->_id){
        $params['id'] = $this->_id;
        if(!isset($params['is_active'])){
          $params['is_active'] = 0;
        }
      }
      $resource = CRM_Booking_BAO_ResourceConfigOption::create($params);
      CRM_Core_Session::setStatus(ts('The Record \'%1\' has been saved.', array(1 => $resource->label)), ts('Saved'), 'success');

    }

    $url = CRM_Utils_System::url('civicrm/admin/resource/config_set/config_option', 'reset=1&action=browse&sid=' . $this->_sid);
    $session = CRM_Core_Session::singleton();
    $session->replaceUserContext($url);
  }




}
