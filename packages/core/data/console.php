<?php
/*
    This file is part of the eQual framework <http://www.github.com/cedricfrancoys/equal>
    Some Rights Reserved, Cedric Francoys, 2010-2021
    Licensed under GNU LGPL 3 license <http://www.gnu.org/licenses/>
*/

use core\Log;

list($params, $providers) = eQual::announce([
    'description'   => 'Returns a descriptor of current installation Settings, holding specific values for current User, if applicable.',
    'access'        => [
        'visibility'        => 'public'
    ],
    'params' => [
        'thread_id' => [
            'type' => 'string',
            'description' => 'Thread_id of the line'
        ],
        'level' => [
            'type'         => 'string',
            'description' => 'Level of the  WARNING | DEBUG | INFO | ERROR'
        ],
        'mode' => [
            'type'        => 'string',
            'description' => 'php | orm | sql | api | app'
        ],
        'time' => [
            'type' => 'string',
            'description' => 'Indicates the time of the log'

        ],
        'mtime' => [
            'type' => 'string',
            'description' => 'Mtime allows to look for a precise time'

        ],
        'help' => [
            'type' => 'boolean',
            'description' => 'Set to true to display help'
        ],
        'limit' => [
            'type' => 'integer',
            'description' => 'Returns the selected number of lines'
        ]
    ],
    'response'      => [
        'content-type'      => 'application/json',
        'charset'           => 'UTF-8',
        'accept-origin'     => '*'
    ],
    'providers'     => ['context', 'orm', 'auth']
]);


list($context, $om, $auth) = [$providers['context'], $providers['orm'], $providers['auth']];

function printStack($thread, int $level = 0)
{
    $green = "\e[32;1m";
    $red = "\e[31;1m";
    $blue = "\e[34;1m";
    $yellow = "\e[33;1m";
    $white = "\e[0m";
    $bold = "\e[00;1m";
    $text = "";

    for ($tmp_lvl = $level; $tmp_lvl > 0; $tmp_lvl--) {
        $text .= "-";
    }
    $text .= "$green ${thread['time']} $white";
    if ($thread['mtime']) {
        $text .= "$bold {$thread['mtime']} $white";
    }

    if (is_string($thread['level'])) {
        $text .= " [" .  calColor($thread['level'])  . $thread['level'] . "]" . $white;
    }
    $text .= "$bold${$thread['mode']} $white";
    $text .= "$bold ${thread['function']} ";
    $text .= "@ ${thread['file']} : ";
    $text .= "line ${bold} ${thread['line']}  | $white";
    $text .= "thread_id $red ${thread['thread_id']} $white";
    // $text .= "cla : " .  $bold . $thread['class'] . " \n" . $white;
    if (is_string($thread['message'])) {
        $text .= "\e[7m \nmessage: $white ${thread['message']} ";
        // $text .= "\n body $messageBody  \n $white";
    }
    if (isset($thread['stack']) && count($thread['stack']) > 0) {
        foreach ($thread['stack'] as $subitem) {
            $text .= "\n" . printStack($subitem, $level + 1);
        }
    }

    return ($text);
}

function calColor(string $value)
{

    $green = "\e[32;1m";
    $red = "\e[31;1m";
    $blue = "\e[34;1m";
    $yellow = "\e[33;1m";
    $white = "\e[0m";

    if (is_null($value)) return $white;
    switch (strtoupper($value)) {
        case 'WARNING':
        case E_USER_WARNING:
            return $yellow;
        case 'DEBUG':
        case E_USER_DEPRECATED:
            return $green;
        case 'INFO':
        case 'NOTICE':
        case E_USER_NOTICE:
            return $blue;
        case 'ERROR':
        case 'FATAL':
        case 'Fatal error':
        case 'Parse error':
            return $red;
        default:
            return $white;
    }
}

/**
 * Displays a thread
 * @var Array $thread
 */
function displayThread(array $thread)
{
    $green = "\e[32;1m";
    $red = "\e[31;1m";
    $blue = "\e[34;1m";
    $yellow = "\e[33;1m";
    $white = "\e[0m";
    $bold = "\e[00;1m";
    $text = "";

    $text .= "$green ${thread['time']} $white";
    if ($thread['mtime']) {
        $text .= "$bold {$thread['mtime']} $white";
    }
    if (is_string($thread['level'])) {
        $text .=  calColor($thread['level']) . "[${thread['level']}]$white";
    }
    $text .= "$bold${$thread['mode']} $white";
    $text .= "$bold ${thread['function']} ";
    $text .= "@ ${thread['file']} : ";
    $text .= "line ${bold} ${thread['line']}  | $white";
    $text .= "thread_id $red ${thread['thread_id']} $white";
    // $text .= "class : " .  $bold . $thread['class'] . " \n" . $white;
    if (is_string($thread['message'])) {
        $text .= "$bold \nmessage: $white ${thread['message']} ";
        // $text .= "\n body $messageBody  \n $white";
    }
    if (isset($thread['stack'])) {
        for ($i = 0; $i < count($thread['stack']); $i++) {
            $stack = $thread['stack'][count($thread['stack']) - $i - 1];
            $text .= $i == count($thread['stack']) - 1 ? "\n └ " : "\n ├ ";
            $text .= "${stack['function']} @ ${stack['file']} ${stack['line']} $white";
        }
    }
    return ($text);
}

/**
 * Filters a thread arguments are given in params
 * @return Array $thread | null
 */
function filterThreadByParams(array $thread, array $params)
{
    if (isset($params['mode']) && $params['mode'] !== '' && $thread['mode'] == strtoupper($params['mode'])) {
        return $thread;
    }
    if (isset($params['level']) && isset($params['level']) != '' && $thread['level'] == strtoupper($params['level'])) {
        return $thread;
    };
    if (isset($params['thread_id']) && $params['thread_id'] != '' && $thread['thread_id'] == $params['thread_id']) {
        return $thread;
    }
    if (isset($params['mtime']) && $params['mtime'] != '' && $thread['mtime'] == $params['mtime']) {
        return $thread;
    }
    if (isset($params['time']) && $params['time'] != '' && $thread['time'] == $params['time']) {
        return $thread;
    }
    if (!isset($params['time']) && !isset($params['mtime']) && !isset($params['thread_id']) && !isset($params['level']) && !isset($params['mode'])) {
        return $thread;
    }
}

if (file_exists('/var/www/html/log/eq_error.log')) {
    // read raw data from pointer log file
    $fp = fopen("/var/www/html/log/eq_error.log", "r");
    echo "START LOG\n $white";
    $cpt = 0;
    if ($fp) {
        while ((($data = stream_get_line($fp, 65535, PHP_EOL)) !== false) && ((isset($params["limit"]) && $cpt <= $params["limit"]) || !isset($params["limit"]))) {

            $thread = json_decode($data, true);
            if (!is_null($thread)) {
                $filteredThread = filterThreadByParams($thread, $params);
                if (!is_null($filteredThread)) {
                    print(displayThread($thread));
                    echo ("\n------------------------------------------------------------------------\n");
                };
            }
            $cpt++;
        }
        fclose($fp);
    }
    echo "\nEND LOG $white \n";
};

// $context->httpResponse()
//     ->body($text)
//     ->send();