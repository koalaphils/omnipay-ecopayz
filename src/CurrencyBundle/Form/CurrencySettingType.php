<?php

namespace CurrencyBundle\Form;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Bridge\Doctrine\Form\Type as EType;

/**
 * Description of CurrencySettingType.
 *
 * @author cnonog
 */
class CurrencySettingType extends AbstractType
{
    /**
     * @var \Doctrine\Bundle\DoctrineBundle\Registry
     */
    private $doctrine;

    public function __construct(Registry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('baseCurrency', EType\EntityType::class, [
            'class' => 'DbBundle:Currency',
            'choice_label' => 'name',
            'label' => 'settings.fields.baseCurrency',
            'translation_domain' => 'CurrencyBundle',
            'required' => true,
        ])->add('save', Type\SubmitType::class, [
            'label' => 'form.save',
            'attr' => [
                'class' => 'btn-success',
            ],
            'translation_domain' => 'AppBundle',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'setting_currency',
            'validation_groups' => 'default',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'CurrencySetting';
    }

    /**
     * Get currency repository.
     *
     * @return \DbBundle\Repository\CurrencyRepository
     */
    public function getCurrencyRepository()
    {
        return $this->doctrine->getRepository('DbBundle:Currency');
    }
}
