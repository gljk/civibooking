<?php
use CRM_Booking_ExtensionUtil as E; 

/**
 * Collection of upgrade steps
 */
class CRM_Booking_Upgrader extends CRM_Booking_Upgrader_Base {

  /**
   * Example: Run an external SQL script when the module is installed
   */
  public function install() {
    $params = array(
      'version' => 3,
      'sequential' => 1,
      'label' =>  'Booking',
      'name' => CRM_Booking_Utils_Constants::ACTIVITY_TYPE,
    );
    //chcck if it exist in case of re-installation 
    $optionValue = civicrm_api3('OptionValue', 'get',$params);
    if($optionValue['count'] == 0){
      $params['weight'] = 1;
      $params['is_reserved'] = 1;
      $params['is_active'] = 1;
      $result = civicrm_api('ActivityType', 'create', $params);
    }

    //create new activity type for sending email confirmation :CVB-95
    $params = array(
      'version' => 3,
      'sequential' => 1,
      'label' =>  'Send booking confirmation',
      'name' => CRM_Booking_Utils_Constants::ACTIVITY_TYPE_SEND_EMAIL,
    );
    //chcck if it exist in case of re-installation 
    $optionValue = civicrm_api3('OptionValue', 'get',$params);
    if($optionValue['count'] == 0){
      $params['weight'] = 1;
      $params['is_reserved'] = 1;
      $params['is_active'] = 1;
      $result = civicrm_api('ActivityType', 'create', $params);
    }

    $result = civicrm_api('OptionGroup', 'getsingle', array(
      'version' => 3,
      'sequential' => 1,
      'name' => 'msg_tpl_workflow_booking')
    );

    if(isset($result['id'])){
      $params = array(
        'version' => 3,
        'sequential' => 1,
        'option_group_id' => $result['id'],
      );
      $opvResult = civicrm_api('OptionValue', 'get', $params);
      if(isset($opvResult['values'])  && !empty($opvResult['values'])){
        foreach ($opvResult['values'] as  $value) {
          switch ($value['name']) {
            case 'booking_offline_receipt':
              $html = file_get_contents($this->extensionDir . '/msg_tpl/booking_offline_receipt.html', FILE_USE_INCLUDE_PATH);
              $text = file_get_contents($this->extensionDir . '/msg_tpl/booking_offline_receipt.txt', FILE_USE_INCLUDE_PATH);
              $title = E::ts("Booking - Confirmation and Receipt (off-line)");
              break;
          }
          if(isset($title)){
            $params = array(
              'version' => 3,
              'sequential' => 1,
              'msg_title' => $title,
              'msg_subject' => E::ts("Booking - Confirmation Receipt").' - '.ts("Booking Status:").'{$booking_status}',
              'msg_text' => $text,
              'msg_html' => $html,
              'is_active' => 1,
              'workflow_id' =>  $value['id'],
              'is_default' => 1,
              'is_reserved' => 0,
            );
            $result = civicrm_api('MessageTemplate', 'create', $params);
            $params['is_default'] = 0;
            $params['is_reserved'] = 1;
            //re-created another template
            $result = civicrm_api('MessageTemplate', 'create', $params);

          }
        }
      }
    }
    $this->executeSqlFile('sql/civibooking_default.sql');
  }

  /**
   * Example: Run an external SQL script when the module is uninstalled
   */
  public function uninstall() {}

  /**
   * Example: Run a simple query when a module is enabled
   *
*/
  public function enable() {
   $this->executeSqlFile('sql/civibooking_enable.sql');
  }

  /**
   * Example: Run a simple query when a module is disabled
   *
  */
  public function disable() {
   //TODO:: Disable the message template
   $this->executeSqlFile('sql/civibooking_disable.sql');
  }

  // By convention, functions that look like "function upgrade_NNNN()" are
  // upgrade tasks. They are executed in order (like Drupal's hook_update_N).

  public function upgrade_1100() {
    $this->ctx->log->info('Applying update 1100');
    $this->executeSqlFile('sql/update_1100.sql');
    return TRUE;
  }



  /**
   * Example: Run a couple simple queries
   *
   * @return TRUE on success
   * @throws Exception
   *
  public function upgrade_4200() {
    $this->ctx->log->info('Applying update 4200');
    CRM_Core_DAO::executeQuery('UPDATE foo SET bar = "whiz"');
    CRM_Core_DAO::executeQuery('DELETE FROM bang WHERE willy = wonka(2)');
    return TRUE;
  } // */


  /**
   * Example: Run an external SQL script
   *
   * @return TRUE on success
   * @throws Exception
  public function upgrade_4201() {
    $this->ctx->log->info('Applying update 4201');
    // this path is relative to the extension base dir
    $this->executeSqlFile('sql/upgrade_4201.sql');
    return TRUE;
  } // */


  /**
   * Example: Run a slow upgrade process by breaking it up into smaller chunk
   *
   * @return TRUE on success
   * @throws Exception
  public function upgrade_4202() {
    $this->ctx->log->info('Planning update 4202'); // PEAR Log interface

    $this->addTask(E::ts('Process first step'), 'processPart1', $arg1, $arg2);
    $this->addTask(E::ts('Process second step'), 'processPart2', $arg3, $arg4);
    $this->addTask(E::ts('Process second step'), 'processPart3', $arg5);
    return TRUE;
  }
  public function processPart1($arg1, $arg2) { sleep(10); return TRUE; }
  public function processPart2($arg3, $arg4) { sleep(10); return TRUE; }
  public function processPart3($arg5) { sleep(10); return TRUE; }
  // */


  /**
   * Example: Run an upgrade with a query that touches many (potentially
   * millions) of records by breaking it up into smaller chunks.
   *
   * @return TRUE on success
   * @throws Exception
  public function upgrade_4203() {
    $this->ctx->log->info('Planning update 4203'); // PEAR Log interface

    $minId = CRM_Core_DAO::singleValueQuery('SELECT coalesce(min(id),0) FROM civicrm_contribution');
    $maxId = CRM_Core_DAO::singleValueQuery('SELECT coalesce(max(id),0) FROM civicrm_contribution');
    for ($startId = $minId; $startId <= $maxId; $startId += self::BATCH_SIZE) {
      $endId = $startId + self::BATCH_SIZE - 1;
      $title = E::ts('Upgrade Batch (%1 => %2)', array(
        1 => $startId,
        2 => $endId,
      ));
      $sql = '
        UPDATE civicrm_contribution SET foobar = whiz(wonky()+wanker)
        WHERE id BETWEEN %1 and %2
      ';
      $params = array(
        1 => array($startId, 'Integer'),
        2 => array($endId, 'Integer'),
      );
      $this->addTask($title, 'executeSql', $sql, $params);
    }
    return TRUE;
  } // */

}
