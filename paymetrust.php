<?php


/**
 * PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *
 * Payment Gateway PaymeTrust.net
 *
 * created by @ibnux <me@ibnux.com>
 *
 **/

function paymetrust_get_currency()
{
    return ['XOF', 'GNF', 'XAF'];
}

function paymetrust_validate_config()
{
    global $config;
    if (empty($config['paymetrust_api_key']) || empty($config['paymetrust_api_password']) || empty($config['paymetrust_currencys'])) {
        sendTelegram("PaymeTrust payment gateway not configured");
        r2(U . 'order/package', 'w', Lang::T("Admin has not yet setup PaymeTrust payment gateway, please tell admin"));
    }
}

function paymetrust_show_config()
{
    global $ui;
    $ui->assign('_title', 'PaymeTrust - Payment Gateway');
    $ui->assign("show", "config");
    $ui->assign("currs", paymetrust_get_currency());
    $ui->display('paymetrust.tpl');
}


function paymetrust_save_config()
{
    global $admin, $_L;
    $paymetrust_api_key = _post('paymetrust_api_key');
    $paymetrust_api_password = _post('paymetrust_api_password');
    $d = ORM::for_table('tbl_appconfig')->where('setting', 'paymetrust_api_key')->find_one();
    if ($d) {
        $d->value = $paymetrust_api_key;
        $d->save();
    } else {
        $d = ORM::for_table('tbl_appconfig')->create();
        $d->setting = 'paymetrust_api_key';
        $d->value = $paymetrust_api_key;
        $d->save();
    }
    $d = ORM::for_table('tbl_appconfig')->where('setting', 'paymetrust_api_password')->find_one();
    if ($d) {
        $d->value = $paymetrust_api_password;
        $d->save();
    } else {
        $d = ORM::for_table('tbl_appconfig')->create();
        $d->setting = 'paymetrust_api_password';
        $d->value = $paymetrust_api_password;
        $d->save();
    }
    $d = ORM::for_table('tbl_appconfig')->where('setting', 'paymetrust_currencys')->find_one();
    if ($d) {
        $d->value = implode(',', $_POST['paymetrust_currencys']);
        $d->save();
    } else {
        $d = ORM::for_table('tbl_appconfig')->create();
        $d->setting = 'paymetrust_currencys';
        $d->value = implode(',', $_POST['paymetrust_currencys']);
        $d->save();
    }
    _log('[' . $admin['username'] . ']: PaymeTrust ' . $_L['Settings_Saved_Successfully'], 'Admin', $admin['id']);

    r2(U . 'paymentgateway/paymetrust', 's', $_L['Settings_Saved_Successfully']);
}

function paymetrust_create_transaction($trx, $user)
{
    global $ui, $routes, $config;
    if (!in_array($routes[4], paymetrust_get_currency())) {
        $ui->assign('_title', 'PaymeTrust');
        $ui->assign("show", "channels");
        $ui->assign("currs", explode(',', $config['paymetrust_currencys']));
        $ui->assign('path', $routes[2] . '/' . $routes[3]);
        $ui->display('paymetrust.tpl');
        die();
    }
    $lang = ($routes[5]) ? $routes[5] : 'fr';
    $cur = ($routes[4]) ? $routes[4] : 'XOF';
    $name = fl_name($user['fullname']);
    $json = [
        'currency' => $cur,
        //'payment_method' => $method,
        'merchant_transaction_id' => $trx['id'],
        'amount' => strval($trx['price']),
        'lang' => $lang,
        'designation' => $trx['plan_name'],
        'client_first_name' => $name[0],
        'client_last_name' => $name[1],
        'client_phone_number' => $user['phonenumber'],
        'client_email' => $user['email'],
        'success_url' => U . 'order/view/' . $trx['id'] . '/check',
        'failed_url' => U . 'order/view/' . $trx['id'] . '/check',
        'notify_url' => U . 'order/view/' . $trx['id'] . '/check',
    ];

    $result = json_decode(
        Http::postJsonData(
            paymetrust_get_server() . '/payment',
            $json,
            [
                'Authorization: Bearer ' . paymetrustGetAccessToken()
            ]
        ),
        true
    );
    if ($result['status'] != 'OK') {
        sendTelegram("paymetrust_create_transaction FAILED: \n\n" . json_encode($result, JSON_PRETTY_PRINT));
        r2(U . 'order/package', 'e', Lang::T("Failed to create PaymeTrust transaction."));
    }
    $urlPayment = $result['payment_url'];
    $d = ORM::for_table('tbl_payment_gateway')
        ->where('username', $user['username'])
        ->where('status', 1)
        ->find_one();
    $d->gateway_trx_id = $result['payment_token'];
    $d->pg_url_payment = $result['payment_url'];
    $d->pg_request = json_encode($result);
    $d->expired_date = date('Y-m-d H:i:s', strtotime("+ 6 HOUR"));
    $d->save();
    header('Location: ' . $urlPayment);
    exit();
}

function fl_name($name)
{
    $names = explode(' ', $name);
    if (count($names) > 1) {
        return $names;
    } else {
        return [$name, $name];
    }
}

/*
*/

function paymetrust_payment_notification()
{
    // Not yet implemented
    die('OK');
}

function paymetrust_get_status($trx, $user)
{
    $result = json_decode(Http::getData(paymetrust_get_server() . '/payment/' . $trx['gateway_trx_id'], ['Authorization: Bearer ' . paymetrustGetAccessToken()]), true);
    if ($result['status'] == 'SUCCESS') {
        if (!Package::rechargeUser($user['id'], $trx['routers'], $trx['plan_id'], $trx['gateway'], ($_SESSION['channel']) ? $_SESSION['channel'] : 'Channel')) {
            r2(U . "order/view/" . $trx['id'], 'd', Lang::T("Failed to activate your Package, try again later."));
        }
        $trx->pg_paid_response = json_encode($result);
        $trx->payment_method = 'PaymeTrust';
        $trx->payment_channel = ($_SESSION['channel']) ? $_SESSION['channel'] : 'Channel';
        $trx->paid_date = date('Y-m-d H:i:s', strtotime($result['updated']));
        $trx->status = 2;
        $trx->save();
        r2(U . "order/view/" . $trx['id'], 's', Lang::T("Transaction has been paid."));
    } else {
        sendTelegram("paymetrust_get_status: unknown result\n\n" . json_encode($result, JSON_PRETTY_PRINT));
        r2(U . "order/view/" . $trx['id'], 'w', "Transaction status :" . $result['status']);
    }
}

function paymetrustGetAccessToken()
{
    global $config;
    $result = Http::postJsonData(paymetrust_get_server() . '/oauth/login', [
        'api_key' => $config['paymetrust_api_key'],
        'api_password' => $config['paymetrust_api_password']
    ]);
    $json = json_decode($result, true);
    return $json['access_token'];
}


function paymetrust_get_server()
{
    global $_app_stage;
    if ($_app_stage == 'Live') {
        return 'https://api.paymetrust.net/v1';
    } else {
        return 'https://api.sandbox.paymetrust.net/v1';
    }
}
