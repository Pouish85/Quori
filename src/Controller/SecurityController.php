<?php

namespace App\Controller;

use App\Entity\ResetPassword;
use App\Entity\User;
use App\Form\UserType;
use App\Repository\ResetPasswordRepository;
use App\Repository\UserRepository;
use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class SecurityController extends AbstractController
{
    function __construct(private $formLoginAuthenticator)
    {
    }


    #[Route('/signup', name: 'signup')]
    public function signup(Request $request, UserPasswordHasherInterface $userPasswordHasherInterface, EntityManagerInterface $em, UserAuthenticatorInterface $userAuthenticator, MailerInterface $mailer): Response
    {
        $user = new User();
        $signupForm = $this->createForm(UserType::class, $user);
        $signupForm->handleRequest($request);

        if ($signupForm->isSubmitted() && $signupForm->isValid()) {
            $hashedPassword = $userPasswordHasherInterface->hashPassword($user, $user->getPassword());
            $user->setPassword($hashedPassword);
            $user->setSignUpDate(new \DateTimeImmutable(timezone: new DateTimeZone("Europe/Paris")));

            $em->persist($user);
            $em->flush();

            $email = new TemplatedEmail();
            $email->to($user->getEmail())
                ->subject('Bienvenue sur Quori')
                ->htmlTemplate('@email_templates/welcome.html.twig')
                ->context([
                    'username' => $user->getUsername()
                ]);
            $mailer->send($email);

            flash()->addSuccess("Bienvenue sur Quori");
            return $userAuthenticator->authenticateUser($user, $this->formLoginAuthenticator, $request);
        }

        return $this->render('security/signup.html.twig', ['form' => $signupForm->createView()]);
    }

    #[Route('/signin', name: 'signin')]
    public function signin(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('home');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $username = $authenticationUtils->getLastUsername();

        return $this->render('security/signin.html.twig', [
            'error' => $error,
            'username' => $username
        ]);
    }

    #[Route('/logout', name: 'logout')]
    public function logout()
    {
    }

    #[Route('/reset-password-request', name: 'reset-password-request')]
    public function resetPasswordRequest(Request $request, UserRepository $userRepository, EntityManagerInterface $em, ResetPasswordRepository $resetPasswordRepository, MailerInterface $mailerInterface)
    {
        $emailForm = $this->createFormBuilder()
            ->add('email', EmailType::class, [
                'constraints' => [
                    new NotBlank([
                        'message' => "Veuillez renseigner ce champ."
                    ]),
                    new Email([
                        'message' => 'Veuillez entre un e-mail valide.'
                    ])
                ]
            ])
            ->getForm();

        $emailForm->handleRequest($request);

        if ($emailForm->isSubmitted() && $emailForm->isValid()) {
            $email = $emailForm->get('email')->getData();
            $user = $userRepository->findOneBy(['email' => $email]);

            if ($user) {
                $oldResetPassword = $resetPasswordRepository->findOneBy(['user' => $user]);
                if ($oldResetPassword) {
                    $em->remove($oldResetPassword);
                    $em->flush();
                }


                $token = substr(str_replace(['+', '/', '='], '', base64_encode(random_bytes(40))), 0, 20);

                $resetPassword = new ResetPassword();
                $resetPassword->setUser($user)
                    ->setToken($token)
                    ->setExpiredAt(new DateTimeImmutable('+2 hours'));

                $em->persist($resetPassword);
                $em->flush();

                $resetEmail = new TemplatedEmail();
                $resetEmail->to($email)
                    ->subject('Demande de réinitialisation de mot de passe')
                    ->htmlTemplate('@email_templates/reset-password-request.html.twig')
                    ->context([
                        'username' => $user->getUsername(),
                        'token' => $token
                    ]);
                $mailerInterface->send($resetEmail);
            }


            flash()->addSuccess("Si l'adresse email entrée est enregistrée, un email vous a été envoyé");
            return $this->redirectToRoute('signin');
        }

        return $this->render('security/reset-password-request.html.twig', ['form' => $emailForm->createView()]);
    }

    #[Route('/reset-password/{token}', name: 'reset-password')]
    public function resetPassword(Request $request, ResetPasswordRepository $resetPasswordRepository)
    {
        // $token = $this->token;
        // if($token->expiredAt() > DateTime('now')) {



        //     $newPasswordFom = $this->createFormBuilder()
        //         ->add('newPassword', PasswordType::class), [
        //             'constraints' => [
        //                 new NotBlank([
        //                     'message' => "Veuillez renseigner ce champ."
        //                 ]),
        //                 new Length([
        //                     'message' => 'Veuillez entre un mot de passe de 6 caractères minimum.'
        //                 ])
        //             ]
        //         ]
        //         ->getForm();

        //     $newPassordForm->handleRequest($request);

        //     if($newPasswordFom->isSubmitted() && $newPasswordFom->isValid() ) {
        //         $user = $resetPasswordRepository->findOneBy('token' => $token);

        //     }


        // } else {
        //     $this->addFlash('error', 'Votre demande a expirée');
        // }
    }
}
