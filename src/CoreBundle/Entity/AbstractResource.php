<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Metadata\ApiProperty;
use Chamilo\CoreBundle\Entity\Listener\ResourceListener;
use Chamilo\CoreBundle\Security\Authorization\Voter\ResourceNodeVoter;
use Chamilo\CoreBundle\Traits\UserCreatorTrait;
use Chamilo\CourseBundle\Entity\CGroup;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\MappedSuperclass]
#[ORM\HasLifecycleCallbacks]
#[ORM\EntityListeners([ResourceListener::class])]
abstract class AbstractResource
{
    use UserCreatorTrait;

    #[ApiProperty(types: ['https://schema.org/contentUrl'])]
    #[Groups(['resource_file:read', 'resource_node:read', 'document:read', 'media_object_read', 'message:read', 'student_publication:read', 'student_publication_comment:read'])]
    public ?string $contentUrl = null;

    /**
     * Download URL of the Resource File Property set by ResourceNormalizer.php.
     */
    #[ApiProperty(types: ['https://schema.org/contentUrl'])]
    #[Groups(['resource_file:read', 'resource_node:read', 'document:read', 'media_object_read', 'message:read', 'student_publication:read', 'student_publication_comment:read', 'student_publication_rel_document:read'])]
    public ?string $downloadUrl = null;

    /**
     * Content from ResourceFile - Property set by ResourceNormalizer.php.
     */
    #[Groups(['resource_file:read', 'resource_node:read', 'document:read', 'document:write', 'media_object_read', 'student_publication:read'])]
    public ?string $contentFile = null;

    /**
     * Resource illustration URL - Property set by ResourceNormalizer.php.
     */
    #[ApiProperty(types: ['https://schema.org/contentUrl'])]
    #[Groups([
        'resource_node:read',
        'document:read',
        'media_object_read',
        'course:read',
        'session:read',
        'course_rel_user:read',
        'session_rel_course:read',
        'session_rel_user:read',
        'session_rel_course_rel_user:read',
        'user_subscriptions:sessions',
    ])]
    public ?string $illustrationUrl = null;

    #[Assert\Valid]
    #[Groups([
        'resource_node:read',
        'resource_node:write',
        'personal_file:write',
        'document:write',
        'ctool:read',
        'course:read',
        'illustration:read',
        'message:read',
        'c_tool_intro:read',
        'attendance:read',
        'student_publication:read',
        'student_publication_comment:read',
    ])]
    #[ORM\OneToOne(targetEntity: ResourceNode::class, cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'resource_node_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    public ?ResourceNode $resourceNode = null;

    /**
     * This property is used when using api platform.
     */
    #[Groups([
        'resource_node:read',
        'resource_node:write',
        'document:read',
        'document:write',
        'c_student_publication:write',
        'calendar_event:write',
    ])]
    public ?int $parentResourceNode = 0;

    #[ApiProperty(types: ['https://schema.org/image'])]
    public ?UploadedFile $uploadFile = null;

    /**
     * @var AbstractResource|ResourceInterface|null
     */
    public $parentResource;

    #[Groups(['resource_node:read', 'document:read', 'attendance:read', 'student_publication:read', 'student_publication_comment:read'])]
    public ?array $resourceLinkListFromEntity = null;

    /**
     * Use when sending a request to Api platform.
     * Temporal array that saves the resource link list that will be filled by CreateDocumentFileAction.php.
     *
     * @var array<int, array<string, int>>
     */
    #[Groups(['c_tool_intro:write', 'resource_node:write', 'c_student_publication:write', 'calendar_event:write', 'attendance:write'])]
    public array $resourceLinkList = [];

    /**
     * @var array<ResourceLink>
     */
    public array $resourceLinkEntityList = [];

    /**
     * This function separates the users from the groups users have a value
     * USER:XXX (with XXX the groups id have a value
     * GROUP:YYY (with YYY the group id).
     *
     * @param array $to Array of strings that define the type and id of each destination
     *
     * @return array Array of groups and users (each an array of IDs)
     */
    public static function separateUsersGroups(array $to): array
    {
        $sendTo = ['groups' => [], 'users' => []];

        foreach ($to as $toItem) {
            if (empty($toItem)) {
                continue;
            }

            $parts = explode(':', $toItem);
            $type = $parts[0] ?? '';
            $id = $parts[1] ?? '';

            switch ($type) {
                case 'GROUP':
                    $sendTo['groups'][] = (int) $id;

                    break;

                case 'USER':
                    $sendTo['users'][] = (int) $id;

                    break;
            }
        }

        return $sendTo;
    }

    abstract public function getResourceName(): string;

    abstract public function setResourceName(string $name);

    public function getResourceLinkEntityList(): array
    {
        return $this->resourceLinkEntityList;
    }

    /**
     * @throws Exception
     */
    public function addCourseLink(
        Course $course,
        ?Session $session = null,
        ?CGroup $group = null,
        int $visibility = ResourceLink::VISIBILITY_PUBLISHED
    ): self {
        if (null === $this->getParent()) {
            throw new Exception('$resource->addCourseLink requires to set the parent first.');
        }

        $resourceLink = (new ResourceLink())
            ->setVisibility($visibility)
            ->setCourse($course)
            ->setSession($session)
            ->setGroup($group)
        ;

        $rights = [];

        switch ($visibility) {
            case ResourceLink::VISIBILITY_PENDING:
            case ResourceLink::VISIBILITY_DRAFT:
                $editorMask = ResourceNodeVoter::getEditorMask();
                $resourceRight = (new ResourceRight())
                    ->setMask($editorMask)
                    ->setRole(ResourceNodeVoter::ROLE_CURRENT_COURSE_TEACHER)
                ;
                $rights[] = $resourceRight;

                break;
        }

        if (!empty($rights)) {
            foreach ($rights as $right) {
                $resourceLink->addResourceRight($right);
            }
        }

        if ($this->hasResourceNode()) {
            $resourceNode = $this->getResourceNode();
            $exists = $resourceNode->getResourceLinks()->exists(
                fn ($key, $element) => $course === $element->getCourse()
                    && $session === $element->getSession()
                    && $group === $element->getGroup()
            );

            if ($exists) {
                return $this;
            }
            $resourceNode->addResourceLink($resourceLink);
        } else {
            $this->addLink($resourceLink);
        }

        return $this;
    }

    public function getParent()
    {
        return $this->parentResource;
    }

    public function hasResourceNode(): bool
    {
        return $this->resourceNode instanceof ResourceNode;
    }

    public function getResourceNode(): ?ResourceNode
    {
        return $this->resourceNode;
    }

    public function setResourceNode(?ResourceNode $resourceNode): self
    {
        $this->resourceNode = $resourceNode;

        return $this;
    }

    /**
     * $this->resourceLinkEntityList will be loaded in the ResourceListener in the setLinks() function.
     */
    public function addLink(ResourceLink $link): static
    {
        $this->resourceLinkEntityList[] = $link;

        return $this;
    }

    public function setParent(ResourceInterface $parent): static
    {
        $this->parentResource = $parent;

        return $this;
    }

    public function addResourceToUserList(
        array $userList,
        ?Course $course = null,
        ?Session $session = null,
        ?CGroup $group = null
    ): static {
        if (!empty($userList)) {
            foreach ($userList as $user) {
                $this->addUserLink($user, $course, $session, $group);
            }
        }

        return $this;
    }

    public function addUserLink(
        User $user,
        ?Course $course = null,
        ?Session $session = null,
        ?CGroup $group = null
    ): static {
        $resourceLink = (new ResourceLink())
            ->setVisibility(ResourceLink::VISIBILITY_PUBLISHED)
            ->setUser($user)
            ->setCourse($course)
            ->setSession($session)
            ->setGroup($group)
        ;

        if ($this->hasResourceNode()) {
            $resourceNode = $this->getResourceNode();
            $exists = $resourceNode->getResourceLinks()->exists(
                function ($key, $element) use ($user) {
                    if ($element->hasUser()) {
                        return $user->getId() === $element->getUser()->getId();
                    }

                    return false;
                }
            );

            if ($exists) {
                return $this;
            }

            $resourceNode->addResourceLink($resourceLink);
        } else {
            $this->addLink($resourceLink);
        }

        return $this;
    }

    public function addResourceToGroupList(
        array $groupList,
        ?Course $course = null,
        ?Session $session = null,
    ): static {
        foreach ($groupList as $group) {
            $this->addGroupLink($course, $group, $session);
        }

        return $this;
    }

    public function addGroupLink(Course $course, CGroup $group, ?Session $session = null): static
    {
        $resourceLink = (new ResourceLink())
            ->setCourse($course)
            ->setSession($session)
            ->setGroup($group)
            ->setVisibility(ResourceLink::VISIBILITY_PUBLISHED)
        ;

        if ($this->hasResourceNode()) {
            $resourceNode = $this->getResourceNode();
            $exists = $resourceNode->getResourceLinks()->exists(
                function ($key, $element) use ($group) {
                    if ($element->getGroup()) {
                        return $group->getIid() === $element->getGroup()->getIid();
                    }
                }
            );

            if ($exists) {
                return $this;
            }
            $resourceNode->addResourceLink($resourceLink);
        } else {
            $this->addLink($resourceLink);
        }

        return $this;
    }

    public function getResourceLinkArray(): array
    {
        return $this->resourceLinkList;
    }

    public function setResourceLinkArray(array $links): static
    {
        $this->resourceLinkList = $links;

        return $this;
    }

    public function getResourceLinkListFromEntity(): ?array
    {
        return $this->resourceLinkListFromEntity;
    }

    public function setResourceLinkListFromEntity(): void
    {
        $resourceLinkList = [];
        if ($this->hasResourceNode()) {
            $resourceNode = $this->getResourceNode();
            $links = $resourceNode->getResourceLinks();
            foreach ($links as $link) {
                $resourceLinkList[] = [
                    'id' => $link->getId(),
                    'visibility' => $link->getVisibility(),
                    'visibilityName' => $link->getVisibilityName(),
                    'session' => $link->getSession(),
                    'course' => $link->getCourse(),
                    'group' => $link->getGroup(),
                    'userGroup' => $link->getUserGroup(),
                    'user' => $link->getUser(),
                ];
            }
        }
        $this->resourceLinkListFromEntity = $resourceLinkList;
    }

    public function hasParentResourceNode(): bool
    {
        return null !== $this->parentResourceNode && 0 !== $this->parentResourceNode;
    }

    public function getParentResourceNode(): ?int
    {
        return $this->parentResourceNode;
    }

    public function setParentResourceNode(?int $resourceNode): self
    {
        $this->parentResourceNode = $resourceNode;

        return $this;
    }

    public function hasUploadFile(): bool
    {
        return null !== $this->uploadFile;
    }

    public function getUploadFile(): ?UploadedFile
    {
        return $this->uploadFile;
    }

    public function setUploadFile(?UploadedFile $file): self
    {
        $this->uploadFile = $file;

        return $this;
    }

    #[Groups([
        'student_publication:read',
        'student_publication_comment:read',
    ])]
    public function getFirstResourceLink(): ?ResourceLink
    {
        $resourceNode = $this->getResourceNode();

        if ($resourceNode && $resourceNode->getResourceLinks()->count()) {
            $result = $resourceNode->getResourceLinks()->first();
            if ($result) {
                return $result;
            }
        }

        return null;
    }

    public function isVisible(Course $course, ?Session $session = null): bool
    {
        $link = $this->getFirstResourceLinkFromCourseSession($course, $session);

        if (null === $link && $this instanceof ResourceShowCourseResourcesInSessionInterface) {
            $link = $this->getFirstResourceLinkFromCourseSession($course);
        }

        if (null === $link) {
            return false;
        }

        return ResourceLink::VISIBILITY_PUBLISHED === $link->getVisibility();
    }

    public function getFirstResourceLinkFromCourseSession(Course $course, ?Session $session = null): ?ResourceLink
    {
        $resourceNode = $this->getResourceNode();
        if ($resourceNode && $resourceNode->getResourceLinks()->count() > 0) {
            $links = $resourceNode->getResourceLinks();
            $found = false;
            $link = null;
            foreach ($links as $link) {
                if ($session) {
                    if ($link->getCourse() === $course && $link->getSession() === $session) {
                        $found = true;

                        break;
                    }
                } else {
                    if ($link->getCourse() === $course) {
                        $found = true;

                        break;
                    }
                }
            }

            if ($found) {
                return $link;
            }
        }

        return null;
    }

    public function isUserSubscribedToResource(User $user): bool
    {
        $links = $this->getResourceNode()->getResourceLinks();

        $result = false;
        foreach ($links as $link) {
            if ($link->hasUser() && $link->getUser()->getId() === $user->getId()) {
                $result = true;

                break;
            }
        }

        return $result;
    }

    public function getUsersAndGroupSubscribedToResource(): array
    {
        $users = [];
        $groups = [];
        $everyone = false;
        $links = $this->getResourceNode()->getResourceLinks();
        foreach ($links as $link) {
            if ($link->hasUser()) {
                $users[] = $link->getUser()->getId();

                continue;
            }
            if ($link->hasGroup()) {
                $groups[] = $link->getGroup()->getIid();
            }
        }

        if (empty($users) && empty($groups)) {
            $everyone = true;
        }

        return [
            'everyone' => $everyone,
            'users' => $users,
            'groups' => $groups,
        ];
    }
}
