<?php

namespace Understory;

abstract class CustomPostType implements DelegatesMetaDataBinding, Registerable, Registry, Composition
{
    private $post;
    private $registry = [];

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
    public function setPost($post)
    {
        $this->getMetaDataBinding()->setPost($post);
    }

    protected function configure(PostType $postType)
    {
        return $postType;
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

    private function generatePostType()
    {
        $postType = new PostType();
        $className = get_called_class();
        preg_match('@(\\\\)?([\w]+)$@', $className, $matches);
        $cptName = strtolower(
            preg_replace('/(?<=\\w)(?=[A-Z])/', "-$1", $matches[2])
        );
        $postType->setPostType($cptName);
        $postType->getConfig();
        return $this->configure($postType);
    }

    public function getPostType()
    {
        return $this->getMetaDataBinding()->getPostType();
    }

    public function has($property, $value)
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

        $this->$property = $value;
    }

    private $sequence = 1;
    private function getNextInSequence()
    {
        return $this->sequence++;
    }


    public function register()
    {
        $this->getMetaDataBinding()->register();
        $this->registerItemsInRegistry();
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
        return $this->getMetaDataBinding()->getBindingName();
    }

    /**
     * @return PostType [description]
     */
    public function getMetaDataBinding()
    {
        if (!isset($this->post)) {
            $this->setMetaDataBinding($this->generatePostType());
        }

        return $this->post;
    }

    public function setMetaDataBinding(MetaDataBinding $binding)
    {
        $this->post = $binding;
    }

    public function __isset($propertyName)
    {
        return isset($this->getMetaDataBinding()->$propertyName);
    }

    public function __get($propertyName)
    {
        if (method_exists($this, 'get'.$propertyName)) {
            return call_user_func_array([$this, 'get'.$propertyName], []);
        } else if (property_exists($this->getMetaDataBinding(), $propertyName)) {
            return $this->getMetaDataBinding()->$propertyName;
        }
    }

    public function __call($methodName, $args = [])
    {
        return call_user_func_array(
            [
                $this->getMetaDataBinding(),
                $methodName,
            ],
            $args
        );
    }
}
