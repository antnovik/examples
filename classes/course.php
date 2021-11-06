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
use Bitrix\Main\Loader,
    Kdelo\Settings,
    Kdelo\Helper,
    Kdelo\Lesson,
    CIBlockSection,
    CIBlockElement,
    CUserFieldEnum,
    CFile;
    

Loader::includeModule("highloadblock"); 
    
use Bitrix\Highloadblock as HL, 
    Bitrix\Main\Entity;

class Course
{  
    public $id;
    public $name;

    public $learnMode;
    public $learnModeName;
    public $lessonAutoLoadDays;

    public $descriptionText;
    public $descriptionVideo = array();

    public $addLinks = array();
    public $addFiles = array();
    public $addDesc;

    public $lessons = array();

    private $iblockID;

    private $formId;
    public $isFormActive = false;

    public $errors;

    public $test;

    static public function getTree() : \CIBlockResult
    {
        return CIBlockSection::GetTreeList(
            array("IBLOCK_ID"=>Settings::getCoursesIblockID()), 
            array("ID", "NAME", "DEPTH_LEVEL")
        );
    }

    static public function getCourseFromIblock($courseId) : \CIBlockResult
    {
        return CIBlockSection::getList(
            array('SORY' => 'ASC'),
            array(
                'ID' => $courseId,
                'IBLOCK_ID' => Settings::getCoursesIblockID(),
                'ACTIVE' => 'Y'
            ),
            false,
            array('ID', 'NAME', 'UF_LEARN_MODE', 'UF_PARENT')
        );
    }

    static public function getLessonsListFromIblock($courseId) : \CIBlockResult
    {
        return CIBlockElement::GetList(
            array("SORT"=>"ASC"),
            array(
                'ACTIVE' => 'Y',
                'SECTION_ID' => $courseId, 
                'IBLOCK_ID' => Settings::getCoursesIblockID()
            ),
            false,
            false,
            array('SORT', 'NAME', 'ID','DETAIL_PAGE_URL', 'PROPERTY_FIN_USERS')
        );
    }


    public function __construct($courseId = 0)
    {
        if(is_numeric($courseId)){
            $this->id = (int) $courseId;
            $this->iblockID =  Settings::getCoursesIblockID();
        }
    }

    public function init() : bool 
    {
        if($this->id > 0){
            $obSection = CIBlockSection::getList(
                array('SORT' => 'ASC'),
                array(
                    'ID' => $this->id,
                    'ACTIVE' => 'Y',
                    'IBLOCK_ID' =>  $this->iblockID//$this->settings->coursesIblockID,
                ),
                false,
                array('ID', 'NAME', 'UF_LEARN_MODE', 'UF_LESSON_AUTO_LOAD_TIME')
            );

            if($section = $obSection->fetch()){
                $this->name =  $section['NAME'];

                if($section['UF_LEARN_MODE']){
                    $arLearMode = CUserFieldEnum::GetList(array(), array('ID' => $section['UF_LEARN_MODE']))->fetch();
                    $this->learnMode =  $arLearMode['XML_ID'];
                    $this->learnModeName =  $arLearMode['VALUE'];
                }
                $this->lessonAutoLoadDays = $section['UF_LESSON_AUTO_LOAD_TIME'];
               
                return true;
            } else{
                return false;
            }
        } else{
            return false;
        }
    }

    public function getId() : int
    {
        return $this->id;
    }

    public function setName(string $name) : Course
    {
        $this->name = $name;
        return $this;
    }

    public function getName() : ?string
    {
        return $this->name;
    }

    private function setLessons() : bool
    {
        $select = array('SORT', 'NAME', 'ID','DETAIL_PAGE_URL', 'PROPERTY_FIN_USERS', 'PROPERTY_VIDEO', 'PROPERTY_VIDEO_V', 'PROPERTY_FILES', 'PROPERTY_FIN_USERS', 'PROPERTY_OPEN_FOR', 'PROPERTY_LINKED_TEST');
       
        if($this->id > 0){  
            $obLessons = CIBlockElement::GetList(
                array("SORT"=>"ASC"),
                array(
                    'ACTIVE' => 'Y',
                    'SECTION_ID' => $this->id, 
                    'IBLOCK_ID' => $this->iblockID//$this->settings->coursesIblockID
                ),
                false,
                false,
                $select
            );

            $lessonsList = array();
            while($arLesson = $obLessons->getNext()){
                $LESSON = new Lesson($arLesson['ID']);

                $arData = array(
                    'ORDER' => $arLesson['SORT'],
                    'NAME' => $arLesson['NAME'],
                    'VIDEO_COUNT' => count($arLesson['PROPERTY_VIDEO_VALUE']) + count($arLesson['PROPERTY_VIDEO_V_VALUE']),
                    'FILES_COUNT' => count($arLesson['PROPERTY_FILES_VALUE']),
                    'PASSED_BY_STUDENTS' => $arLesson['PROPERTY_FIN_USERS_VALUE'],
                    'OPEN_FOR_STUDENTS' =>  $arLesson['PROPERTY_OPEN_FOR_VALUE'],
                    'LINKED_TEST' =>  $arLesson['PROPERTY_LINKED_TEST_VALUE'],
                );
                $lessonsList[] =  $LESSON->editByArray($arData);
            
            }
            if($lessonsList){
                $this->lessons =  $lessonsList;
                return true;
            }else{
                return false;
            }
            return true;
        } else{
            return false;
        }
  
    }

    public function getLessons() : array
    {
        if(!$this->lessons)
            $this->setLessons();

        return $this->lessons;
    }

    public function getFormId() : ?int
    {
        return $this->formId;
    }

    public function setAddInfo(bool $getDesc = true, bool $getLinks = true, bool $getFiles = true) : Course
    {      
        if(is_numeric($this->id)){
            $select = array('ID', 'NAME');

            $arFields =  array_merge(
                Settings::getUsefullLinksFields(),
                Settings::getCourseAddInfoFields()
            );

            if($getDesc)
                $select[] =  $arFields['description'];
            
            if($getLinks)
                $select[] =  $arFields['links'];
        
            if($getFiles)
                $select[] =  $arFields['files'];
  
            $obSection = CIBlockSection::getList(
                array('SORT' => 'ASC'),
                array(
                    'ID' => $this->id,
                    'ACTIVE' => 'Y',
                    'IBLOCK_ID' =>   $this->iblockID//$this->settings->coursesIblockID,

                ),
                false,
                $select
            );
            $arrSection = $obSection->fetch();
            
            //Запись в объект текста дополнительных материалов
            if($arrSection[$arFields['description']]){
                $this->addDesc = $arrSection[$arFields['description']];
            }

            //Запись в объект файлов для скачивания
            if($arrSection[$arFields['files']]){
                $obFiles = CFile::GetList(
                   array(),
                   array('@ID' => implode(',', $arrSection[$arFields['files']]))
                );

                while($arFile = $obFiles->fetch()){
                    $arFile['FILE_SIZE_TEXT'] = Helper::formatFileSize($arFile['FILE_SIZE']);
                    $arFile['FILE_PATH'] = CFile::GetPath($arFile['ID']);
                    $this->addFiles[] = $arFile;
                }
            }

            //Запись в объект полезных ссылок
            if($arrSection[$arFields['links']]){
                $hlbl = Settings::getUsefullLinksHlblockID();
                $hlblock = HL\HighloadBlockTable::getById($hlbl)->fetch(); 
                $entity = HL\HighloadBlockTable::compileEntity($hlblock);
                $entityClass = $entity->getDataClass();

                $obLinks = $entityClass::getList(
                    array(
                    'filter' => array('ID' => $arrSection[$arFields['links']])
                    )
                );

                while($arLink =  $obLinks->fetch()){
                    $this->addLinks[$arLink['ID']] =  array(
                        'LINK' => $arLink[$arFields['link']],
                        'TEXT' => $arLink[$arFields['link-text']]
                    );   
                }   
            }
        }
        return $this;
    }


    public function setDescription() : Course
    {
        if(is_numeric($this->id)){
            $arFields = Settings::getCourseDescFields();
            //$select = array('ID', 'NAME');
            $select = array(
                'ID',
                $arFields['description'],
                $arFields['video']
            );

            $obSection = CIBlockSection::getList(
                array('SORT' => 'ASC'),
                array(
                    'ID' => $this->id,
                    'ACTIVE' => 'Y',
                    'IBLOCK_ID' =>   $this->iblockID//$this->settings->coursesIblockID,
                ),
                false,
                $select
            );

            $arrSection = $obSection->fetch();

            //Запись в объект описания курса
            if($arrSection[$arFields['description']])
                $this->descriptionText = $arrSection[$arFields['description']];

            //Запись в объект видео к описанию курса
            if($arrSection[$arFields['video']])
               $this->descriptionVideo = $arrSection[$arFields['video']];
        }
        return $this;
    }

    public function setForm() : Course
    {  
        if(is_numeric($this->id)){
            $fieldCodes = Settings::getCourseFormFields();
            $select = array('ID',  $fieldCodes['form-id'], $fieldCodes['form-active']);

            $obSection = CIBlockSection::getList(
                array('SORT' => 'ASC'),
                array(
                    'ID' => $this->id,
                    'ACTIVE' => 'Y',
                    'IBLOCK_ID' =>   $this->iblockID //$this->settings->coursesIblockID,
                ),
                false,
                $select
            );

            $arrSection = $obSection->fetch();
            if($arrSection[$fieldCodes['form-id']]){
                $this->formId = (int) $arrSection[$fieldCodes['form-id']];
                $this->isFormActive = (bool) $arrSection[$fieldCodes['form-active']];
            }

            return $this;
        }
    } 

}