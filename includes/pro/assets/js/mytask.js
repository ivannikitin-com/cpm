(function($) {

	$(document).on('cpm.markDone.after', function( event, res, self ) {

	    var wrap = $('.my-tasks'),
	    header = self.closest('ul.cpm-uncomplete-mytask');

	    if( $('.cpm-uncomplete-mytask').children('li').length <= 1 ) {
	        $('.cpm-no-task').fadeIn(1500);
	    }
	    if( header.children('li').length <= 1 ) {
	        header.closest('li').remove();
	    }
	    wrap.find('.cpm-mytas-current').text( res.current_task );
	    wrap.find('.cpm-mytas-outstanding').text( res.outstanding);
	    wrap.find('.cpm-mytas-complete').text(res.complete);
	});

	$(document).on('cpm.markUnDone.after', function(e, res, self) {

	    var wrap = $('.my-tasks'),
	    header = $('.cpm-my-todolists').children('li');
	    $.map( header, function( value, key ) {
	    	var li = $(value),
	    		length = li.find('.cpm-todo-completed').find('li').length;
	    	if( length == 0) {
	    		li.remove();
	    	}
	    });
	    if( $('.cpm-todo-completed').children('li').length <= 0 ) {
	        $('.cpm-no-task').fadeIn(1500);
	    }
	    wrap.find('.cpm-mytas-current').text( res.current_task );
	    wrap.find('.cpm-mytas-outstanding').text( res.outstanding);
	    wrap.find('.cpm-mytas-complete').text(res.complete );
	});

        $("body").on('click', ".cpm-my-task-menu li a", function(e){
            /* e.preventDefault();
             $(".cpm-my-tasks .cpm-my-task-menu li").removeClass('active') ;
             $(this).parent('li').addClass('active');
             var ctab = $(this).attr('data-item') ;
             var cuser = $(this).attr('data-user') ;
             // Load pages
             var data = {
                    action: 'get_mytask_content',
                    tab_act : ctab,
                    user : cuser,
                    _wpnonce: CPM_Vars.nonce
                };
                $.post(CPM_Vars.ajaxurl, data, function(resp) {
                    if (resp) {
                        $('#cpm-mytask-page-content').html(resp);
                    }
                }); */
        });

        $("body").on('change', ".cpm-mytask-switch-user", function(){
            var uid = $(this).val() ;
            var tab = $(this).attr('data-tab') ;

            var url = window.location.pathname+"?page=cpm_task&user_id="+uid+"&tab="+tab ;
             window.location.href =  url;

        });

        $("body").on('change', "#mytask-change-range", function(e){
             e.preventDefault();
             var v = $(this).val() ;
             var user = $('option:selected', this).attr('data-user') ;
             var data = {
                    action: 'user_line_graph',
                    range : v,
                    user : user,
                    _wpnonce: CPM_Vars.nonce
                };
                $.post(CPM_Vars.ajaxurl, data, function(resp) {
                    if (resp) {

                       $('#mytask-line-graph').html(resp);
                    }
                });


        });

        $("body").on('click', ".cpm-load-more-ua", function(e){
            e.preventDefault();
                var self = $(this),
                    total = self.data('total'),
                    start = parseInt(self.data('start')),
                    data = {
                        user_id: self.data('user_id'),
                        offset: start,
                        action: 'get_user_activity',
                        _wpnonce: CPM_Vars.nonce
                    };
                self.append('<div class="cpm-loading">Loading...</div>');
                $.get(CPM_Vars.ajaxurl, data, function(res) {
                    res = $.parseJSON(res);
                    if (res.success) {
                        start = res.count + start;
                        self.prev('.cpm_activity_list').append(res.content);
                        self.data('start', start);
                    } else {
                        self.remove();
                    }

                    $('.cpm-loading').remove();
                });




        });



})(jQuery);