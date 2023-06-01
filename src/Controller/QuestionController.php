<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Question;
use App\Entity\Vote;
use App\Form\CommentType;
use App\Form\QuestionType;
use App\Repository\QuestionRepository;
use App\Repository\VoteRepository;
use App\Services\UploadImageService;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class QuestionController extends AbstractController
{
    #[Route('/question/ask', name: 'ask_question')]
    #[isGranted('IS_AUTHENTICATED_REMEMBERED')]
    public function ask(Request $request, entityManagerInterface $em, UploadImageService $pictureUploader): Response
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

            $userId = $question->getAuthor()->getId();
            $picture = $formQuestion->get('pictureFile')->getData();
            if ($picture) {
                $question->setPicture($pictureUploader->uploadQuestionPicture($picture, $userId));
            }

            $em->persist($question);
            $em->flush();

            flash()->addSuccess("Votre questions à été ajoutée!");

            return $this->redirectToRoute('home');
        }

        return $this->render('question/index.html.twig', ['form' => $formQuestion->createView()]);
    }

    #[Route('/question/search/{search}', name: 'question_search')]
    public function questionSearch(QuestionRepository $questionRepository, string $search = "none")
    {
        if ($search === "none") {
            $questions = [];
        } else {
            $questions = $questionRepository->findByTerm($search);
        }

        return $this->json(json_encode(($questions)));
    }

    #[Route('/question/{id}', name: 'show_question')]
    public function show(Request $request, Question $question, EntityManagerInterface $em)
    {

        $user = $this->getUser();
        // $question = $questionRepository->findQuestionWithAllCommentsAndAuthors($id);
        // dd($question);
        if ($question) {
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

                    flash()->addSuccess("Votre réponse à bien été publiée");

                    return $this->redirect($request->getUri());
                }
                $options['form'] = $commentForm->createView();
            }

            return $this->render('question/show.html.twig', $options);
        }
        return $this->redirectToRoute('home');
    }

    #[Route('/question/rating/{id}/{score}', name: 'question_rating')]
    #[isGranted('IS_AUTHENTICATED_REMEMBERED')]
    public function rate(Request $request, Question $question, int $score, EntityManagerInterface $em, VoteRepository $voteRepository)
    {
        $currentUser = $this->getUser();

        if ($currentUser !== $question->getAuthor()) {

            $vote = $voteRepository->findOneBy([
                'author' => $currentUser,
                'question' => $question
            ]);

            if ($vote) {

                if (($vote->getIsLiked() && $score > 0) | (!$vote->getIsLiked() && $score < 0)) {
                    $em->remove($vote);
                    $question->setRating($question->getRating() + ($score > 0 ? -1 : 1));
                } else {
                    $vote->setIsLiked(!$vote->getIsLiked());
                    $question->setRating($question->getRating() + ($score > 0 ? 2 : -2));
                }
            } else {
                $newVote = new Vote();
                $newVote->setAuthor($currentUser)
                    ->setQuestion($question)
                    ->setIsLiked($score > 0 ? true : false);

                $em->persist($newVote);
                $question->setRating($question->getRating() + $score);
            }

            $em->flush();
        }


        $referer = $request->server->get('HTTP_REFERER');
        return $referer ? $this->redirect($referer) : $this->redirectToRoute('home');
    }
}
