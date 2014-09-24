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
    }

    modal.addClass(className);
    modal.slideDown(200);
}

function getScope(script_name) {
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
                clusterCaption: 'Объект # '+val.id,
                balloonContent: 'Объект # '+val.id
            }
        },{
            hasBaloon: false
        });
        g.properties.set('mrocId',val.id);
        g.events.add('balloonopen',function(e){
            fillExtendedInfoCallback(g.properties.get('mrocId'));
        });

        mapObjects.push(g);
    });

    var cluster = new ymaps.Clusterer();
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