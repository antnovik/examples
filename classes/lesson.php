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
    CIBlockElement,
    CFile;



class Lesson
{
    public $test;
    
    private $id;
    public $order;
    public $name;
    public $addName;
    public $openTimestamp;
    

    public $text;

    private $passedBy = array();
    private $openFor = array();

    private $linkedTestId;

    public $video = array();
    public $videoCount = 0;

    public $files = array();
    public $filesCount = 0;

    public $commentsCount = 0;

    public $url; //убрать??

    
   
    public function __construct($lessonId)
    {
        if(is_numeric($lessonId))
            $this->id = (int) $lessonId;
    }

    public function getId() : int
    {
        return $this->id;
    }

    public function getPassedBy() : array
    {
        return $this->passedBy;
    }

    public function getOpenFor() : array
    {
        return $this->openFor;
    }

    public function getTestId() : ?int
    {
        return $this->linkedTestId;
    }

    public function init() : Lesson
    {
        if($this->id){

            $select =  array('NAME', 'ID', 'SORT', 'DETAIL_TEXT', 'PROPERTY_ALT_NAME', 'PROPERTY_VIDEO', 'PROPERTY_VIDEO_V', 'PROPERTY_FILES');

            $obLesson = CIBlockElement::GetList(
                array(),
                array(
                    'ACTIVE' => 'Y',
                    'ID' => $this->id,
                    'IBLOCK_ID' => Settings::getCoursesIblockID()
                ),
                false,
                false,
                $select
            );

            $arLesson = $obLesson->fetch();

            $this->text = $arLesson['DETAIL_TEXT'];
            $this->order = $arLesson['SORT'];
            $this->name = $arLesson['NAME'];
            $this->addName = $arLesson['PROPERTY_ALT_NAME_VALUE'];
            

            foreach($arLesson['PROPERTY_VIDEO_VALUE'] as $index => $videoCode)
                $this->addVideo($videoCode, $arLesson['PROPERTY_VIDEO_DESCRIPTION'][$index]);

            foreach($arLesson['PROPERTY_VIDEO_V_VALUE'] as $index => $videoCode)
                $this->addVideo($videoCode, $arLesson['PROPERTY_VIDEO_V_DESCRIPTION'][$index]);

            foreach($arLesson['PROPERTY_FILES_VALUE'] as $fileId)
                $this->addFileById($fileId);
        }

        
        return $this;
    }


    public function setOrder(string $order) : Lesson
    {
        if(is_numeric($order)){
            $this->order = $order;
        }
        return $this;
    }

    public function setName(string $name) : Lesson
    {
        $this->name = $name;
        return $this;
    }

    public function setOpenTimestamp($timestamp) : Lesson
    {
        if(is_numeric($timestamp))
            $this->openTimestamp = (int) $timestamp;
        return $this;
    }

    public function setUrl(string $url) : Lesson
    {
        $this->url = $url;
        return $this;
    }

    public function addVideo(string $videoCode, string $videoDesc = '') : Lesson
    {
        $this->video[] = array(
            'code' => $videoCode,
            'desciption' => $videoDesc
        );
        return $this;
    }

    public function countVideo() : Lesson
    {
        $this->videoCount = count($this->video);
        return $this;
    }

    private function addFile(array $arFile) : Lesson
    {
        $this->files[] = $file;
        return $this;
    }

    private function addFileById($fileId) : Lesson
    {
        if(is_numeric($fileId)){
            $arFile = CFile::GetFileArray($fileId);
            $this->files[] = array(
                'PATH' => $arFile['SRC'],
                'ID' => $fileId,
                'SIZE' => $arFile['FILE_SIZE'],
                'SIZE_TEXT' => Helper::formatFileSize($arFile['FILE_SIZE']),
                'FILE_NAME' =>  $arFile['FILE_NAME'],
                'NAME' =>  $arFile['ORIGINAL_NAME']
            );
        }
        return $this;
    }

    public function countFiles() : Lesson
    {
        $this->filesCount = count( $this->files);
        return $this;
    }

    public function editByArray(array $arData) : Lesson
    {
        if($arData['ORDER'])
            $this->order = $arData['ORDER'];

        if($arData['NAME'])
            $this->name = $arData['NAME'];    

        if($arData['URL'])
            $this->url = $arData['URL'];   

        if($arData['VIDEO_COUNT'])
            $this->videoCount = $arData['VIDEO_COUNT'];  

        if($arData['FILES_COUNT'])
            $this->filesCount = $arData['FILES_COUNT']; 

        if($arData['PASSED_BY_STUDENTS'])
            $this->passedBy = $arData['PASSED_BY_STUDENTS'];

        if($arData['OPEN_FOR_STUDENTS'])
            $this->openFor = $arData['OPEN_FOR_STUDENTS']; 

        if($arData['LINKED_TEST'])
            $this->linkedTestId = (int) $arData['LINKED_TEST'];
            
        return $this;
    }

    /*
    * Группа методов для переключения урока в статус пройден/не пройдет
    */

    public function setPassedBy() : Lesson
    {
        if($this->id){
            $this->passedBy = array();
            $arFields =  Settings::getLessonFields();

            $obRes = CIBlockElement::GetProperty(
                Settings::getCoursesIblockID(),
                $this->id,
                array(),
                array ('CODE' => $arFields['finished-students'])
            );

            while($arRes =  $obRes->fetch()){
                $this->passedBy[] = $arRes['VALUE'];
            }
        }          
        return $this;
    }

    public function checkIfPassedByStudent($studentId) : bool
    {
        if(!empty($this->passedBy) && is_numeric($studentId)){
            return in_array($studentId, $this->passedBy);
        }else {
            return false;
        }
    }

    //private function savePassedByInIblock() : bool
    public function savePassedByInIblock() : bool
    {
        $arFields =  Settings::getLessonFields();
        return CIBlockElement::SetPropertyValueCode(
            $this->id, 
            $arFields['finished-students'], 
            $this->passedBy
        );
    }

    public function finishForStudent($studendId, bool $reset = true) : bool
    {
        $result = false;
        if(is_numeric($studendId)){
            if($reset)
                $this->setPassedBy();
            if(!in_array($studendId, $this->passedBy)){
                $this->passedBy[] = $studendId;
                $result = $this->savePassedByInIblock();
            }
        }
        return $result;
    }

    public function startForStudent($studendId, bool $reset = true) : bool
    {
        $result = false;
        if(is_numeric($studendId)){
            if($reset)
                $this->setPassedBy();
            foreach($this->passedBy as $index => $userId){
                if($userId == $studendId){
                    unset($this->passedBy[$index]);
                    $result = $this->savePassedByInIblock();
                    break;
                }
            }
        }
        return $result;
    }

    /*
    * Группа методов для открытия закрытия урока для студента
    */

    public function setOpenFor() : Lesson
    {
        if($this->id){
            $this->openFor = array();
            $arFields =  Settings::getLessonFields();

            $obRes = CIBlockElement::GetProperty(
                Settings::getCoursesIblockID(),
                $this->id,
                array(),
                array ('CODE' => $arFields['open-for-students'])
            );

            while($arRes =  $obRes->fetch()){
                $this->openFor[] = $arRes['VALUE'];
            }
        }          
        return $this;
    }

    public function checkIfOpenForStudent($studentId) : bool
    {
        if(!empty($this->openFor) && is_numeric($studentId)){
            return in_array($studentId, $this->openFor);
        }else {
            return false;
        }
    }


    private function saveOpenForInIblock() : bool
    {
        $arFields =  Settings::getLessonFields();
        return CIBlockElement::SetPropertyValueCode(
            $this->id, 
            $arFields['open-for-students'], 
            $this->openFor
        );
    }

    public function openForStudent($studendId, bool $reset = true) : bool
    {
        $result = false;
        if(is_numeric($studendId)){
            if($reset)
                $this->setOpenFor();
            if(!in_array($studendId, $this->openFor)){
                $this->openFor[] = $studendId;
                $result = $this->saveOpenForInIblock();
            }
        }
        return $result;
    }

    public function closeForStudent($studendId, bool $reset = true) : bool
    {
        $result = false;
        if(is_numeric($studendId)){
            if($reset)
                $this->setOpenFor();
            foreach($this->openFor as $index => $userId){
                if($userId == $studendId){
                    unset($this->openFor[$index]);
                    $result = $this->saveOpenForInIblock();
                    break;
                }
            }
        }
        return $result;
    }

}

