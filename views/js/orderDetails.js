$(document).ready(function(){
    $('#generateUrl').on('click', function(){
        $.ajax({
            url: '/index?fc=module&module=straal&controller=ajax',
            data:{
                token: new Date().getTime(),
                id_order: rensr_id_order,
                action: 'createUrl'
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
});