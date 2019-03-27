<!DOCTYPE html>
<html>
<head>
    <title>LiveTree by Filipe Knoedt</title>

    <!-- adjustment for IOS -->
    <meta name="viewport" content="initial-scale=1.0">

	<link rel="icon" href="img/favicon.ico" />

    <!-- main style sheet -->
    <link rel="stylesheet" href="css/style.css" />

    <!-- main page (tree) javascript functions -->
    <!--script type="text/javascript" src="js/index.js"></script-->

    <!-- jQuery -->
    <script src="js/jstree/libs/jquery-3.2.1.min.js"></script>

    <!-- files required by jstree -->
    <link rel="stylesheet" href="js/jstree/themes/default/style.min.css" />
    <script src="js/jstree/jstree.min.js"></script>

    <!-- files required by hurkanSwitch -->
    <link rel="stylesheet" href="js/hurkanSwitch/style.css" />
    <script src="js/hurkanSwitch/hurkanSwitch.js"></script>

    <!-- font awesome (icons) -->
    <script src="https://use.fontawesome.com/2789e763d5.js"></script>

    <!-- jQuery Modal -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-modal/0.9.1/jquery.modal.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-modal/0.9.1/jquery.modal.min.css" />

    <!-- notify.js -->
    <script src="js/notify.js"></script>

	<!-- main project's javascript -->
	<script src="js/main.js"></script>

    <script language="JavaScript">
        // indicates which architecture is being use: SSE or Websockets
        var arch = '{$ARCH}';
        // WebSocket host and port
        var wsHost = '{$WSHOST}';
        var wsPort = '{$WSPORT}';
        var wsProtocol = '{$WSPROTOCOL}';
    </script>

    <!--[if IE]>
    <script type="text/javascript">
        // Internet Explorer can't handle SSE
        if(arch == 'sse') {
            window.location = 'http://' + wsHost + '?arch=websockets';
        }
    </script>
    <![endif]-->

    <!-- SSE or Websocket javascript -->
    <script src="js/<?=$arch;?>.js"></script>

    <!-- Global site tag (gtag.js) - Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=UA-109716981-1"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', 'UA-109716981-1');
    </script>

    </head>
<body>

    <h1 id="livetree_logo" class="livetree_logo"><i class="fa fa-tree fa-lg"></i> LiveTree<br/></h1>
    <div class="signature_label">by <a href="https://filipe.knoedt.net" target="_blank">Filipe Knoedt</a></div>
    <!-- white rounded div -->
    <section>
        <div class="container">
            <div class="inner">
                <!-- action blue buttons -->
                <div>
                    <input type="button" value="Create"  onclick="factoryAction('create')" />&nbsp;
                    <input type="button" value="Update"  onclick="factoryAction('update')" />&nbsp;
                    <input type="button" value="Delete"  onclick="factoryAction('delete')" />&nbsp;
                    <input type="button" value="Generate"  onclick="factoryAction('generate')" />&nbsp;
                </div>

                <br/>

                <div id="ajax-panel"></div>

                <!-- jstree goes in here -->
                <div id="jstree_div" class="jstree_div"></div>

                <!-- ajax loader and responses -->


                <br/>
                <div id="console_log" class="console">
                    javascript output goes here<br/>
                </div>
                <br/>
                <!-- action blue buttons -->
                <div>
                    <input type="button" class="grey" value="Refresh"  onclick="reloadTree()" />&nbsp;
                    <input type="button" class="grey" value="JSON"  onclick="getJson()" />&nbsp;
                    <input type="button" class="grey" value="Sum"  onclick="getSum()" />&nbsp;
                    <input type="button" class="grey" value="Console"  onclick="toggleConsole()" />&nbsp;
                </div>

            </div>
        </div>
    </section>
    <footer>
        <div>
            <small><a href="#modal_help" rel="modal:open">How does it work?</a></small>
        </div>
        <div style="margin-bottom: 10px;">
            <small>see source and presentation on <a href="https://github.com/fknoedt/livetree" target="_blank"><strong>github</strong>&nbsp;<i class="fa fa-github fa-2x"></i></a></small>
        </div>
        <div id="switchArch">
            <input data-title="SSE" data-on-color="info" value="sse" data-on="<?php echo ($arch == 'sse' ? 'true' : 'false');  ?>" name="arch" type="radio" title="Running with Server Sent Events (click to switch to Websockets)" />
            <input data-title="Websocket" data-off-color="success" value="websockets" data-off="<?php echo ($arch == 'websockets' ? 'true' : 'false');  ?>"  name="arch" type="radio" title="Running with Websockets (click to switch to SSE)" />
        </div>
    </footer>

    <!-- Generate / Adjusts Nodes for a Factory -->
    <form class="modal_generate_form modal" id="modal_generate_form" onsubmit="formModalSubmit('generate'); return false;">
        <h3 class="h3_form">How many items do you want for the Factory <label id="lbl_generate"></label>?</h3>
        <p>Item Count&nbsp;<input type="number" id="item_count_generate" name="item_count_generate" min="0" max="15" required onblur="itemCountBlur('generate')"></p> <!-- A number from 0 to 15 -->
        <p>Lower Bound&nbsp;<input type="number" id="lower_bound_generate" name="lower_bound_generate" min="0" max="0" required></p> <!-- A number smaller than or equal to Upper Bound -->
        <p>Upper Bound&nbsp;<input type="number" id="upper_bound_generate" name="upper_bound_generate" min="0" max="1000000" required></p> <!-- A number greater than or equal to Lower Bound -->
        <p class="p_buttons"><input type="button" value="Generate"  onclick="formModalSubmit('generate')" />&nbsp;&nbsp;
            <input type="button" value="Cancel" onclick="formModalClose('generate')"></p>
        <input type="hidden" name="factory_id_generate" id="factory_id_generate" value="aee">
        <input type="hidden" name="action" value="generate">
    </form>

    <!-- Create Factory -->
    <form class="modal_create_form modal" id="modal_create_form" onsubmit="formModalSubmit('create'); return false;">
        <h3 class="h3_form">New Factory</h3>
        <p>Factory Name&nbsp;<input type="text" id="factory_name_create" name="factory_name_create" autocomplete="off" required></p> <!-- Alphanumeric up to 255 characters -->
        <p>Item Count&nbsp;<input type="number" id="item_count_create" name="item_count_create" min="0" max="15" required onblur="itemCountBlur('create')"></p> <!-- A number from 0 to 15 -->
        <p>Lower Bound&nbsp;<input type="number" id="lower_bound_create" name="lower_bound_create" min="0" max="0" required></p> <!-- A number smaller than or equal to Upper Bound -->
        <p>Upper Bound&nbsp;<input type="number" id="upper_bound_create" name="upper_bound_create" min="0" max="1000000" required></p> <!-- A number greater than or equal to Lower Bound -->
        <p class="p_buttons"><input type="button" value="Create" onclick="formModalSubmit('create')" />&nbsp;&nbsp;
            <input type="button" value="Cancel" onclick="formModalClose('create')"></p>
        <input type="hidden" name="action" value="create">
    </form>

    <!-- Update Factory -->
    <form class="modal_update_form modal" id="modal_update_form" onsubmit="formModalSubmit('update'); return false;">
        <h3 class="h3_form">Update Factory <label id="lbl_update"></label></h3>
        <p><input type="text" id="factory_name_update" name="factory_name_update" autocomplete="off" required></p> <!-- Alphanumeric up to 255 characters -->
        <p><input type="button" value="Update" onclick="formModalSubmit('update')" />&nbsp;&nbsp;
            <input type="button" value="Cancel" onclick="formModalClose('update')"></p>
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="factory_id_update" id="factory_id_update" value="1">
    </form>

    <!-- Delete Factory -->
    <form class="modal_delete_form modal" id="modal_delete_form" onsubmit="formModalSubmit('delete'); return false;">
        <h3 class="h3_form">Delete Factory <label id="lbl_delete"></label>?</h3>
        <p><input type="button" value="Delete" onclick="formModalSubmit('delete')" />&nbsp;&nbsp;
            <input type="button" value="Cancel" onclick="formModalClose('delete')"></p>
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="factory_id_delete" id="factory_id_delete" value="">
    </form>

    <!-- Modal HTML embedded directly into document -->
    <div id="modal_help" class="modal">
        <p>This is an interactive, responsive, persistent, live-updating tree.</p>
        <p>If anyone creates, updates or deletes nodes, every other user has it's tree instantly reloaded (via SSE).</p>
        <p>Try it with different browsers or phone =)</p>
        <p>Project information and source code on github (link below)</p>
        <input type="button" value="Got it" onclick="formModalClose('help')"></p>
    </div>
</body>
</html>
<script language="JavaScript">
    $('#switchArch').hurkanSwitch({
        'checked': '<?=$arch;?>',
        'width': '95px',
        'offConfirm':function(input){
            if(confirm("Do you want to change the architecture to Websockets?")){
                $(input).trigger("click",true);
                window.location.href = '/?arch=websockets'
            }
        },
        'onConfirm':function(input){
            if(confirm("Do you want to change the architecture to SSE?")){
                $(input).trigger("click",true);
                window.location.href = '/?arch=sse'
            }
        }
    });
</script>