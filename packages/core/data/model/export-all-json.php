<?php
/*
    This file is part of the eQual framework <http://www.github.com/cedricfrancoys/equal>
    Some Rights Reserved, Cedric Francoys, 2010-2021
    Licensed under GNU LGPL 3 license <http://www.gnu.org/licenses/>
*/

list($params, $providers) = announce([
    'description'   => "Returns a view populated with a collection of objects, and outputs it as an XLS spreadsheet.",
    'params'        => [
        'entity' =>  [
            'description'   => 'Full name (including namespace) of the class to use (e.g. \'core\\User\').',
            'type'          => 'string', 
            'required'      => true
        ],
        'lang' =>  [
            'description'   => 'Language in which labels and multilang field have to be returned (2 letters ISO 639-1).',
            'type'          => 'string', 
            'default'       => DEFAULT_LANG
        ]        
    ],
    'response'      => [
        'accept-origin' => '*'        
    ],
    'providers'     => ['context', 'orm', 'auth'] 
]);


list($context, $orm, $auth) = [$providers['context'], $providers['orm'], $providers['auth']];

$entity = $params['entity'];

$model = $orm->getModel($entity);
$schema = $model->getSchema();


$fields = [];

foreach($schema as $field => $descr) {
    if($descr['type'] != 'one2many' && $descr['type'] != 'computed') {
        $fields[] = $field;
    }
}


$output = [];

$serie = [ 
    "name" => $entity,
    "lang" => $params['lang'],
    "data" => []
];


$values = $params['entity']::search([])
        ->read($fields)
        ->adapt('txt')
        ->get();

foreach($values as $oid => $odata) {
    $serie["data"][] = $odata;
}

$output[] = $serie;

$context->httpResponse()
        ->body($output)
        ->send();