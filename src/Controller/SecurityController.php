<?php

namespace App\Controller;

use App\Entity\ResetPassword;
use App\Entity\User;
use App\Form\UserType;
use App\Repository\ResetPasswordRepository;
use App\Repository\UserRepository;
use App\Services\UploadImageService;
use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\FrameworkBundle\Controller\RedirectController;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
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
    public function signup(Request $request, UserPasswordHasherInterface $userPasswordHasherInterface, EntityManagerInterface $em, UserAuthenticatorInterface $userAuthenticator, MailerInterface $mailer, UploadImageService $uploaderPicture): Response
    {
        $user = new User();
        $signupForm = $this->createForm(UserType::class, $user);
        $signupForm->handleRequest($request);

        if ($signupForm->isSubmitted() && $signupForm->isValid()) {
            $hashedPassword = $userPasswordHasherInterface->hashPassword($user, $user->getPassword());
            $user->setPassword($hashedPassword);
            $user->setSignUpDate(new \DateTimeImmutable(timezone: new DateTimeZone("Europe/Paris")));

            $picture = $signupForm->get('pictureFile')->getData();
            if ($picture) {
                $user->setImage($uploaderPicture->uploadProfileImage($picture));
            } else {
                $user->setImage("/profiles/default_profile.png");
            }

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
    public function resetPasswordRequest(Request $request, UserRepository $userRepository, EntityManagerInterface $em, ResetPasswordRepository $resetPasswordRepository, MailerInterface $mailerInterface, RateLimiterFactory $passwordRecoveryLimiter)
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

            $limiter = $passwordRecoveryLimiter->create($request->getClientIp());
            if (!$limiter->consume(1)->isAccepted()) {
                flash()->addError('Vous devez attendre 2 heures avant de faire une nouvelle demande');
                return $this->redirectToRoute('signin');
            }

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
                    ->setToken(sha1($token))
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
    public function resetPassword(string $token, Request $request, ResetPasswordRepository $resetPasswordRepository, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher, RateLimiterFactory $passwordRecoveryLimiter)
    {

        $limiter = $passwordRecoveryLimiter->create($request->getClientIp());
        if (!$limiter->consume(1)->isAccepted()) {
            flash()->addSuccess('Votre mot de passe a été modifié');
            return $this->redirectToRoute('signin');
        }

        $resetPassword = $resetPasswordRepository->findOneBy(['token' => sha1($token)]);

        if (!$resetPassword || $resetPassword->getExpiredAt() < new DateTime('now')) {

            if ($resetPassword) {
                $em->remove($resetPassword);
                $em->flush();
            }

            flash()->addError("Votre demande a expirée, veuillez recommencer");
            return $this->redirectToRoute('reset-password-request');
        }
        $resetPasswordForm = $this->createFormBuilder()
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'invalid_message' => "Les mots de passe ne correspondent pas",
                'required' => false,
                'first_options' => ['label' => "Nouveau mot de passe"],
                'second_options' => ['label' => "Vérification du mot de passe"]
            ])
            ->getForm();

        $resetPasswordForm->handleRequest($request);

        if ($resetPasswordForm->isSubmitted() && $resetPasswordForm->isValid()) {
            $user = $resetPassword->getUser();
            $newPassword = $resetPasswordForm->get('password')->getData();
            $hashedNewPassword = $passwordHasher->hashPassword($user, $newPassword);
            $user->setPassword($hashedNewPassword);

            $em->remove($resetPassword);
            $em->flush();

            flash()->addSuccess('Votre mot de passe a été mis a jour');
            $this->redirectToRoute('signin');
        }



        return $this->render('security/reset-password-form.html.twig', ['form' => $resetPasswordForm->createView()]);
    }
}
