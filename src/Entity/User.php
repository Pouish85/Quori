<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
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
    #[Assert\NotBlank(message: "Ce champ ne peut etre vide")]
    private ?string $email = null;

    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    #[Assert\Length(min: 6, minMessage: "Le mot de passe doit faire au minimum 6 caractères")]
    #[Assert\NotBlank(message: "Ce champ ne peut etre vide")]
    private ?string $password = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Ce champ ne peut etre vide")]
    private ?string $firstname = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Ce champ ne peut etre vide")]
    private ?string $lastname = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\OneToMany(mappedBy: 'author', targetEntity: Question::class)]
    private Collection $questions;

    #[ORM\OneToMany(mappedBy: 'author', targetEntity: Comment::class)]
    private Collection $comments;

    #[ORM\OneToMany(mappedBy: 'author', targetEntity: Question::class)]
    private Collection $author;

    #[ORM\OneToMany(mappedBy: 'author', targetEntity: Comment::class)]
    private Collection $comments2;

    #[Assert\Length(min: 6, minMessage: "Le mot de passe doit faire au minimum 6 caractères")]
    private ?string $newPassword = null;

    #[ORM\OneToMany(mappedBy: 'author', targetEntity: Vote::class)]
    private Collection $votes;

    public function __construct()
    {
        $this->questions = new ArrayCollection();
        $this->comments = new ArrayCollection();
        $this->author = new ArrayCollection();
        $this->comments2 = new ArrayCollection();
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

    public function getAverageQuestionsRating(): int
    {
        $averageQuestionsRating = 0;
        $totalQuestionRating = $this->getTotalQuestionsRating();
        $totalQuestions = $this->getTotalQuestions();

        $averageQuestionsRating = $totalQuestionRating / $totalQuestions;

        return $averageQuestionsRating;
    }

    // public function getQuestionWithHighestRating(): ?Question
    // {
    //     $highestRating = 0;
    //     $questionWithHighestRating = null;

    //     foreach ($this->questions as $question) {
    //         if ($question->getRating() > $highestRating) {
    //             $highestRating = $question->getRating();
    //             $questionWithHighestRating = $question;
    //         }
    //     }

    //     return $questionWithHighestRating;
    // }

    // public function getCommentWithHighestRating(): ?Comment
    // {
    //     $highestRating = 0;
    //     $commentWithHighestRating = null;

    //     foreach ($this->comments as $comment) {
    //         if ($comment->getRating() > $highestRating) {
    //             $highestRating = $comment->getRating();
    //             $commentWithHighestRating = $comment;
    //         }
    //     }

    //     return $commentWithHighestRating;
    // }

    public function getTotalQuestions(): int
    {
        return $this->questions->count();
    }

    public function getTotalComments(): int
    {
        return $this->comments->count();
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
     * @return Collection<int, Question>
     */
    public function getAuthor(): Collection
    {
        return $this->author;
    }

    public function addAuthor(Question $author): self
    {
        if (!$this->author->contains($author)) {
            $this->author->add($author);
            $author->setAuthor($this);
        }

        return $this;
    }

    public function removeAuthor(Question $author): self
    {
        if ($this->author->removeElement($author)) {
            // set the owning side to null (unless already changed)
            if ($author->getAuthor() === $this) {
                $author->setAuthor(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Comment>
     */
    public function getComments2(): Collection
    {
        return $this->comments2;
    }

    public function addComments2(Comment $comments2): self
    {
        if (!$this->comments2->contains($comments2)) {
            $this->comments2->add($comments2);
            $comments2->setAuthor($this);
        }

        return $this;
    }

    public function removeComments2(Comment $comments2): self
    {
        if ($this->comments2->removeElement($comments2)) {
            // set the owning side to null (unless already changed)
            if ($comments2->getAuthor() === $this) {
                $comments2->setAuthor(null);
            }
        }

        return $this;
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
