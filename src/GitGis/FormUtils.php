<?php
/**
 * Created by PhpStorm.
 * User: gg
 * Date: 11/17/13
 * Time: 11:58 AM
 */

namespace GitGis;


class FormUtils {

    public function splitToArray($input, $idName, $fields, $retVal = array()) {
        $ids = $input[$idName];

        if (!empty($input)) {
            $existingKeys = array_keys($retVal);
            foreach ($existingKeys as $key) {
                if (!in_array($key, $ids)) {
                    unset($retVal[$key]);
                }
            }
        }

        foreach ($fields as $field) {
            foreach ($input[$field] as $cnt => $v) {

                if (!empty($ids[$cnt])) {
                    $id = $ids[$cnt];
                    if (!isset($retVal[$id])) {
                        $retVal[$id] = array();
                    }
                    $retVal[$id][$field] = $v;
                } else {
                    if (!isset($retVal[$cnt])) {
                        $retVal[$cnt] = array();
                    }
                    $retVal[$cnt][$field] = $v;
                }

            }
        }

        return $retVal;
    }

    public function toTimestamp($date) {
        $format = '%m/%d/%Y %H:%M:%S';

        if (strlen($date) == strlen('xxxx-xx-xx xx:xx:xx') - 3) {
            $date .= ':00';
        }

        $_timezone = date_default_timezone_get();
        date_default_timezone_set('America/Sao_Paulo');

        $ts = strptime($date, $format);

        $time = mktime($ts['tm_hour'], $ts['tm_min'], $ts['tm_sec'],
            $ts['tm_mon']+1, $ts['tm_mday'], ($ts['tm_year'] + 1900));

        date_default_timezone_set( $_timezone );

        if ($time < 0) $time = 0;

        return $time;
    }

}