<?php

namespace Understory;

abstract class CustomTaxonomy implements DelegatesMetaDataBinding, Registerable, Registry, Composition
{
    private $taxonomy;
    private $registry = [];

    public $core;

    function __construct($tid = null)
    {
        if ($tid) {
            $this->setId($tid);
        }
    }

    public function getTaxonomy()
    {
        return $this->getMetaDataBinding();
    }

    public function setId($tid)
    {
        $this->getTaxonomy()->setId();
    }

    public function getName()
    {
        return $this->getTaxonomy()->getName();
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
        $taxonmyName = strtolower(preg_replace('/(?<=\\w)(?=[A-Z])/', "-$1", $matches[1]));
        $taxonomy->setName($taxonmyName);
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

        $this->$property = $value;
    }

    public function register()
    {
        $this->getMetaDataBinding()->register();
        $this->registerItemsInRegistry();
    }

    /**
     * Implentation of HasMetaData->getMetaValue
     *
     * @param  string $metaFieldKey Key for the meta field
     * @return string                Value of the meta field
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
}
