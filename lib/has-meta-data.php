<?php

namespace Understory;

interface HasMetaData
{
    /**
     * Return the meta value
     *
     * @param  string $metaFieldKey Key for the meta field
     * @return string                Value of the meta field
     */
    public function getMetaValue($metaFieldKey);

    /**
     * Set the meta value
     *
     * @param  string $metaFieldKey Key for the meta field
     * @param  string $metaFieldValue Value for the meta field
     */
    public function setMetaValue($metaFieldKey, $metaFieldValue);
}
