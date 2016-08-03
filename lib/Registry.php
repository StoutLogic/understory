<?php

namespace Understory;

/**
 * Collects Registerable items for the purpose of registering them at a certain time.
 */
interface Registry
{
    /**
     * Add a Registerable item to the Registry
     * @param string $key
     * @param Registerable $value
     */
    public function addToRegistry($key, Registerable $value);
}
