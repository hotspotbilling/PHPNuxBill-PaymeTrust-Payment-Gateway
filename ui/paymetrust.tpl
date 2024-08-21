{include file="sections/header.tpl"}
{if $show == 'config'}
    <form class="form-horizontal" method="post" role="form" action="{$_url}paymentgateway/paymetrust">
        <div class="row">
            <div class="col-sm-12 col-md-12">
                <div class="panel panel-primary panel-hovered panel-stacked mb30">
                    <div class="panel-heading">PaymeTrust</div>
                    <div class="panel-body">
                        <div class="form-group">
                            <label class="col-md-2 control-label">Api key</label>
                            <div class="col-md-6">
                                <input type="text" class="form-control" id="paymetrust_api_key" name="paymetrust_api_key"
                                    placeholder="D" value="{$_c['paymetrust_api_key']}">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-md-2 control-label">API Password</label>
                            <div class="col-md-6">
                                <input type="password" class="form-control" id="paymetrust_api_password"
                                    name="paymetrust_api_password" placeholder="xxxxxxxxxxxxxxxxx"
                                    value="{$_c['paymetrust_api_password']}" onmouseleave="this.type = 'password'"
                                    onmouseenter="this.type = 'text'">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-md-2 control-label">Url Callback Notification</label>
                            <div class="col-md-6">
                                <input type="text" readonly class="form-control" onclick="this.select()"
                                    value="{$_url}callback/paymetrust">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-md-2 control-label">Currency</label>
                            <div class="col-md-6">
                                {foreach $currs as $c}
                                    <label class="checkbox-inline"><input type="checkbox"
                                            {if strpos($_c['paymetrust_currencys'], $c) !== false}checked="true" {/if}
                                            id="paymetrust_currencys" name="paymetrust_currencys[]" value="{$c}">
                                        {$c}</label><br>
                                {/foreach}
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="col-lg-offset-2 col-lg-10">
                                <button class="btn btn-primary waves-effect waves-light"
                                    type="submit">{Lang::T('Save Change')}</button>
                            </div>
                        </div>
                        <pre>/ip hotspot walled-garden
        add dst-host=paymetrust.net
        add dst-host=*.paymetrust.net</pre>
                        <small id="emailHelp" class="form-text text-muted">{Lang::T('Set Telegram Bot to get any error and
                            notification')}</small>
                    </div>
                </div>

            </div>
        </div>
    </form>
{else}
    <div class="row">
        <div class="col-sm-12">
            <div class="panel panel-info panel-hovered">
                <div class="panel-body">
                    <div class="btn-group btn-group-justified" role="group" aria-label="...">
                        {foreach $currs as $c}
                            <a href="{$_url}order/buy/{$path}/{$c}/fr" onclick="return confirm('{$c}?')"
                                class="btn btn-block btn-primary">{$c}</a>
                        {/foreach}
                    </div>
                </div>
            </div>
        </div>
    {/if}
{include file="sections/footer.tpl"}
