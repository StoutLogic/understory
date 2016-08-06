<?php

namespace Understory;

use Timber;

abstract class CustomTaxonomy implements DelegatesMetaDataBinding, Registerable, Registry, Composition, Sequential
{
    private $taxonomy;
    private $registry = [];

    public $core;

    function __construct($tid = null, $tax = '')
    {
        if ($tid) {
            $this->setId($tid);
        }
    }

    public function getTaxonomy()
    {
        return $this->getMetaDataBinding()->getTaxonomy();
    }

    public function setId($tid)
    {
        $this->getMetaDataBinding()->setId($tid);
    }

    public function autobind()
    {
        $this->getMetaDataBinding()->autobind();
        return $this;
    }

    public function getName()
    {
        return $this->getMetaDataBinding()->getName();
    }

    protected function configure(Taxonomy $taxonomy) {
        return $taxonomy;
    }

    public function addToRegistry($key, Registerable $registerable)
    {
        $this->registry[$key] = $registerable;
    }

    public function registerItemsInRegistry()
    {
        foreach($this->registry as $registerable) {
            $registerable->register();
        }
    }

    private function generateTaxonomy()
    {
        $taxonomy = new Taxonomy();
        $className = get_called_class();
        preg_match('@\\\\([\w]+)$@', $className, $matches);
        $taxonomyName = strtolower(preg_replace('/(?<=\\w)(?=[A-Z])/', "-$1", $matches[1]));
        $taxonomy->setTaxonomy($taxonomyName);
        return $this->configure($taxonomy);
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
        ){
            $this->$property = $value;
        }
    }

    /**
     * Sets the Taxonomy Meta Box Order.
     * @param $position
     * @param MetaDataBinding $metaDataBinding
     */
    public function setSequentialPosition($position, MetaDataBinding $metaDataBinding)
    {
        add_action('do_meta_boxes', function($post_type) use ($position, $metaDataBinding){
            global $wp_meta_boxes;

            if ($metaDataBinding->getBindingName() !== $post_type) {
                return;
            }

            foreach($wp_meta_boxes[$post_type]['side']['core'] as $key => $metaBox) {
                // Don't replace our recently positioned taxonomy
                if (preg_match('/^understory-taxonomy.+/', $key)) {
                    continue;
                }

                if (
                    is_array($metaBox)
                    && array_key_exists('args', $metaBox)
                    && is_array($metaBox['args'])
                    && array_key_exists('taxonomy', $metaBox['args'])
                    && $metaBox['args']['taxonomy'] === $this->getBindingName()
                ) {
                    $wp_meta_boxes[$post_type]['side']['core']['understory-taxonomy'.$position] = $metaBox;
                    unset($wp_meta_boxes[$post_type]['side']['core'][$key]);
                }
            }
        }, 10000, 1);
    }


    public function register()
    {
        $this->getMetaDataBinding()->register();
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
        return $this->getMetaDataBinding()->getBindingName();
    }

    /**
     * @return Taxonomy
     */
    public function getMetaDataBinding()
    {
        if(!isset($this->taxonomy)) {
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

    public function findAll($args = [])
    {
        $args = array_merge([
            'posts_per_page' => '-1',
            'taxonomy' => $this->getBindingName()
        ], $args);

        $called_class = get_called_class();
        return Timber\TermGetter::get_terms($args, $called_class);
    }

    public function findAllTopLevel($args = [])
    {
        $args = array_merge([
            'parent' => '0',
        ], $args);

        return $this->findAll($args);
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
