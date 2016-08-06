<?php

namespace Understory;

/**
 * Has a specified position in relation to other sequential items
 */
interface Sequential
{
    /**
     * Set the position of the item in a sequence.
     * @param int $position
     * @param MetaDataBinding $metaDataBinding
     */
    public function setSequentialPosition($position, MetaDataBinding $metaDataBinding);
}
