<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class SecurityController extends AbstractController
{
    #[Route('/signup', name: 'signup')]
    public function signup(Request $request, UserPasswordHasherInterface $userPasswordHasherInterface, EntityManagerInterface $em): Response
    {
        $user = new User();
        $signupForm = $this->createForm(UserType::class, $user);
        $signupForm->handleRequest($request);

        if ($signupForm->isSubmitted() && $signupForm->isValid()) {
            $hashedPassword = $userPasswordHasherInterface->hashPassword($user, $user->getPassword());
            $user->setPassword($hashedPassword);

            $em->persist($user);
            $em->flush();

            return $this->redirectToRoute('signin');
        }

        return $this->render('security/signup.html.twig', ['form' => $signupForm->createView()]);
    }

    #[Route('/signin', name: 'signin')]
    public function signin(): Response
    {
        return $this->render('security/signin.html.twig');
    }

    #[Route('/logout', name: 'logout')]
    public function logout()
    {
    }
}
