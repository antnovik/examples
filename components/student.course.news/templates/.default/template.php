<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();?>

<?if(!$arResult['AUTHORIZED']):?>
<div class="mb-5 text-center">
    <h3><?=GetMessage("NOT_AUTHORIZED");?></h3>
</div>

<?else:?>

<?//$APPLICATION->AddHeadScript('/assets/js/lb/js/lightbox.js');?>

<?$APPLICATION->AddHeadScript('/assets/js/lb/js/fslightbox.js');?>

<?
define('PHOTO_IN_NEWS', 5);
$photoInItemCount = $arParams['PHOTO_IN_NEWS_ITEM']?$arParams['PHOTO_IN_NEWS_ITEM']:PHOTO_IN_NEWS;
?>

<div class="container-fluid">
	<div class="col-xl-10 offset-xl-1 col-sm-10 offset-sm-1 active-courses__inner">

		<?if($arResult['GRADES_TABLE']['SRC']):?>
		<div class="active-courses__uspev">
			<div class="content__podzag">
				Таблица успеваемости <?=$arResult['GRADES_TABLE']['DATE'];?>
				<br>
				<a href="<?=$arResult['GRADES_TABLE']['SRC']?>" target="_blank" class="active-courses__link mt-0">Скачать</a>
			</div>
		</div>
		<?else:?>
		<div style="margin-bottom:20px;"></div>
		<?endif;?>

		<?if($arResult['NEWS']):?>
		<div class="active-courses__lastnews">
			<div class="content__podzag">
				Последние новости
			</div>

			<?foreach($arResult['NEWS'] as $arNews):?>
				<div class="active-courses__onenews">
					<?$newsLink = '/student/group/'.$arResult['GROUP_ID'].'/news-item/'.$arNews['ID'].'/';?>

					<div class="active-courses__comments">
						<img src="<?=SITE_TEMPLATE_PATH?>/img/icon-comment.png">
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

						<?if($counter < $photoInItemCount):?>
						
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

			<?if($arResult['LOAD_BY_AJAX'] && $arResult['CUR_PAGE'] < $arResult['PAGES_COUNT']):?>
			<div class="text-center">
				<a 
					href="javascript:void(0);" class="black-link"
					id="load-news" 
					data-next-page="<?=$arResult['CUR_PAGE'] + 1;?>" 
					data-items-count="<?=$arResult['ITEMS_ON_PAGE'];?>" 
					data-ajax-url="<?=$componentPath.'/ajax.php';?>" 
					data-ajax-template="<?=$templateFolder.'/ajax.php';?>" 
					data-site-template="<?=SITE_TEMPLATE_PATH?>" 
					data-group-id="<?=$arResult['GROUP_ID'];?>"
					data-pages-count="<?=$arResult['PAGES_COUNT'];?>"
					data-photo-count="<?=$photoInItemCount;?>"
				>
				Показать еще
				</a>
			</div>
			<?endif;?>
		</div>
		<?endif;?>
		
	</div>
</div>
<?endif;?>
