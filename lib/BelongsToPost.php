<?php

namespace Understory;

/**
 * Interface BelongsToPost
 * Describes a belong to relationship with respect to a post. This gives a class
 * the opportunity to receive the owener CustomPostType and return a customized
 * closure which can be called to further describe the relationship, that can
 * be called by the CustomPostType at a later time, on demand.
 * @package Understory
 */
interface BelongsToPost
{
    /**
     * @param CustomPostType $post
     * @return \Closure
     */
    public function belongsToPost(CustomPostType $post);
}