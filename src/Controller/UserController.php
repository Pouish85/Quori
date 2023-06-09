<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Services\UploadImageService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class UserController extends AbstractController
{
    #[Route('/user', name: 'current_user_profile_settings')]
    #[IsGranted("IS_AUTHENTICATED_FULLY")]
    public function currentUserProfile(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $em, UploadImageService $uploaderPicture): Response
    {
        /**
         * @var User
         */
        $currentUser = $this->getUser();
        $profileForm = $this->createForm(UserType::class, $currentUser);
        $profileForm->remove('password');
        $profileForm->add('newPassword', RepeatedType::class, [
            'type' => PasswordType::class,
            'invalid_message' => "Les mots de passe ne correspondent pas",
            'required' => false,
            'first_options' => ['label' => "Nouveau mot de passe"],
            'second_options' => ['label' => "Vérification du mot de passe"]
        ]);

        $profileForm->handleRequest($request);

        if ($profileForm->isSubmitted() && $profileForm->isValid()) {
            $newPassword = $currentUser->getNewPassword();
            if ($newPassword) {
                $hashedNewPassword = $passwordHasher->hashPassword($currentUser, $newPassword);
                $currentUser->setPassword($hashedNewPassword);
            }

            $picture = $profileForm->get('pictureFile')->getData();
            if ($picture) {
                $currentUser->setImage($uploaderPicture->uploadProfileImage($picture, $currentUser->getImage()));
            }


            $em->flush();
            flash()->addSuccess("Modification des informations sauvegardées!");
        }

        return $this->render('user/profile_settings.html.twig', [
            'form' => $profileForm->createView()
        ]);
    }

    #[Route('/user/profile', name: 'current_user_profile')]
    #[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
    public function showProfile()
    {
        return $this->render('user/profile.html.twig');
    }

    #[Route('/user/questions', name: 'show_questions')]
    #[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
    public function showQuestions()
    {
        return $this->render('user/show_questions.html.twig');
    }

    #[Route('/user/comments', name: 'show_comments')]
    #[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
    public function showComments()
    {
        return $this->render('user/show_comments.html.twig');
    }

    #[Route('/user/{id}', name: 'user_profile')]
    #[IsGranted("IS_AUTHENTICATED_REMEMBERED")]
    public function userProfile(User $user): Response
    {
        $currentUser = $this->getUser();
        if ($currentUser === $user) {
            return $this->redirectToRoute('current_user_profile');
        }
        return $this->render('user/show.html.twig', ['user' => $user]);
    }
}
