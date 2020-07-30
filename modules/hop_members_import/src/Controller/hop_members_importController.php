<?php

/**
 * @file
 * Contains \Drupal\hop_members_import\Controller\hop_members_importController.
 */

namespace Drupal\hop_members_import\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;
use Drupal\field_collection\Entity\FieldCollectionItem;
use Drupal\taxonomy\Entity\Term;
use Drupal\Core\Entity;
use Drupal\taxonomy\TermInterface;
use Symfony\Component\HttpFoundation\Request;

class hop_members_importController extends ControllerBase {

  public function content() {

    function hopdate($prefix1, $year1, $prefix2, $year2) {


      $prefix1 = $prefix2 = "";

      $years = array();

      if ($prefix1 != "")
        $prefix1 .= " ";
      if ($prefix2 != "")
        $prefix2 .= " ";

      if ($year1 > 1000 && $year1 < 2100) {
        $years[] = $year1;
      }
      if (!empty($year2) && $year2 > 1000 && $year2 < 2100) {
        $year2100 = $year2 % 100;
        if (intval($year1 / 100) == intval($year2 / 100)) {
          $years[] = (intval($year1 / 100) == intval($year2 / 100)) ? $prefix2 . $year2100 : $prefix2 . $year2;
        }
      }

      $output = (!empty($years)) ? "(" . implode("-", $years) . ")" : "";

      return ($output);
    }

    // Add Constiuencies - 1 constiuency - more volumes (Taxonomy)
    set_time_limit(0);
    $c = 0;
    $categories_vocabulary = 'constituency_label';
    $files = array('constituencies_1422-61.csv', 'constituencies_1640_60.csv');
    $parent = "constituency_label";
    //tid: 2 , 7
    $volume_tids = array(2, 7);


    /*
      $tids = \Drupal::entityQuery('taxonomy_term')
      ->condition('vid', 'constituency_label')
      ->execute();

      $controller = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
      $entities = $controller->loadMultiple($tids);
      $controller->delete($entities);

      $tax = \Drupal::service('entity_type.manager')->getStorage("taxonomy_term")->loadTree("constituency_label", $parent = 0, $max_depth = NULL, $load_entities = FALSE);

      dpm ($tax);
     */

    $files = array();

    foreach ($files as $files_num => $file) {

      global $base_url;
      global $base_path;
      $link = $base_url . $base_path;

      $lines = file('/usr/www/users/commons/commons.prod/libraries/hopdata/' . $file);

      foreach ($lines as $line_num => $line) {
        // && $line_num <= 1
        if ($line_num >= 0) {

          $row = explode(",", $line);
          foreach ($row as $coloumn_num => $r) {
            $row[$coloumn_num] = trim($r);
          }

          $volume_tid = $volume_tids[$files_num];

          $category = $row[0];
          $tid = key(taxonomy_term_load_multiple_by_name($row[0], "constituency_label"));

          if (empty($tid)) {

            $term = Term::create(array(
                'parent' => array($parent),
                'name' => $category,
                'vid' => $categories_vocabulary,
                'field_cl_volume' => array($volume_tid),
              ))->save();
            $c++;
          }
          else {

            $taxonomy_volumes = \Drupal\taxonomy\Entity\Term::load($tid)->field_cl_volume->getValue();

            $volumeids = array();
            foreach ($taxonomy_volumes as $key => $vids) {
              $volumeids[] = $vids['target_id'];
            }

            if (!in_array($volume_tid, $volumeids)) {
              // add  new add volume
              $volumeids[] = $volume_tid;
              $term = Term::load($tid);
              $term->field_cl_volume->setValue($volumeids);
              $term->Save();
            }
          }
        }
      }
    }

    // Members...


    /* Prefixes of DoB and DoD

      aft.|aft.
      at least|at least
      b.|b.
      bef.|bef.
      bef.c.|bef.c.
      bet.|bet.
      by|by
      c.|c.
      d.|d.
      ?|?
      ?aft|?aft.
      ?bef|?bef.
      ?c|?c.
      ?by|?by
      fl.|fl.
      in or aft.|in or aft.
      b.bef.|b.bef.
      b. by|b. by
      b.c.|b.c.
      exec.|exec.
      prob. by|prob. by
      b. ?|b. ?


      ?
      1643 or 1658

      aft. jul.
      at least
      aft.1
      by Jan.
      d after
      d.
      prefix death




      aft.|aft.
      at least|at least
      b.|b.
      bef.|bef.
      bef.c.|bef.c.
      bet.|bet.
      by|by
      c.|c.
      d.|d.
      ?|?
      ?aft|?aft.
      ?bef|?bef.
      ?c|?c.
      ?by|?by
      fl.|fl.
      in or aft.|in or aft.
      b.bef.|b.bef.
      b. by|b. by
      b.c.|b.c.
      exec.|exec.
      prob. by|prob. by
      b. ?|b. ?

     */


    $list_decodes = array("variant spelling" => "spelling",
      "alias" => "alias",
      "Sir" => "Sir",
      "?" => "?",
      "aft." => "aft.",
      "b. bef." => "bef.",
      "b. before " => "bef.",
      "by" => "by",
      "c" => "c.",
      "c." => "c.",
      "fl." => "fl.",
      "fl.c." => "fl.",
      "pefix_birth" => "",
      "Unknown" => "",
      "at least" => "at least",
    );

    $files = array('checklist_1422.csv', 'checklist_1640_2.csv');


    $files = array();
    //$files = array ('checklist_1422.csv');
    /*
      Coloumns:
      1422: ID	Surname	alternative surname type	Alternative surname1	alternative surname type	Alternative surname 2	Title before name
      1640: ID	Full name	Surname	alternative surname type	alternative surname	Title before name	forename(s)	Title after name	b. date	d. date	b. date	d. date			prefix_birth	yob	prefix_death	yod
     */
    $cnames = array(
      array("ID", "Surname", "alternative surname type", "Alternative surname1", "alternative surname type2", "Alternative surname 2", "Title before name", "forename(s)"),
      array("ID", "Full name", "Surname", "alternative surname type", "alternative surname", "Title before name", "forename(s)", "Title after name", "b. date", "d. date", "b. date2", "d. date2", "a", "b", "prefix_birth", "yob", "prefix_death", "yod"));

    foreach ($files as $files_num => $file) {

      global $base_url;
      global $base_path;
      $link = $base_url . $base_path;

      // Get csv file
      $lines = file('/usr/www/users/commons/commons.prod/libraries/hopdata/' . $file);

      $volume_tid = $volume_tids[$files_num];

      foreach ($lines as $line_num => $line) {
        // && $line_num <= 1
        if ($line_num > 0) {

          $row = explode(",", $line);
          foreach ($row as $coloumn_num => $r) {
            $row[$coloumn_num] = trim($r);
          }

          if ($files_num == 0) {

            $title = ($row[6] == "") ? strtoupper($row[1]) . ", " . $row[7] : strtoupper($row[1]) . ", " . $row[6] . " " . $row[7];
            $list_data = (isset($list_decodes[$row[6]])) ? $list_decodes[$row[6]] : "";

            $values = array(
              'nid' => NULL,
              'type' => 'member_biography',
              'title' => $title,
              'field_article_reference_number' => $row[0],
              'field_volume' => array($volume_tid),
              'field_surname' => $row[1],
              'field_forename' => $row[7],
              'field_title_before_name' => $list_data,
              'field_weight' => $row[0] * 10,
              'body' => array(
                'value' => '',
                'format' => 'full_html',
              ),
              'uid' => 1,
              'status' => TRUE,
            );

            $node = \Drupal::entityManager()->getStorage('node')->create($values);

            if (!empty($row[2])) {
              $field_collection_item = FieldCollectionItem::create(array(
                  'field_name' => 'field_alternative_surname',
                  'langcode' => 'en',
                  'skip_host_save' => TRUE,
                  'host_type' => 'node',
                  'field_alias_type' => $list_decodes[$row[2]],
                  'field_alternative_surname' => "$row[3]",
              ));

              $field_collection_item->setHostEntity($node);
            }

            if (!empty($row[4])) {
              $field_collection_item = FieldCollectionItem::create(array(
                  'field_name' => 'field_alternative_surname',
                  'langcode' => 'en',
                  'skip_host_save' => TRUE,
                  'host_type' => 'node',
                  'field_alias_type' => $list_decodes[$row[4]],
                  'field_alternative_surname' => "$row[5]",
              ));

              $field_collection_item->setHostEntity($node);
            }
          }
          elseif ($row[1] != "") {
            // RUSSELL, Edward (1652-1727), NEVILL, George (c. 1623-66), HERBERT, Edward (1630-78)


            $title = ($row[7] == "") ? strtoupper($row[2]) . ", " . $row[6] . " " . hopdate($row[14], $row[15], $row[16], $row[17]) : strtoupper($row[2]) . ", " . $row[5] . " " . $row[6] . " " . hopdate($row[14], $row[15], $row[16], $row[17]);

            $field_alias_type = (isset($list_decodes[$row[3]])) ? $list_decodes[$row[3]] : "";
            $field_title_before_name = (isset($list_decodes[$row[5]])) ? $list_decodes[$row[5]] : "";
            $field_dob_prefix = (isset($list_decodes[$row[14]])) ? $list_decodes[$row[14]] : "";
            $field_dod_prefix = (isset($list_decodes[$row[16]])) ? $list_decodes[$row[16]] : "";


            $values = array(
              'nid' => NULL,
              'type' => 'member_biography',
              'title' => $title,
              'field_article_reference_number' => $row[0],
              'field_volume' => array($volume_tid),
              'field_surname' => $row[2],
              'field_forename' => $row[6],
              'field_title_before_name' => $field_title_before_name,
              'field_title_after_name' => $row[7],
              'field_birth_year' => $row[15],
              'field_dob_prefix' => $field_dob_prefix,
              'field_death_year' => $row[17],
              'field_dod_prefix' => $field_dod_prefix,
              'field_weight' => $row[0] * 10,
              'body' => array(
                'value' => '',
                'format' => 'full_html',
              ),
              'uid' => 1,
              'status' => TRUE,
            );

            $node = \Drupal::entityManager()->getStorage('node')->create($values);

            if (!empty($row[2])) {
              $field_collection_item = FieldCollectionItem::create(array(
                  'field_name' => 'field_alternative_surname',
                  'langcode' => 'en',
                  'skip_host_save' => TRUE,
                  'host_type' => 'node',
                  'field_alias_type' => $field_alias_type,
                  'field_alternative_surname' => $row[4],
              ));

              $field_collection_item->setHostEntity($node);
            }
            /*
              Dob
              field_date_of_birth
              field_dob_prefix
              field_birth_day
              field_birth_month
              field_birth_year

              field_date_of_death
              field_death_day
              field_death_month
              field_death_year
              field_dod_prefix


             */
          }

          if (!empty($node))
            $node->save();
          print ("<br>$title, $row[0]");
          unset($node);
        }
      }
    } // files

    return array(
      '#type' => 'markup',
      '#markup' => $this->t($c . 'Constirunecies imported'),
    );
  }

  /**
   * {@inheritdoc}
   */
  function get_field_collection($node, $field_collection_item, $field_collection_field) {

    $output = "";

    $fc_value = $node->$field_collection_item->value;

    $storage = \Drupal::entityManager()->getStorage('field_collection_item');
    $storage_value = $storage->load($fc_value);
    $output = $storage_value->$field_collection_field->value;

    return ($output);
  }

}
