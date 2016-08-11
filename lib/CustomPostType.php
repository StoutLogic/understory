<?php

namespace Understory;

use Timber;

abstract class CustomPostType implements DelegatesMetaDataBinding, Registerable, Registry, Composition
{
    private $post;
    private $registry = [];
    private $config;
    private $closureProp = [];

    /**
     * If we are supplied a post then we try to instantiate as the correct
     * custom post type, otherwise call the normal constructor
     *
     * @param \Timber\Post|int $post \Timber\Post or post id to initialize at \Timber\Post
     */
    public function __construct($post = null)
    {
        if ($post) {
            $this->setPost($post);
        }
    }

    /**
     * Set and Init Post
     * If nothing is passed, WordPress's current post will try to be determined
     * @param mixed $post Post, Post ID or null
     */
    public function setPost($post = null)
    {
        $this->setMetaDataBinding(new Post($post));
        $this->getConfig();
    }

    public function autobind()
    {
        $this->setPost();
        return $this;
    }

    public function getPostType()
    {
        return $this->getConfig()->getPostTypeName();
    }

    protected function configure(PostTypeBuilder $postTypeBuilder)
    {
        return $postTypeBuilder;
    }

    public function getConfig()
    {
        if (!$this->config) {
            $this->config = $this->generateBuilder();
        }

        return $this->config;
    }

    private function generateBuilder()
    {
        preg_match('@(\\\\)?([\w]+)$@', get_called_class(), $matches);
        $cptName = strtolower(
            preg_replace('/(?<=\\w)(?=[A-Z])/', "-$1", $matches[2])
        );
        return $this->configure(new PostTypeBuilder($cptName));
    }

    public function addToRegistry($key, Registerable $registerable)
    {
        $this->registry[$key] = $registerable;
    }

    public function registerItemsInRegistry()
    {
        foreach ($this->registry as $registerable) {
            if ($registerable instanceof CustomTaxonomy) {
                $this->registerTaxonomy($registerable);
                continue;
            }
            $registerable->register();
        }
    }

    public function has($property, $value, $many = false)
    {
        if ($value instanceof Sequential) {
            $value->setSequentialPosition($this->getNextInSequence(), $this);
        }

        if ($value instanceof Registerable) {
            $this->addToRegistry($property, $value);
        }

        if ($value instanceof DelegatesMetaDataBinding) {
            $value->setMetaDataBinding($this);
        }

        if ($value instanceof BelongsToPost) {
            $this->setClosureProperty($property, $value->belongsToPost($this, $many));
        }

        $this->setProperty($property, $value);
    }

    public function hasMany($property, $value)
    {
        $this->has($property, $value, $many = true);
    }

    public function hasOne($property, $value)
    {
        $this->has($property, $value);
    }

    /**
     * @param string $property
     * @param mixed $value
     */
    private function setProperty($property, $value)
    {
        $reflection = new \ReflectionObject($this);
        if (
            !isset($this->closureProp[$property])
            && (
                !$reflection->hasProperty($property)
                || !$reflection->getProperty($property)->isPrivate()
            )
        ){
            $this->$property = $value;
        }
    }

    private function setClosureProperty($property, $value)
    {
        if ($value instanceof \Closure) {
            $this->closureProp[$property] = $value;
        }
    }

    private $sequence = 1;
    private function getNextInSequence()
    {
        return $this->sequence++;
    }

    public function register()
    {
        if (!$this->modifyRegisteredPostType()) {
            register_post_type(
                $this->getPostType(),
                $this->getConfig()->build()
            );
        }

        $this->registerRevisionLimit();
        $this->registerItemsInRegistry();
    }

    private function modifyRegisteredPostType()
    {
        global $wp_post_types;

        if (isset($wp_post_types[$this->getPostType()])) {
            // Convert associative arrays to a stdobject 1 level deep
            $config = json_decode(json_encode($this->getConfig()->build()), false);
            $wp_post_types[$this->getPostType()] = $config;
            return true;
        }

        return false;
    }

    private function registerRevisionLimit()
    {
        add_filter('wp_revisions_to_keep', function($num, $post) {
            if ($post->post_type === $this->getPostType()) {
                return $this->getConfig()->getRevisionLimit();
            }
            return $num;
        }, 10, 2 );
    }

    /**
     * Registers a Taxonomy for this PostType with WordPress
     * @param CustomTaxonomy $taxonomy
     * @return bool
     */
    public function registerTaxonomy(CustomTaxonomy $taxonomy)
    {
        return register_taxonomy_for_object_type(
            $taxonomy->getTaxonomy(),
            $this->getPostType()
        );
    }

    /**
     * Implentation of HasMetaData->getMetaValue
     *
     * @param  string $key Key for the meta field
     * @return string Value of the meta field
     */
    public function getMetaValue($key)
    {
        return $this->getMetaDataBinding()->getMetaValue($key);
    }

    /**
     * Implentation of HasMetaData->setMetaValue
     *
     * @param  string $key Key for the meta field
     * @param  string $value Value for the meta field
     */
    public function setMetaValue($key, $value)
    {
        $this->getMetaDataBinding()->setMetaValue($key, $value);
    }

    public function getBindingName()
    {
        return $this->getPostType();
    }

    /**
     * @return Post [description]
     */
    public function getMetaDataBinding()
    {
        if (!isset($this->post)) {
            $this->autobind();
        }

        return $this->post;
    }

    public function setMetaDataBinding(MetaDataBinding $binding)
    {
        $this->post = $binding;
    }

    public function findAll($args = [])
    {
        $args = array_merge([
            'posts_per_page' => '-1',
            'post_type' => $this->getPostType()
        ], $args);

        return Timber\PostGetter::get_posts($args, get_called_class());
    }

    public function __isset($property)
    {
        if (isset($this->closureProp[$property])) {
            return true;
        }
        return isset($this->getMetaDataBinding()->$property);
    }

    public function __get($property)
    {
        if ($this->hasClosureProp($property)) {
            return $this->getClosureProp($property);
        }

        if (method_exists($this, 'get'.$property)) {
            return call_user_func_array([$this, 'get'.$property], []);
        }

        return $this->getMetaDataBinding()->$property;
    }

    public function __call($method, $args)
    {
        if ($this->hasClosureProp($method)) {
            return $this->getClosureProp($method);
        }

        return call_user_func_array(
            [
                $this->getMetaDataBinding(),
                $method,
            ],
            $args
        );
    }

    private function hasClosureProp($property)
    {
        return isset($this->closureProp[$property]);
    }

    private function getClosureProp($property)
    {
        if ($this->hasClosureProp($property)) {
            $closure = $this->closureProp[$property];
            return $closure();
        }
    }
}
