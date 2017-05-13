<?php
define('const_max_retrieve_items', 1000);

$labels = multichain_labels();

no_displayed_error_result($liststreams, multichain('liststreams', '*', true));
no_displayed_error_result($getinfo, multichain('getinfo'));

$subscribed = false;
$viewstream = null;
$project = null;
$success = null;
$countitems = null;
$suffix = '';

foreach ($liststreams as $stream) {

    if ($stream['name'] == 'Contracts') {
        $viewstream = $stream;
        $success = no_displayed_error_result($items, multichain('liststreamitems', $viewstream['createtxid'], true, const_max_retrieve_items));
        $countitems = $viewstream['items'];
    }

}

foreach ($items as $item) {
    if (@$_GET['project'] == $item['key'])
        $project = $item;
}

?>

<div class="row">

    <div class="col-sm-4">
        <form method="post" action="./?chain=<?php echo html($_GET['chain']) ?>&page=<?php echo html($_GET['page']) ?>">

            <?php

            if ($success) {
                ?>

                <h3><?php echo html($viewstream['name']) ?> &ndash; <?php echo count($items) ?>
                    of <?php echo $countitems ?> <?php echo ($countitems == 1) ? 'item' : 'items' ?><?php echo html($suffix) ?></h3>
                <?php
                $oneoutput = false;
                $items = array_reverse($items); // show most recent first

                foreach ($items as $item) {
                    $oneoutput = true;

                    ?>
                    <table class="table table-bordered table-condensed table-striped table-break-words">
                        <tr>
                            <th style="width:35%;">Project Name</th>
                            <td>
                                <a href="./?chain=<?php echo html($_GET['chain']) ?>&page=<?php echo html($_GET['page']) ?>&project=<?php echo html($item['key']) ?>"><?php echo html($item['key']) ?></a>
                            </td>
                        </tr>
                        <tr>
                            <th>Client</th>
                            <td><?php

                                foreach ($item['publishers'] as $publisher) {
                                    $label = @$labels[$publisher]
                                    ?><?php echo $label ?><?php

                                }

                                ?></td>
                        </tr>
                        <tr>
                            <th>Timestamp</th>
                            <td><?php echo gmdate('Y-m-d H:i:s', isset($item['blocktime']) ? $item['blocktime'] : $item['time']) ?>
                                GMT<?php echo isset($item['blocktime']) ? ' (confirmed)' : '' ?></td>
                        </tr>
                    </table>
                    <?php
                }
            }
            ?>
        </form>
    </div>

    <?php

    if (isset($_GET['project'])) {

        if ($project) {
            ?>

            <div class="col-sm-8">
                <h3>Contract - <?php echo html($project['key']) ?></h3>

                <form method="post"
                      action="./?chain=<?php echo html($_GET['chain']) ?>&page=<?php echo html($_GET['page']) ?>">

                    <?php
                    $oneoutput = false;
                    $items = array_reverse($items); // show most recent first
                    $oneoutput = true;

                    $binary = pack('H*', $project['data']);
                    $json = json_decode($binary, true);

                    ?>
                    <table class="table table-bordered table-condensed table-striped table-break-words">
                        <tr>
                            <th style="width:30%;">Project Name</th>
                            <td>
                                <a href="./?chain=<?php echo html($_GET['chain']) ?>&page=<?php echo html($_GET['page']) ?>&project=<?php echo html($project['key']) ?>"><?php echo html($project['key']) ?></a>
                            </td>
                        </tr>
                        <tr>
                            <th>Client</th>
                            <td><?php

                                foreach ($project['publishers'] as $publisher) {
                                    $link = './?chain=' . $_GET['chain'] . '&page=' . $_GET['page'] . '&stream=' . $viewstream['createtxid'] . '&client=' . $publisher;

                                    ?><?php echo format_address_html($publisher, false, $labels, $link) ?><?php

                                }

                                ?></td>
                        </tr>
                        <tr>
                            <th>Freelancer</th>
                            <td>
                                <?php
                                $freelancer = $json['freelancer'];
                                $labels = multichain_labels();
                                $link = './?chain=' . $_GET['chain'] . '&page=' . $_GET['page'] . '&stream=' . $viewstream['createtxid'] . '&freelancer=' . $freelancer;

                                ?><?php echo format_address_html($freelancer, false, $labels, $link) ?><?php

                                ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Contract type</th>
                            <td><?php echo $json['contract_type'] ?></td>
                        </tr>
                        <tr>
                            <th>Asset name</th>
                            <td><?php echo $json['asset'] ?></td>
                        </tr>
                        <tr>
                            <th>Amount</th>
                            <td><?php echo $json['qty'] ?></td>
                        </tr>
                        <tr>
                            <th>Project description</th>
                            <td><?php echo $json['description'] ?></td>
                        </tr>
                        <tr>
                            <th>State</th>
                            <td></td>
                        </tr>
                        <tr>
                            <th>Timestamp</th>
                            <td><?php echo gmdate('Y-m-d H:i:s', isset($project['blocktime']) ? $project['blocktime'] : $project['time']) ?>
                                GMT<?php echo isset($project['blocktime']) ? ' (confirmed)' : '' ?></td>
                        </tr>
                    </table>
                    <div class="form-group">
                        <div class="pull-right">
                            <input class="btn btn-default" type="submit" name="cancel" value="Cancel">
                            <input class="btn btn-default" type="submit" name="pay" value="Pay">
<!--                            <input class="btn btn-default" type="submit" name="publish" value="Complete">-->
                        </div>
                    </div>
                </form>
            </div>

            <?php
        }
    }
    ?>
</div>
