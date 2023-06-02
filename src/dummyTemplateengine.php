<?php

namespace codename\core\test;

use codename\core\templateengine;

/**
 * Dummy template engine
 */
class dummyTemplateengine extends templateengine
{
    /**
     * @inheritDoc
     */
    public function render(string $referencePath, $data = null): string
    {
        return '';
    }

    /**
     * @inheritDoc
     */
    public function renderView(string $viewPath, $data = null): string
    {
        return '';
    }

    /**
     * @inheritDoc
     */
    public function renderTemplate(string $templatePath, $data = null): string
    {
        return '';
    }
}
