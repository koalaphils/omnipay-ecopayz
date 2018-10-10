<?php

namespace AppBundle\Widget;

use AppBundle\Interfaces\WidgetInterface;
use AppBundle\Manager\PageManager;
use RuntimeException;
use Symfony\Bridge\Twig\AppVariable;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Request;
use Twig_Environment;
use Twig_Template;
use function array_set;
use function camel_case;

abstract class AbstractPageWidget implements WidgetInterface
{
    const IDENTIFIER_PAGEMANAGER = 'pagemanager';
    const IDENTIFIER_CONTROLLER = 'controller';

    use ContainerAwareTrait;

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

    private $name;

    private $action;

    private $pageManager;

    private $appVariable;

    private $includedTemplates;

    public function __construct(string $action, string $name, PageManager $pageManager)
    {
        $this->views = [];
        $this->setId($action . '_' . $name);

        $this->action = $action;
        $this->name = $name;
        $this->pageManager = $pageManager;
        $this->includedTemplate = [];
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

    public function hasProperty($name): bool
    {
        return array_has($this->properties, $name);
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

    public function getAction(): string
    {
        return $this->action;
    }

    public function parent(): ?AbstractPageWidget
    {
        return $this->parent;
    }

    public function setParent(AbstractPageWidget $parent): self
    {
        $this->parent = $parent;
        $this->setId($this->getId());

        return $this;
    }

    public function setProperties(array $properties): self
    {
        $this->properties = array_merge($this->defaultProperties(), $properties);
        $this->pageManager->processConfigValues($this->properties);

        return $this;
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

    public function renderBlock($blockSuffix, $options = [], array $includeTemplates = [])
    {
        if (in_array($blockSuffix, $this->rendered)) {
            return;
        }
        $this->onRender($blockSuffix, $options);
        $options = array_merge($options, $this->views);
        $options['app'] = $this->appVariable;
        $options['widget'] = $this;
        $options['widget_name'] = $this->getBlockName();
        $options['block_id'] = $this->getFullId() . '_' . $blockSuffix;
        $options['id'] = $this->getFullId();
        $options['camelized_id'] = camel_case($this->getFullId());
        $options['widget_path'] = $this->getPath();
        $options['properties'] = $this->properties;

        if (!empty($includeTemplates)) {
            foreach ($includeTemplates as $templateIndex => $template) {
                if (
                    !$this->isBlockAlreadyRendered('include.' . $templateIndex . '.' . $this->getId() . '_' . $blockSuffix)
                    && $this->getIncludedTemplate($template)->hasBlock($this->getId() . '_' . $blockSuffix, ['widget' => $this])
                ) {
                    $this->rendered[] = 'include.' . $templateIndex . '.' . $this->getId() . '_' . $blockSuffix;

                    return $this->getIncludedTemplate($template)->renderBlock($this->getId() . '_' . $blockSuffix, $options);
                }

                if (
                    !$this->isBlockAlreadyRendered('include.' . $templateIndex . '.' . $this->getBlockName() . '_' . $blockSuffix)
                    && $this->getIncludedTemplate($template)->hasBlock($this->getBlockName() . '_' . $blockSuffix, ['widget' => $this])
                ) {
                    $this->rendered[] = 'include.' . $templateIndex . '.' . $this->getBlockName() . '_' . $blockSuffix;

                    return $this->getIncludedTemplate($template)->renderBlock($this->getBlockName() . '_' . $blockSuffix, $options);
                }
            }
        }

        if ($this->hasInheritTemplatePath()
            && !$this->isBlockAlreadyRendered('inherit.' . $this->getId() . '_' . $blockSuffix)
            && $this->getInheritTemplate()->hasBlock($this->getId() . '_' . $blockSuffix, ['widget' => $this])
        ) {
            $this->rendered[] = 'inherit.' . $this->getId() . '_' . $blockSuffix;

            return $this->getInheritTemplate()->renderBlock($this->getId() . '_' . $blockSuffix, $options);
        }

        if ($this->hasInheritTemplatePath()
            && !$this->isBlockAlreadyRendered('inherit.' . $this->getBlockName() . '_' . $blockSuffix)
            && $this->getInheritTemplate()->hasBlock($this->getBlockName() . '_' . $blockSuffix, ['widget' => $this])
        ) {
            $this->rendered[] = 'inherit.' . $this->getBlockName() . '_' . $blockSuffix;

            return $this->getInheritTemplate()->renderBlock($this->getBlockName() . '_' . $blockSuffix, $options);
        }

        if ($this->hasBlockFromParentInheritTemplate($blockSuffix)
            && !$this->isBlockAlreadyRendered('parentInherit.' . $this->getBlockName() . '_' . $blockSuffix)
        ) {
            $this->rendered[] = 'parentInherit.' . $this->getBlockName() . '_' . $blockSuffix;
            return $this->parent()->getInheritTemplate()->renderBlock($this->getBlockName() . '_' . $blockSuffix, $options);
        }

        if ($this->hasBlockFromParentTemplate($blockSuffix)
            && !$this->isBlockAlreadyRendered('parent.' . $this->getBlockName() . '_' . $blockSuffix)
        ) {
            $this->rendered[] = 'parent.' . $this->getBlockName() . '_' . $blockSuffix;

            return $this->parent()->getTemplate()->renderBlock($this->getBlockName() . '_' . $blockSuffix, $options);
        }

        if ($this->getTemplate()->hasBlock($this->getBlockName() . '_' . $blockSuffix, ['widget' => $this])
            && !$this->isBlockAlreadyRendered($this->getBlockName() . '_' . $blockSuffix)
        ) {
            $this->rendered[] = $this->getBlockName() . '_' . $blockSuffix;

            return $this->getTemplate()->renderBlock($this->getBlockName() . '_' . $blockSuffix, $options);
        }

        if ($this->getTemplate()->hasBlock('widget_' . $blockSuffix, $options)
            && !$this->isBlockAlreadyRendered('widget_' . $blockSuffix)
        ) {
            $this->rendered[] = 'widget_' . $blockSuffix;

            return $this->getTemplate()->renderBlock('widget_' . $blockSuffix, $options);
        }
        if ($this->parent() !== null
            && !$this->isBlockAlreadyRendered('parentwidget_' . $blockSuffix)
        ) {
            $this->rendered[] = 'parentwidget_' . $blockSuffix;

            return $this->parent()->getTemplate()->renderBlock('widget_' . $blockSuffix, $options);
        }

        throw new RuntimeException('No widget block found');
    }

    public function isBlockAlreadyRendered(string $block): bool
    {
        return in_array($block, $this->rendered);
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

    public function getTemplate(): Twig_Template
    {
        if ($this->template === null) {
            $this->template = $this->getTwig()->loadTemplate($this->getView());
        }

        return $this->template;
    }

    public function getInheritTemplate(): Twig_Template
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

    public function getIncludedTemplate(string $twigPath): Twig_Template
    {
        if (!isset($this->includedTemplates[$twigPath])) {
            $this->includedTemplates[$twigPath] = $this->getTwig()->loadTemplate($twigPath);
        }

        return $this->includedTemplates[$twigPath];
    }

    abstract public static function defineDetails(): array;

    public function init()
    {
        $this->initializedAppVariable();
        $this->onInit();
    }

    public function run()
    {
        $this->onRun();
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
            $widget = new $info['type']($this->action, $id, $this->pageManager);
            $widget->setContainer($this->container);
            $widget->setProperties($info['properties']);
            $widget->setParent($this);
            $widget->init();
            $widget->run();

            $this->children[$id] = $widget;
        }
    }

    public function children(): array
    {
        return $this->children;
    }

    public function getChild($id): ?AbstractPageWidget
    {
        if ($this->hasChild($id)) {
            return array_get($this->children, $id);
        }

        $childrens = $this->property('children', []);
        $info = $childrens[$id];

        $widget = new $info['type']($this->action, $id, $this->getPageManager());
        $widget->setContainer($this->container);
        $widget->setProperties($info['properties']);
        $widget->setParent($this);
        $widget->init();
        $widget->run();
        $this->children[$id] = $widget;

        return $widget;
    }

    public function hasChild($id): bool
    {
        return array_has($this->children, $id);
    }

    public function findChildren($path): ?AbstractPageWidget
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

    public function validateProperties(array $properties)
    {

    }

    protected function onInit()
    {
    }

    protected function onRender(string $blockSuffix, array &$options): void
    {
    }

    protected function getTwig(): Twig_Environment
    {
        return $this->container->get('twig');
    }

    public static function defineProperties($container): array
    {
        return [];
    }

    public function getPageManager(): PageManager
    {
        return $this->pageManager;
    }

    protected function getView()
    {
        return 'AppBundle:Widget:basewidget.html.twig';
    }

    protected function getAdditionalRenderOptions(): array
    {
        return [];
    }

    protected function setProperty(string $property, $value): void
    {
        array_set($this->properties, $property, $value);
    }

    abstract protected function getBlockName(): string;

    private function generateId()
    {
        return $this->getBlockName();
    }

    public function initializedAppVariable(): void
    {
        $this->appVariable = new AppVariable();
        $this->appVariable->setRequestStack($this->container->get('request_stack'));
    }

    public function getResultFromFunction(string $function, array $arguments)
    {
        $action = $this->getAction();
        $controller = $this->getPageManager()->getController();
        $result = null;

        list($class, $functionName) = explode('::', $function);
        if ($class === self::IDENTIFIER_CONTROLLER) {
            if (method_exists($controller, $action . '_' . $this->getFullId() . '_' . $functionName)) {
                $result = call_user_func_array([$controller, $action . '_' . $this->getFullId() . '_' . $functionName], $arguments);
            } elseif (method_exists($controller, $action . '_' . $function)) {
                $result = call_user_func_array([$controller, $action . '_' . $functionName], $arguments);
            } else {
                $result = call_user_func_array([$controller, $functionName], $arguments);
            }
        } elseif ($class === self::IDENTIFIER_PAGEMANAGER) {
            $result = call_user_func_array([$this->getPageManager(), $functionName], $arguments);
        } elseif (substr($class, 0, 1) === '@') {
            $service = $this->container->get(substr($class, 1));
            $result = call_user_func_array([$service, $functionName], $arguments);
        } else {
            $result = call_user_func_array([$class, $functionName], $arguments);
        }

        return $result;
    }

    public function getCallable(string $function): array
    {
        return $this->pageManager->getCallable($function);
    }

    public function getCallableFromArray(array $callableInfo): array
    {
        return $this->pageManager->getCallableFromArray($callableInfo);
    }

    protected function getAppVariable(): AppVariable
    {
        return $this->appVariable;
    }

    protected function defaultProperties(): array
    {
        return [];
    }

    protected function getCurrentRequest(): Request
    {
        return $this->container->get('request_stack')->getCurrentRequest();
    }

    protected function dispatchEvent(string $eventName, Event $event): void
    {
        $dispatcher = $this->container->get('event_dispatcher');
        $dispatcher->dispatch($eventName, $event);
    }
}
