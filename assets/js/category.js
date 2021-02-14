/**
 * Модуль для обработки события при изменении
 * выбора категории задачи 
 */
(function($) {
    $( "#tasklist_category_term_id" ).change(function() {  
        let termId = $( "#tasklist_category_term_id" ).val();              
        let getDateForm = new FormData();
        getDateForm.append("action", "cpm_put_task_list_trem_id"); 
        getDateForm.append("termid", termId);

        $.ajax({
            url:CPM_Vars.ajaxurl, 
            data:getDateForm,
            processData : false,
            contentType : false,              
            type:'POST',  
            success:function(request){                               
               $('#show_request').html(request);           
                               
            }
        });
       
        
    }); 
})(jQuery);