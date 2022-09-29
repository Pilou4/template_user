<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Karser\Recaptcha3Bundle\Form\Recaptcha3Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Karser\Recaptcha3Bundle\Validator\Constraints\Recaptcha3;

class ContactType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'firstname',
            TextType::class, 
            [
                'label' => 'prénom',
                'required' => true,
                'label_attr' => ['class' => 'firstname'],
            ]
        );

        $builder->add(
            'lastname',
            TextType::class, 
            [
                'label' => 'nom',
                'required' => true,
                'label_attr' => ['class' => 'lastname'],
            ]
        );

        $builder->add(
            'object',
            TextType::class, 
            [
                'label' => 'sujet',
                'required' => true,
                'label_attr' => ['class' => 'object'],
            ]
        );
        $builder->add(
            'email',
            EmailType::class,
            [
                'label' => 'email',
                'required' => true,
                'label_attr' => ['class' => 'email'],
            ]
        );
        $builder->add(
            'phone',
            TextType::class,
            [
                'label' => 'téléphone',
                'required' => false,
            ]
        );
        $builder->add(
            'message',
            TextareaType::class, 
            [
                'label' => 'message',
                'required' => true,
                'label_attr' => ['class' => 'message'],
            ]
        );
        $builder->add(
            'agreeTerms',
            CheckboxType::class, [
                'mapped' => false,
                // 'label' => "En soumettant ce formulaire, j'accepte 
                // que mes données personnelles soient utilisées pour 
                // me recontacter. Aucun autre traitement ne sera effectué 
                // avec mes informations.",
                'label' => false,
                'constraints' => [
                    new IsTrue([
                        'message' => 'You should agree to our terms.',
                    ]),
                ],
                'label_attr' => ['class' => 'contact__label__check'],
            ]
        );
        $builder->add('captcha', Recaptcha3Type::class, [
            'constraints' => new Recaptcha3(),
            'action_name' => 'home',
            'locale' => 'fr',
        ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}