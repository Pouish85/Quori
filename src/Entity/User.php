<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[UniqueEntity(fields: "email", message: "Cet email est déjà enregistré.")]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\Email(message: "Veuillez renseigner un email valide")]
    #[Assert\NotBlank(message: 'Ce champ ne peut être vide')]
    private ?string $email = null;

    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    #[Assert\Length(min: 6, minMessage: "Le mot de passe doit faire au minimum 6 caracteres")]
    #[Assert\NotBlank(message: 'Ce champ ne peut être vide')]
    private ?string $password = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Ce champ ne peut être vide')]
    private ?string $firstname = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Ce champ ne peut être vide')]
    private ?string $lastname = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\OneToMany(mappedBy: 'author', targetEntity: Question::class)]
    private Collection $questions;

    #[ORM\OneToMany(mappedBy: 'author', targetEntity: Comment::class)]
    private Collection $comments;

    #[Assert\Length(min: 6, minMessage: "Le mot de passe doit faire au minimum 6 caracteres")]
    private ?string $newPassword = null;

    #[ORM\OneToMany(mappedBy: 'author', targetEntity: Vote::class)]
    private Collection $votes;

    public function __construct()
    {
        $this->questions = new ArrayCollection();
        $this->comments = new ArrayCollection();
        $this->votes = new ArrayCollection();
    }

    public function getTotalQuestionsRating(): int
    {
        $totalRating = 0;

        foreach ($this->questions as $question) {
            $totalRating += $question->getRating();
        }

        return $totalRating;
    }


    public function getTotalCommentsRating(): int
    {
        $totalRating = 0;

        foreach ($this->comments as $comment) {
            $totalRating += $comment->getRating();
        }

        return $totalRating;
    }

    public function getTotalQuestions(): int
    {
        return $this->questions->count();
    }

    public function getTotalComments(): int
    {
        return $this->comments->count();
    }

    public function getAverageQuestionsRating(): int
    {
        $averageQuestionsRating = 0;
        $totalQuestionRating = $this->getTotalQuestionsRating();
        $totalQuestions = $this->getTotalQuestions();

        if ($totalQuestions === 0) {

            $averageQuestionsRating = 0;
        } else {

            $averageQuestionsRating = $totalQuestionRating / $totalQuestions;
        }

        return $averageQuestionsRating;
    }

    public function getAverageCommentsRating(): int
    {
        $averageCommentsRating = 0;
        $totalCommentRating = $this->getTotalCommentsRating();
        $totalComments = $this->getTotalComments();

        if ($totalComments === 0) {

            $averageCommentsRating = 0;
        } else {

            $averageCommentsRating = $totalCommentRating / $totalComments;
        }

        return $averageCommentsRating;
    }

    public function getQuestionWithHighestRating(): ?Question
    {
        $highestRating = 0;
        $questionWithHighestRating = null;

        foreach ($this->questions as $question) {
            if ($question->getRating() > $highestRating) {
                $highestRating = $question->getRating();
                $questionWithHighestRating = $question;
            }
        }

        return $questionWithHighestRating;
    }

    public function getCommentWithHighestRating(): ?Comment
    {
        $highestRating = 0;
        $commentWithHighestRating = null;

        foreach ($this->comments as $comment) {
            if ($comment->getRating() > $highestRating) {
                $highestRating = $comment->getRating();
                $commentWithHighestRating = $comment;
            }
        }

        return $commentWithHighestRating;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getNewPassword(): ?string
    {
        return $this->newPassword;
    }

    public function setNewPassword(string $password): self
    {
        $this->newPassword = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): self
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): self
    {
        $this->lastname = $lastname;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): self
    {
        $this->image = $image;

        return $this;
    }

    /**
     * @return Collection<int, Question>
     */
    public function getQuestions(): Collection
    {
        return $this->questions;
    }

    public function addQuestion(Question $question): self
    {
        if (!$this->questions->contains($question)) {
            $this->questions->add($question);
            $question->setAuthor($this);
        }

        return $this;
    }

    public function removeQuestion(Question $question): self
    {
        if ($this->questions->removeElement($question)) {
            // set the owning side to null (unless already changed)
            if ($question->getAuthor() === $this) {
                $question->setAuthor(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Comment>
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function addComment(Comment $comment): self
    {
        if (!$this->comments->contains($comment)) {
            $this->comments->add($comment);
            $comment->setAuthor($this);
        }

        return $this;
    }

    public function removeComment(Comment $comment): self
    {
        if ($this->comments->removeElement($comment)) {
            // set the owning side to null (unless already changed)
            if ($comment->getAuthor() === $this) {
                $comment->setAuthor(null);
            }
        }

        return $this;
    }

    public function getFullname(): string
    {
        return $this->firstname . ' ' . $this->lastname;
    }

    /**
     * @return Collection<int, Vote>
     */
    public function getVotes(): Collection
    {
        return $this->votes;
    }

    public function addVote(Vote $vote): self
    {
        if (!$this->votes->contains($vote)) {
            $this->votes->add($vote);
            $vote->setAuthor($this);
        }

        return $this;
    }

    public function removeVote(Vote $vote): self
    {
        if ($this->votes->removeElement($vote)) {
            // set the owning side to null (unless already changed)
            if ($vote->getAuthor() === $this) {
                $vote->setAuthor(null);
            }
        }

        return $this;
    }
}
