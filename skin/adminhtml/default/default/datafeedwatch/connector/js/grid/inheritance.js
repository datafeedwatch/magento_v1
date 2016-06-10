document.addEventListener("DOMContentLoaded", function(event) {
    getInheritanceGrid();
});

function getInheritanceGrid(page, limit) {
    var urlInput = document.getElementById('inheritance_grid_action_url');
    if (urlInput != null) {
        var url = urlInput.value;
        new Ajax.Request(url, {
            method: 'get',
            parameters: {page: page, limit: limit},
            onSuccess: function(response) {
                if (response != null && response.responseText != null) {
                    document.getElementById('attribute_inheritance_grid_items').innerHTML = response.responseText;
                }
            }
        });
    }
}

function saveInheritance(attributeId, value) {
    var urlInput = document.getElementById('save_inheritance_action_url');
    if (urlInput != null) {
        var url = urlInput.value;
        new Ajax.Request(url, {
            method: 'get',
            parameters: {attribute_id: attributeId, value: value}
        });
    }
}

function saveImport(attributeId, value) {
    var urlInput = document.getElementById('save_import_action_url');
    if (urlInput != null) {
        var url = urlInput.value;
        new Ajax.Request(url, {
            method: 'get',
            parameters: {attribute_id: attributeId, value: value}
        });
    }
};