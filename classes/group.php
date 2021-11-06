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
    Kdelo\Course,
    CIBlockSection,
    CIBlockElement,
    CFile;

class Group
{
    private const SELECTED_FIELDS = array('ID', 'NAME', 'IBLOCK_SECTION_ID', 'PROPERTY_STUDENTS', 'PROPERTY_PREPOD');
    private const GRADES_TABLE_PROP_CODE = 'GRADES_TABLE';
    private const OPEN_FORM_FOR_PROP_CODE = 'OPEN_FORM_FOR';
    private const GRADES_TABLE_DATE_FORMAT = 'd.m.Y';
    
    protected $groupesIblockId;

    public $id; //сделать protected
    public $name;
    
    public $dateStart;
    public $dateStartTimestamp;
    public $dateFinish;
    public $dateFinishTimestamp;
    public $teachersID = array();
    public $studentsID = array();
    public $studentsCount;

    public $courseID;
    public $course;

    public $openFormForStudentsList = array();


    private $gradesTableFileId;
    public $gradesTableFileDate = ''; 
    public $gradesTableLink = '';

    static public function getGroupsFromIblock(array $filter) : \CIBlockResult
    {
        if(!$filter['IBLOCK_ID'])
            $filter['IBLOCK_ID'] = Settings::getGroupesIblockID();


        if(!$filter['ACTIVE'])
            $filter['ACTIVE'] = 'Y';
    
        return CIBlockElement::GetList(
            array("SORT"=>"ASC"),
            $filter,
            false,
            false,
            array('ID', 'NAME', 'IBLOCK_SECTION_ID', 'PROPERTY_STUDENTS', 'PROPERTY_PREPOD')
        );
    }

    static public function getGroupSectionFromIblock($sectionId) : \CIBlockResult
    {   
        return CIBlockSection::GetList(
            array('SORT'=>"ASC"),
            array(
                'ACTIVE' => 'Y',
                'ID'=>$sectionId, 
                'IBLOCK_ID' => Settings::getGroupesIblockID()
            ),
            false,
            array('ID', 'ACTIVE', 'NAME', 'UF_COURSE', 'UF_START', 'UF_END_DATE')
        );
    }

    static public function getTeachers($groupId) : ?array
    {
        if(is_numeric($groupId)){
            $teacherList = array();
            $obTeachers = CIBlockElement::GetProperty(
                Settings::getGroupesIblockID(),
                $groupId,
                array(),
                array('CODE' => 'PREPOD')
            );
            while($res = $obTeachers->fetch())
                $teacherList[] = $res['VALUE'];

            return $teacherList;
        }
    }

    static public function getStudents($groupId) : ?array
    {
        if(is_numeric($groupId)){
            $studentList = array();
            $obStudents = CIBlockElement::GetProperty(
                Settings::getGroupesIblockID(),
                $groupId,
                array(),
                array('CODE' => 'STUDENTS')
            );
            while($res = $obStudents->fetch())
                $studentList[] = $res['VALUE'];

            return $studentList;
        }
    }


    static public function checkCommentsEnable($groupId) : ?bool
    {
        if(is_numeric($groupId)){
            $obRes = CIBlockElement::GetProperty(
                Settings::getGroupesIblockID(),
                $groupId,
                array(),
                array('CODE' => 'IS_COMMENTED')
            );
            $res = $obRes ->fetch();
            return $res['VALUE_ENUM'] === 'Y';
        }
    }


    static public function checkCommentsOpenForStudent($groupId, $studentId) : ?bool
    {
        if(is_numeric($groupId) && is_numeric($studentId)){
            $obRes = CIBlockElement::GetProperty(
                Settings::getGroupesIblockID(),
                $groupId,
                array(),
                array('CODE' => 'CLOSE_COMMENTS_FOR')
            );
            $commentsIsClosed = false;
            while($res = $obRes ->fetch()){
                if($res['VALUE'] ==  $studentId){
                    $commentsIsClosed = true;
                    break;
                }
            }
            return !$commentsIsClosed;
        }
    }

    static public function checkIfActive($groupId) //: ?bool
    {
        
        if(is_numeric($groupId)){
            $result = false;
            $arElem = CIBlockElement::GetByID($groupId)->fetch();

            if($arElem['ACTIVE'] === 'Y' && $arElem['IBLOCK_SECTION_ID']){
                $obSection = CIBlockSection::GetList(
                    array(),
                    array('ID' => $arElem['IBLOCK_SECTION_ID'],  'IBLOCK_ID' =>Settings::getGroupesIblockID()),
                    false,
                    array('ID', 'UF_START', 'UF_END_DATE')
                );
                $arSection = $obSection->fetch();

                return strtotime($arSection['UF_START']) < time() && strtotime($arSection['UF_END_DATE']) > time();
            }
        }
    }
    
    public function __construct($id = 0)
    {
        if(is_numeric($id)){
            $this->id = $id;
            $this->groupesIblockId = Settings::getGroupesIblockID();
        }
    }

    /**
    *  Инициализация объекта группы - получение данных из инфоблока групп и запись их в объект, прикрепление объекта учебного курса
    */
    public function init() : Group
    {
        if($this->id){
            $this->initByArray($this->getGroupDataFromIblock());
          
            if($this->courseID){
                $course = new Course($this->courseID);
                $course->init();
                $this->course =  $course;
            }
        }
        return $this;
    }

    //Геттер id группы
    public function getId() : int
    {          
        return (int) $this->id;
    }

    //Сеттеры основных свойств объекта
    public function setLessons() : Group
    {
        if($this->course)
            $this->course->getLessons();
            
        return $this;
    }

    public function setName($name) : Group
    {
        $this->name = $name;
        return $this;
    }

    public function setCourseID($courseID) : Group
    {
        $this->courseID = $courseID;
        return $this;
    }

    public function setTeachersID($arTeachers) : Group
    {
        if(is_array($arTeachers))
            $this->teachersID = $arTeachers;
        elseif(is_numeric($arTeachers))
            $this->teachersID = array($arTeachers);
        return $this;
    }

    public function addTeacher($teacherID) : Group
    {
        $this->teachersID[] = $$teacherID; 
        return $this;
    }

    public function setStudentsID(array $arStudents) : Group
    {
        $this->studentsID = $arStudents;
        $this->studentsCount = count($arStudents);
        return $this;
    }

    public function addStudent($studentID) : Group
    {
        $this->studentsID[] = $studentID;
        $this->studentsCount = count($this->studentsID);
        return $this;
    }


    public function setDateStart(string $dateStart) : Group
    {
        $this->dateStart = $dateStart;
        $this->dateStartTimestamp = strtotime($dateStart);    
        return $this;
    }

    public function setDateFinish(string $dateFinish) : Group
    {
        $this->dateFinish = $dateFinish;
        $this->dateFinishTimestamp = strtotime($dateFinish);   
        return $this;
    }

    public function getName() : ?string
    {  
        return $this->name;
    }

 
    /**
    *  Запись свойств объекта группы по данным из массива
    */
    public function initByArray($arData)
    {
        if($arData['ID'])
            $this->id = $arData['ID'];

        if($arData['NAME'])
            $this->name = $arData['NAME'];  

        if($arData['COURSE_ID'])
            $this->courseID = $arData['COURSE_ID'];  

        if($arData['TEACHERS_ID'])
            $this->setTeachersID($arData['TEACHERS_ID']);

        if($arData['STUDENTS_ID']){
            $this->studentsID = $arData['STUDENTS_ID'];
            $this->studentsCount = count($this->studentsID);
        }
            
        if($arData['DATE_START']){
            $this->dateStart = $arData['DATE_START'];
            $this->dateStartTimestamp = strtotime($arData['DATE_START']);    
        }

        if($arData['DATE_FINISH']){
            $this->dateFinish = $arData['DATE_FINISH'];
            $this->dateFinishTimestamp = strtotime( $arData['DATE_FINISH']);   
        }

        return $this;
    }

    /**
    *  Получение массива данных о группе из инфоблока групп
    */
    protected function getGroupDataFromIblock() : array
    {
        $groupData = array();
        if($this->id){
            $obElem = CIBlockElement::GetList(
                array(),
                array(
                    'ACTIVE' => 'Y',
                    'IBLOCK_ID' => $this->groupesIblockId, 
                    'ID' => $this->id
                ),
                false,
                false,
                self::SELECTED_FIELDS
             );

            if($arGroup = $obElem->fetch()){
                $groupData['ID'] = $arGroup['ID'];
                $groupData['NAME'] = $arGroup['NAME'];
                $groupData['STUDENTS_ID'] = $arGroup['PROPERTY_STUDENTS_VALUE'];
                $groupData['TEACHERS_ID'] = $arGroup['PROPERTY_PREPOD_VALUE'];

                $obSection = CIBlockSection::GetList(
                    array(),
                    array('ID' => $arGroup['IBLOCK_SECTION_ID'],  'IBLOCK_ID' => $this->groupesIblockId),
                    false,
                    array('ID', 'NAME', 'UF_*')
                );
                if($arSection = $obSection->fetch()){
                    $groupData['COURSE_ID'] = $arSection['UF_COURSE'];
                    $groupData['DATE_START'] = $arSection['UF_START'];
                    $groupData['DATE_FINISH'] = $arSection['UF_END_DATE'];
                }               
            }
        }
        return $groupData;
    }

    function setLessonsOpenDate() : Group
    {
        $course = $this->course;
        if($course instanceof Course && $course->lessons){
            if($course->learnMode === 'AUTO' && $course->lessonAutoLoadDays && $this->dateStartTimestamp){
                $autoLoadPeriod = $course->lessonAutoLoadDays*60*60*24;
                foreach($course->lessons as $index => $obLesson)
                    $obLesson->setOpenTimestamp($this->dateStartTimestamp + $index*$autoLoadPeriod);
            }else{
                foreach($course->lessons as $index => $obLesson)
                    $obLesson->setOpenTimestamp($this->dateStartTimestamp);
            }
        }
        return $this;
    }

 

    /**
    *  Запись в объект данных файла таблицы успеваемости - его id и ссылки
    */
    protected function setGradesTable() : bool
    {
        if($this->id){
            $res = CIBlockElement::GetProperty(
                $this->groupesIblockId,
                $this->id,
                array (),
                array ('CODE' => self::GRADES_TABLE_PROP_CODE)
            );
            $arFile = $res->fetch();

            if($arFile['VALUE']){
                $this->gradesTableFileId = $arFile['VALUE'];
                $this->gradesTableLink =  CFile::GetPath($arFile['VALUE']); 
                $this->setGradesTableFileDate();
            }
        }
        
        return (bool) $this->gradesTableLink;
    }

    /**
    *  Запись в объект даты загрузки файла таблицы успеваемости
    */
    protected function setGradesTableFileDate() : bool
    {
        if($this->gradesTableFileId){
           $arFile = CFile::GetByID($this->gradesTableFileId)->fetch();
           $this->gradesTableFileDate = date(
                self::GRADES_TABLE_DATE_FORMAT, 
                $arFile['TIMESTAMP_X']->getTimestamp());
        }
        return (bool) $this->gradesTableFileDate;
    }

    /**
    *  Геттер ссылки на файл таблицы успеваемости
    */
    public function getGradesTableLink() : string
    {
        if(!$this->gradesTableLink)
            $this->setGradesTable();
        
        return $this->gradesTableLink;
    }

    /**
    *  Геттер даты загрузки файла таблицы успеваемости
    */
    public function getGradesTableDate() : string
    {
        if(!$this->gradesTableFileDate)
            $this->setGradesTableFileDate();

        return $this->gradesTableFileDate;
    }

    
    /**
    *  Запись в объект студентов группы для которых открыта анкета
    */

    public function setOpenFormForStudents() : Group
    {
        if($this->id){
            $res = CIBlockElement::GetProperty(
                $this->groupesIblockId,
                $this->id,
                array (),
                array ('CODE' => self::OPEN_FORM_FOR_PROP_CODE)
            );
            while($arFile = $res->fetch())
                $this->openFormForStudentsList[] = $arFile['VALUE'];                
        }
            
        return $this;
    }

    public function getOpenFormForStudents(bool $loadFromBaseIfEmpty = true) : array
    {       
        if($loadFromBaseIfEmpty && empty($this->openFormForStudentsList))
            $this->setOpenFormForStudents();

        return $this->openFormForStudentsList;
    }

    public function clearOpenFormForStudents() : Group
    {       
        $this->openFormForStudentsList = array();
        return $this;
    }

    /**
    *  Запись перечня студентов группы для которых открыта анкета в базу
    */
    
    protected function saveOpenFormForStudentsToDb() : Group
    {
        if($this->id){
            $res = CIBlockElement::SetPropertyValues(
                $this->id,
                $this->groupesIblockId,
                $this->openFormForStudentsList,
                self::OPEN_FORM_FOR_PROP_CODE
            );
        }
            
        return $this;
    }

    /**
    *  Запись и удаление id пользователя в список студентов, для которых открыта анкета
    */
    
    public function addUserToOpenFormList($studentId) : Group
    {
        if(!in_array($studentId, $this->openFormForStudentsList))
            $this->openFormForStudentsList[] = $studentId;

        return $this;
    }

    public function delUserFromOpenFormList($studentId) : Group
    {
        if(is_numeric($studentId) && !empty($this->openFormForStudentsList)){
            $index = array_search($studentId, $this->openFormForStudentsList);
            if($index !== false){
                unset($this->openFormForStudentsList[$index]);
            }
        }
        return $this;
    }

    public function openFormForStudent($studentId) : Group
    {
        return $this->setOpenFormForStudents()->addUserToOpenFormList($studentId)->saveOpenFormForStudentsToDb();
    }

    public function closeFormForStudent($studentId) : Group
    {
        return $this->setOpenFormForStudents()->delUserFromOpenFormList($studentId)->saveOpenFormForStudentsToDb();
    }
    
}