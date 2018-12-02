<?php

namespace Wallabag\UserBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use FOS\UserBundle\Model\User as BaseUser;
use JMS\Serializer\Annotation\Accessor;
use JMS\Serializer\Annotation\Groups;
use JMS\Serializer\Annotation\XmlRoot;
use Scheb\TwoFactorBundle\Model\Email\TwoFactorInterface as EmailTwoFactorInterface;
use Scheb\TwoFactorBundle\Model\Google\TwoFactorInterface as GoogleTwoFactorInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;
use Wallabag\ApiBundle\Entity\Client;
use Wallabag\CoreBundle\Entity\Config;
use Wallabag\CoreBundle\Entity\Entry;
use Wallabag\CoreBundle\Helper\EntityTimestampsTrait;

/**
 * User.
 *
 * @XmlRoot("user")
 * @ORM\Entity(repositoryClass="Wallabag\UserBundle\Repository\UserRepository")
 * @ORM\Table(name="`user`")
 * @ORM\HasLifecycleCallbacks()
 *
 * @UniqueEntity("email")
 * @UniqueEntity("username")
 */
class User extends BaseUser implements EmailTwoFactorInterface, GoogleTwoFactorInterface
{
    use EntityTimestampsTrait;

    /** @Serializer\XmlAttribute */
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @Groups({"user_api", "user_api_with_client"})
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="text", nullable=true)
     *
     * @Groups({"user_api", "user_api_with_client"})
     */
    protected $name;

    /**
     * @var string
     *
     * @Groups({"user_api", "user_api_with_client"})
     */
    protected $username;

    /**
     * @var string
     *
     * @Groups({"user_api", "user_api_with_client"})
     */
    protected $email;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     *
     * @Groups({"user_api", "user_api_with_client"})
     */
    protected $createdAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime")
     *
     * @Groups({"user_api", "user_api_with_client"})
     */
    protected $updatedAt;

    /**
     * @ORM\OneToMany(targetEntity="Wallabag\CoreBundle\Entity\Entry", mappedBy="user", cascade={"remove"})
     */
    protected $entries;

    /**
     * @ORM\OneToOne(targetEntity="Wallabag\CoreBundle\Entity\Config", mappedBy="user", cascade={"remove"})
     */
    protected $config;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Wallabag\CoreBundle\Entity\SiteCredential", mappedBy="user", cascade={"remove"})
     */
    protected $siteCredentials;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Wallabag\ApiBundle\Entity\Client", mappedBy="user", cascade={"remove"})
     */
    protected $clients;

    /**
     * @see getFirstClient() below
     *
     * @Groups({"user_api_with_client"})
     * @Accessor(getter="getFirstClient")
     */
    protected $default_client;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $authCode;

    /**
     * @ORM\Column(name="googleAuthenticatorSecret", type="string", nullable=true)
     */
    private $googleAuthenticatorSecret;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private $emailTwoFactor = false;

    public function __construct()
    {
        parent::__construct();
        $this->entries = new ArrayCollection();
        $this->roles = ['ROLE_USER'];
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return User
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @param Entry $entry
     *
     * @return User
     */
    public function addEntry(Entry $entry)
    {
        $this->entries[] = $entry;

        return $this;
    }

    /**
     * @return ArrayCollection<Entry>
     */
    public function getEntries()
    {
        return $this->entries;
    }

    public function isEqualTo(UserInterface $user)
    {
        return $this->username === $user->getUsername();
    }

    /**
     * Set config.
     *
     * @param Config $config
     *
     * @return User
     */
    public function setConfig(Config $config = null)
    {
        $this->config = $config;

        return $this;
    }

    /**
     * Get config.
     *
     * @return Config
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @return bool
     */
    public function isEmailTwoFactor()
    {
        return $this->emailTwoFactor;
    }

    /**
     * @param bool $emailTwoFactor
     */
    public function setEmailTwoFactor($emailTwoFactor)
    {
        $this->emailTwoFactor = $emailTwoFactor;
    }

    /**
     * Used in the user config form to be "like" the email option.
     */
    public function isGoogleTwoFactor()
    {
        return $this->isGoogleAuthenticatorEnabled();
    }

    /**
     * {@inheritdoc}
     */
    public function isEmailAuthEnabled(): bool
    {
        return $this->emailTwoFactor;
    }

    /**
     * {@inheritdoc}
     */
    public function getEmailAuthCode(): string
    {
        return $this->authCode;
    }

    /**
     * {@inheritdoc}
     */
    public function setEmailAuthCode(string $authCode): void
    {
        $this->authCode = $authCode;
    }

    /**
     * {@inheritdoc}
     */
    public function getEmailAuthRecipient(): string
    {
        return $this->email;
    }

    /**
     * {@inheritdoc}
     */
    public function isGoogleAuthenticatorEnabled(): bool
    {
        return $this->googleAuthenticatorSecret ? true : false;
    }

    /**
     * {@inheritdoc}
     */
    public function getGoogleAuthenticatorUsername(): string
    {
        return $this->username;
    }

    /**
     * {@inheritdoc}
     */
    public function getGoogleAuthenticatorSecret(): string
    {
        return $this->googleAuthenticatorSecret;
    }

    /**
     * {@inheritdoc}
     */
    public function setGoogleAuthenticatorSecret(?string $googleAuthenticatorSecret): void
    {
        $this->googleAuthenticatorSecret = $googleAuthenticatorSecret;
    }

    /**
     * @param Client $client
     *
     * @return User
     */
    public function addClient(Client $client)
    {
        $this->clients[] = $client;

        return $this;
    }

    /**
     * @return ArrayCollection<Entry>
     */
    public function getClients()
    {
        return $this->clients;
    }

    /**
     * Only used by the API when creating a new user it'll also return the first client (which was also created at the same time).
     *
     * @return Client
     */
    public function getFirstClient()
    {
        if (!empty($this->clients)) {
            return $this->clients->first();
        }
    }
}
