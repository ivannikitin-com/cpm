document.addEventListener( 'DOMContentLoaded', function( ) {
// register modal component


    Vue.directive( 'fileupload', {
        bind: function( ) {
            new CPM_Uploader( 'cpm-upload-pickfiles-nd', 'cpm-upload-container-nd' );
        }
    } );
    Vue.directive( 'colorimg', {
        bind: function( ) {
            jQuery( 'body .cpm-colorbox-img' ).prettyPhoto( );
        }
    } );
// Maxxin
    var myMixin = {
        data: {
            text: CPM_pro_files.static_text,
        },
        methods: {
            hideAllform: function( ) {
                vm.docFormShow = false;
                vm.googleDocsForm = false;
                vm.uploadFormShow = false;
            },
            deletefile: function( file_id ) {

                if ( confirm( this.text.delete_file_confirm ) ) {
                    var self = this;
                    var file_id = file_id;
                    var data = {
                        file_id: file_id,
                        action: 'cpm_delete_file',
                        '_wpnonce': CPM_Vars.nonce
                    };
                    jQuery.post( CPM_Vars.ajaxurl, data, function( ) {} );
                    jQuery( "#" + file_id ).closest( '.cpm-uploaded-item' ).remove( );
                }
            },
            readDoc: function( doc ) {
                vm.viewDoc = doc;
                vm.viewDocOrg = doc;
                vm.getComments( doc.post_id );
                vm.getRevissions( doc.post_id );
                vm.revisionMode = false;
                vm.readDocModal = true;
                this.checkDocIcon( vm.viewDoc.doclink );
            },
            checkDocIcon: function( dlink ) {
                
                var link = dlink;
                    var icon = CPM_pro_files.base_url + "/includes/pro/assets/images/others.svg";
                    var g = link.search( /google.com/i );
                    if ( g != -1 ) {
                        icon = CPM_pro_files.base_url + "/includes/pro/assets/images/google_drive.svg";
                        // check google docs
                        g = link.search( /document/i );
                        if ( g != -1 ) {
                            icon = CPM_pro_files.base_url + "/includes/pro/assets/images/google_docs.svg";
                        }

                        g = link.search( /spreadsheets/i );
                        if ( g != -1 ) {
                            icon = CPM_pro_files.base_url + "/includes/pro/assets/images/google_spreadsheets.svg";
                        }

                        g = link.search( /presentation/i );
                        if ( g != -1 ) {
                            icon = CPM_pro_files.base_url + "/includes/pro/assets/images/google_presentation.svg";
                        }

                    }

                    g = link.search( /dropbox.com/i );
                    if ( g != -1 ) {
                        icon = CPM_pro_files.base_url + "/includes/pro/assets/images/dropbox.svg";
                    }

                    g = link.search( /live.com/i );
                    if ( g != -1 ) {
                        icon = CPM_pro_files.base_url + "/includes/pro/assets/images/sky_drive.svg";
                    }

                    return  icon;
            },
            loadmorefile: function() {
                vm.dataLoading = true;
                var data = {
                    action: 'cpm_pro_get_more_files',
                    _wpnonce: CPM_Vars.nonce,
                    project_id: CPM_pro_files.current_project,
                    offset: this.fileoffset,
                }
                jQuery.post( CPM_Vars.ajaxurl, data, function( res ) {
                    res = JSON.parse( res );
                    if ( res.success == true ) {

                        if ( res.file_list != null ) {
                            for ( var i = 0; i < res.file_list.length; i++ ) {
                                var rf = res.file_list[i];
                                var file_obj = {
                                    id: rf.id,
                                    attachment_id: rf.attachment_id,
                                    parent: rf.parent,
                                    private: rf.private,
                                    thumb: rf.thumb,
                                    file_url: rf.file_url,
                                    css_class: rf.css_class,
                                    name: rf.name,
                                    attach_text: rf.attach_text,
                                    topic_url : rf.topic_url,
                                    content: '',
                                    attachment: null,
                                    comment_count: rf.comment_count,
                                    type: rf.type,
                                    post_id: '',
                                    created_by: rf.created_by,
                                    created_name: '',
                                    created_at: '',
                                    permission: false,
                                };
                                vm.filelist.push( file_obj );

                            }
                            vm.fileoffset = res.file_offset;
                            vm.dataLoading = false;
                            vm.showLoadMoreBtn() ;
                        }


                    }
                    vm.dataLoading = false;
                } );
                this.getfolderLinks( );

            },
            showLoadMoreBtn: function(){
                if(vm.project_obj.total_attach_doc >= vm.fileoffset && vm.currntfolder == 0){
                    vm.showMoreBtn = true;
                }else{
                    vm.showMoreBtn = false;
                }
            },
            showLoading: function( ) {
                //this.dataLoading = true;
            },
            hideLoading: function( ) {
                jQuery( ".cpm-data-load-before" ).hide( );
                jQuery( ".cpm-pro-file-container" ).show( );
                //this.dataLoading = false
            },
        }
    }


    Vue.component( 'modal', {
        mixins: [ myMixin ],
        template: require( './../html/files/createfolder.html' ),
        props: [ 'show', 'text', 'folderLink', 'modalwide', 'baseurl', 'folderName', 'current_project', 'parent', 'folderlist', 'privacy', 'formAction', 'empty_filesfolder' ],
        methods: {
            createFolder: function( ) {
                var name = this.folderName, projectid = this.current_project, parent = this.parent, privacy = this.privacy, folderlist = this.folderlist;
                // AJAX call for create new folder
                var data = {
                    action: 'cpm_pro_folder_new',
                    _wpnonce: CPM_Vars.nonce,
                    name: name,
                    privacy: privacy,
                    project_id: CPM_pro_files.current_project,
                    parent: parent,
                }
                var self = this;
                jQuery.post( CPM_Vars.ajaxurl, data, function( res ) {
                    res = JSON.parse( res );
                    if ( res.success === true ) {

                        var new_folder = {
                            id: res.id,
                            name: name,
                            parent: parent,
                            private: res.private,
                            permission: true,
                            //

                        };
                        vm.showModal = false;
                        vm.folderlist.push( new_folder );
                        vm.empty_filesfolder = false;
                        self.folderName = '';
                        self.privacy = false;
                    } else {
                        alert( res.error );
                        self.folderName = '';
                        self.privacy = false;
                    }
                } );
                // After success AJAX call.


            },
        },
        ready: function( ) {
            this.baseurl = CPM_pro_files.base_url;
        }
    } );
    Vue.component( 'readdocmodal', {
        template: require( './../html/files/readdocmodal.html' ),
        mixins: [ myMixin ],
        props: [ 'show', 'text', 'currentIcon', 'modalwide', 'current_project', 'wp_nonce', 'revisionMode', 'readDocModal', 'viewDoc', 'docEidted', 'docFormShow', 'comments', 'docRevisions' ],
        ready: function( ) {

        },
        methods: {
            closeDocRead: function( ) {
                this.docEidted = false;
                vm.readDocModal = false;
                vm.comments = null;
            },
            updateDoc: function( ) {
                var data = jQuery( "#doc-update-form" ).serialize( ), self = this;
                jQuery.post( CPM_Vars.ajaxurl, data, function( res ) {
                    res = JSON.parse( res );
                    var d = res.document;
                    if ( res.success == true ) {
                        self.viewDoc.name = d.name;
                        self.viewDoc.full_name = d.full_name;
                        self.viewDoc.content = d.content;
                        self.viewDoc.doclink = d.doclink;
                        self.viewDoc.attachment = d.attachment;
                        self.viewDoc.private = d.private;
                        /*jQuery( '#doc-update-form .cpm-upload-filelist' ).html( '' );
                         jQuery( '#doc-update-form input[name="description"]' ).val( '' );
                         jQuery( "#doc-update-form trix-editor" ).val( '' );*/
                        //
                        self.docEidted = false;
                        vm.getRevissions( self.viewDoc.post_id );
                    } else {
                        alert( res.error );
                    }
                } );
            },
            createDocComment: function( ) {
                var data = jQuery( "#new_comment_form" ).serialize( ), self = this;
                if ( jQuery( "#new_comment_form #coment-content" ).val( ) == '' ) {
                    alert( vm.text.empty_comment );
                    return;
                }
                jQuery.post( CPM_Vars.ajaxurl, data, function( res ) {
                    res = JSON.parse( res );
                    var c = res.comment;
                    if ( res.success == true ) {
                        //
                        var comment_obj = {
                            comment_ID: c.comment_ID,
                            comment_author: c.comment_author,
                            comment_author_email: c.comment_author_email,
                            comment_content: c.comment_content,
                            comment_date: c.comment_date,
                            comment_post_ID: c.comment_post_ID,
                            files: c.files,
                            user_id: c.user_id,
                            avatar: c.avatar
                        }
                        vm.comments.push( comment_obj );
                        jQuery( '#new_comment_form .cpm-upload-filelist' ).html( '' );
                        jQuery( '#new_comment_form input[name="description"]' ).val( '' );
                        jQuery( "#new_comment_form trix-editor" ).val( '' );
                        //

                    } else {
                        alert( res.error );
                    }
                } );
            },
            showDocRev: function( doc ) {
                vm.viewDoc = doc;
                vm.revisionMode = true;
            },
            showOrgDoc: function( ) {
                vm.revisionMode = false;
                vm.viewDoc = vm.viewDocOrg;
            },
        },
    } );
    Vue.component( 'doccreate', {
        template: require( './../html/files/docform.html' ),
        mixins: [ myMixin ],
        props: [ 'show', 'text', 'currntfolder', 'docFormShow', 'folderlist', 'formAction', 'current_project', 'wp_nonce' ],
        methods: {
            sendDocForm: function( ) {
                var form = jQuery( "#cpm-doc-form" ).serialize( );
                jQuery.post( CPM_Vars.ajaxurl, form, function( res ) {
                    res = JSON.parse( res );
                    var nd = res.document;
                    var file_obj = {
                        id: nd.id,
                        attachment_id: nd.attachment_id,
                        parent: nd.parent_id,
                        private: nd.private,
                        thumb: '',
                        file_url: '',
                        css_class: nd.css_class,
                        name: nd.name,
                        full_name: nd.full_name,
                        content: nd.content,
                        attachment: nd.attachment,
                        comment_count: nd.comment_count,
                        type: 'doc',
                        post_id: nd.post_id,
                        created_by: nd.created_by,
                        created_name: nd.created_name,
                        created_at: nd.created_at,
                        permission: nd.permission,
                    };
                    vm.filelist.push( file_obj );
                    vm.docFormShow = false;
                    jQuery( '#cpm-doc-form .cpm-upload-filelist' ).html( '' );
                    jQuery( '#cpm-doc-form input[name="title"]' ).val( '' );
                    jQuery( '#cpm-doc-form input[name="description"]' ).val( '' );
                    jQuery( "trix-editor" ).val( '' );
                } );
            },
        },
    } );
    Vue.component( 'googledoccreate', {
        template: require( './../html/files/googledocform.html' ),
        mixins: [ myMixin ],
        props: [ 'show', 'text', 'doclink', 'currentIcon', 'currntfolder', 'googleDocsForm', 'folderlist', 'formAction', 'current_project', 'wp_nonce' ],
        methods: {
            sendGoogleDocForm: function( ) {
                var form = jQuery( "#cpm-googledoc-form" ).serialize( );
                jQuery.post( CPM_Vars.ajaxurl, form, function( res ) {
                    res = JSON.parse( res );
                    var nd = res.document;
                    var file_obj = {
                        id: nd.id,
                        attachment_id: nd.attachment_id,
                        parent: nd.parent_id,
                        private: nd.private,
                        thumb: '',
                        file_url: '',
                        css_class: nd.css_class,
                        name: nd.name,
                        full_name: nd.full_name,
                        content: nd.content,
                        doclink: nd.doclink,
                        attachment: nd.attachment,
                        comment_count: nd.comment_count,
                        type: 'google_doc',
                        post_id: nd.post_id,
                        created_by: nd.created_by,
                        created_name: nd.created_name,
                        created_at: nd.created_at,
                        permission: nd.permission,
                    };
                    vm.filelist.push( file_obj );
                    vm.googleDocsForm = false;
                    jQuery( '#cpm-googledoc-form input[name="title"]' ).val( '' );
                    jQuery( '#cpm-googledoc-form input[name="doclink"]' ).val( '' );
                    jQuery( '#cpm-googledoc-form input[name="description"]' ).val( '' );
                    jQuery( "trix-editor" ).val( '' );
                } );
            },
            getDocIcon: function( ) {
                var icon = vm.checkDocIcon( this.doclink );
                vm.currentIcon = icon;
            }
        },
    } );
    Vue.component( 'fileuploader', {
        template: require( './../html/files/fileuploader.html' ),
        mixins: [ myMixin ],
        props: [ 'files', 'baseurl', 'text' ],
        methods: {
        },
        ready: function( ) {
            new CPM_Uploader( 'cpm-upload-pickfiles-dc', 'cpm-upload-container-dc' );
        }

    } );
    Vue.component( 'prettyphoto', {
        template: require( './../html/files/imageview.html' ),
        mixins: [ myMixin ],
        props: [ 'file', 'colorbox="false"' ],
        methods: {
        },
        ready: function( ) {
            jQuery( '.cpm-colorbox-img' ).prettyPhoto( );
        }

    } );
    Vue.component( 'onlinedocs', {
        template: require( './../html/files/onlinedocs.html' ),
        mixins: [ myMixin ],
        props: [ 'file', 'currentIcon' ],
        methods: {
        },
        ready: function( ) {
            vm.checkDocIcon( this.file.doclink );
        }

    } );
    Vue.component( 'blanktemplate', {
        template: require( './../html/files/blanktemplate.html' ),
        mixins: [ myMixin ],
        props: [ 'dataLoading', 'folderlist', 'filelist' ],
        methods: {
        },
        ready: function( ) {
        }

    } );
    Vue.component( 'dataloading', {
        template: require( './../html/files/dataloading.html' ),
        mixins: [ myMixin ],
        props: [ 'dataLoading' ],
        methods: {
        },
        ready: function( ) {
        }

    } );
    // start app
    var vm = new Vue( {
        el: '#myapp',
        mixins: [ myMixin ],
        data: {
            fullLoad: false,
            showModal: false,
            docForm: false,
            uploadFormShow: false,
            dataLoading: false,
            docFormShow: false,
            googleDocsForm: false,
            editeddoc: null,
            docEidted: false,
            readDocModal: false,
            revisionMode: false,
            comments: null,
            viewDoc: null,
            viewDocOrg: null,
            docRevisions: null,
            currentIcon: CPM_pro_files.base_url + "/includes/pro/assets/images/others.svg",
            modalwide: '400px',
            current_project: CPM_pro_files.current_project,
            currntfolder: 0,
            backtofolder: 0,
            formAction: 'createFolder',
            folderlist: [ ],
            filelist: [ ],
            folderlink: [ ],
            fileoffset: 0,
            editedFolder: null,
            showeditModal: false,
            showMoreBtn: false,
            emptyList: false,
            wp_nonce: CPM_Vars.nonce,
            projectid: CPM_pro_files.current_project,
            project_obj: CPM_pro_files.project_obj,
            baseurl: CPM_pro_files.base_url,
            folderLink: CPM_pro_files.base_url + "/includes/pro/assets/images/folder.png",
        },
        ready: function( ) {
            this.getfolders( );
            this.dataLoading = false;
            this.fullLoad = true;
            this.hideLoading();
        },
        methods: {
            getfolders: function( ) {
                this.dataLoading = true;
                this.showMoreBtn =  false;
                var self = this;
                this.emptyList = false;
                var data = {
                    action: 'cpm_pro_get_file_folder',
                    _wpnonce: CPM_Vars.nonce,
                    project_id: CPM_pro_files.current_project,
                    parent: this.currntfolder,
                }
                this.folderlist = [ ];
                this.filelist = [ ];
                jQuery.post( CPM_Vars.ajaxurl, data, function( res ) {
                    res = JSON.parse( res );
                    if ( res.success == true ) {
                        if ( res.folder_list != null ) {
                            vm.folderlist = res.folder_list;
                        }
                        if ( res.file_list != null ) {
                            vm.filelist = res.file_list;
                        }

                        if ( res.folder_list == null && res.file_list == null ) {
                            self.emptyList = true;
                        }
                        self.backtofolder = res.backto;
                        self.currntfolder = res.current_folder;
                        self.fileoffset = res.file_offset;
                        self.showLoadMoreBtn() ;
                    }
                    self.dataLoading = false;
                } );
                this.getfolderLinks( );
            },
            getfolderLinks: function( ) {

                var self = this;
                var data = {
                    action: 'cpm_pro_get_folderpath',
                    _wpnonce: CPM_Vars.nonce,
                    project_id: CPM_pro_files.current_project,
                    fid: this.currntfolder,
                }
                this.folderlink = [ ];
                jQuery.post( CPM_Vars.ajaxurl, data, function( res ) {
                    res = JSON.parse( res );
                    if ( res.success == true ) {

                        self.folderlink = res.list;
                    }
                } );
            },
            updatefolder: function( folder ) {
                if ( !folder.permission ) {
                    return;
                }
                // this.editedfolder = folder;
                var folderobj = {
                    id: folder.id,
                    name: folder.name,
                    parent: folder.parent,
                    private: folder.private,
                    created_by: folder.created_by,
                }
                this.editedFolder = folder;
                this.beforeEditCache = folderobj;
                // this.showeditModal = true;
            },
            doneEdit: function( folder ) {
                if ( !this.editedFolder ) {
                    return;
                }
                // AJAX Call
                var self = this;
                var data = {
                    action: 'cpm_pro_folder_rename',
                    _wpnonce: CPM_Vars.nonce,
                    project_id: CPM_pro_files.current_project,
                    folderid: folder.id,
                    name: folder.name
                };
                jQuery.post( CPM_Vars.ajaxurl, data, function( res ) {
                    res = JSON.parse( res );
                    if ( res.success == true ) {
                        folder.name = folder.name.trim( );
                    } else {
                        vm.cancelEdit( folder );
                        alert( res.error );
                    }
                } );
                this.editedFolder = null;
            },
            cancelEdit: function( folder ) {
                this.editedFolder = null;
                folder.name = this.beforeEditCache.name;
                folder.private = this.beforeEditCache.private;
            },
            removeFolder: function( folder ) {
                if ( confirm( this.text.delete_folder ) ) {
                    var id = folder.id;
                    // Ajax call for delete folder and its content ...
                    var self = this;
                    var data = {
                        action: 'cpm_pro_folder_delete',
                        _wpnonce: CPM_Vars.nonce,
                        project_id: CPM_pro_files.current_project,
                        folderid: id,
                    }
                    jQuery.post( CPM_Vars.ajaxurl, data, function( res ) {
                        res = JSON.parse( res );
                        if ( res.success == true ) {
                            vm.folderlist.$remove( folder );
                        } else {
                            alert( res.error );
                        }
                    } );
                    // After success AJAX Call
                } else {
                    return false;
                }
            },
            uplaodfiles: function( ) {
                this.hideAllform( );
                var form = jQuery( "#newuploadform" ).serialize( );
                var self = this;
                jQuery.post( CPM_Vars.ajaxurl, form, function( res ) {
                    res = JSON.parse( res );
                    if ( res.success === true ) {
                        vm.uploadFormShow = false;
                        jQuery( '.form-uploader .cpm-upload-filelist' ).html( '' );
                        for ( var i = 0; i < res.file_list.length; i++ ) {
                            var rf = res.file_list[i];
                            var file_obj = {
                                id: rf.id,
                                attachment_id: rf.attachment_id,
                                parent: rf.parent,
                                private: rf.private,
                                thumb: rf.thumb,
                                file_url: rf.file_url,
                                css_class: rf.css_class,
                                name: rf.name,
                                full_name: rf.full_name,
                                content: '',
                                attachment: null,
                                comment_count: 0,
                                type: 'attach',
                                post_id: rf.post_id,
                                created_by: rf.created_by,
                                created_name: '',
                                created_at: '',
                                permission: true,
                            };
                            vm.filelist.push( file_obj );
                        }

                    } else {
                        alert( res.error );
                    }
                } );
            },
            delfile: function( file ) {
                var id = file.id;
                // Ajax call for delete folder and its content ...
                if ( confirm( this.text.delete_file ) ) {
                    var self = this;
                    var data = {
                        action: 'cpm_delete_uploded_file',
                        _wpnonce: CPM_Vars.nonce,
                        project_id: CPM_pro_files.current_project,
                        file_id: id,
                    }
                    jQuery.post( CPM_Vars.ajaxurl, data, function( res ) {
                        res = JSON.parse( res );
                        if ( res.success == true ) {
                            vm.filelist.$remove( file );
                        } else {
                            alert( res.error );
                        }
                    } );
                }
            },
            updatePrivacy: function( folder ) {
                if ( confirm( this.text.change_file_privacy ) ) {
                    var folderobj = {
                        id: folder.id,
                        name: folder.name,
                        parent: folder.parent,
                        private: ( folder.private == 'yes' ) ? '1' : 0,
                        created_by: folder.created_by,
                    }
                    this.beforeEditCache = folderobj;
                    var self = this;
                    var data = {
                        action: 'cpm_pro_change_ff_privacy',
                        _wpnonce: CPM_Vars.nonce,
                        project_id: CPM_pro_files.current_project,
                        attach_id: folder.id,
                        cprivacy: folder.private,
                    };
                    jQuery.post( CPM_Vars.ajaxurl, data, function( res ) {
                        res = JSON.parse( res );
                        if ( res.success == true ) {
                            folder.private = res.privacy;
                        } else {
                            // vm.cancelEdit( folder );
                            alert( res.error );
                        }
                    } );
                    this.editedFolder = null;
                }
            },
            updateFilePrivacy: function( file ) {
                if ( confirm( this.text.change_file_privacy ) ) {
                    var self = this;
                    var data = {
                        action: 'cpm_pro_change_ff_privacy',
                        _wpnonce: CPM_Vars.nonce,
                        project_id: CPM_pro_files.current_project,
                        attach_id: file.id,
                        cprivacy: file.private,
                    };
                    jQuery.post( CPM_Vars.ajaxurl, data, function( res ) {
                        res = JSON.parse( res );
                        if ( res.success == true ) {
                            file.private = res.privacy;
                        } else {
                            // vm.cancelEdit( folder );
                            alert( res.error );
                        }
                    } );
                }
            },
            fileUploadShow: function( ) {
                this.hideAllform( );
                this.uploadFormShow = true;
            },
            showfolder: function( id ) {
                this.hideAllform( );
                this.currntfolder = id;
                this.getfolders( );
            },
            createNewDoc: function( ) {
                this.hideAllform( );
                vm.docFormShow = true;
                vm.formAction = 'cpm_create_new_doc';
            },
            createGoogleDoc: function( ) {
                this.hideAllform( );
                vm.googleDocsForm = true;
                vm.formAction = 'cpm_create_goole_doc';
            },
            getComments: function( docid ) {
                var data = {
                    action: 'cpm_get_doc_comments',
                    _wpnonce: CPM_Vars.nonce,
                    doc_id: docid,
                }
                var self = this;
                self.comments = [ ];
                jQuery.post( CPM_Vars.ajaxurl, data, function( res ) {
                    res = JSON.parse( res );
                    if ( res.success == true ) {
                        self.comments = res.comments;
                    } else {
                        alert( res.error );
                    }
                } );
            },
            getRevissions: function( docid ) {
                var data = {
                    action: 'cpm_get_doc_revision',
                    _wpnonce: CPM_Vars.nonce,
                    doc_id: docid,
                }
                jQuery.post( CPM_Vars.ajaxurl, data, function( res ) {
                    res = JSON.parse( res );
                    if ( res.success == true ) {
                        vm.docRevisions = res.revisions;
                    } else {
                        alert( res.error );
                    }
                } );
            }
            // End Method

        },
        // Custom Derictivr
        directives: {
            'edit-focus': function( value ) {
                //alert(value);
                if ( !value ) {
                    return;
                }
                var el = this.el;
                Vue.nextTick( function( ) {
                    el.focus( );
                } );
            }
        }
    } )


} );