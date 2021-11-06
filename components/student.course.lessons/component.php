<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
/** @var CBitrixComponent $this */
/** @var array $arParams */
/** @var array $arResult */
/** @var string $componentPath */
/** @var string $componentName */
/** @var string $componentTemplate */
/** @global CDatabase $DB */
/** @global CUser $USER */
/** @global CMain $APPLICATION */

use Bitrix\Main\Loader,
    Kdelo\Student,
    Kdelo\StudentGroup,
    Kdelo\CommentsCounter,
    Kdelo\CommentsTree;

$USER_ID = $userID = $arParams['USER_ID'];
$isAuth = $USER->IsAuthorized();
$arNavigation = false;
$pagerParameters = array();

if($arParams["USE_CACHE"] !=="Y" || $this->startResultCache(false))
{
	if(!Loader::includeModule("kdelo.adminlearn")){
		$this->abortResultCache();
		ShowError("Модуль kdelo.adminlearn не установлен");
		return;
    }
    
    if($isAuth){
        $this->arResult['AUTHORIZED'] = true;

    if(isset($arParams['GROUP_ID'])){
        $groupId =  $arResult['GROUP_ID'] = $arParams['GROUP_ID'];

        $simleStudent = new Student($userID, false); 

        $arResult['ALL_CUR_LESSONS'] = $curLessons = $simleStudent->getCurLessonsList();

        $GROUP = new StudentGroup($groupId);      
        $GROUP->init()->setLessons();

        $COMMENTS_TREE = new CommentsTree($groupId);
        $teachers = StudentGroup::getTeachers($groupId);
        foreach($teachers as $teacherId)
            $COMMENTS_TREE->addUser($teacherId);

        $COMMENTS_TREE->addUser($userID);
        $COMMENTS_TREE->objectType = 'lesson';

        function getNameByCount($count, $defaultName, $nameFor_1, $nameFor_2_4){
            $numText =  $defaultName;
            $number = $count % 10;
            if($number == 1 && $count != 11)
                $numText =  $nameFor_1;
            elseif($number > 1 && $number<5 && ($count < 12 || $count > 14))
                $numText =  $nameFor_2_4;

            return $numText;
        }

        $this->setBaseCourseOptions($GROUP);
        $timeToStart = $this->setTimeOptions($GROUP, 'return_timeToStart');
        $this->setFormOptions($GROUP, $userID);

        if($arResult['TIME_LEFT'] && $arResult['DAYS_LEFT'])
            $arResult['DAYS_LEFT_FORMATED'] = $arResult['DAYS_LEFT'].' '.getNameByCount($arResult['DAYS_LEFT'], 'дней', 'день', 'дня');


        if($arResult['IS_WAITING']){
            $toStart = array(
                'DAYS' => intdiv($timeToStart, 86400),
                'HOURS' => intdiv($timeToStart % 86400, 3600),
                'MINUTS' => intdiv($timeToStart % 3600, 60)
            );

            $arToStart = array(); 

            if($toStart['DAYS'] > 0){
                $toStart['DAYS_FORMATED'] = $toStart['DAYS'].' '.getNameByCount($toStart['DAYS'], 'дней', 'день', 'дня');
                $arToStart[] =  $toStart['DAYS_FORMATED'];
            }

            if($toStart['HOURS'] > 0){
                $toStart['HOURS_FORMATED'] = $toStart['HOURS'].' '.getNameByCount($toStart['HOURS'], 'часов', 'час', 'часа');
                $arToStart[] =  $toStart['HOURS_FORMATED'];
            }

            if($toStart['MINUTS'] > 0){
                $toStart['MINUTS_FORMATED'] = $toStart['MINUTS'].' '.getNameByCount($toStart['MINUTS'], 'минут', 'минута', 'минуты');
                $arToStart[] =  $toStart['MINUTS_FORMATED'];
            }

            $toStart['TIME_FORMATED'] = implode(', ', $arToStart);

            $arResult['TIME_BEFORE_START'] = $toStart;
        }

        $arResult['COURSE']['LESSONS'] = array();

        foreach($GROUP->course->lessons as $obLesson){          
            $COMMENTS_TREE->clearCommentsTree()->setObjectId($obLesson->getId());
            if($arParams['USE_COMMENTS_CACHE_FILE'] === 'Y')
                $COMMENTS_TREE->setTreeFromCacheFile($userID);
            else
                $COMMENTS_TREE->initTree();

            $obLesson->commentsCount =  $COMMENTS_TREE->countCommentsInTree();


            $commentsNumText = getNameByCount($obLesson->commentsCount, 'комментариев', 'комментарий', 'комментария');
            $videoNumText = getNameByCount($obLesson->videoCount, 'видеозаписей', 'видеозапись', 'видеозаписи');
            $filesNumText = getNameByCount($obLesson->filesCount, 'файлов', 'файл', 'файла');

            
            $lessonStartDate = $GROUP->dateStart; //Потом поменять для автоматических курсов

            $arResult['COURSE']['LESSONS'][$obLesson->getId()] = array(
                'ORDER' => $obLesson->order,
                'NAME' => $obLesson->name,
                'VIDEO_COUNT' => $obLesson->videoCount,
                'VIDEO_COUNT_TEXT' => $videoNumText,
                'FILES_COUNT' => $obLesson->filesCount,
                'FILES_COUNT_TEXT' =>$filesNumText,
                'COMMENTS_COUNT' => $obLesson->commentsCount,
                'COMMENTS_COUNT_TEXT' =>  $commentsNumText,
                "PASSED" => false,
                "START_DATE" =>  $lessonStartDate

            );
        }

        $lessonsIdList = array_keys($arResult['COURSE']['LESSONS']);

        foreach($curLessons as $lessonId){
            if(in_array($lessonId, $lessonsIdList)){
                $arResult['CUR_LESSON'] = $lessonId;
                foreach($arResult['COURSE']['LESSONS'] as $id => &$arLesson){
                    $arLesson['PASSED'] = true;
                    if($id == $lessonId)
                        break;                       
                }
                break;
            }
        }

   
    }

    }else{
        $this->arResult['AUTHORIZED'] = false;
    }
 
	$this->includeComponentTemplate();
}
?>