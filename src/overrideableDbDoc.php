<?php

namespace codename\core\test;

use codename\architect\dbdoc\dbdoc;

/**
 * Modified DbDoc class
 * that enables editing/setting the model adapters directly
 */
class overrideableDbDoc extends dbdoc
{
    /**
     * [setModelAdapters description]
     * @param array $modeladapters [description]
     */
    public function setModelAdapters(array $modeladapters): void
    {
        $this->adapters = $modeladapters;
    }
}
