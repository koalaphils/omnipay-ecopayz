<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type;
use AppBundle\Manager\WidgetManager;
use Symfony\Component\Form\ChoiceList\Loader\CallbackChoiceLoader;
use AppBundle\Listener\WidgetFormSubscriber;

class WidgetType extends AbstractType
{
    private $widgetManager;

    public function __construct(WidgetManager $widgetManager)
    {
        $this->widgetManager = $widgetManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('size', Type\ChoiceType::class, [
            'choice_loader' => new CallbackChoiceLoader(function () {
                $choices = [];
                for ($i = 4; $i <= 12; ++$i) {
                    $choices[$i] = $i;
                }

                return $choices;
            }),
        ]);

        $builder->add('title', Type\TextType::class);

        $builder->addEventSubscriber(new WidgetFormSubscriber());
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
            'allow_extra_fields' => true,
            'widget' => null,
        ]);
    }
}
