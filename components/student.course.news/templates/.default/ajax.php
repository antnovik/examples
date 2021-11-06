<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
/** $result - формируется при обработке ajax-запроса */
?>

<?
define('PHOTO_IN_NEWS', 5);
$photoInItemCount = $result['PHOTO_IN_NEWS_ITEM']?$result['PHOTO_IN_NEWS_ITEM']:PHOTO_IN_NEWS;
unset($arNews);
?>

<?foreach($result['NEWS'] as $arNews):?>
 

    <div class="active-courses__onenews">

        <?$newsLink = '/student/group/'.$result['GROUP_ID'].'/news-item/'.$arNews['ID'].'/';?>

        <div class="active-courses__comments">
            <img src="<?=$result['SITE_TEMPLATE']?>/img/icon-comment.png">
            <p>+<?=$arNews['NEW_COMMENTS_COUNT']?></p>
        </div>
        <h3 class="active-courses__name-news">
            <?=$arNews['NAME'];?>
        </h3>
        <p class="active-courses__author-news">
            <?=$arNews['AUTHOR'];?>
        </p>
        
        <p class="active-courses__date-news"><?=timestampToDate(strtotime($arNews['DATE_CREATE']));?></p>

        <p class="active-courses__textnews">
            <?=$arNews['PREVIEW_TEXT'];?>
        </p>

        <?if($arNews['PHOTOS']):?>
        <div class="active-courses__img-news">
            <?$counter = 0;?>
            <?foreach($arNews['PROPERTY_PHOTO_VALUE'] as $photoId):?>
            <?
                $resizeImg = CFile::ResizeImageGet(
                    $photoId,
                    array("width" => '100', "height" => '100'),
                    BX_RESIZE_IMAGE_PROPORTIONAL_ALT
                );
                $counter++;
            ?>

            <?if($counter<$photoInItemCount):?>
            <div>
                <a href="<?=CFile::GetPath($photoId);?>" data-fslightbox="news-images-<?=$arNews['ID']?>">
                    <img src="<?=$resizeImg['src'];?>">
                </a>
            </div>
            <?elseif($counter == $photoInItemCount):?>
            <a href="<?=CFile::GetPath($photoId);?>" data-fslightbox="news-images-<?=$arNews['ID']?>">
                <div class="photo-last">
                    <img src="<?=$resizeImg['src'];?>">
                </div>

                <input type='hidden' class="photo-count" value="<?=count($arNews['PROPERTY_PHOTO_VALUE']) - $counter + 1;?>">
            </a>
            <?else:?>
            <div class="d-none">
                <a href="<?=CFile::GetPath($photoId);?>" data-fslightbox="news-images-<?=$arNews['ID']?>">
                    <img src="<?=$resizeImg['src'];?>">
                </a>
            </div>
            <?endif;?>
            <?endforeach;?>
        </div>

        <?endif;?>


        <a href="<?=$newsLink;?>" class="active-courses__link">Читать далее...</a>
    </div>
<?endforeach;?>
