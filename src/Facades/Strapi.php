<?php

namespace Svnwa\LaravelStrapi\Facades;

use Illuminate\Support\Facades\Facade;

class Strapi extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'strapi';
    }
}
