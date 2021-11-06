<?php
/**
 * Bitrix vars
 * @global CUser $USER
 * @global CMain $APPLICATION
 * @global CDatabase $DB
 * @global string $by
 * @global string $order
 */

namespace Kdelo;
use Kdelo\Settings,
    Kdelo\Helper,
    Kdelo\CommentsTree,
    CIBlockElement,
    CFIle;

class GroupNewsItem
{
    private $id;
    private $title;
    private $announce;
    private $authorId;
    private $text;
    private $groupId;
    private $photoList;
    private $files;
    public $isActive = true;

    public function __get($propertyName)
    {
        return $this->$propertyName;
    }

    public function setId($id) : GroupNewsItem
    {
        if(is_numeric($id))
            $this->id = (int) $id;
        return $this;
    }

    public function setTitle(string $title) : GroupNewsItem
    {
        if(!empty($title))
            $this->title = $title;
        return $this;
    }

    public function setAnnounce(string $announce) : GroupNewsItem
    {
        if(!empty($announce))
            $this->announce = $announce;
        return $this;
    }

    public function setText(string $text) : GroupNewsItem
    {
        if(!empty($text))
            $this->text = $text;
        return $this;
    }

    public function setAuthorId($userId) : GroupNewsItem
    {
        if(is_numeric($userId))
            $this->authorId = $userId;
        return $this;
    }

    public function setGroupId($groupId) : GroupNewsItem
    {
        if(is_numeric($groupId))
            $this->groupId = $groupId;
        return $this;
    }

    public function setPhotoList(array $photoList) : GroupNewsItem
    {
        $this->photoList = $photoList;
        return $this;
    }

    public function setFiles(array $files) : GroupNewsItem
    {
        $this->files = $files;
        return $this;
    }
}