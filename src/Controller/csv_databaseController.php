<?php

/** 
 * @file
 * Contains \Drupal\ncaoc_pending_cases\Controller\csv_databaseController
 */

namespace Drupal\csv_database_query\Controller;

use Drupal\Core\Database;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\Sql;
use Drupal\Core\Datetime\Element;
use Drupal\Component\Serialization;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;


/**
 * Controller routines
 */

class csv_databaseController {
    public function uploadFile() {
        $vars = array();
        return array('#theme' => 'test1', '#variables' => $vars);  
    }

    public function convertFile() {
        ini_set("auto_detect_line_endings", true);
        $file_name = DRUPAL_ROOT.'/sites/default/files/SampleData.csv';
        $vars['start_time'] = new \DateTime('NOW');
        $count = 0;
        $max_recs = 1000000;
        $timer = 0;
        $table = 'csv_database_query_table';
        $spec = array();
    // delete and truncate table
        $connection = \Drupal\Core\Database\Database::getConnection()->schema();
        $connection->dropTable($table);
        $db = \Drupal::database();
   
        // TODO: need to rebuild table without losing current data
        $handle=fopen($file_name,"r");
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
        $connection->createTable($table, $spec);
        debug($table, 'table');
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

                $query = $db->insert($table)->fields($fields_array)->execute();
            }
            if (!feof($handle)) {
               echo "Error: unexpected fgets() fail\n";
            }
        }
      
        fclose($handle);
        $vars['records'] = $count;
        $field_list = serialize($field_names);
        \Drupal::state()->set('csv_database_query_fields',$field_list);
    //    $county_district_map = csv_databaseController::createDistrictCountyMap();
    //    \Drupal::state()->set('csv_database_district_county_map', $county_district_map);
        // rebuild fields table
        csv_databaseController::rebuildFieldsTable($field_names);
        $vars['end_time'] = new \DateTime('NOW');
        return array('#theme' => 'converter', '#variables' => $vars);
    }

    public static function queryTable($conditions, $fields, $start = 0, $limit= 10000) {
        $results = array();
        $results['record_start'] = $start;
        $results['record_limit'] = $limit;
        $db = \Drupal::database();
        $query = $db->select('csv_database_query_table', 'pending');
        if (isset($fields)) {
            $query->fields('pending', $fields);
        } else {
            $query->fields('pending');
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
    public function xfieldsAPI() {       
        $results = array();
        $db = \Drupal::database();
        $query = $db->select('ncaoc_pending_cases_fields', 'fields');
        $query->fields('fields');
        $results = $query->execute()->fetchall();
//        return $results;
        return array('#theme' => 'test1', '#variables' => $vars);
        
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
    }
    
    public function xfieldInfoAPI() {
        $results = array();
        $field = 'DEFENDANT_SEX';
        $db = \Drupal::database();
        $query = $db->select('ncaoc_pending_cases_fields', 'fields');
        $query->fields('fields');
        $query->condition('name', $field, '=');
        $results = $query->execute()->fetchall();
//        return $results;

        
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
    
    public function getDistrictCountyMap() {
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
//        dpm($query_fields, 'Q F');
        $fields_array = array();
        $results = $response = \Drupal\csv_database_query\Controller\csv_databaseController::getFieldsList();
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
    //    $field_names = array();
        $query = $db->select('csv_database_query_fields', 'fields')
                ->fields('fields', array('name','column_heading', 'display_order'))
                ->condition('name', $fields_array, 'in')
                ->orderby('display_order');
        $results = $query->execute()->fetchall();
        foreach($results as $result) {
 //           $column_headings[] = str_replace(' ', '<br/>', $result->column_heading);
            $column_headings[$result->name] = $result->column_heading;
       //     $field_names[] = $result->name;
        } 
      //  $vars['field_names'] = $field_names;
        $vars['headings'] = $column_headings;
        $vars['selects'] = $selects;
        $vars['filters'] = $filters;
        $vars['cases'] = $cases;
 //       dpm($vars['query_fields'], 'vars');
        return array('#theme' => 'results', '#variables' => $vars);
    }
    
    public function queryResultsNext() {
        $query_fields = Request::createFromGlobals()->query;
 //       dpm($query_fields, 'Q F');
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
  
    public static function findUniqueValues($field) { 
        set_time_limit ( 250 );
        $date = new \DateTime('NOW');
        dpm($date, 'start '.$field);
        $query = db_select('csv_database_query_table', 't');
        $query->fields('t', array($field));
        $query->orderby($field, "ASC");
        $results =$query->distinct()->execute()->fetchall();
        $options = array();
        foreach ($results as $result) {
            $options[] = $result->$field;
}
        dpm(($date->date), 'end '.$field);
        return $options;
    }
    
   public static function getFieldsList() {  
        $db = \Drupal::database();
        $query = $db->select('csv_database_query_fields', 'fields')
                ->fields('fields', array('name', 'display_type'))
                ->orderby('display_order')
                ->condition('display_type', 'hide', '<>');
        $db_names = $query->execute()->fetchall();
        return $db_names;
    }
    
    private static function createDistrictCountyMap() { 
        set_time_limit ( 250 );
        $query = db_select('csv_database_query_table', 't');
        $query->fields('t', array('COUNTY', 'district'));
        $query->orderby('COUNTY', "ASC");
        $results =$query->distinct()->execute()->fetchall();
        $options = array();
        foreach ($results as $result) {
            $value[$result->district][] = [$result->COUNTY];
            
        }    
        return serialize($value);
    }
     private static function rebuildFieldsTable($field_names) {
        // Get records from DB that are associated to the Fields
        // remove any entries that are not in the $fields array
        // 
         // if it exists in the Fields table:
         //     keep the display_type and column_heading, rebuild the options and display_order
         // if it is not in the Fields table - add it
         // loop through Fields table and remove any entries that are not in the fields array
        
        $db = \Drupal::database();
        $column_headings = array();
    
        $query = $db->select('csv_database_query_fields', 'fields')
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
                        $options = serialize(\Drupal\csv_database_query\Controller\csv_databaseController::findUniqueValues($field_name));
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
        $db->truncate('csv_database_query_fields')->execute();
        foreach ($db_array as $field_array) {
            $query = $db->insert("csv_database_query_fields")->fields($field_array)->execute();
        }
        return;
    }   
    
}

