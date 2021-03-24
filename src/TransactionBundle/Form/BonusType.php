<?php

namespace TransactionBundle\Form;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Extension\Core\Type;
use AppBundle\Form\Type as CType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Bundle\FrameworkBundle\Routing\Router;

/**
 * Description of StatusType.
 *
 * @author Cydrick Nonog <cydrick.nonog@zmtsys.com>
 */
class BonusType extends AbstractType
{
    /**
     * @var \Symfony\Bundle\FrameworkBundle\Routing\Router
     */
    private $doctrine;

    /**
     * @var \Doctrine\Bundle\DoctrineBundle\Registry
     */
    private $router;

    public function __construct(Registry $doctrine, Router $router)
    {
        $this->doctrine = $doctrine;
        $this->router = $router;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('id', CType\Select2Type::class, [
                'label' => 'fields.details.bonus.id',
                'translation_domain' => 'TransactionBundle',
                'attr' => [
                    'data-placeholder' => 'Select Bonus',
                    'data-autostart' => 'true',
                    'data-ajax--url' => $this->router->generate('bonus.list_search'),
                    'data-ajax--type' => 'POST',
                    'data-ajax--delay' => 1000,
                    'data-minimum-input-length' => 0,
                    'data-length' => 10,
                    'data-ajax--cache' => 1,
                    'data-allowClear' => 1,
                    'data-minimum-results-for-search' => 'Infinity',
                    'data-use-template-result' => 'bonusTemplateResult',
                    'data-use-template-selection' => 'bonusTemplateSelection',
                ],
                'mapped' => array_get($builder->getOption('unmap'), 'id', true),
            ])
            ->add('subject', Type\TextType::class, [
                'label' => 'fields.details.bonus.subject',
                'translation_domain' => 'TransactionBundle',
                'mapped' => array_get($builder->getOption('unmap'), 'subject', true),
            ])
        ;

        $builder->get('id')->addViewTransformer(new CallbackTransformer(
            function ($bonus) {
                if ($bonus) {
                    $entity = $this->getBonusRepository()->findById($bonus, \Doctrine\ORM\Query::HYDRATE_ARRAY);

                    return [$entity];
                }

                return $bonus;
            },
            function ($bonus) {
                return $bonus;
            }
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'validation_groups' => 'default',
            'view' => false,
            'views' => [],
            'unmap' => [],
        ]);
    }

    public function finishView(\Symfony\Component\Form\FormView $view, \Symfony\Component\Form\FormInterface $form, array $options)
    {
        parent::finishView($view, $form, $options);

        if ($form->getConfig()->getOption('view')) {
            foreach ($view->children as &$child) {
                $child->vars['view'] = true;
            }
        }

        foreach ($form->getConfig()->getOption('views') as $field => $isView) {
            $view->children[$field]->vars['view'] = $isView;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'bonus';
    }

    /**
     * Get customer product.
     *
     * @return \DbBundle\Repository\BonusRepository
     */
    public function getBonusRepository()
    {
        return $this->doctrine->getRepository('DbBundle:Bonus');
    }
}
