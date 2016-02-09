document.observe('dom:loaded', function(){

    $('additional_attributes_0_all').observe('click', function toggleChkBox(){

        var attrValue = $('additional_attributes_0_all').checked;
        // toggle Check Boxes using Prototype Library
        var form=$('edit_form');
        var i=form.getElements('checkbox');
        i.each(function(item)
            {
                if(item.id.indexOf('additional_attributes')>-1 && item.id!='additional_attributes_0_all'){
                    item.checked = attrValue;
                }
            }
        );
    });
});