<?php

namespace Understory;

use Timber;

abstract class CustomTaxonomy implements DelegatesMetaDataBinding, Registerable, Registry, Composition, Sequential, BelongsToPost
{
    private $taxonomy;
    private $registry = [];
    private $config;

    public function __construct($tid = null)
    {
        if ($tid) {
            $this->setTerm($tid);
        }
    }

    public function setTerm($tid = null)
    {
        $this->setMetaDataBinding(new Term($tid, $this->getTaxonomy()));
        $this->getConfig();
    }

    public function autobind()
    {
        $this->setTerm();
        return $this;
    }

    public function getTaxonomy()
    {
        return $this->getConfig()->getTaxonomyName();
    }


    protected function configure(TaxonomyBuilder $taxonomyBuilder)
    {
        return $taxonomyBuilder;
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
        preg_match('@\\\\([\w]+)$@', get_called_class(), $matches);
        $taxonomyName = strtolower(
            preg_replace('/(?<=\\w)(?=[A-Z])/', "-$1", $matches[1])
        );
        return $this->configure(new TaxonomyBuilder($taxonomyName));
    }

    public function addToRegistry($key, Registerable $registerable)
    {
        $this->registry[$key] = $registerable;
    }

    public function registerItemsInRegistry()
    {
        foreach ($this->registry as $registerable) {
            $registerable->register();
        }
    }

    public function has($property, $value)
    {
        if ($value instanceof Registerable) {
            $this->addToRegistry($property, $value);
        }

        if ($value instanceof DelegatesMetaDataBinding) {
            $value->setMetaDataBinding($this);
        }

        $this->setProperty($property, $value);
    }

    /**
     * @param string $property
     * @param mixed $value
     */
    private function setProperty($property, $value)
    {
        $reflection = new \ReflectionObject($this);
        if (
            !$reflection->hasProperty($property)
            || !$reflection->getProperty($property)->isPrivate()
        ) {
            $this->$property = $value;
        }
    }

    /**
     * Sets the Taxonomy Meta Box Order.
     * @param $position
     * @param MetaDataBinding $context
     */
    public function setSequentialPosition($position, MetaDataBinding $context)
    {
        add_action('do_meta_boxes', function ($post_type) use ($position, $context) {
            global $wp_meta_boxes;

            if ($context->getBindingName() !== $post_type) {
                return;
            }

            foreach ($wp_meta_boxes[$post_type]['side']['core'] as $key => $metaBox) {
                // Don't replace our recently positioned taxonomy
                if (preg_match('/^understory-taxonomy.+/', $key)) {
                    continue;
                }

                if (
                    is_array($metaBox)
                    && isset($metaBox['args'])
                    && is_array($metaBox['args'])
                    && isset($metaBox['args']['taxonomy'])
                    && $metaBox['args']['taxonomy'] === $this->getBindingName()
                ) {
                    $wp_meta_boxes[$post_type]['side']['core']['understory-taxonomy' . $position] = $metaBox;
                    unset($wp_meta_boxes[$post_type]['side']['core'][$key]);

                }

            }
        }, 10000, 1);
    }


    public function register()
    {
        register_taxonomy(
            $this->getConfig()->getTaxonomyName(),
            '',
            $this->getConfig()->build()
        );

        $this->registerItemsInRegistry();
    }

    /**
     * Implantation of HasMetaData->getMetaValue
     *
     * @param  string $key Key for the meta field
     * @return string                Value of the meta field
     */
    public function getMetaValue($key)
    {
        return $this->getMetaDataBinding()->getMetaValue($key);
    }

    /**
     * Implantation of HasMetaData->setMetaValue
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
        return $this->getTaxonomy();
    }

    /**
     * @return Term
     */
    public function getMetaDataBinding()
    {
        if (!isset($this->taxonomy)) {
            $this->setMetaDataBinding($this->generateTaxonomy());
        }

        return $this->taxonomy;
    }

    public function setMetaDataBinding(MetaDataBinding $binding)
    {
        if (!$binding instanceof CustomPostType) {
            $this->taxonomy = $binding;
        }
    }

    public function belongsToPost(CustomPostType $post, $many = false)
    {
        return function () use (&$post, $many) {
            $terms = $post->terms($this->getTaxonomy(), true, get_class($this));

            if ($many) {
                return $terms;
            }

            if (count($terms) > 0) {
                return $terms[0];
            }

            return null;
        };
    }

    public function getChildren()
    {
        return array_map(function ($child) {
            return new static($child);
        }, $this->getMetaDataBinding()->children());
    }

    public function findAll($args = [])
    {
        $args = array_merge([
            'posts_per_page' => '-1',
            'taxonomy' => $this->getTaxonomy(),
        ], $args);

        return Timber\TermGetter::get_terms($args, get_called_class());
    }

    public function findAllTopLevel($args = [])
    {
        $args = array_merge([
            'parent' => '0',
        ], $args);

        return $this->findAll($args);
    }

    public function __isset($property)
    {
        return isset($this->getMetaDataBinding()->$property);
    }

    public function __get($property)
    {
        if (method_exists($this, 'get' . $property)) {
            return call_user_func_array([$this, 'get' . $property], []);
        }

        return $this->getMetaDataBinding()->$property;
    }

    public function __call($method, $args)
    {
        return call_user_func_array(
            [
                $this->getMetaDataBinding(),
                $method,
            ],
            $args
        );
    }

    public static function from($tid, $taxonomy)
    {
        return new static($tid);
    }

    public function __toString()
    {
        return $this->getTaxonomy();
    }
}
