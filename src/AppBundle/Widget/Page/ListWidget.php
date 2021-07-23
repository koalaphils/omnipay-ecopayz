<?php

namespace AppBundle\Widget\Page;

use AppBundle\Widget\AbstractPageWidget;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\HttpFoundation\Response;

class ListWidget extends AbstractPageWidget
{
    public static function defineDetails(): array
    {
        return [
            'title' => 'List'
        ];
    }

    public function  getColumns(): array
    {
        return $this->property('columns');
    }

    public function hasFooter(): bool
    {
        return $this->hasProperty('footer');
    }

    public function getFooterColumns(): array
    {
        return $this->property('footer.columns', []);
    }

    public function onGetList(array $data): array
    {
        $data['filters'] = array_merge($data['filters'] ?? [], $this->getAdditionalFilters());
	    $controller = $this->getPageManager()->getController();
	    $function = $this->property('processFilter', null);

	    if($function) {
		    list($class, $functionName) = explode('::', $function);
		    if ($class === 'controller') {
			    $data['filters'] = call_user_func([$controller, $functionName], $data['filters']);
		    } elseif (substr($class, 0, 1) === '@') {
			    $service = $this->container->get(substr($class, 1));
			    $data['filters'] = call_user_func([$service, $functionName], $data['filters']);
		    } else {
			    $data['filters'] = call_user_func([$class, $functionName], $data['filters']);
		    }
	    }
        $data['limit'] = $data['limit'] ?? 20;
        $data['page'] = $data['page'] ?? 1;
        $data['offset'] = $data['offset'] ?? ($data['page'] - 1) * $data['limit'];

        $repository = $this->getRepository();
        $records = call_user_func_array(
            [$repository, $this->property('methods.records.name')],
            $this->getParamsForMethod('records', $data)
        );

        $totalFiltered = call_user_func_array(
            [$repository, $this->property('methods.totalFiltered.name')],
            $this->getParamsForMethod('totalFiltered', $data)
        );

        $total = call_user_func_array(
            [$repository, $this->property('methods.total.name')],
            $this->getParamsForMethod('total', $data)
        );

        $results =  [
            'records' => $records,
            'recordsFiltered' => !empty($records) ? $totalFiltered : 0,
            'recordsTotal' => $total,
        ];

        return $this->processResult($results, $data);
    }

    public function isAutoload(): bool
    {
        return $this->property('autoload', true);
    }

    public function getFilterChoices(string $filterName)
    {
        $filter = $this->property('filters.' . $filterName);
        $function = array_get($filter, 'choicesFrom.function');
        $arguments = array_get($filter, 'choicesFrom.arguments', []);
        $label = array_get($filter, 'choicesFrom.label');
        $value = array_get($filter, 'choicesFrom.value');
        $expressionLanguage = new ExpressionLanguage();

        $result = $this->getResultFromFunction($function, $arguments);
        $choices = [];

        if (array_has($filter, 'choicesFrom.from')) {
            $result = $result[array_get($filter, 'choicesFrom.from')];
        }

        foreach ($result as $record) {
            $choices[$expressionLanguage->evaluate($value, ['result' => $record])] = $expressionLanguage->evaluate($label, ['result' => $record]);
        }

        return $choices;
    }

    public function onRun()
    {
        if ($this->hasProperty('form.create')) {
            $formCreateConfig = [
                'type' => FormWidget::class,
                'properties' => $this->property('form.create'),
            ];
            $this->setProperty('children.createForm', $formCreateConfig);
        }
    }

    public function onRenderCreateFormView(array $data = []): Response
    {
        $formWidget = $this->getChild('createForm');

        return new Response($formWidget->renderBlock('form'));
    }

    protected function getBlockName(): string
    {
        return 'list';
    }

    protected function getView(): string
    {
        return 'AppBundle:Widget:Page/list.html.twig';
    }


    protected function onInit()
    {
        if (!empty($this->property('filters', []))) {
            $applyFilter = array_merge([
                'type' => 'button',
                'label' => 'Apply Filter',
                'symbol' => 'a',
                'attrs' => ['type' => 'button'],
                'initialized' => $this->getFullId() . '_listInitializedApply',
            ], $this->property('filters.applyFilter', []));
            $resetFilter = array_merge([
                'type' => 'button',
                'label' => 'Reset Filter',
                'symbol' => 'r',
                'attrs' => ['type' => 'button'],
                'initialized' => $this->getFullId() . '_listInitializedReset',
            ], $this->property('filters.resetFilter', []));
            $this->setProperty('filters.applyFilter', $applyFilter);
            $this->setProperty('filters.resetFilter', $resetFilter);
        }

        if (!$this->hasProperty('search')) {
            $this->setProperty('search', '');
        }
    }

    private function getAdditionalFilters(): array
    {
        return $this->property('additionalFilters', []);
    }

    private function processResult(array $results, array $data): array
    {
        $processedResult = $results;
        $function = $this->property('processResult', null);
        $action = $this->getAction();
        $controller = $this->getPageManager()->getController();

        if ($function === null) {
            if (method_exists($controller, $action . '_' . $this->getFullId() . '_processResult')) {
                $processedResult = call_user_func([$controller, $action . '_' . $this->getFullId() . '_processResult'], $results, $this, $data);
            } elseif (method_exists($controller, $action . '_processResult')) {
                $processedResult = call_user_func([$controller, $action . '_processResult'], $results, $this, $data);
            }
        } else {
            list($class, $functionName) = explode('::', $function);
            if ($class === 'controller') {
                $processedResult = call_user_func([$controller, $functionName], $results, $this, $data);
            } elseif (substr($class, 0, 1) === '@') {
                $service = $this->container->get(substr($class, 1));
                $processedResult = call_user_func([$service, $functionName], $results, $this, $data);
            } else {
                $processedResult = call_user_func([$class, $functionName], $results, $this, $data);
            }
        }

        return $processedResult;
    }

    private function getParamsForMethod(string $method, array $data): array
    {
        $params = $this->property('methods.'. $method . '.params', []);
        $staticParams = $this->property('methods.' . $method . '.staticParams', []);
        $methodParams = [];
        foreach ($params as $param => $defaultData) {
            if (isset($staticParams[$param])) {
                $methodParams[$param] = $staticParams[$param];
            } else {
                $methodParams[$param] = $data[$param] ?? $defaultData;
            }
        }

        return $methodParams;
    }

    private function getRepository(): EntityRepository
    {
        return $this->container->get('doctrine')->getRepository($this->property('class'));
    }
}
