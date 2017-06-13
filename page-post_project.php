<?php
$max_upload_size = multichain_max_data_size() - 512; // take off space for file name and mime type

if (@$_POST['publish']) {

    $json = array(
        'name' => $_POST['key'],
        'time_period' => $_POST['time'],
        'price' => $_POST['price'],
        'description' => $_POST['text']
    );
   
    $data = string_to_txout_bin(json_encode($json));

    if (no_displayed_error_result($publishtxid, multichain(
        'publishfrom', $_POST['from'], 'Projects', $_POST['key'], bin2hex($data)
    )))
        output_success_text('Project successfully published in transaction ' . $publishtxid);
}

$labels = multichain_labels();

no_displayed_error_result($liststreams, multichain('liststreams', '*', true));

if (no_displayed_error_result($getaddresses, multichain('getaddresses', true))) {
    foreach ($getaddresses as $index => $address)
        if (!$address['ismine'])
            unset($getaddresses[$index]);

    if (no_displayed_error_result($listpermissions,
        multichain('listpermissions', 'send', implode(',', array_get_column($getaddresses, 'address')))
    ))
        $sendaddresses = array_get_column($listpermissions, 'address');
}

?>

<div class="row">

    <div class="col-sm-12">
        <h3>Post a Project</h3>

        <form class="form-horizontal" method="post" enctype="multipart/form-data"
              action="./?chain=<?php echo html($_GET['chain']) ?>&page=<?php echo html($_GET['page']) ?>">
            <div class="form-group">
                <label for="from" class="col-sm-2 control-label">Client:</label>
                <div class="col-sm-9">
                    <select class="form-control" name="from" id="from">
                        <?php
                        foreach ($sendaddresses as $address) {
                            ?>
                            <option value="<?php echo html($address) ?>"><?php echo format_address_html($address, true, $labels) ?></option>
                            <?php
                        }
                        ?>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label for="key" class="col-sm-2 control-label">Project name:</label>
                <div class="col-sm-9">
                    <input class="form-control" name="key" id="key">
                </div>
            </div>
            <div class="form-group">
                <label for="key" class="col-sm-2 control-label">Time Period:</label>
                <div class="col-sm-9">
                    <input class="form-control" name="time" id="time">
                </div>
            </div>
            <div class="form-group">
                <label for="key" class="col-sm-2 control-label">Price:</label>
                <div class="col-sm-9">
                    <input class="form-control" name="price" id="price">
                </div>
            </div>
            <div class="form-group">
                <label for="text" class="col-sm-2 control-label">Description:</label>
                <div class="col-sm-9">
                    <textarea class="form-control" rows="4" name="text" id="text"></textarea>
                </div>
            </div>
            <div class="form-group">
                <div class="col-sm-offset-2 col-sm-9">
                    <input class="btn btn-default" type="submit" name="publish" value="Publish Project">
                </div>
            </div>
        </form>

    </div>
</div>