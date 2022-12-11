<?php
/*
    This file is part of the eQual framework <http://www.github.com/cedricfrancoys/equal>
    Some Rights Reserved, Cedric Francoys, 2010-2021
    Licensed under GNU LGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace equal\orm;

use equal\orm\UsageFactory;
use equal\orm\usages\Usage;

class Field {

    /**
     * Descriptor of the field, as returned by Model::getColumns()
     * @var array
     */
    private $descriptor = [];

    /** @var Usage */
    private $usage = null;

    /**
     * Pseudo type of the Field instance.
     * @var string
     */
    private $type = null;

    /**
     * @param array $descriptor Associative array mapping field properties and their values.
     */
    public function __construct(array $descriptor) {
        // store original descriptor
        $this->descriptor = $descriptor;
        if(isset($descriptor['type'])) {
            $this->type = $descriptor['type'];
        }
    }

    /**
     * Provides the usage string equivalent of the pseudo type of the Field instance.
     * This method maps explicit usages to types (that are a form of implicit usages).
     */
    protected function getUsageString(): string {
        static $map = [
            'boolean'       => 'numeric/boolean',
            'integer'       => 'numeric/integer:9',
            'float'         => 'numeric/real:10.2',
            'string'        => 'text/plain:255',
            'text'          => 'text/plain:32000',
            'date'          => 'date/plain',
            'time'          => 'numeric/integer:9',
            'datetime'      => 'date/plain',
            'binary'        => 'binary/plain:64000000',
            'many2one'      => 'numeric/integer:9'
        ];
        return $map[$this->type];
    }

    final protected function getUsage(): Usage {
        if(is_null($this->usage)) {
            // use usage string from the descriptor if present
            if(isset($this->descriptor['usage'])) {
                $this->usage = UsageFactory::create($this->descriptor['usage']);
            }
            // otherwise, use the usage string of which the field type is an alias
            else {
                $this->usage = UsageFactory::create($this->getUsageString());
            }
        }
        return $this->usage;
    }

    /**
     * Retrieves the constraints that apply on the field, according to its usage (explicit or implicit).
     *
     * @return array
     */
    public function getConstraints(): array {
        return $this->getUsage()->getConstraints();
    }
}
