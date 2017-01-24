<?php

/** 
 * @file
 * Contains \Drupal\csv_database_query\Controller\csv_databaseController
 */

namespace Drupal\csv_database_query\Controller;

use Drupal\Core\Database;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\Sql;
use Drupal\Core\Datetime\Element;
use Drupal\Component\Serialization;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Drupal\csv_database_query\Form\csv_databaseUploadForm;


/**
 * Controller routines
 */

class csv_databaseController {
    public function uploadFilexx() {
        $vars = array();
        return array('#theme' => 'test1', '#variables' => $vars);  
    }

    public function convertFile($fid, $table_name) {
        ini_set("auto_detect_line_endings", true);
     //   $file_name = DRUPAL_ROOT.'/sites/default/files/csv_files/'.file_name;
        $file_info = \Drupal\csv_database_query\Form\csv_databaseUploadForm::getFileName($fid);
        $file_uri = $file_info['uri'];
        $vars['start_time'] = new \DateTime('NOW');
        $count = 0;
        $max_recs = 20000;
        $timer = 0;
        $db_table = 'csv_database_query_table_'.$table_name;
        $fields_table = 'csv_database_fields_table_'.$table_name;
        $spec = array();
    // delete and truncate table
        $connection = \Drupal\Core\Database\Database::getConnection()->schema();
        $connection->dropTable($db_table);
        $connection->dropTable($fields_table);
    //    $db_conn = \Drupal\Core\Database\Database::getConnection();
        $db = \Drupal::database();
   
        // TODO: need to rebuild table without losing current data
        $handle=fopen($file_uri,"r");
        // get header line and create DB table using fields
     //   $first_line = fgets($handle);

        $field_names_array = fgetcsv($handle);
        $field_names = array();
        foreach ($field_names_array as $field_name_in) {
            $field_name = preg_replace('/[^A-Za-z0-9_]/', "", $field_name_in);
            $field_names[] = $field_name;
            $spec['fields'][$field_name] = array(
                'description' => $field_name,
                'type' => 'text',
            );
        }
        $connection->createTable($db_table, $spec);
  //      dpm($spec, 'spec');
  //      sleep(20);
        if ($handle) {
            while ((($rec_array = fgetcsv($handle)) !== false) && ($count < $max_recs)) {
                if ($timer == 50000) {
                    set_time_limit ( 280 );
                    $timer = 0;
                }        
                $count++;
                $timer++;
                $index = 0;
                foreach ($field_names as $field_name) {
                   $field_name = preg_replace('/[^A-Za-z0-9_]"/', "", $field_name);  //*****?????
                   $fields_array[$field_name] = $rec_array[$index];  
                   $index++;
                }
//                $insert = $db->insert($db_table)->fields($fields_array);
//                $query = $insert->execute();

                $query = $db->insert($db_table)->fields($fields_array)->execute();
          //      $db_conn->insert($db_table)->fields($fields_array)->execute();
      //          dpm($query, 'query1');
      //          dpm($fields_array, 'fields array');
            }
            if (!feof($handle)) {
               echo "Error: unexpected fgets() fail\n";
            }
        }
     //   $db->commit();
        fclose($handle);
        $vars['file_name'] = $file_uri;
        $vars['table_name'] = $db_table;
   //     $user = \Drupal\user\Entity\User::load($uid);
   //     $vars['user_name'] = $user->getAccountName();
        $vars['records'] = $count;
        $field_list = serialize($field_names);
        \Drupal::state()->set('csv_database_fields_table_'.$table_name,$field_list);
    //    $county_district_map = csv_databaseController::createDistrictCountyMap();
    //    \Drupal::state()->set('csv_database_district_county_map', $county_district_map);
        csv_databaseController::createFieldsTable($fields_table);
        // rebuild fields table
        csv_databaseController::buildFieldsTable($db_table, $fields_table, $field_names);
        $vars['end_time'] = new \DateTime('NOW');
        return array('#theme' => 'converter', '#variables' => $vars);
    }

    public static function queryTable($table, $conditions, $fields, $start = 0, $limit= 10000) {
        $results = array();
        $results['record_start'] = $start;
        $results['record_limit'] = $limit;
        $db = \Drupal::database();
     //   $user = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
     //   $uid = \Drupal::currentUser()->id();
        $query = $db->select($table, 'db_table');
        if (isset($fields)) {
            $query->fields('db_table', $fields);
        } else {
            $query->fields('db_table');
        }
//        dpm($conditions, 'conditions');
        foreach ($conditions as $condition) {
            if ($condition['values']['0'] !== '') {
                $query->condition($condition['field'], $condition['values'], $condition['operator']);    
            }
        }
        // get record count
        $results['record_count'] = $query->countQuery()->execute()->fetchField();
        if ($results['record_count'] > $results['record_limit']) {
            $query->range($results['record_start'], $results['record_limit']);
        }
        $results['cases'] = $query->execute()->fetchall();



        return $results;
    }

    // This is currently not being used
    public function queryAPI() {
        $request = Request::createFromGlobals();
        $parameters = $request->request;
       $start = $parameters->get('start');
       $length = $parameters->get('length');
       $fields = array();
       foreach ($parameters->get('columns') as $field) {
           $fields[] = $field['data'];
       }
        $content_array = \Drupal\Component\Serialization\Json::decode($content);
  //      \Drupal::state()->set('pending_cases', $parameters);
  //      \Drupal::state()->set('pending_cases_length', $length);
  //              \Drupal::state()->set('pending_cases_start', $start);
     //   $fields = $content_array['fields'];
     //   $fields = array('District', 'COUNTY', 'CASE_NUMBER');
        $conditions = $content_array['conditions'];
        $data['data'] = csv_databaseController::queryTable(
                $conditions, $fields, $start, $length);
        $tables_data['draw'] = $parameters->draw;
        $tables_data['recordsTotal'] = $length;
        $tables_data['recordsFiltered'] = $length;
        $tables_data['data'] = $data['data']['cases'];
     //   $data['results'] = $request;
        $data['recordsTotal'] = $data['data']['results']['record_count'];    
        $response = new Response();
        $response->setContent(json_encode($tables_data));
     //   $response->setContent(json_encode($content_array));
        $response->headers->set('Content-Type', 'application/json');
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
     //   $response->headers->set('Access-Control-Allow-Headers: X-Requested-With');
  //      $response->headers->set('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
        return $response;
  //      return array('#theme' => 'test1', '#variables' => $vars);
    }

    
    public function testPage() {
        $results = array();

        
/*         
        $response = new Response();
        $response->setContent(json_encode($results));
     //   $response->setContent(json_encode($content_array));
        $response->headers->set('Content-Type', 'application/json');
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
     //   $response->headers->set('Access-Control-Allow-Headers: X-Requested-With');
  //      $response->headers->set('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
        return $response;  */
        return array('#theme' => 'test1', '#variables' => $results);
    }    
    
    public function xgetDistrictCountyMap() {
        $district_county_map = unserialize(\Drupal::state()->get('pending_cases_district_county_map'));        
        $response = new Response();
        $response->setContent(json_encode($district_county_map));
        $response->headers->set('Content-Type', 'application/json');
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        return $response;
    }
    
    public function queryResults() {
        $query_fields = Request::createFromGlobals()->query;
        $fields_table = $query_fields->get('fields_table');
        $db_table = $query_fields->get('db_table');
        $fields_array = array();
        $results = $response = \Drupal\csv_database_query\Controller\csv_databaseController::getFieldsList($fields_table);
        foreach($results as $result) {
            $fields_array[] = $result->name;
        }

        $conditions = $query_fields->get('conditions');
        $selects = array();
        $filters = array();
 //       $fields = \Drupal\ncaoc_pending_cases\Controller\csv_databaseController::getcsv_databaseFields();
        foreach($conditions as $cond_name => $cond_values) {
            if ($cond_values['operator'] !== '') {
            $conds[] = array('field' => $cond_name, 'values' => array($cond_values['value']), 'operator' => $cond_values['operator']);   
            }
        }

        $cases = array();
        foreach ($conditions as $field => $condition) {
            if (($condition['operator'] == '=') && ($condition['value'] !== '')) {
               $selects[] = array('field' => $field, 'value' => $condition['value'], 'operator'=>$condition['operator']);
            } 
            if (($condition['operator'] !== '=') && ($condition['value'] !== '')) {
               $filters[] = array('field' => $field, 'value' => $condition['value'], 'operator'=>$condition['operator']);
            }
        }
        foreach ($selects as $select) {
            if (in_array($select['field'], $fields_array)) {
                foreach ($fields_array as $index => $value) {
                    if ($select['field'] == $value) {
                        unset($fields_array[$index]);
                    }
                }
            }
        }
        $response = \Drupal\csv_database_query\Controller\csv_databaseController::queryTable($db_table, $conds, $fields_array);
        foreach ($response['cases'] as $result) {
            foreach ($result as $key => $value) {
                $case[$key] = $value;
            }
            $cases[] = $case;
        }
        $vars['query_fields'] = $query_fields;
        $vars['results_count'] = $response['record_count'];
        $vars['record_start'] = $response['record_start']+1;
        $vars['record_limit'] = $response['record_limit'];
        if ($response['record_limit'] > $response['record_count']) {
           $vars['record_limit'] = $response['record_count'];
        }
        
        // TODO: make this use function
        $db = \Drupal::database();
        $column_headings = array();
        $field_names = array();
        $query = $db->select($fields_table, 'fields')
                ->fields('fields', array('name','column_heading', 'display_order'))
                ->condition('name', $fields_array, 'in')
                ->orderby('display_order');
        $results = $query->execute()->fetchall();
        foreach($results as $result) {
 //X           $column_headings[] = str_replace(' ', '<br/>', $result->column_heading);
            $column_headings[$result->name] = $result->column_heading;
       //X     $field_names[] = $result->name;
        }
        $vars['field_names'] = $field_names;
        $vars['headings'] = $column_headings;
        $vars['selects'] = $selects;
        $vars['filters'] = $filters;
        $vars['cases'] = $cases;
        return array('#theme' => 'results', '#variables' => $vars);
    }
    
    public function queryResultsNextx() {
        $query_fields = Request::createFromGlobals()->query;
        $fields_array = $query_fields->get(show_fields);
        $conditions = $query_fields->get(conditions);
        $selects = array();
        $filters = array();
 //       $fields = \Drupal\ncaoc_pending_cases\Controller\csv_databaseController::getcsv_databaseFields();
        foreach($conditions as $cond_name => $cond_values) {
            if ($cond_values['operator'] !== '') {
            $conds[] = array('field' => $cond_name, 'values' => array($cond_values['value']), 'operator' => $cond_values['operator']);   
            }
        }
        $results = array();
        $cases = array();

        foreach ($conditions as $field => $condition) {
            if (($condition['operator'] == '=') && ($condition['value'] !== '')) {
               $selects[] = array('field' => $field, 'value' => $condition['value'], 'operator'=>$condition['operator']);
            } 
            if (($condition['operator'] !== '=') && ($condition['value'] !== '')) {
               $filters[] = array('field' => $field, 'value' => $condition['value'], 'operator'=>$condition['operator']);
            }
        }
        foreach ($selects as $select) {
            if (in_array($select['field'], $fields_array)) {
                foreach ($fields_array as $index => $value) {
                    if ($select['field'] == $value) {
                        unset($fields_array[$index]);
                    }
                }
            }
        }
        
        $response = \Drupal\csv_database_query\Controller\csv_databaseController::queryTable($conds, $fields_array);
        foreach ($response['cases'] as $result) {
            foreach ($result as $key => $value) {
                $case[$key] = $value;
            }
            $cases[] = $case;
        }
        
        $vars['results_count'] = $response['record_count'];
        $vars['record_start'] = $response['record_start']+1;
        $vars['record_limit'] = $response['record_limit'];
        if ($response['record_limit'] > $response['record_count']) {
           $vars['record_limit'] = $response['record_count']; 
        }
        $vars['fields'] = $fields_array;
        $vars['selects'] = $selects;
        $vars['filters'] = $filters;
        $vars['cases'] = $cases;
        return array('#theme' => 'results', '#variables' => $vars);
    }
  
    public static function findUniqueValues($db_table, $field) {
        set_time_limit ( 250 );
        $date = new \DateTime('NOW');
        $query = db_select($db_table, 't');
        $query->fields('t', array($field));
        $query->orderby($field, "ASC");
        $results =$query->distinct()->execute()->fetchall();
        $options = array();
        foreach ($results as $result) {
            $options[] = $result->$field;
}
        return $options;
    }
    
   public static function getFieldsList($fields_table) {
        $db = \Drupal::database();
        $query = $db->select($fields_table, 'fields')
                ->fields('fields', array('name', 'display_type'))
                ->orderby('display_order')
                ->condition('display_type', 'hide', '<>');
        $db_names = $query->execute()->fetchall();
        return $db_names;
    }


    private static function createFieldsTable($fields_table) {
        $connection = \Drupal\Core\Database\Database::getConnection()->schema();
        $spec['fields'] = array(
            'name' => array(
                'description' => 'Name of field.',
                'type' => 'varchar',
                'not null' => TRUE,
                'length' => '100',
            ),
            'options' => array(
                'description' => 'serialized array of options for the field',
                'type' => 'text',
                'not null' => FALSE,
            ),
            'display_type' => array(
                'description' => 'Type of display - show only or dropdown.',
                'type' => 'varchar',
                'not null' => TRUE,
                'length' => '10',
            ),
            'column_heading' => array(
                'description' => 'Column heading for display.',
                'type' => 'varchar',
                'not null' => FALSE,
                'length' => '25',
            ),
            'display_order' => array(
                'description' => 'Order for display output.',
                'type' => 'int',
                'not null' => FALSE,
            ),
        );

        $connection->createTable($fields_table, $spec);
        $spec = array('name');
        $connection->addPrimaryKey($fields_table, $spec);
    }

     private static function buildFieldsTable($db_table, $fields_table, $field_names) {
        // Get records from DB that are associated to the Fields
        // remove any entries that are not in the $fields array
        // 
         // if it exists in the Fields table:
         //     keep the display_type and column_heading, rebuild the options and display_order
         // if it is not in the Fields table - add it
         // loop through Fields table and remove any entries that are not in the fields array
        
        $db = \Drupal::database();
        $column_headings = array();
    
        $query = $db->select($fields_table, 'fields')
                ->fields('fields', array('name','column_heading', 'display_type'))
                ->condition('name', $field_names, 'in');
        $results = $query->execute()->fetchall();
        $db_array = array();
        $display_order = 0;
        foreach ($field_names as $field_name) {
            $flag = 0;
            $display_order++;
            foreach($results as $db_field) {
                if ($field_name == $db_field->name) {
                    $options = NULL;
                    if ($db_field->display_type == 'select') {
                        $options = serialize(\Drupal\csv_database_query\Controller\csv_databaseController::findUniqueValues($db_table, $field_name));
                    }
                    
                    $db_array[] = array('name'=>$field_name, 'options'=>$options, 'display_type' => $db_field->display_type, 
                        'column_heading' => $db_field->column_heading, 'display_order' => $display_order);
                    $flag = 1;
                   break;
                } 
            }
            if ($flag == 0) {
               $column_heading = ucwords(strtolower(str_replace("_", " ", $field_name))); 
                $db_array[] = array('name'=>$field_name, 'options'=>NULL, 'display_type' => 'hide', 
                        'column_heading' => $column_heading, 'display_order' => $display_order); 
            }
        }
        $db->truncate($fields_table)->execute();
        foreach ($db_array as $field_array) {
            $query = $db->insert($fields_table)->fields($field_array)->execute();
        }
        return;
    }


}

/*
use Drupal\Core\Database;
use Drupal\Core\Database\Connection;
$table_name = 'yyy1';
    // delete and truncate table
$connection = \Drupal\Core\Database\Database::getConnection()->schema();
       $db_table = 'csv_database_query_table_'.$table_name;
        $fields_table = 'csv_database_fields_table_'.$table_name;
       $connection->dropTable($db_table);
 $connection->dropTable($fields_table);
 */

