<?php
namespace Understory;

use Timber;

/**
 * Understory View
 *
 * Extend your View with this class to gain Understory View functionality.
 *
 * On subclasses, define a `configure` function that will register.
 *
 * Inside the `configure` method, use the `has` method to set any Registerables
 * or MetaDataBindings like posts or pages. It will also set the values as
 * context variables to be available in the Twig template. If no MetaDataBinding
 * is specifeid the current Post will be assumed.
 *
 * Use the `setContext` method to bypass any registration and simple set a
 * context variable.
 *
 * The twig template file name name is assumed to be identical to the View's
 * file name. Use the `setTemplate` method to change it.
 *
 */
abstract class View implements DelegatesMetaDataBinding, Registerable, Registry, Composition
{
    /**
     * Path of the template. Leave null to auto generate based on namespace and
     * class name.
     *
     * @var string
     */
    private $template = '';

    /**
     * Object that contains the meta values, usually the Post
     *
     * @var MetaDataBinding
     */
    private $metaDataBinding;

    /**
     * @var array
     */
    private $registry = [];

    /**
     * @var array
     */
    private $context = [];

    /**
     * @var array
     */
    private $contextRegistry = [];
    private $site;

    public function __construct()
    {

    }

    public function setSite(\Understory\Site $site)
    {
        $this->site = $site;
    }

    /**
     * @return \Understory\Site
     */
    public function getSite()
    {
        return $this->site;
    }

    /**
     * Override to configure the view
     */
    protected function configure()
    {
    }

    protected function configureContext($context)
    {
        return $context;
    }

    /**
     * @param string $key
     * @param Registerable $registerable
     */
    public function addToRegistry($key, Registerable $registerable)
    {
        $this->registry[$key] = $registerable;
    }

    /**
     * Implements Registry Interface method to register the items in the registery.
     */
    public function registerItemsInRegistry()
    {
        foreach ($this->registry as $registerable) {
            $registerable->register();
        }
    }

    /**
     * Run when the view is registered. Configures the View and registers
     * any items added to the View's registry.
     */
    public function register()
    {
        $this->configure();
        $this->registerItemsInRegistry();
    }

    /**
     * Implements `Composition::has`
     * Registers any Registerables
     * Sets as MetaDataBinding to delegate to, if $values is a MetaDataBinding
     * Adds to the Timber context
     * Adds as a class property
     *
     * @param string $property
     * @param mixed $value
     */
    public function has($property, $value)
    {
        if ($value instanceof Registerable) {
            $this->addToRegistry($property, $value);
        }

        if ($value instanceof DelegatesMetaDataBinding) {
            $value->setMetaDataBinding($this);
        }

        $this->setContext($property, $value);

        if (!is_callable($value)) {
            $this->$property = $value;
        }
    }

    /**
     * If the delegated MetaDataBinding hasn't been set yet,
     * assume the current post, as a PostType object.
     * Use to bind Registery items.
     */
    protected function initializeBindings()
    {
        if (!$this->getMetaDataBinding()) {
            $siteClass = get_class($this->site);
            if ($binding = $siteClass::getPost()) {
                $this->setMetaDataBinding($binding);
            }

        }
        $this->bindRegistryItems();
    }

    /**
     * Set binding on any Registry items that delegate to a MetaDataBinding.
     */
    protected function bindRegistryItems()
    {
        foreach ($this->registry as $registerable) {
            if ($registerable instanceof DelegatesMetaDataBinding) {
                $registerable->setMetaDataBinding($this->getMetaDataBinding());
            }
        }
    }

    /**
     * Return the file name based on the camel-cased class name.
     *
     * @return string
     */
    public function getFileName()
    {
        $calledClass = preg_replace('/.*Views/i', '', get_called_class());

        return str_replace('\\', DIRECTORY_SEPARATOR, $calledClass);
    }

    /**
     * Return the file name based on the class name that is dashed lower case.
     * This file name format is used by WordPress.
     *
     * @return string
     */
    public function getFileNameDashedCase()
    {
        $calledClass = preg_replace('/.*Views/i', '', get_called_class());

        $calledClass = strtolower(
            str_replace(
                '_',
                '',
                preg_replace('/(?<=\\w)(?=[A-Z])/', '-$1', $calledClass)
            )
        );

        return str_replace('\\', DIRECTORY_SEPARATOR, $calledClass);
    }

    /**
     * Generate a Template file path from the namespace (after \Views) and
     * class name of the view. Be sure to keep Views in the \Views namespace.
     *
     * @return string template file path
     */
    public function generateTemplateFileName()
    {
        $template = $this->getFileName() . '.twig';
        $templatePath = preg_replace('/Views/i', 'templates', TEMPLATEPATH);

        if (!file_exists($templatePath . $template)) {
            $templateWithDashes = $this->getFileNameDashedCase() . '.twig';
            if (file_exists($templatePath . $templateWithDashes)) {
                return $templateWithDashes;
            }
        }

        return $template;
    }

    /**
     * Return the template file. It will first check to see if the $template
     * variable is defined, otherwise a template file path will be generated
     * from the namespace (after \Views) and class name of the view. Be sure
     * to keep Views in the \Views namespace.
     *
     * @return string template file path
     */
    public function getTemplate()
    {
        if (!$this->template) {
            $this->setTemplate($this->generateTemplateFileName());
        }

        return $this->template;
    }

    /**
     * Manually set the path of the template from the base 'templates' directory.
     * Set to empty string or don't set to autogenerate the template path
     * from the namespace and class name.
     *
     * @param string $template template path
     */
    public function setTemplate($template)
    {
        $this->template = $template;
    }

    /**
     * Return the Timber context.
     *
     * @return array
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * Set a variable to be available in the Twig template.
     *
     * @param string $key
     * @param mixed $value
     */
    public function setContext($key, $value)
    {
        $this->contextRegistry[$key] = $value;
    }

    /**
     * Return the Timber context with the View's context values added.
     *
     * @return array
     */
    private function initializeContext()
    {
        $this->context = Timber\Timber::get_context();

        $this->context['page'] = $this;
        $this->context['post'] = $this->getMetaDataBinding();

        $this->context = $this->configureContext($this->context);

        foreach ($this->contextRegistry as $key => $value) {

            if (is_callable($value)) {
                $value = call_user_func($value);
            }
            $this->context[$key] = $value;
        }

        return $this->context;
    }

    /**
     * Render the View after initialzing the bindings and context
     */
    public function render()
    {
        $this->initializeBindings();
        Timber\Timber::render($this->getTemplate(), $this->initializeContext());
    }

    /**
     * Implentation of MetaDataBinding::getMetaValue
     *
     * @param  string $key Key for the meta field
     * @return string Value of the meta field
     */
    public function getMetaValue($key)
    {
        return $this->getMetaDataBinding()->getMetaValue($key);
    }

    /**
     * Implentation of MetaDataBinding::>setMetaValue
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
        return $this->getTemplate();
    }

    /**
     * @return MetaDataBinding
     */
    public function getMetaDataBinding()
    {
        return $this->metaDataBinding;
    }

    /**
     * @param MetaDataBinding $binding
     */
    public function setMetaDataBinding(MetaDataBinding $binding)
    {
        $this->metaDataBinding = $binding;
    }

    /**
     * If a method doesn't exist on the View, delegate to the MataDataBinding
     *
     * @param string $methodName
     * @param array $args
     * @return mixed
     */
    public function __call($methodName, $args)
    {
        if (method_exists($this->getMetaDataBinding(), $methodName)) {
            return call_user_func_array([$this->getMetaDataBinding(), $methodName], $args);
        }

        // Return property
        return $this->$methodName;
    }

    /**
     * If a property or method doesn't exist on the View, delegate to the
     * MetaDataBinding
     *
     * @param  string $property
     * @return mixed
     */
    public function __get($property)
    {
        return $this->getMetaDataBinding()->$property;
    }
}
