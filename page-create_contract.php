<?php
define('const_issue_custom_fields', 10);

$max_upload_size = multichain_max_data_size() - 512; // take off space for file name and mime type

if (@$_POST['issueasset']) {
    $multiple = (int)round(1 / $_POST['units']);

    $addresses = array( // array of addresses to issue units to
        $_POST['to'] => array(
            'issue' => array(
                'raw' => (int)($_POST['qty'] * $multiple)
            )
        )
    );

    $custom = array();

    for ($index = 0; $index < const_issue_custom_fields; $index++)
        if (strlen(@$_POST['key' . $index]))
            $custom[$_POST['key' . $index]] = $_POST['value' . $index];

    $datas = array( // to create array of data items
        array( // metadata for issuance details
            'name' => $_POST['name'],
            'multiple' => $multiple,
            'open' => true,
            'details' => $custom,
        )
    );

    $upload = @$_FILES['upload'];
    $upload_file = @$upload['tmp_name'];

    if (strlen($upload_file)) {
        $upload_size = filesize($upload_file);

        if ($upload_size > $max_upload_size) {
            echo '<div class="bg-danger" style="padding:1em;">Uploaded file is too large (' . number_format($upload_size) . ' > ' . number_format($max_upload_size) . ' bytes).</div>';
            return;

        } else {
            $datas[0]['details']['@file'] = fileref_to_string(2, $upload['name'], $upload['type'], $upload_size); // will be in output 2
            $datas[1] = bin2hex(file_to_txout_bin($upload['name'], $upload['type'], file_get_contents($upload_file)));
        }
    }

    if (!count($datas[0]['details'])) // to ensure it's converted to empty JSON object rather than empty JSON array
        $datas[0]['details'] = new stdClass();

    $success = no_displayed_error_result($issuetxid, multichain('createrawsendfrom', $_POST['from'], $addresses, $datas, 'send'));

    if ($success)
        output_success_text('Asset successfully issued in transaction ' . $issuetxid);
}

$getinfo = multichain_getinfo();

$issueaddresses = array();
$keymyaddresses = array();
$receiveaddresses = array();
$labels = array();

if (no_displayed_error_result($getaddresses, multichain('getaddresses', true))) {

    if (no_displayed_error_result($listpermissions,
        multichain('listpermissions', 'issue', implode(',', array_get_column($getaddresses, 'address')))
    ))
        $issueaddresses = array_get_column($listpermissions, 'address');

    foreach ($getaddresses as $address)
        if ($address['ismine'])
            $keymyaddresses[$address['address']] = true;

    if (no_displayed_error_result($listpermissions, multichain('listpermissions', 'receive')))
        $receiveaddresses = array_get_column($listpermissions, 'address');

    $labels = multichain_labels();
}
?>

<div class="row">
    <div class="col-sm-4">
        <h3>Available Balances</h3>
        <?php
        $sendaddresses = array();
        $usableaddresses = array();
        $keymyaddresses = array();
        $keyusableassets = array();
        $haslocked = false;
        $getinfo = multichain_getinfo();
        $labels = array();

        if (no_displayed_error_result($getaddresses, multichain('getaddresses', true))) {

            if (no_displayed_error_result($listpermissions,
                multichain('listpermissions', 'send', implode(',', array_get_column($getaddresses, 'address')))
            ))
                $sendaddresses = array_get_column($listpermissions, 'address');

            foreach ($getaddresses as $address)
                if ($address['ismine'])
                    $keymyaddresses[$address['address']] = true;

            $labels = multichain_labels();

            if (no_displayed_error_result($listpermissions, multichain('listpermissions', 'receive')))
                $receiveaddresses = array_get_column($listpermissions, 'address');

            foreach ($sendaddresses as $address) {
                if (no_displayed_error_result($allbalances, multichain('getaddressbalances', $address, 0, true))) {

                    if (count($allbalances)) {
                        $assetunlocked = array();

                        if (no_displayed_error_result($unlockedbalances, multichain('getaddressbalances', $address, 0, false))) {
                            if (count($unlockedbalances))
                                $usableaddresses[] = $address;

                            foreach ($unlockedbalances as $balance)
                                $assetunlocked[$balance['name']] = $balance['qty'];
                        }

                        $label = @$labels[$address];

                        ?>
                        <table class="table table-bordered table-condensed table-break-words <?php echo ($address == @$getnewaddress) ? 'bg-success' : 'table-striped' ?>">
                            <?php
                            if (isset($label)) {
                                ?>
                                <tr>
                                    <th style="width:25%;">Owner</th>
                                    <td><?php echo html($label) ?></td>
                                </tr>
                                <?php
                            }
                            ?>
                            <tr>
                                <th style="width:20%;">Address</th>
                                <td class="td-break-words small"><?php echo html($address) ?></td>
                            </tr>
                            <?php
                            foreach ($allbalances as $balance) {
                                $unlockedqty = floatval($assetunlocked[$balance['name']]);
                                $lockedqty = $balance['qty'] - $unlockedqty;

                                if ($lockedqty > 0)
                                    $haslocked = true;
                                if ($unlockedqty > 0)
                                    $keyusableassets[$balance['name']] = true;
                                ?>
                                <tr>
                                    <th><?php echo html($balance['name']) ?></th>
                                    <td><?php echo html($unlockedqty) ?><?php echo ($lockedqty > 0) ? (' (' . $lockedqty . ' locked)') : '' ?></td>
                                </tr>
                                <?php
                            }
                            ?>
                        </table>
                        <?php
                    }
                }
            }
        }

        if ($haslocked) {
            ?>
            <form class="form-horizontal" method="post"
                  action="./?chain=<?php echo html($_GET['chain']) ?>&page=<?php echo html($_GET['page']) ?>">
                <input class="btn btn-default" type="submit" name="unlockoutputs" value="Unlock all outputs">
            </form>
            <?php
        }
        ?>
    </div>

    <div class="col-sm-8">
        <h3>Create Contract</h3>
        <form class="form-horizontal" method="post" enctype="multipart/form-data"
              action="./?chain=<?php echo html($_GET['chain']) ?>&page=<?php echo html($_GET['page']) ?>">
            <div class="form-group">
                <label for="project_name" class="col-sm-3 control-label">Project name</label>
                <div class="col-sm-9">
                    <input class="form-control" name="project_name" id="project_name">
                </div>
            </div>
            <div class="form-group">
                <label for="from" class="col-sm-3 control-label">Client</label>
                <div class="col-sm-9">
                    <select class="form-control col-sm-6" name="from" id="from">
                        <?php
                        foreach ($issueaddresses as $address) {
                            ?>
                            <option value="<?php echo html($address) ?>"><?php echo format_address_html($address, true, $labels) ?></option>
                            <?php
                        }
                        ?>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label for="to" class="col-sm-3 control-label">Freelancer</label>
                <div class="col-sm-9">
                    <select class="form-control col-sm-6" name="to" id="to">
                        <?php
                        foreach ($receiveaddresses as $address) {
                            if ($address == $getinfo['burnaddress'])
                                continue;
                            ?>
                            <option value="<?php echo html($address) ?>"><?php echo format_address_html($address, @$keymyaddresses[$address], $labels) ?></option>
                            <?php
                        }
                        ?>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label for="asset" class="col-sm-3 control-label">Asset name</label>
                <div class="col-sm-9">
                    <select class="form-control" name="asset" id="asset">
                        <?php
                        foreach ($keyusableassets as $asset => $dummy) {
                            ?>
                            <option value="<?php echo html($asset) ?>"><?php echo html($asset) ?></option>
                            <?php
                        }
                        ?>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label for="qty" class="col-sm-3 control-label">Amount</label>
                <div class="col-sm-9">
                    <input class="form-control" name="qty" id="qty" placeholder="100.0">
                </div>
            </div>
            <div class="form-group">
                <label for="name" class="col-sm-3 control-label">Project description</label>
                <div class="col-sm-9">
                    <textarea class="form-control" name="project_description" id="project_description" rows="5"></textarea>
                </div>
            </div>
            <div class="form-group">
                <label for="upload" class="col-sm-3 control-label">Project file<br/><span
                            style="font-size:75%; font-weight:normal;">Max <?php echo floor($max_upload_size / 1024) ?>
                        KB</span></label>
                <div class="col-sm-9">
                    <input class="form-control" type="file" name="upload" id="upload">
                </div>
            </div>
<!--            --><?php
//            for ($index = 0; $index < const_issue_custom_fields; $index++) {
//                ?>
<!--                <div class="form-group">-->
<!--                    <label for="key--><?php //echo $index ?><!--"-->
<!--                           class="col-sm-3 control-label">--><?php //echo $index ? '' : 'Terms & Conditions:' ?><!--</label>-->
<!--                    <div class="col-sm-7">-->
<!--                        <select class="form-control col-sm-6" name="key--><?php //echo $index ?><!--" id="key--><?php //echo $index ?><!--"-->
<!--                                placeholder="term--><?php //echo ($index + 1) ?><!--">-->
<!---->
<!--                        </select>-->
<!--                    </div>-->
<!--                </div>-->
<!--                --><?php
//            }
//            ?>
            <br>
            <div class="form-group">
                <div class="col-sm-offset-3 col-sm-7">
                    <input class="btn btn-default" type="submit" name="createcontract" value="Create Contract">
                </div>
            </div>
        </form>

    </div>
</div>