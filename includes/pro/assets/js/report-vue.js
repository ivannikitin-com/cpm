document.addEventListener( 'DOMContentLoaded', function( ) {

    //deractive for datepicker
    Vue.directive( 'datepicker', {
        bind: function() {
            jQuery( this.el ).datepicker( {
                dateFormat: 'yy-m-dd',
            } );
        }
    } );


    //deractive for datepicker
    Vue.directive( 'select', function() {
        var self = this;
        jQuery( this.el ).on( 'change', function() {
            vm.selectedproject = this.value;
            var pdata = {
                action: 'cpm_report_filtermilestone',
                _wpnonce: CPM_Vars.nonce,
                project: vm.selectedproject
            };
            jQuery.post( CPM_Vars.ajaxurl, pdata, function( res ) {
                res = JSON.parse( res );
                vm.milestonelist = res;
            } );
        } );
    } );

    // define a mixin object
    var myMixin = {
        methods: {
            filterreport: function( formid ) {
                var form = jQuery( "#" + formid ).serialize();
                jQuery.post( CPM_Vars.ajaxurl, form, function( res ) {
                    res = JSON.parse( res );
                    vm.datalist = res.output;
                    vm.reporttitle = res.reporttitle;
                    vm.selectedproject = res.selectedproject;
                    vm.selectedcoworder = res.selectedcoworder;
                    vm.countresult = res.countresult;
                    vm.extrahead = res.extrahead;
                    vm.dataloading = false;
                    vm.modalhide();
                } );
            },
        }
    }
// register modal component
    Vue.component( 'overduemodal', {
        mixins: [ myMixin ],
        template: '#modal-template',
        props: [ 'show', 'modalwide', 'wpnonce', 'projectonlyModal', 'formaction', 'datalist', 'advanceSearch', 'countresult', 'reportmode', 'dashboadmode' ],
        methods: {
        },
    } );

    Vue.component( 'usermodal', {
        mixins: [ myMixin ],
        template: '#modal-template-range',
        props: [ 'show', 'modalwide', 'wpnonce', 'projectonlyModal', 'formaction', 'datalist', 'advanceSearch', 'countresult', 'daterange', 'reportmode', 'dashboadmode' ],
        methods: {
        },
    } );

    Vue.component( 'projectonlymodal', {
        mixins: [ myMixin ],
        template: '#modal-template-status',
        props: [ 'show', 'modalwide', 'wpnonce', 'projectonlyModal', 'formaction', 'datalist', 'advanceSearch', 'countresult', 'daterange', 'reportmode', 'dashboadmode' ],
        methods: {
        },
    } );

    Vue.component( 'milestonemodal', {
        mixins: [ myMixin ],
        template: '#projectmilestoneModal',
        props: [ 'show', 'modalwide', 'wpnonce', 'projectonlyModal', 'formaction', 'datalist', 'countresult', 'reportmode', 'dashboadmode', 'milestonelist' ],
        methods: {
        },
    } );
    // For avdvnce sarch
    Vue.component( 'advancesearchmodal', {
        mixins: [ myMixin ],
        template: '#advance-search-template',
        props: [ 'show', 'modalwide', 'wpnonce', 'advancesearchModal', 'formaction', 'datalist', 'countresult', 'reportmode', 'dashboadmode' ],
        methods: {
        },
    } );

// start app
    var today = new Date();
    var start_date = today.getFullYear() + "-" + ( today.getMonth() + 1 ) + "-01";
    var end_date = today.getFullYear() + "-" + ( today.getMonth() + 1 ) + "-31";
    var vm = new Vue( {
        mixins: [ myMixin ],
        el: '#cpm-report',
        data: {
            advancesearchModal: false,
            showModal: false,
            usershowModal: false,
            projectonlyModal: false,
            milestoneModal: false,
            modalwide: '400px',
            formaction: '',
            datalist: null,
            milestonelist: null,
            reporttitle: "",
            selectedproject: '',
            selectedcoworder: '',
            completedate: false,
            duedate: false,
            dataloading: false,
            countresult: 0,
            reportmode: false,
            dashboadmode: true,
            showTime:true,
            currentfiltermodal: 'showModal',
            extrahead: '',
            reporticon: 'overdue_task.svg',
            wpnonce: CPM_Vars.nonce,
            daterange: {
                start: start_date,
                end: end_date
            },
            selectmilestone: '',
        },
        ready: function( ) {

        },
        methods: {
            switcreporthmode: function() {
                this.reportmode = !this.reportmode;
                this.dashboadmode = !this.dashboadmode;
            },
            modalhide: function() {
                vm.showModal = false;
                vm.usershowModal = false;
                vm.projectonlyModal = false;
                vm.milestoneModal = false;
                vm.advancesearchModal = false;
            },
            showmodal: function() {
                if ( this.currentfiltermodal === 'showModal' )
                    this.showModal = true;
                if ( this.currentfiltermodal === 'usershowModal' )
                    this.usershowModal = true;
                if ( this.currentfiltermodal === 'projectonlyModal' )
                    this.projectonlyModal = true;
                if ( this.currentfiltermodal === 'milestoneModal' )
                    this.milestoneModal = true;
                if ( this.currentfiltermodal === 'advancesearchModal' )
                    this.advancesearchModal = true;
            },
            showreport: function( report ) {
                var report = report;
                var ajaxdata = { };
                this.datalist = null;
                this.currentfiltermodal = 'showModal';

                this.reporttitle = '';
                // Overdue Report
                if ( report == 'overdue' ) {
                    this.completedate = false;
                    this.duedate = true;
                    this.formaction = 'cpm_report_overdue_task';
                    //this.reporticon = 'overdue_task.svg';
                    vm.$set('reporticon', 'overdue_task.svg')
                }
                // Complete task
                if ( report == 'completetask' ) {
                    this.formaction = 'cpm_report_complete_task';
                    this.completedate = true;
                    this.duedate = false;
                    vm.reporticon = 'completed_task.svg';
                }
                // User task and activity
                if ( report == 'useractivity' ) {
                    this.formaction = 'cpm_report_useractivity';
                    this.completedate = true;
                    this.duedate = true;
                    this.currentfiltermodal = 'usershowModal';
                    vm.reporticon = 'user_activity.svg';
                    ajaxdata['daterange'] = this.daterange;
                    vm.usershowModal = true;
                    this.switcreporthmode();
                    return;
                }

                if ( report == 'taskbyproject' ) {
                    this.formaction = 'cpm_report_taskbyproject';
                    this.completedate = true;
                    this.duedate = true;
                    this.currentfiltermodal = 'projectonlyModal';
                    vm.projectonlyModal = true;
                    vm.reporticon = 'taskby_project.svg';
                    this.switcreporthmode();
                    return;
                }

                if ( report == 'taskbymilestone' ) {
                    this.milestonelist = [
                        { text: 'Select  project first', val: '' }
                    ];
                    this.formaction = 'cpm_report_taskbymilestone';
                    this.completedate = true;
                    this.duedate = true;
                    this.currentfiltermodal = 'milestoneModal';
                    vm.milestoneModal = true;
                    vm.reporticon = 'taskby_milestone.svg';
                    this.switcreporthmode();
                    return;
                }

                if ( report == 'unassignedtask' ) {
                    this.formaction = 'cpm_report_unassignedtask';
                    this.completedate = true;
                    this.duedate = true;
                    vm.reporticon = 'unassign_task.svg';
                }
                if ( report == 'advancesearch' ) {
                    this.formaction = 'cpm_report_advancesearch';
                    this.completedate = true;
                    this.duedate = true;
                    this.currentfiltermodal = 'advancesearchModal';

                    vm.advancesearchModal = true;
                    vm.reporticon = 'unassign_task.svg';
                    this.switcreporthmode();
                    return;
                }

                this.switcreporthmode();
                this.dataloading = true;
                ajaxdata['action'] = this.formaction;
                ajaxdata['_wpnonce'] = CPM_Vars.nonce;

                jQuery.post( CPM_Vars.ajaxurl, ajaxdata, function( res ) {
                    res = JSON.parse( res );
                    vm.datalist = res.output;
                    vm.reporttitle = res.reporttitle;
                    vm.selectedproject = res.selectedproject;
                    vm.selectedcoworder = res.selectedcoworder;
                    vm.countresult = res.countresult;
                    vm.extrahead = res.extrahead;
                    vm.showModal = false;
                    vm.dataloading = false;

                } );
            },
            exporttocsv: function() {
                var newForm = jQuery( '<form>', {
                    'action': 'admin-ajax.php?action=cpm_report_csv_output',
                    'target': '_top',
                    'method': 'post'
                } ).append( jQuery( '<input>', {
                    'name': '_wpnonce',
                    'value': CPM_Vars.nonce,
                    'type': 'hidden'
                } ) );
                newForm.submit();
            },
        },
        computed: {
            reporticon: function() {
                return vm.reporticon ;
            },
            wpnonce: function() {
                return CPM_Vars.nonce;
            },
        }
    } );


} );
