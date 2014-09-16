$(function(){
    $(document).mouseup(function (e)
    {
        var container = $(".admin-menu ul.child");

        if (!container.is(e.target)
            && container.has(e.target).length === 0)
        {
            container.hide();
        }
    });

    $('.admin-menu li.parent').click(function(){
        if($(this).find('ul').is(':visible')){

            $(this).find('ul').slideUp(70);
        }else{
            $('.admin-menu ul.child').hide();
            $(this).find('ul').slideDown(70);
        }
    });


});