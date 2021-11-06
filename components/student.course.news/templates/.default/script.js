$(function(){
    const selectorNews = '.active-courses__onenews';
    const selectorLastPhoto = '.photo-last';
    const selectorPhotoCount = '.photo-count';


    //Вывод количества скрытых фотографий
    function setImgCount(){
        $(selectorLastPhoto).each(function(){
            let photoCount = $(this).next(selectorPhotoCount).val();
            this.style.setProperty("--img-count", "'+"+ photoCount + "'");
        });
    }

    setImgCount();
    
    if(typeof refreshFsLightbox === 'function')
        refreshFsLightbox();


    //Подключение ajax подгрузки новостей
    let loadBtn = $('#load-news');
    
    if(loadBtn){
        let ajaxUrl = loadBtn.attr('data-ajax-url'),
            pagesCount = loadBtn.attr('data-pages-count');

            loadBtn.click(function(){
            let data = {
                groupId: loadBtn.attr('data-group-id'),
                nextPage: loadBtn.attr('data-next-page'),
                templateUrl: loadBtn.attr('data-ajax-template'),
                siteTemplateUrl: loadBtn.attr('data-site-template'),
                photoInItemCount: loadBtn.attr('data-photo-count')
            }

            let itemsOnPage = loadBtn.attr('data-items-count');
            if(itemsOnPage)
                data.itemsCount = itemsOnPage;

            $.ajax({
                url: ajaxUrl,
                data: data,
                type: 'POST',
                dataType: 'html',
                success: function(result){
                    loadNews(result);
                }
            }); 
        });

        function loadNews(html){
            $(selectorNews).last().after(html);

            setImgCount();

            if(typeof refreshFsLightbox === 'function')
                refreshFsLightbox();

            let nextPage = Number(loadBtn.attr('data-next-page')) + 1;
           
            if(nextPage > pagesCount)
                loadBtn.remove();
            else
                loadBtn.attr('data-next-page', nextPage);

        }
    }
});