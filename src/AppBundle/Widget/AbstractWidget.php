<?php

namespace AppBundle\Widget;

use AppBundle\Exceptions\FormValidationException;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\EventDispatcher\Event;

abstract class AbstractWidget implements \AppBundle\Interfaces\WidgetInterface
{
    use \Symfony\Component\DependencyInjection\ContainerAwareTrait;

    private $formFactory;

    /**
     * @var FormBuilder
     */
    private $formBuilder;

    private $properties = [];

    private $template = null;

    private $inheritTemplatePath = null;

    private $inheritTemplate = null;

    private $id;

    private $fullId;

    private $parent = null;

    private $rendered = [];

    private $children = [];

    private $widgetManager;

    private $type;

    public function __construct(FormFactory $formFactory, \AppBundle\Manager\WidgetManager $manager)
    {
        $this->views = [];
        $this->formFactory = $formFactory;
        $this->setId($this->generateId());
        $this->widgetManager = $manager;
    }

    public function setType($type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getProperties(): array
    {
        return $this->properties;
    }

    public function property(string $name, $default = null)
    {
        return array_get($this->properties, $name, $default);
    }

    public function setId($id): self
    {
        $this->id = $id;
        if ($this->parent !== null) {
            $this->fullId = $this->parent()->getFullId() . '_' . $id;
        } else {
            $this->fullId = $id;
        }

        return $this;
    }

    public function getFullId()
    {
        return $this->fullId;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getPath()
    {
        $path = $this->getId();
        if ($this->hasParent()) {
            $path = $this->parent()->getPath() . '[' . $path . ']';
        }

        return $path;
    }

    public function parent(): ?AbstractWidget
    {
        return $this->parent;
    }

    public function setParent(AbstractWidget $parent): self
    {
        $this->parent = $parent;
        $this->setId($this->getId());

        return $this;
    }

    public function setProperties(array $properties): self
    {
        $this->properties = $properties;

        return $this;
    }

    public function validateProperties(array $properties)
    {
        $form = $this->formBuilder->getForm();

        $form->submit($properties);
        if (!$form->isValid()) {
            throw new FormValidationException($form);
        }
    }

    public function onRun()
    {
    }

    public function render($options = [])
    {
        return $this->renderBlock('container', $options);
    }

    public function renderWidget($options = [])
    {
        return $this->renderBlock('widget', $options);
    }

    public function renderBlock($blockSuffix, $options = [])
    {
        if (in_array($blockSuffix, $this->rendered)) {
            return;
        }
        $this->onRender($blockSuffix, $options);
        $options = array_merge($options, $this->views);
        $options['widget'] = $this;
        $options['widget_name'] = $this->getBlockName();
        $options['block_id'] = $this->getFullId() . '_' . $blockSuffix;
        $options['id'] = $this->getFullId();
        $options['widget_path'] = $this->getPath();
        $this->rendered[] = $blockSuffix;

        if ($this->hasInheritTemplatePath()
            && $this->getInheritTemplate()->hasBlock($this->getId() . '_' . $blockSuffix, ['widget' => $this])
        ) {
            return $this->getInheritTemplate()->renderBlock($this->getId() . '_' . $blockSuffix, $options);
        }

        if ($this->hasInheritTemplatePath()
            && $this->getInheritTemplate()->hasBlock($this->getBlockName() . '_' . $blockSuffix, ['widget' => $this])
        ) {
            return $this->getInheritTemplate()->renderBlock($this->getBlockName() . '_' . $blockSuffix, $options);
        }

        if ($this->hasBlockFromParentInheritTemplate($blockSuffix)) {
            return $this->parent()->getInheritTemplate()->renderBlock($this->getBlockName() . '_' . $blockSuffix, $options);
        }

        if ($this->hasBlockFromParentTemplate($blockSuffix)) {
            return $this->parent()->getTemplate()->renderBlock($this->getBlockName() . '_' . $blockSuffix, $options);
        }

        if ($this->getTemplate()->hasBlock($this->getBlockName() . '_' . $blockSuffix, ['widget' => $this])) {
            return $this->getTemplate()->renderBlock($this->getBlockName() . '_' . $blockSuffix, $options);
        }

        if ($this->getTemplate()->hasBlock('widget_' . $blockSuffix, $options)) {
            return $this->getTemplate()->renderBlock('widget_' . $blockSuffix, $options);
        }

        return $this->parent()->getTemplate()->renderBlock('widget_' . $blockSuffix, $options);
    }

    public function setInheritTemplatePath(string $templatePath)
    {
        $this->inheritTemplatePath = $templatePath;
    }

    public function hasBlockFromParentInheritTemplate($blockSuffix): bool
    {
        if (!$this->hasParent()) {
            return false;
        }

        if (!$this->parent()->hasInheritTemplatePath()) {
            return false;
        }

        return $this->parent()->getInheritTemplate()->hasBlock($this->getBlockName() . '_' . $blockSuffix, ['widget' => $this]);
    }

    public function hasBlockFromParentTemplate($blockSuffix): bool
    {
        if (!$this->hasParent()) {
            return false;
        }

        return $this->parent()->getTemplate()->hasBlock($this->getBlockName() . '_' . $blockSuffix, ['widget' => $this]);
    }

    public function hasParent(): bool
    {
        return $this->parent !== null;
    }

    public function getTemplate(): \Twig_Template
    {
        if ($this->template === null) {
            $this->template = $this->getTwig()->loadTemplate($this->getView());
        }

        return $this->template;
    }

    public function getInheritTemplate(): \Twig_Template
    {
        if ($this->inheritTemplate === null) {
            $this->inheritTemplate = $this->getTwig()->loadTemplate($this->inheritTemplatePath);
        }

        return $this->inheritTemplate;
    }

    public function getInteritTemplateRealValue()
    {
        return $this->inheritTemplate;
    }

    public function hasInheritTemplatePath()
    {
        return $this->inheritTemplatePath !== null;
    }

    abstract public static function defineDetails(): array;

    public function init()
    {
        $this->initForm();
        $this->onInit();
    }

    public function run()
    {
        $widgets = $this->property('children', []);
        if ($widgets !== null) {
            $widgetIds = array_keys($widgets);
        } else {
            $widgetIds = [];
        }

        usort($widgetIds, function ($firstId, $secondId) {
            $firstIdPart = explode('_', $firstId);
            $secondIdPart = explode('_', $secondId);

            return $firstIdPart[count($firstIdPart) - 1] <=> $secondIdPart[count($secondIdPart) - 1];
        });

        foreach ($widgetIds as $id) {
            $info = $widgets[$id];
            $widget = $this->widgetManager->getWidget($info['type'], $info['properties'], $info['definition']);
            $widget->setParent($this);
            $widget->setId($id);

            $this->children[$id] = $widget;
        }
    }

    public function children(): array
    {
        return $this->children;
    }

    public function getChild($id): ?AbstractWidget
    {
        if ($this->hasChild($id)) {
            return array_get($this->children, $id);
        }

        return null;
    }

    public function hasChild($id): bool
    {
        return array_has($this->children, $id);
    }

    public function findChildren($path): ?AbstractWidget
    {
        $this->run();
        if ($path === $this->getId()) {
            return $this;
        }

        $path = str_replace(']', '', $path);
        $paths = explode('[', $path);

        if ($paths[0] === $this->getId()) {
            array_splice($paths, 0, 1);
        }

        if (count($paths) === 1 && $this->hasChild($paths[0])) {
            return $this->getChild($paths[0]);
        }

        if ($this->hasChild($paths[0])) {
            $childPath = $paths[0];
            array_splice($paths, 0, 1);

            return $this->getChild($childPath)->findChildren($childPath . '[' . implode('][', $paths) . ']');
        }

        return null;
    }

    public function buildForm(FormBuilder $formBuilder)
    {
        foreach ($this->defineProperties($this->container) as $name => $definition) {
            $type = $definition['type'] ?? Type\TextType::class;
            $formBuilder->add($name, $type, $definition['options'] ?? []);
        }
    }

    protected function onInit()
    {
    }

    protected function onRender(string $blockSuffix, array &$options): void
    {
    }

    protected function getTwig(): \Twig_Environment
    {
        return $this->container->get('twig');
    }

    public static function defineProperties($container): array
    {
        return [];
    }

    private function initForm()
    {
        $this->formBuilder = $this->formFactory->createBuilder(Type\FormType::class, null, [
            'csrf_protection' => false,
            'allow_extra_fields' => true,
        ]);
        $this->buildForm($this->formBuilder);
    }

    protected function getView()
    {
        return 'AppBundle:Widget:appwidget.html.twig';
    }

    protected function getAdditionalRenderOptions(): array
    {
        return [];
    }

    abstract protected function getBlockName(): string;

    private function generateId()
    {
        return $this->getBlockName();
    }

    protected function dispatchEvent(string $eventName, Event $event): void
    {
        $dispatcher = $this->container->get('event_dispatcher');
        $dispatcher->dispatch($eventName, $event);
    }
}
