<?php
/*
    This file is part of the eQual framework <http://www.github.com/cedricfrancoys/equal>
    Some Rights Reserved, Cedric Francoys, 2010-2021
    Licensed under GNU GPL 3 license <http://www.gnu.org/licenses/>
*/
namespace demo;

class Foo {

    public static function getColumns() {
        return [
            'field_a' => [
                'type'      => 'string',
                'multilang' => true
            ],
            'field_b' => [
                'type'      => 'string'
            ]
      ];
  }
}