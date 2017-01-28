<?php

$projects = array();
$lists    = array();
$data     = array();

foreach ( $task_list as $task ) {
    $pid = $task->project_id;
    if ( isset( $data[$pid] ) ) {

        $task_array = array(
            'task_id' => $task->task_id,
            'task'    => $task->task
        );

        $list_array = array(
            'list_id' => $task->list_id,
            'list'    => $task->list,
            'task'    => $task_array,
        );

        if( in_array_r( $task->list_id, $data[$pid] ) ){
            $data[$pid]['list'][] = $task_array;
        }else{
            $data[$pid][] = $list_array;
        }

    } else {
        $data[$pid] = array(
            'project_id' => $pid,
            'project'    => $task->project,
            'list'       => array(
                'list_id' => $task->list_id,
                'list'    => $task->list,
                'task'    => arraY(
                    'task_id' => $task->task_id,
                    'task'    => $task->task
                ),
            ),
        );
    }
}




function in_array_r($needle, $haystack, $strict = false) {
    foreach ($haystack as $item) {
        if (($strict ? $item === $needle : $item == $needle) || (is_array($item) && in_array_r($needle, $item, $strict))) {
            return true;
        }
    }

    return false;
}

