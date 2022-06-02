{if $is_straal == true}
<style>
   .table-pagamentos{
       width: 80%;
       text-align: center!important;
       border: 1px solid #cccccc;
   } 
   .table-pagamentos tr:nth-child(odd){
       background-color: rgb(240, 240, 240);
   }
   .table-pagamentos th{
       padding: 5px 0px;
   }
</style>


<div class="row">
    <div class="col-lg-12">
        <div class="col-lg-12 box">
            <h2>{l s='Straal Payment Info' mod='straal'}</h2>
            {if $status != true}
                {if $current_url == false}
                    {l s='Your payment url has expired, if you want to generate a new url to pay you can' mod='straal'} <button id="generateUrl" class="btn btn-primary pointer" target="blank_">{l s='Click Here!' mod='straal'}</button>
                {else}
                    {l s="You can use the following url to finish the payment by Straal"} - <a href="{$current_url}" id="goUrl" class="btn btn-primary pointer" target="blank_">{l s='Click Here!' mod='straal'}</a>
                {/if}
            {else}
                {l s='Your payment was processed successfully.' mod='straal'}
            {/if}
        </div>
    </div>
</div>
<script>

        $('#generateUrl').on('click', function(){
            $.ajax({
                url: '/index?fc=module&module=straal&controller=ajax',
                data:{
                    token: new Date().getTime(),
                    id_order: {$id_order},
                    action: 'createUrl',
                    method: 'createUrl'
                },
                method:'POST',
                dataType: 'json',
                }).done(
                function(data){
                    console.log("Url created!");
                    document.location.reload(true);
                }
            );
        });

</script>
{/if}



