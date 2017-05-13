<?php
$max_upload_size = multichain_max_data_size() - 512; // take off space for file name and mime type

if (@$_POST['publish']) {

    $json = array(
        'freelancer' => $_POST['freelancer'],
        'contract_type' => $_POST['contract_type'],
        'asset' => $_POST['asset'],
        'qty' => $_POST['qty'],
        'description' => $_POST['description']
    );

    $data = string_to_txout_bin(json_encode($json));

    if (no_displayed_error_result($publishtxid, multichain(
        'publishfrom', $_POST['client'], 'Contracts', $_POST['name'], bin2hex($data)
    ))) {
        if (no_displayed_error_result($prepare, multichain('preparelockunspentfrom',
            $_POST['client'], array($_POST['asset'] => floatval($_POST['qty'])))))
            output_success_text('Item successfully published in transaction ' . $publishtxid);
    }
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

// create
if (@$_POST['issueasset']) {
    $multiple = (int)round(1 / $_POST['units']);

    $addresses = array( // array of addresses to issue units to
        $_POST['to'] => array(
            'issue' => array(
                'raw' => (int)($_POST['qty'] * $multiple)
            )
        )
    );
}

$getinfo = multichain_getinfo();

$usableaddresses = array();
$sendaddresses = array();
$issueaddresses = array();
$keymyaddresses = array();
$receiveaddresses = array();
$keyusableassets = array();
$labels = array();
$haslocked = false;

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

                foreach ($allbalances as $balance) {
                    $unlockedqty = floatval($assetunlocked[$balance['name']]);
                    $lockedqty = $balance['qty'] - $unlockedqty;

                    if ($lockedqty > 0)
                        $haslocked = true;
                    if ($unlockedqty > 0)
                        $keyusableassets[$balance['name']] = true;
                }
            }
        }
    }
}
?>

<div class="row">

    <div class="col-sm-12">
        <h3>Create Contract</h3>

        <form class="form-horizontal" method="post" enctype="multipart/form-data"
              action="./?chain=<?php echo html($_GET['chain']) ?>&page=<?php echo html($_GET['page']) ?>">

            <div class="form-group">
                <label for="name" class="col-sm-2 control-label">Project Name</label>
                <div class="col-sm-9">
                    <input class="form-control" name="name" id="name">
                </div>
            </div>
            <div class="form-group">
                <label for="client" class="col-sm-2 control-label">Client</label>
                <div class="col-sm-9">
                    <select class="form-control" name="client" id="client">
                        <?php
                        foreach ($usableaddresses as $address) {
                            ?>
                            <option value="<?php echo html($address) ?>"><?php echo format_address_html($address, true, $labels) ?></option>
                            <?php
                        }
                        ?>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label for="freelancer" class="col-sm-2 control-label">Freelancer</label>
                <div class="col-sm-9">
                    <select class="form-control" name="freelancer" id="freelancer">
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
                <label for="contract_type" class="col-sm-2 control-label">Contract type</label>
                <div class="col-sm-9">
                    <select class="form-control" name="contract_type" id="contract_type">
                        <option value="Client-Developer">Client-Developer</option>
                        <option value="Client-QA">Client-QA</option>
                        <option value="Client-Consultant">Client-Consultant</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label for="asset" class="col-sm-2 control-label">Asset name</label>
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
                <label for="qty" class="col-sm-2 control-label">Amount</label>
                <div class="col-sm-9">
                    <input class="form-control" name="qty" id="qty" placeholder="100.0">
                </div>
            </div>
            <div class="form-group">
                <label for="description" class="col-sm-2 control-label">Project description</label>
                <div class="col-sm-9">
                    <textarea class="form-control" rows="4" name="description" id="description"></textarea>
                </div>
            </div>
            <div class="form-group">
                <div class="col-sm-offset-2 col-sm-9">
                    <input class="btn btn-default" type="submit" name="publish" value="Publish Contract">
                </div>
            </div>
        </form>

    </div>
</div>