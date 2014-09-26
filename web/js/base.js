var _xhr_mroc_main_global_complaint = '/global';
var _xhr_mroc_main_complaint = '/complaint';
var _xhr_mroc_main_object_complaint = '/object_complaint';
var _xhr_mroc_main_question = '/question';
var _xhr_mroc_main_comment = '/comment';
var _scope = getScope('base.js');

function iterate(arrayLikeStructure,callback)
{
    if(
        arrayLikeStructure !== null &&
            typeof arrayLikeStructure === 'object' &&
            arrayLikeStructure instanceof Array == false &&
            arrayLikeStructure instanceof HTMLCollection == false
        )
    {
        for(var key in arrayLikeStructure){
            callback(key,arrayLikeStructure[key]);
        }
    }else{
        if(arrayLikeStructure instanceof Array){
            for(var k = 0; k < arrayLikeStructure.length; k++){
                callback(k,arrayLikeStructure[k]);
            }
        }else{
            if(arrayLikeStructure instanceof HTMLCollection){
                for(var j = 0; j < arrayLikeStructure.length; j++){
                    callback(j,arrayLikeStructure[j]);
                }
            }
        }
    }
}

function showPopup(className,header,content)
{
    var modal = $('.modal');
    document.getElementsByClassName('modal')[0].className = 'modal';

    $('.darken').show();
    $('.modal-content').html(content);
    $('.header-text').html(header);

    if(_scope == 'mobile'){
        $('.menu-layer').hide();
        $('.object-info-layer').hide();
        $('.nice-layer').show();
        $('.darken').hide();
    }

    modal.addClass(className);
    modal.slideDown(200);
}

function getScope(script_name)
{
    // Find all script tags
    var scripts = document.getElementsByTagName("script");

    // Look through them trying to find ourselves
    for(var i=0; i<scripts.length; i++) {
        if(scripts[i].src.indexOf("/" + script_name) > -1) {
            // Get an array of key=value strings of params
            var pa = scripts[i].src.split("?").pop().split("&");

            // Split each key=value into array, the construct js object
            var p = {};
            for(var j=0; j<pa.length; j++) {
                var kv = pa[j].split("=");
                p[kv[0]] = kv[1];
            }
            return p.scope;
        }
    }

    // No scripts match
    return {};
}

function fillMap(map,list,fillExtendedInfoCallback)
{
    var mapObjects = [];

    iterate(list,function(key,val){
        var g = new ymaps.GeoObject({
            geometry: {
                type: "Point",
                coordinates: val.coordinates
            },
            properties: {
                clusterCaption: 'Объект # '+ key,
                balloonContent: 'Объект # '+ key
            }
        },{
            openBalloonOnClick: false,
            fillColor: val.color,
            iconColor: val.color,
            strokeColor: val.color

        });
        g.properties.set('mrocId',key);
        g.events.add('click',function(e){
            fillExtendedInfoCallback(g.properties.get('mrocId'));
        });

        mapObjects.push(g);
    });

    var cluster = new ymaps.Clusterer({
        hasBalloon: false
    });

    var activeObjectMonitor;

    cluster.events
        .add('balloonopen',function(event){
            activeObjectMonitor = new ymaps.Monitor(event.get('cluster').state);
            activeObjectMonitor.add('activeObject', function (newValue) {
                fillExtendedInfoCallback(newValue.properties.get('mrocId'));
            });
        })
        .remove('balloonclose', function (event) {
            activeObjectMonitor.removeAll();
        });

    cluster.add(mapObjects);
    map.geoObjects.add(cluster);
}

function initMap()
{
    var zoom = _scope == 'mobile' ? 8 : 9;

    moscowRegionMap = new ymaps.Map("map",
        {
            center: [55.76, 37.64],
            zoom: zoom,
            controls: ['typeSelector','geolocationControl']
        }
    );


    var zoomControl = new ymaps.control.ZoomControl({
        options:{
            size: 'small'
        }
    });
    var searchControl = new ymaps.control.SearchControl({
        options:{
            size: 'small'
        }
    });

    moscowRegionMap.controls.add(zoomControl);
    moscowRegionMap.controls.add(searchControl);
}

function handleStates(list,fillExtendedInfoCallback)
{
    $(document).on('change','.sale_type_select, .object_type_select',function(e){
        var newList = [];
        var checkedOT = [];
        var checkedST = [];

        $('.sale_type_select:checked').each(function(){
            checkedST.push(parseInt($(this).val()));
        });

        $('.object_type_select:checked').each(function(){
            checkedOT.push(parseInt($(this).val()));
        });

        iterate(list,function(key,val){
            if(checkedST.indexOf(parseInt(val.st)) != -1 && checkedOT.indexOf(parseInt(val.ot)) != -1){
                newList.push(val);
            }else{
                if(checkedST.indexOf(parseInt(val.st)) != -1){
                    newList.push(val);
                }else{
                    if(checkedOT.indexOf(parseInt(val.ot)) != -1){
                        newList.push(val);
                    }
                }
            }
        });

        moscowRegionMap.geoObjects.removeAll();
        newList = newList.length == 0 ? list : newList;
        fillMap(moscowRegionMap,newList,fillExtendedInfoCallback);
    });
}

function handleQR(list,fillExtendedInfoCallback)
{
    if(document.URL.indexOf('#') != -1){
        var id = document.URL.substring(document.URL.indexOf('#') + 1,document.URL.length).split('#')[0].substring(3);
        if(Object.keys(list).indexOf(id) != -1){
            fillExtendedInfoCallback(id);
            moscowRegionMap.setCenter(list[id].coordinates,16);
        }else{
            showPopup('small','Ошбика','Нет такого объекта');
        }
    }
}

$(document).on('click tap','.global-complaint',function(e){
    e.preventDefault();
    $('.darken').show();
    $.ajax({
        type: 'get',
        url: _xhr_mroc_main_global_complaint,
        success: function(result){
            showPopup('normal','Отправить жалобу',result);
        }
    });
});

$(document).on('click tap','.complaint',function(){
    $('.darken').show();
    $.ajax({
        type: 'get',
        url: _xhr_mroc_main_complaint,
        success: function(result){
            showPopup('small','Отправить жалобу',result);
        }
    });
});

$(document).on('click tap','.object-complaint',function(){
    $('.darken').show();
    $.ajax({
        type: 'get',
        url: _xhr_mroc_main_object_complaint,
        data:{
            id: $(this).attr('data-for-id')
        },
        success: function(result){
            showPopup('small','Пожаловаться на объект',result);
        }
    });
});

$(document).on('click tap','.question',function(){
    $('.darken').show();
    $.ajax({
        type: 'get',
        url: _xhr_mroc_main_question,
        success: function(result){
            showPopup('small','Задать вопрос',result);
        }
    });
});

$(document).on('click tap','.do-comment',function(){
    $('.darken').show();
    $.ajax({
        type: 'get',
        url: _xhr_mroc_main_comment,
        data:{
            id: $(this).attr('data-for-id')
        },
        success: function(result){
            showPopup('small','Оставить комментарий',result);
        }
    });
});




$(document).on('click tap','.send-global-complaint',function(e){
    e.preventDefault();
    var form = $('.global-complaint-form');

    $.ajax({
        type: 'post',
        url: form.attr('action'),
        data: form.serialize(),
        success: function(result){
            if(result.errors.length > 0){
                showPopup('normal','Отправить жалобу','<b>' + result.message + '</b>' + '<br>'+ result.errors.join('<br>'));
            }else{
                showPopup('normal','Отправить жалобу',result.message);
            }
        }
    });
});

$(document).on('click tap','.send-comment',function(e){
    e.preventDefault();
    var form = $('form[name="mroc_mainbundle_comment"]');

    $.ajax({
        type: 'post',
        url: form.attr('action'),
        data: form.serialize(),
        success: function(result){
            if(result.errors.length > 0){
                showPopup('small','Оставить комментарий','<b>' + result.message + '</b>' + '<br>'+ result.errors.join('<br>'));
            }else{
                showPopup('small','Оставить комментарий',result.message);
            }
        }
    });
});

$(document).on('click tap','.send-complaint',function(e){
    e.preventDefault();
    var form = $('form[name="mroc_mainbundle_complaint"]');

    $.ajax({
        type: 'post',
        url: form.attr('action'),
        data: form.serialize(),
        success: function(result){
            if(result.errors.length > 0){
                showPopup('small','Отправить жалобу','<b>' + result.message + '</b>' + '<br>'+ result.errors.join('<br>'));
            }else{
                showPopup('small','Отправить жалобу',result.message);
            }
        }
    });
});

$(document).on('click tap','.send-question',function(e){
    e.preventDefault();
    var form = $('form[name="mroc_mainbundle_question"]');

    $.ajax({
        type: 'post',
        url: form.attr('action'),
        data: form.serialize(),
        success: function(result){
            if(result.errors.length > 0){
                showPopup('small','Задать вопрос','<b>' + result.message + '</b>' + '<br>'+ result.errors.join('<br>'));
            }else{
                showPopup('small','Задать вопрос',result.message);
            }
        }
    });
});



$(document).on('click tap','.modal .close',function(){
    $('.modal').hide();
    $('.darken').hide();

    if(_scope == 'mobile'){
        $('.nice-layer').show();
        $('.menu-layer').show();
    }
});

$(document).on('click tap','.tabs .tab',function(){
    var allTabs = $('.tabs .tab');
    var allContents = $('.tab-content .content');
    var targetName = $(this).attr('data-for-tab');
    var target = $('.tab-content .content[data-tab-name="'+targetName+'"]');

    allTabs.removeClass('active');
    $(this).addClass('active');


    allContents.removeClass('active');
    target.addClass('active');
});