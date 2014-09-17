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