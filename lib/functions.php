<?php

/*!
 * paramana.com
 * Version: 2.0
 * Started: 02-09-2010
 * Updated: 02-09-2010
 * http://www.paramana.com
 *
 * Copyright (c) 2010 paramana.com
 *
 * General purpose functions
 *
 */

include_once('class/db_class.php');
include_once('../config/config_.php');

    //global $idb;
    $idb        = new idb(DB_USER, DB_PASSWORD, DB_NAME, DB_HOST);

    $query      = 'SELECT p.page_title, p.last_modified, t.page_type_title
                    FROM paramana_page_list p LEFT JOIN paramana_page_types t
                   ON p.dom_id = t.ID_2';

    $results    = $idb->get_results($query);
    echo json_encode($results);
?>