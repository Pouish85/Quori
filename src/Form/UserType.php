<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Constraints\Required;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username', TextType::class, ['label' => "Nom d'utilisateur *", 'required' => false])
            ->add('firstname', TextType::class, ['label' => 'Prénom *', 'required' => false])
            ->add('lastname', TextType::class, ['label' => 'Nom *', 'required' => false])
            ->add('pictureFile', FileType::class, [
                'label' => 'Image de profil',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new Image([
                        'mimeTypesMessage' => "Veuillez uploader un fichier au bon format",
                        'maxSize' => '1M',
                        'maxSizeMessage' => "L'image est trop lourde. (Votre fichier est de {{size}} et la limite est de {{limi}}{{sufix}}"
                    ])
                ]
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email *', 'required' => false,
            ])
            ->add('password', PasswordType::class, ['label' => 'Mot de passe *', 'required' => false]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
