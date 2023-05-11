<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Question;
use App\Form\CommentType;
use App\Form\QuestionType;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class QuestionController extends AbstractController
{
    #[Route('/question/ask', name: 'ask_question')]
    #[isGranted('IS_AUTENTICATED_REMEMBERED')]
    public function ask(Request $request, entityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $question = new Question();
        $formQuestion = $this->createForm(QuestionType::class, $question);
        $formQuestion->handleRequest($request);

        if ($formQuestion->isSubmitted() && $formQuestion->isValid()) {
            $question->setNbResponse(0);
            $question->setRating(0);
            $question->setAuthor($user);
            $question->setCreatedAt(new \DateTimeImmutable(timezone: new DateTimeZone("Europe/Paris")));

            $em->persist($question);
            $em->flush();

            $this->addFlash('success', 'Votre questions à été ajoutée!');

            return $this->redirectToRoute('home');
        }

        return $this->render('question/index.html.twig', ['form' => $formQuestion->createView()]);
    }

    #[Route('/question/{id}', name: 'show_question')]
    public function show(Request $request, EntityManagerInterface $em, Question $question)
    {
        $user = $this->getUser();

        $options = [
            'question' => $question
        ];

        if ($user) {
            $comment = new Comment();
            $commentForm = $this->createForm(CommentType::class, $comment);
            $commentForm->handleRequest($request);

            if ($commentForm->isSubmitted() && $commentForm->isValid()) {
                $comment->setCreatedAt(new \DateTimeImmutable(timezone: new DateTimeZone("Europe/Paris")));
                $comment->setRating(0);
                $comment->setQuestion($question);
                $comment->setAuthor($user);

                $question->setNbResponse($question->getNbResponse() + 1);

                $em->persist($comment);
                $em->flush();

                $this->addFlash('success', "votre réponse à bien été publiée");

                return $this->redirect($request->getUri());
            }
            $options['form'] = $commentForm->createView();
        }

        return $this->render('question/show.html.twig', $options);
    }

    #[Route('/question/rating/{id}/{score}', name: 'question_rating')]
    #[isGranted('IS_AUTENTICATED_REMEMBERED')]
    public function rate(Request $request, Question $question, int $score, EntityManagerInterface $em)
    {
        $question->setRating($question->getRating() + $score);
        $em->flush();

        $referer = $request->server->get('HTTP_REFERER');
        return $referer ? $this->redirect($referer) : $this->redirectToRoute('home');
    }
}
