{extends file='page.tpl'}

{block name='page_header_container'}{/block}

{block name='left_column'}

    <div class="page-content card card-block">
        <h1>Prestashop internal Straal logs</h1>

        <div class="row" style="margin-top: 50px;">
            <div class="col-lg-1">ID</div>
            <div class="col-lg-4">Title</div>
            <div class="col-lg-5">Description</div>
            <div class="col-lg-2">Date</div>
        </div>
        {if $logs!=false}
            {foreach from=$logs  item=log}
                <div class="row" style="padding-top: 10px; padding-bottom: 10px; border: 1px solid rgba(60,60,60, .5);">
                    <div class="col-lg-1">{$log.id}</div>
                    <div class="col-lg-4">{$log.title}</div>
                    <div class="col-lg-5">{$log.description}</div>
                    <div class="col-lg-2">{$log.date}</div>
                </div>
            {/foreach}
        {else}
            <div class="row" style="margin-top: 20px;">
                <div class="col-lg-12" style="padding-top: 10px; padding-bottom: 10px; border: 1px solid rgba(60,60,60, .5);">
                    There are no registered logs, send a notification to https://straal.rensr.pt/pt/module/straal/agent or run it manually.
                </div>
            </div>
        {/if}
    </div>

{/block}
