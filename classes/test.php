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
//use Kdelo\DataBaseSettings,
    //Kdelo\Task,

use Kdelo\Settings,
    Kdelo\TestSettings,
    Kdelo\TaskManager,
    CIBlockSection,
    CIBlockElement,
    Bitrix\Main\Loader;

Loader::includeModule("highloadblock"); 
    
use Bitrix\Highloadblock as HL, 
    Bitrix\Main\Entity;

class Test
{
    public $test;

    const MESS_NO_LESSONS = 'Урок не выбран';
    const SORT_DEFAULT = 500;

    public $id;
    public $courseID;
    public $lessonID;
    public $name;
    public $description = '';
    public $sort;
    public $isEmpty;
    public $isActive;

    public $errors = array();

    public $taskList = array();

    private $DB;
    private $taskManager;

    //параметры hl блока списка тестов (для привязки к курсам)
    private $hlTestList;
    private $hlTestListFields;
    private $hlTestListTable;
    private $hlTestListEntityClass;




    //private $workTable;

    static public function getFieldsCodes()
    {
        return TestSettings::getTableFields();
    }

    static public function getList(array $sort = array(), array $filter = array()) : array
    {
        $hlbl = TestSettings::getHLblockId();
        $hlblock = HL\HighloadBlockTable::getById($hlbl)->fetch(); 
        $entity = HL\HighloadBlockTable::compileEntity($hlblock);
        $entityClass = $entity->getDataClass();

        if(!$sort)
            $sort = array("ID"=>"asc");        

        $result = $entityClass::getList(
            array(
                "select" => array("*"),
                "order" => $sort,
                'filter' => $filter
            )
        );

        $testList = array();
        $courseList = array();
        $lessonList = array();


        $fieldCodes = TestSettings::getTableFields();

        while($test = $result->Fetch()){
            $testData = array(
                'ID' => $test['ID'],
                'NAME' => $test[$fieldCodes['name']],
                'DESCRIPTION' =>  $test[$fieldCodes['description']],
                'DATE' =>  $test[$fieldCodes['date']],
                'ACTIVE' => (bool)$test[$fieldCodes['active']],
                'HAS_TASKS' => !(bool)$test[$fieldCodes['empty']],
                'COURSE_ID' => $test[$fieldCodes['course']],
                'LESSON_ID' => $test[$fieldCodes['lesson']],
                'SORT' =>$test[$fieldCodes['sorting']],
            );
            
            $courseList[] = $test[$fieldCodes['course']];
            if($test[$fieldCodes['lesson']])
                $lessonList[] = $test[$fieldCodes['lesson']];

            $testList[] = $testData;
        }

        $obSection = CIBlockSection::getList(
            array('SORY' => 'ASC'),
            array(
                'ID' =>  $courseList,
                'IBLOCK_ID' => Settings::getCoursesIblockID(),
            ),
            false,
            array('ID', 'NAME')
        );

        $coursesNames = array();
        while($el = $obSection->fetch()){
            $coursesNames[$el['ID']] = $el['NAME'];
        }

        $obLessons = CIBlockElement::GetList(
            array("SORT"=>"ASC"),
            array(
                'ID' => $lessonList, 
                'IBLOCK_ID' => Settings::getCoursesIblockID()
            ),
            false,
            false,
            array('NAME', 'ID')
        );
        $lessonsNames = array();
        while($el = $obLessons->fetch()){
            $lessonsNames[$el['ID']] = $el['NAME'];
        }

        foreach($testList as &$test){
            $test['COURSE_NAME'] =  $coursesNames[$test['COURSE_ID']];
            if($test['LESSON_ID'])
                $test['LESSON_NAME'] =  $lessonsNames[$test['LESSON_ID']];
            else
                $test['LESSON_NAME'] =  self::MESS_NO_LESSONS;
        }

        return  $testList;
    }

    static public function getById($id) : array
    {
        $arResult = array();
        if(is_numeric($id)){
            $arResult = self::getList(array(),array('ID' => $id));
            if(!empty($arResult))
                $arResult = array_shift($arResult);
        }
        return $arResult;
    }

    //УБРАТЬ??
    static function getTasksById($testId) : array
    {
        $result = array();
        if(is_numeric($testId)){
            $table = TestSettings::getTasksTableType();
            $table.= $testId;
            $query = "SELECT * FROM $table";
            global $DB;
            $res = $DB->Query($query);

            while($arTask = $res->fetch()){
                $result[] = $arTask;
            }
        }
        return  $result;
    }

    public function __construct()
    {
        global $DB;
        $this->DB = $DB;

        $this->hlTestList = TestSettings::getHLblockId();
        $this->hlTestListFields = TestSettings::getTableFields();
        $this->hlTestListTable = TestSettings::getHLblockTable();

        $this->workTestListTable =  $this->hlTestListTable;
    }

    private function getHlEntityClass() : string
    {
        if($this->hlTestListEntityClass)
            return $this->hlTestListEntityClass;
        else
            return $this->setHlEntityClass();
    }

    private function setHlEntityClass() : string
    {
        $hlblock = HL\HighloadBlockTable::getById($this->hlTestList)->fetch();
        $entity = HL\HighloadBlockTable::compileEntity($hlblock);
        $this->hlTestListEntityClass = $entity->getDataClass();
        return $this->hlTestListEntityClass;
    }

    public function initByID($id) : bool
    {
        $noErrors = false;
        if(is_numeric($id)){

            $entityClass = $this->getHlEntityClass();

            $res = $entityClass::getList(
                array(
                    "select" => array("*"),
                    "order" => array(),
                    'filter' => array('ID' => $id)
                )
            );

            $arTest = $res->fetch();
            if($arTest){
                $fieldCodes = $this->hlTestListFields;
                $this->id = $arTest['ID'];
                $this->setName($arTest[$fieldCodes['name']])
                     ->setDescription($arTest[$fieldCodes['description']])
                     ->setCourseID($arTest[$fieldCodes['course']])
                     ->setLessonID($arTest[$fieldCodes['lesson']])
                     ->setSort($arTest[$fieldCodes['sorting']]);   
                $this->isActive = (bool) $arTest[$fieldCodes['active']];
                $noErrors = true;
            } else{
                $this->errors[time()] = array('initById' => 'no test with ID '.$id);
            }
        } else{
            $this->errors[time()] = array('initById' => 'not numeric test ID');
        }
        return $noErrors;
    }
    
    public function setCourseID($courseID) : Test
    {
        if(is_numeric($courseID))
            $this->courseID = $courseID;
        else
            $this->errors[time()] = array('setCourseID' => 'not numeric courseID');
        return $this;
    }

    public function setLessonID($lessonID) : Test
    {
        if(is_numeric($lessonID))
            $this->lessonID = $lessonID;
        else
            $this->errors[time()] = array('setLessonID' => 'not numeric lessonID');
        return $this;
    }

    public function setName(string $name) : Test
    {
        if($name)
            $this->name = $name;
        else
            $this->errors[time()] = array('setName' => 'empty name');
        return $this;
    }

    public function setDescription(string $description) : Test
    {
        if($description)
            $this->description = $description;
        else
            $this->errors[time()] = array('setDescription' => 'empty description');
        return $this;
    }

    public function setSort($sort) : Test
    {
        if(is_numeric($sort))
            $this->sort = $sort;
        else
            $this->errors[time()] = array('setSort' => 'not numeric sort');
        return $this;
    }

    public function setIsEmpty(bool $isEmpty) : Test
    {
        $this->isEmpty = $isEmpty;
        return $this;
    }


    public function checkHasTask($id = null) : bool
    {
        if(!$id)
            $id = $this->id;

        if(is_numeric($id)){

            $entityClass = $this->getHlEntityClass();
            $fieldCodes = $this->hlTestListFields;

            $result = $entityClass::getList(
                array(
                    "select" => array($fieldCodes['empty']),
                    'filter' => array('ID' => $id)
                )
            );

            $result = $result->fetch();
            if(isset($result[$fieldCodes['empty']])){
                return !$result[$fieldCodes['empty']];
            }
        }else {
            $this->errors[time()] = array('checkHasTask' => 'test id is not set');
            return  false;
        }
    }

    public function regist(string $method = 'hlblock') : bool
    {
        $noErrors = true;
        if(!$this->courseID){
            $this->errors[time()] = array('regist' => 'courseID is not set');
            $noErrors = false;
        }

        if(!$this->name){
            $this->errors[time()] = array('regist' => 'name is not set');
            $noErrors = false;
        }

        if($noErrors){ 
            switch ($method){
                case 'hlblock':
                    $noErrors = $this->registByHLblock();
                    break;
                case 'mysql':
                    $noErrors = $this->registBySQL();
                    break;
            }
            return $noErrors;
        } else{
            return false;
        }
    }

    //Резервный метод регистрации теста путем прямой записи в базу (не используется) 
    private function registBySQL() : bool
    {
        $table = $this->workTestListTable;
        $tableFieldCourse = $this->hlTestListFields['course'];
        $tableFieldName = $this->hlTestListFields['name'];
        $tableFieldTime = $this->hlTestListFields['timestamp'];
        $tableFieldActive = $this->hlTestListFields['active'];

        $courseId = $this->courseID;
        $testName = $this->name;
        $time = time();

        $query = "INSERT INTO $table ($tableFieldCourse,  $tableFieldName, $tableFieldActive, $tableFieldTime) VALUES ('$courseId', '$testName', 'N', $time)";
        $res = $this->DB->Query($query);

        if($res){
            return true;
        } else{
            $this->errors[time()] = array('registBySQL' => 'db connection error');
            return false;
        }           
    }
    
    private function registByHLblock() : bool
    {       
        $entityClass = $this->getHlEntityClass();
        $fieldCodes = $this->hlTestListFields;

        $data = array(
            $fieldCodes['name']     => $this->name,
            $fieldCodes['course']   =>  $this->courseID,
            $fieldCodes['description'] => $this->description,
            $fieldCodes['timestamp']    => time(),
            $fieldCodes['date']     => date('Y-m-d H:i:s'),
            $fieldCodes['active']   => false,
            $fieldCodes['empty']    => true,
            $fieldCodes['lesson']   =>  $this->lessonID,
            $fieldCodes['sorting']  => self::SORT_DEFAULT
        );
        
        $res = $entityClass::add($data);
        if($res){
            $this->id = $res->getId();
            $adminData = array(
                $fieldCodes['admin-name']  => $this->name,
                $fieldCodes['admin-xml-id']  => $this->id
            );
            $resUpdate = $entityClass::update($this->id, $adminData);
            return $resUpdate->isSuccess();
        } else{
            $this->errors[time()] = array('registByHLblock' => 'db connection error');
            return false;
        }
    }



    public function save(string $method = 'hlblock') : bool
    {
        $noErrors = false;
        if($method == 'hlblock')
            $noErrors = $this->saveByHLblock();
        
        return $noErrors;
    }

    private function saveByHLblock() : bool
    {
        $noErrors = false;
        if($this->id){
            $entityClass = $this->getHlEntityClass();
            $fieldCodes = $this->hlTestListFields;
            $data = array();

            if($this->name)
                $data[$fieldCodes['name']] = $this->name;

            if($this->description)
                $data[$fieldCodes['description']] = $this->description;

            if($this->courseID && is_numeric($this->courseID))
                $data[$fieldCodes['course']] = $this->courseID;

            if($this->lessonID && is_numeric($this->lessonID))
                $data[$fieldCodes['lesson']] = $this->lessonID;

            if($this->sort && is_numeric($this->sort))
                $data[$fieldCodes['sorting']] = $this->sort;
            
            if(!is_null($this->isActive))
                $data[$fieldCodes['active']] = (bool) $this->isActive;

            if(!is_null($this->isEmpty))
                $data[$fieldCodes['empty']] = (bool) $this->isEmpty;

            $res = $entityClass::update($this->id, $data);

            if($res->isSuccess())
                $noErrors = true;
            else 
                $this->errors[time()] = array('saveByHLblock' => 'HL update errors: '.json_encode($res->getErrorMessages()));
 
        }else{
            $this->errors[time()] = array('saveByHLblock' => 'test ID is not set');
        }
        return $noErrors;
    }

    public function deleteFromDB($testId = null) : bool
    {
        if(is_null($testId))
            $testId = $this->id;
        
        if($testId && is_numeric($testId)){
            $table = $this->workTestListTable;
            $query = "DELETE $table WHERE ID = $testId";
            $res = $this->DB->Query($query);
            if($res){
                return true;
            } else{
                $this->errors[time()] = array('deleteFromDB' => 'db connection error');
                return false;
            }
        } else{
            $this->errors[time()] = array('deleteFromDB' => 'is gone or not numeric testId');
            return false;
        }
    }


    public function initTaskManager() : TaskManager
    {
        $this->taskManager = new TaskManager($this->id);
        return  $this->taskManager;
    }
}