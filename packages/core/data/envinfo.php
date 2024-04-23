<?php
/*
    This file is part of the eQual framework <http://www.github.com/equalframework/equal>
    Some Rights Reserved, Cedric Francoys, 2010-2021
    Licensed under GNU LGPL 3 license <http://www.gnu.org/licenses/>
*/

use core\setting\SettingValue;

list( $params, $providers ) = eQual::announce( [
	'description' => 'Retrieve public configuration intended for front-end.',
	'access'      => [
		'visibility' => 'public'
	],
	'response'    => [
		'content-type'  => 'application/json',
		'charset'       => 'UTF-8',
		'accept-origin' => '*'
	],
	'constants'   => [
		"ENV_MODE",
		"DEFAULT_LANG",
		"L10N_LOCALE",
		"ORG_NAME",
		"ORG_URL",
		"APP_NAME",
		"APP_LOGO_URL",
        "BACKEND_URL",
        "REST_API_URL"
	],
    'providers'     => ['context', 'auth']
] );

list($context, $auth) = [$providers['context'], $providers['auth']];

// retrieve current User identifier (through HTTP headers lookup by Authentication Manager)
$user_id = $auth->userId();

$envinfo = [
	"production"    => constant('ENV_MODE'),
	"parent_domain" => parse_url(constant('BACKEND_URL'), PHP_URL_HOST),
	"backend_url"   => constant('BACKEND_URL'),
	"rest_api_url"  => constant('REST_API_URL'),
	"lang"          => constant('DEFAULT_LANG'),
	"locale"        => constant('L10N_LOCALE'),
	"version"       => constant('QN_VERSION'),
	"company_name"  => constant('ORG_NAME'),
	"company_url"   => constant('ORG_URL'),
	"app_name"      => constant('APP_NAME'),
	"app_logo_url"  => constant('APP_LOGO_URL')
];

// append settings values if request is made by an authenticated user
if($user_id) {
    // 1) read global settings
    $settings = SettingValue::search(['user_id', '=', 0])->read(['name', 'value'])->get();

    foreach($settings as $sid => $setting) {
        $envinfo[$setting['name']] = $setting['value'];
    }

    // 2) overload with current User specific settings, if any
    $settings = SettingValue::search(['user_id', '=', $user_id])->read(['name', 'value'])->get();

    foreach($settings as $sid => $setting) {
        $envinfo[$setting['name']] = $setting['value'];
    }
}

$context->httpResponse()
        ->body($envinfo)
        ->send();
