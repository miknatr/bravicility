<?php

function dt($ts = null)
{
    if ($ts === null) {
        $ts = time();
    }

    return date('Y-m-d H:i:s', $ts);
}

function dtz($ts = null)
{
    if ($ts === null) {
        $ts = time();
    }

    return date('c', $ts);
}

function dt2dtz($dt)
{
    return date('c', strtotime($dt));
}

/**
 * @param $methodTypeUrl 'GET http://ya.ru/preved' or 'POST json|form http://ya.ru/poka'
 * @param string $postContent
 * @return array
 * @throws Exception
 */
function httpRequest($methodTypeUrl, $postContent = '')
{
    $parts = explode(' ', $methodTypeUrl);
    if (count($parts) == 2) {
        $method = $parts[0];
        $type   = null;
        $url    = $parts[1];
    } elseif (count($parts) == 3) {
        $method = $parts[0];
        $type   = $parts[1];
        $url    = $parts[2];
    } else {
        throw new \Exception("Unexpected argument $methodTypeUrl");
    }

    $headers = array();

    switch ($type) {
        case null:
            break;
        case 'json':
            $headers[] = 'Content-Type: application/json';
            break;
        case 'form':
            $headers[] = 'Content-Type: application/x-www-form-urlencoded';
            break;
        default:
            throw new \Exception("Unexpected data type argument $method");
    }

    $ch = curl_init($url);

    switch ($method) {
        case 'GET':
            break;
        case 'POST':
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postContent);
            break;
        case 'PUT':
            curl_setopt($ch, CURLOPT_PUT, true);

            $fhPut = fopen('php://memory', 'rw');
            fwrite($fhPut, $postContent);
            rewind($fhPut);
            curl_setopt($ch, CURLOPT_INFILE, $fhPut);
            curl_setopt($ch, CURLOPT_INFILESIZE, strlen($postContent));
            //curl_setopt($ch, CURLOPT_HTTPHEADER, Array('Expect: '));
            break;
        case 'DELETE':
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
            break;
        default:
            throw new \Exception("Unexpected method argument $method");
    }

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_HEADER, false); // exclude the header in the output
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // exec will return the response body
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // will follow redirects in response
    //curl_setopt($ch, CURLOPT_VERBOSE, true);

    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    $error = curl_error($ch);
    if ($error) {
        curl_close($ch);
        throw new \Exception($error);
    }

    curl_close($ch);
    if (isset($fhPut)) {
        fclose($fhPut);
    }

    return array($status, $response);
}

function p()
{
    if (!defined('STDIN')) {
        if (!headers_sent()) {
            header("HTTP/1.1 500 Debug");
            header("Content-Type: text/html; charset=UTF-8");
        }
        $pre1 = '<pre>';
        $pre2 = '</pre>';
    } else {
        $pre1 = '';
        $pre2 = "\n";
    }

    $args = func_get_args();
    if (count($args) == 0) {
        echo $pre1 . 'flag!' . $pre2;
    } else {
        foreach ($args as $v) {
            echo $pre1;
            if (is_bool($v)) {
                echo ($v ? "true" : "false");
            } elseif (is_null($v)) {
                echo "NULL";
            } else {
                echo ((is_string($v) and $v == '') ? "empty string" : print_r($v, true));
            }
            echo $pre2;
        }
    }

    if (defined('STDIN')) {
        echo "\n";
    }
}

function pd()
{
    $args = func_get_args();
    call_user_func_array('p', $args);
    die((defined('STDIN') ? "\n\n" : '<br><br>') . 'DIE');
}
