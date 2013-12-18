Array.max = function( array ){
    return Math.max.apply( Math, array );
};
Array.min = function( array ){
    return Math.min.apply( Math, array );
};

function removeRow(divElement,rowIdToRemove){
    $('.tooltip').remove();
    $('#viw_row_div_'+rowIdToRemove,divElement).remove();
}

