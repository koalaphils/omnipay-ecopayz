<?php

namespace ApiBundle\Form\Member;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Valid;


class FileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('files', Type\CollectionType::class, [
            'entry_type' => Type\FileType::class,
            'allow_add' => true,
            'required' => false,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => \ApiBundle\Model\File::class,
            'csrf_protection' => false,
            'constraints' => [new Valid()],
        ]);
    }
}
