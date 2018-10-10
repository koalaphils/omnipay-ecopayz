<?php

namespace AppBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\CallbackTransformer;

/**
 * Description of TagType.
 *
 * @author cnonog
 */
class MediaType extends AbstractType
{
    /**
     * @var \MediaBundle\Manager\MediaManager
     */
    protected $mediaManager;

    public function __construct(\MediaBundle\Manager\MediaManager $mediaManager)
    {
        $this->mediaManager = $mediaManager;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer(new CallbackTransformer(
            function ($data) {
                try {
                    $data = $this->mediaManager->getFile($data);

                    return $data;
                } catch (\Exception $e) {
                    //throw $e;
                }

                return $data;
            },
            function ($data) {
                return $data;
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
            'compound' => false,
            'multiple' => false,
        ]);
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
        return 'media';
    }
}
