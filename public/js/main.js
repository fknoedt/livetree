/**
 * javascript for main LiveTree page
 * @author fknoedt on 10/16/2017
 */

$( document ).ready(function() {

    // $(function () { $('#jstree_div').jstree(); });
    $('#jstree_div').jstree({
        'core' : {
            'data' : {
                "url" : "/index.jstree.php?node=livetree",
                "dataType" : "json",
                "animation" : 1,
                "check_callback" : true
            }
        },
        "contextmenu":{
            "items": function($node) {
                var tree = $("#tree").jstree(true);
                return {
                    "Create": {
                        "separator_before": false,
                        "separator_after": false,
                        "label": "Create",
                        "icon" : "fa fa-plus-square fa",
                        "action": function (obj) {

                            factoryAction('create');

                        }
                    },
                    "Update": {
                        "separator_before": false,
                        "separator_after": false,
                        "label": "Update",
                        "icon" : "fa fa-edit fa",
                        "action": function (obj) {

                            factoryAction('update');

                        }
                    },
                    "Remove": {
                        "separator_before": false,
                        "separator_after": false,
                        "label": "Remove",
                        "icon" : "fa fa-remove fa",
                        "action": function (obj) {

                            factoryAction('delete');

                        }
                    },
                    "Generate": {
                        "separator_before": false,
                        "separator_after": false,
                        "label": "Generate",
                        "icon" : "fa fa-list fa",
                        "action": function (obj) {

                            factoryAction('generate');

                        }
                    }
                };
            }
        },
        "plugins" : [
            "contextmenu", "dnd", "search", "state", "types"
        ]
    });

    // checks if the console length has been exceeded every 20s (and trims it)
    setTimeout('consoleLengthMonitor',20000);

});

/**
 * ensures console will be rotated (reseted) when it exceeds 1000 characters
 */
function consoleLengthMonitor() {

    if(document.getElementById('console_log').innerHTML.length > 1000)
        document.getElementById('console_log').innerHTML = 'log rotated';

}

/**
 * handles every factory CUD and generate form modal display
 * @param action
 */
function factoryAction(action) {

    // gets the selected node as object
    var node = getSelectedNodeObj();

    if(action != 'create' && (! node || node.parent > 0)) {

        $(".livetree_logo").notify('Select a Factory node first', { position:"bottom center", className: "warn" });

        formModalClose();

        return;

    }

    switch (action) {

        case 'create':

            break;

        case 'update':

            document.getElementById('lbl_update').innerHTML = node.text;

            // clear the html from the node text
            var name = node.text;
            name = name.substring(0, name.indexOf('<'));
            name = name.trim();
            document.getElementById('factory_name_update').value = name;

            document.getElementById('factory_id_update').value = node.id;

            // focus on input
            document.getElementById('factory_name_update').focus();

            break;

        case 'delete':

            document.getElementById('lbl_delete').innerHTML = node.text;
            document.getElementById('factory_id_delete').value = node.id;

            break;

        case 'generate':

            document.getElementById('lbl_generate').innerHTML = node.text;
            document.getElementById('factory_id_generate').value = node.id;

            document.getElementById('item_count_generate').value = node.original.item_count;
            document.getElementById('lower_bound_generate').value = node.original.lower_bound;
            document.getElementById('upper_bound_generate').value = node.original.upper_bound;

            break;

    }

    // shows action's modal
    $('#modal_' + action + '_form').modal();

}

/**
 * submits opened modal
 * @param modal
 */
function formModalSubmit(modal) {

    /**
     * url for the ajax request (the same for every action)
     * @type {string}
     */
    var ajaxUrl = '/index.ajax.php';

    /**
     * response from ajax request
     * @type {json}
     */
    var ajaxData;

    var formId = 'modal_' + modal + '_form';

    // form will be only validated  at the server side (a little server performance was sacrificed for more simplicity)

    // gets form's inputs...
    var form=$("#" + formId);

    // and serializes it
    ajaxData = form.serialize();

    // runs the ajax request
    $.ajax({
        type: 'POST', // always use POST
        url: ajaxUrl, // server side script
        dataType: 'json', // always expects JSON response from the server
        contentType: "application/x-www-form-urlencoded; charset=UTF-8",
        data: ajaxData, // serialized form

        // ajax pre load
        beforeSend:function(){
            // this is where we append a loading image
            $('#ajax-panel').html('<div class="loading"><img src="/img/loading.gif" alt="Loading..." /></div>');
        },
        // ajax request success
        success:function(data){

            // server side exception
            if(data.status == 'error') {

                // main message
                $(".h3_form").notify(data.errorMsg, { position:"top center", className: "error" });

            }
            // server side functional error
            else if(data.status == 'warning') {

                // main message
                $(".h3_form").notify(data.errorMsg, { position:"top center", className: "warn" });

                // individual - fields - messages
                if(! isEmpty(data.aNotify)) {

                    for (var fieldId in data.aNotify) {

                        msg = data.aNotify[fieldId];

                        // call notify() for each field with respective message
                        $("#" +  fieldId).notify(msg, { position:"right middle", className: "warn" });

                    }

                }

            }
            // success
            else {

                // main message
                $(".livetree_logo").notify(data.msg, { position:"bottom center", className: "success" });

                // reloads for every action
                reloadTree();

                // always closes the modal
                formModalClose();

            }

            // logs output to console
            document.getElementById('console_log').innerHTML = 'AJAX: ' + data.status + '(' + data.msg + ')<br/>' + document.getElementById('console_log').innerHTML;

            $('#ajax-panel').empty();

            //JSON.parse(data);

        },
        // request failed: notify user
        error:function(data){

            $(".h3_form").notify('Whoops =/\nSomething went wrong\nFilipe was already notified', { position:"top center", className: "error" });

            $('#ajax-panel').empty();

        }
    });

}

/**
 * reloads the tree
 */
function reloadTree() {

    $('#jstree_div').jstree(true).refresh();

}

/**
 * returns the ID (from json) of the selected node on jstree
 * @returns {*}
 */
function getSelectedNodeId() {

    return $('#jstree_div').jstree('get_selected')[0];

}

/**
 * returns the ID (from json) of the selected node on jstree
 * @returns {*}
 */
function getSelectedNodeObj() {

    return $("#jstree_div").jstree("get_selected",true)[0];

}

/**
 * closes any action modal
 * @param action
 */
function formModalClose() {

    $.modal.close();

}

/**
 * downloads main jstree json
 */
function getJson() {

    location.href = '/index.jstree.php?node=livetree&download=1';

}

/**
 * sum up all the nodes
 */
function getSum() {

    $(".livetree_logo").notify('Not implemented: will sum up every node value and display the total', { position:"bottom center", className: "warn" });

}

/**
 * shows/hides the javascript console
 */
function toggleConsole() {

    $('.console').slideToggle('slow');

}

/**
 * tests if a variable is empty
 * @param obj
 * @returns {boolean}
 */
function isEmpty(obj) {

    for(var key in obj) {
        if(obj.hasOwnProperty(key))
            return false;
    }

    return true;

}

/**
 * common function for testing if string is a json (yes, that's - as some other javascript solutions - the recommended way of doing it)
 * @param str
 * @returns {boolean}
 */
function isJson(str) {
    try {
        JSON.parse(str);
    } catch (e) {
        return false;
    }
    return true;
}