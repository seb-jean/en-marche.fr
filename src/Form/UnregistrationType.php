<?php

namespace App\Form;

use App\Adherent\Unregistration\UnregistrationCommand;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UnregistrationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (!$options['is_renaissance']) {
            $builder
                ->add('comment', TextareaType::class, [
                    'required' => false,
                ])
                ->add('reasons', ChoiceType::class, [
                    'choices' => $options['reasons'],
                    'choice_translation_domain' => 'forms',
                    'expanded' => true,
                    'multiple' => true,
                ])
            ;
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults([
                'data_class' => UnregistrationCommand::class,
                'reasons' => [],
                'is_renaissance' => false,
                'validation_groups' => function (Options $options) {
                    return $options['is_renaissance']
                        ? ['renaissance']
                        : ['Default']
                    ;
                },
            ])
            ->setAllowedTypes('is_renaissance', 'bool')
        ;
    }
}
