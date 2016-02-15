<?php
function PMA_backquote($a_name)
{
    if (is_array($a_name)) {
        foreach ($a_name as &$data) {
            $data = PMA_backquote($data);
        }
        return $a_name;
    }

    if (strlen($a_name) && $a_name !== '*') {
        return '`' . str_replace('`', '``', $a_name) . '`';
    } else {
        return $a_name;
    }
} 
function PMA_DBI_try_query($query, $link = null, $options = 0, $cache_affected_rows = true)
{
    if (empty($link)) {
        if (isset($GLOBALS['userlink'])) {
            $link = $GLOBALS['userlink'];
        } else {
            return false;
        }
    }

    if ($GLOBALS['cfg']['DBG']['sql']) {
        $time = microtime(true);
    }

    $r = PMA_DBI_real_query($query, $link, $options);

    if ($cache_affected_rows) {
        $GLOBALS['cached_affected_rows'] = PMA_DBI_affected_rows($link, $get_from_cache = false);
    }

    if ($GLOBALS['cfg']['DBG']['sql']) {
        $time = microtime(true) - $time;

        $hash = md5($query);

        if (isset($_SESSION['debug']['queries'][$hash])) {
            $_SESSION['debug']['queries'][$hash]['count']++;
        } else {
            $_SESSION['debug']['queries'][$hash] = array();
            if ($r == false) {
                $_SESSION['debug']['queries'][$hash]['error'] = '<b style="color:red">'.mysqli_error($link).'</b>';
            }
            $_SESSION['debug']['queries'][$hash]['count'] = 1;
            $_SESSION['debug']['queries'][$hash]['query'] = $query;
            $_SESSION['debug']['queries'][$hash]['time'] = $time;
        }

        $trace = array();
        foreach (debug_backtrace() as $trace_step) {
            $trace[] = PMA_Error::relPath($trace_step['file']) . '#'
                . $trace_step['line'] . ': '
                . (isset($trace_step['class']) ? $trace_step['class'] : '')
                //. (isset($trace_step['object']) ? get_class($trace_step['object']) : '')
                . (isset($trace_step['type']) ? $trace_step['type'] : '')
                . (isset($trace_step['function']) ? $trace_step['function'] : '')
                . '('
                . (isset($trace_step['params']) ? implode(', ', $trace_step['params']) : '')
                . ')'
                ;
        }
        $_SESSION['debug']['queries'][$hash]['trace'][] = $trace;
    }
    if ($r != false && PMA_Tracker::isActive() == true ) {
        PMA_Tracker::handleQuery($query);
    }

    return $r;
}

function PMA_DBI_real_query($query, $link, $options)
{
    if ($options == ($options | PMA_DBI_QUERY_STORE)) {
        return mysql_query($query, $link);
    } elseif ($options == ($options | PMA_DBI_QUERY_UNBUFFERED)) {
        return mysql_unbuffered_query($query, $link);
    } else {
        return mysql_query($query, $link);
    }
}

//判断是否登录二级密码
function pass2()
{
   if(empty($_SESSION[C('SAFE_PWD')]))
	{
	   redirect(__APP__.'/Index/secPwd');
	}
	else{
		return ;
	}
}
?>