function update_form_fields() {
    var form = $('edit_form');
    var i = form.getElements('radio');
    i.each(function (item) {
        if (item.name.indexOf('attribute_logic[weight]') == 0) {
            if (item.value == 'parent' || item.value == 'child_then_parent') {
                item.disable();
            }
            if (item.value == 'child'){
                item.checked = true;
            }
        }

        if (item.name.indexOf('attribute_logic[size]') == 0) {
            if (item.value == 'parent' || item.value == 'child_then_parent') {
                item.disable();
            }
            if (item.value == 'child'){
                item.checked = true;
            }
        }

        if (item.name.indexOf('attribute_logic[length]') == 0) {
            if (item.value == 'parent' || item.value == 'child_then_parent') {
                item.disable();
            }
            if (item.value == 'child'){
                item.checked = true;
            }
        }

        if (item.name.indexOf('attribute_logic[parent_id]') == 0) {
            if (item.value == 'child' || item.value == 'child_then_parent') {
                item.disable();
            }
            if (item.value == 'parent'){
                item.checked = true;
            }
        }

        if (item.name.indexOf('attribute_logic[parent_sku]') == 0) {
            if (item.value == 'child' || item.value == 'child_then_parent') {
                item.disable();
            }
            if (item.value == 'parent'){
                item.checked = true;
            }
        }

        if (item.name.indexOf('attribute_logic[parent_url]') == 0) {
            if (item.value == 'child' || item.value == 'child_then_parent') {
                item.disable();
            }
            if (item.value == 'parent'){
                item.checked = true;
            }
        }

        if (item.name.indexOf('attribute_logic[product_id]') == 0) {
            if (item.value == 'parent' || item.value == 'child_then_parent') {
                item.disable();
            }
            if (item.value == 'child'){
                item.checked = true;
            }
        }

        if (item.name.indexOf('attribute_logic[product_type]') == 0) {
            if (item.value == 'parent' || item.value == 'child_then_parent') {
                item.disable();
            }
            if (item.value == 'child'){
                item.checked = true;
            }
        }

        if (item.name.indexOf('attribute_logic[sku]') == 0) {
            if (item.value == 'parent' || item.value == 'child_then_parent') {
                item.disable();
            }
            if (item.value == 'child'){
                item.checked = true;
            }
        }

        if (item.name.indexOf('attribute_logic[dfw_default_variant]') == 0) {
            if (item.value == 'parent' || item.value == 'child_then_parent') {
                item.disable();
            }
            if (item.value == 'child'){
                item.checked = true;
            }
        }

        if (item.name.indexOf('attribute_logic[product_url]') == 0) {
            if (item.value == 'parent' || item.value == 'child_then_parent') {
                item.disable();
            }
            if (item.value == 'child'){
                item.checked = true;
            }
        }

        if (item.name.indexOf('attribute_logic[product_url_rewritten]') == 0) {
            if (item.value == 'parent' || item.value == 'child_then_parent') {
                item.disable();
            }
            if (item.value == 'child'){
                item.checked = true;
            }
        }

        if (item.name.indexOf('attribute_logic[updated_at]') == 0) {
            if (item.value == 'parent' || item.value == 'child_then_parent') {
                item.disable();
            }
            if (item.value == 'child'){
                item.checked = true;
            }
        }

        /* prices */
        if (item.name.indexOf('attribute_logic[price]') == 0) {
            if (item.value == 'parent' || item.value == 'child_then_parent') {
                item.disable();
            }
            if (item.value == 'child'){
                item.checked = true;
            }
        }

        if (item.name.indexOf('attribute_logic[price_with_tax]') == 0) {
            if (item.value == 'parent' || item.value == 'child_then_parent') {
                item.disable();
            }
            if (item.value == 'child'){
                item.checked = true;
            }
        }

        if (item.name.indexOf('attribute_logic[special_price]') == 0) {
            if (item.value == 'parent' || item.value == 'child_then_parent') {
                item.disable();
            }
            if (item.value == 'child'){
                item.checked = true;
            }
        }

        if (item.name.indexOf('attribute_logic[special_price_with_tax]') == 0) {
            if (item.value == 'parent' || item.value == 'child_then_parent') {
                item.disable();
            }
            if (item.value == 'child'){
                item.checked = true;
            }
        }

        if (item.name.indexOf('attribute_logic[special_from_date]') == 0) {
            if (item.value == 'parent' || item.value == 'child_then_parent') {
                item.disable();
            }
            if (item.value == 'child'){
                item.checked = true;
            }
        }

        if (item.name.indexOf('attribute_logic[special_to_date]') == 0) {
            if (item.value == 'parent' || item.value == 'child_then_parent') {
                item.disable();
            }
            if (item.value == 'child'){
                item.checked = true;
            }
        }

        if (item.name.indexOf('attribute_logic[variant_spac_price]') == 0) {
            if (item.value == 'parent' || item.value == 'child_then_parent') {
                item.disable();
            }
            if (item.value == 'child'){
                item.checked = true;
            }
        }

        if (item.name.indexOf('attribute_logic[variant_spac_price_with_tax]') == 0) {
            if (item.value == 'parent' || item.value == 'child_then_parent') {
                item.disable();
            }
            if (item.value == 'child'){
                item.checked = true;
            }
        }



        if (item.name.indexOf('attribute_logic[parent_price]') == 0) {
            if (item.value == 'child' || item.value == 'child_then_parent') {
                item.disable();
            }
            if (item.value == 'parent'){
                item.checked = true;
            }
        }

        if (item.name.indexOf('attribute_logic[parent_price_with_tax]') == 0) {
            if (item.value == 'child' || item.value == 'child_then_parent') {
                item.disable();
            }
            if (item.value == 'parent'){
                item.checked = true;
            }
        }

        if (item.name.indexOf('attribute_logic[quantity]') == 0) {
            if (item.value == 'parent' || item.value == 'child_then_parent') {
                item.disable();
            }
            if (item.value == 'child'){
                item.checked = true;
            }
        }

        /* required magento attributes, disable child_then_parent */
        if (item.name.indexOf('attribute_logic[visibility]') == 0) {
            if (item.value == 'child_then_parent') {
                item.disable();
            }
        }

        /* required magento attributes, disable child_then_parent */
        if (item.name.indexOf('attribute_logic[name]') == 0) {
            if (item.value == 'child_then_parent') {
                item.disable();
            }
            if (item.value == 'child'){
                item.checked = true;
            }
        }

        /* required magento attributes, disable child_then_parent */
        if (item.name.indexOf('attribute_logic[description]') == 0) {
            if (item.value == 'child_then_parent') {
                item.disable();
            }
            if (item.value == 'child'){
                item.checked = true;
            }
        }

        /* required magento attributes, disable child_then_parent */
        if (item.name.indexOf('attribute_logic[short_description]') == 0) {
            if (item.value == 'child_then_parent') {
                item.disable();
            }
            if (item.value == 'child'){
                item.checked = true;
            }
        }

        /* required magento attributes, disable child_then_parent */
        if (item.name.indexOf('attribute_logic[tax_class_id]') == 0) {
            if (item.value == 'child_then_parent') {
                item.disable();
            }
            if (item.value == 'child'){
                item.checked = true;
            }
        }

    });
}

document.observe('dom:loaded', function(){

    /*hide required checkboxes */
    $$('input[name="required_attributes[]"]').each(
        function(e){
            e.setStyle({display:'none'});
            e.next('label').setStyle({'margin-left':'17px'});
        }
    );

    /*add one line of space between selectable attrs and these which arent */
    $$('input[name="required_attributes[]"]').each(
        function(e){
            console.log(e.next('label').innerHTML);
            if(e.next('label').innerHTML=='product_id'){
                e.up('li').setAttribute('style', 'margin-top:25px !important');
            }
        }
    );


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

    update_form_fields();

});