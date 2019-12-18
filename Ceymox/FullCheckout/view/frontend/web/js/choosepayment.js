define([
    "jquery",
], function ($) {
    'use strict';
    return function(config)  {
        $("a").click(function(){
            var id = $(this).attr('id');
            paymentoption(id);
         });
        $(document).ready(function() {
            var id ='klarana';
            paymentoption(id);
        });

        function paymentoption(id) {
            if(id=='klarana'){
                var data='flag='+2;
            }
            else if(id=='other'){
                var data='flag='+3;
            }
            else{
                var data='flag='+1;
            }     
            $.ajax({
                showLoader: true,
                url: config.customurl,
                type: 'POST',
                data: data,
                dataType: 'json',
                complete: function (xhr, status) {
                    if (status === 'error' || !xhr.responseText) {
                        handleError();
                    }
                    else {
                        var data = xhr.responseText;
                        $("#tab-"+id).html(data);    
                    }
                }
            })            
        }
    }
});