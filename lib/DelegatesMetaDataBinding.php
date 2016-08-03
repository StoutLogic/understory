<?php

namespace Understory;

/**
 * Acts upon another when MetaDataBinding object when MetaDataBinding methods
 * ard called.
 */
interface DelegatesMetaDataBinding extends MetaDataBinding
{
    public function getMetaDataBinding();
    public function setMetaDataBinding(MetaDataBinding $binding);
}
