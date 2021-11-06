<?
require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');
/** @global CDatabase $DB */
/** @global CUser $USER */
/** @global CMain $APPLICATION */

use Bitrix\Main\Loader,
    Kdelo\Helper,
    Kdelo\GroupNews,
    Kdelo\NewComments;

$USER_ID = (int)$USER->getId();
$isAuth = $USER->IsAuthorized();

if(!Loader::includeModule("kdelo.adminlearn")){
    ShowError("Модуль kdelo.adminlearn не установлен");
}else{
    if($isAuth && $_POST['groupId'] &&  $_POST['nextPage'] &&  $_POST['templateUrl']){
        $GROUP_NEWS = new GroupNews($_POST['groupId']);

        if($_POST['itemsCount'])
            $GROUP_NEWS->setNewsOnPageNum($_POST['itemsCount']); 

        $result = array(
            'GROUP_ID' => $_POST['groupId'],
            'NEWS' => $GROUP_NEWS->setNewsListPage($_POST['nextPage'])->getNewsList(),
            'SITE_TEMPLATE' => $_POST['siteTemplateUrl'],
            'PHOTO_IN_NEWS_ITEM' =>  $_POST['photoInItemCount']
        );

        foreach($result['NEWS'] as & $arNews){
            $NEW_COMMENTS = new NewComments();                          
            $arNews['NEW_COMMENTS_COUNT'] = $NEW_COMMENTS->setSubscriber($userID)->setFromFile()->getCountForObject($arNews['ID']);
        }

        function timestampToDate($timestamp){
            return Helper::timestampToDate($timestamp);
        }

        require($_SERVER['DOCUMENT_ROOT'].$_POST['templateUrl']);
    }
}
    
    