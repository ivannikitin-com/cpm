(function e(t,n,r){function s(o,u){if(!n[o]){if(!t[o]){var a=typeof require=="function"&&require;if(!u&&a)return a(o,!0);if(i)return i(o,!0);var f=new Error("Cannot find module '"+o+"'");throw f.code="MODULE_NOT_FOUND",f}var l=n[o]={exports:{}};t[o][0].call(l.exports,function(e){var n=t[o][1][e];return s(n?n:e)},l,l.exports,e,t,n,r)}return n[o].exports}var i=typeof require=="function"&&require;for(var o=0;o<r.length;o++)s(r[o]);return s})({1:[function(require,module,exports){
module.exports = '<div class="cpm-blank-template file-list">\n    <div class="cpm-content" >\n        <h2 class="cpm-page-title">Documents & Files</h2>\n\n        <p>\n            Access all the files from all threads in one place. Store your project related files, docs, images or any other files on-site. Makes sharing and storing easy and convenient, plus you can add privacy here too.\n        </p>\n\n        <div class="cpm-list-content">\n            <h2 class="cpm-page-title cpm-why-for">Benefits of Documents</h2>\n\n            <ul class="cpm-list">\n                <li>Integrate easily with projects or tasks.</li>\n                <li>Shared storage keeps all data secure.</li>\n                <li>File sharing privacy.</li>\n            </ul>\n        </div>\n\n    </div>\n</div>';
},{}],2:[function(require,module,exports){
module.exports = ' <div class="modal-mask" v-show="show" transition="modal">\n    <div class="modal-wrapper">\n        <div class="modal-container"  style="width:{{modalwide}}">\n\n            <div class="modal-header">\n                <span class="cpm-right close-vue-modal"> <a class=""  @click="show = false">X</a> </span>\n                <slot name="header">\n                    {{text.create_folder}}\n                </slot>\n            </div>\n            <form @submit.prevent="createFolder">\n                <input type="hidden" v-model="projectid" value="{{projectid}}" />\n                <input type="hidden" v-model="parent" value="{{parent}}" />\n\n                <div class="modal-body col-md-12">\n                    <div class="folder-image cpm-col-6 text-center">\n                        <img :src="folderLink"/>\n                        <div> {{folderName}} </div>\n                        <div></div>\n                    </div>\n                    <div class="folder-name">\n                        <input type=\'text\' value="{{folderName}}"  v-model="folderName" class=\'folder-name-input\' required />\n                        <br/>\n                        <label>  <input type="checkbox"  v-bind:true-value="1" v-bind:false-value="0"  v-model="privacy"   /> {{text.make_private}} </label>\n\n                    </div>\n                    <br clear="all"/>\n                    <div class="clearfix" ></div>\n                </div>\n\n                <div class="modal-footer">\n                    <slot name=\'submit\'><input type="submit" name="submit" class=" submit cpm-doc-btn" value="{{text.folder_create}}"    /></slot>\n                </div>\n            </div>\n        </form>\n\n    </div>\n</div>';
},{}],3:[function(require,module,exports){
module.exports = '<div v-if=\'dataLoading\' class="cpm-files-load">\n    <div class="loadmoreanimation">\n        <div class="load-spinner">\n            <div class="rect1"></div>\n            <div class="rect2"></div>\n            <div class="rect3"></div>\n            <div class="rect4"></div>\n            <div class="rect5"></div>\n        </div>\n    </div>\n\n</div>';
},{}],4:[function(require,module,exports){
module.exports = '<div class="docform" v-if="docFormShow" >\n    <div class="cpm-form-title">\n        {{text.create_document}}\n        <a href="#" class="cpm-right"  @click="hideAllform()"> <span class=" dashicons  dashicons-no"></span> </a>\n    </div>\n\n    <form  autocomplete="off" @submit.prevent="sendDocForm" id="cpm-doc-form" >\n        <input type="hidden" name="action" value="{{formAction}}" />\n        <input type="hidden" name="project_id" value="{{current_project}}" />\n        <input type="hidden" name="_wpnonce" value="{{wp_nonce}}" />\n        <input type="hidden" name="parent" value="{{currntfolder}}" />\n\n        <label>{{text.title}}</label>\n        <input type="text" name="title" value="{{editeddoc.name}}" width="100%" />\n\n        <div class="item message cpm-sm-col-12 ">\n            <input id="description" type="hidden" name="description" value="{{{editeddoc.name}}}">\n            <trix-editor input="description"></trix-editor>\n        </div>\n\n         <fileuploader :files="viewDoc.attachment" :text="text" ></fileuploader>\n\n        <div class="cpm-privacy"> <label>  <input type="checkbox" name="privacy" value="yes" /> {{text.make_private}} </label> </div>\n\n        <input type="submit" name="submit" class="button-primary"  value="{{text.create_doc}}" />\n\n    </form>\n</div>';
},{}],5:[function(require,module,exports){
module.exports = '<div class=\'cpm-attachment-area\'>\n    <div id=\'cpm-upload-container-dc\'>\n\n        <div class=\'cpm-upload-filelist\'>\n            <div class="cpm-uploaded-item" v-if="files.length" v-for="file in files"  >\n                <a href="{{file.url}}" target="_blank">\n                    <img :src="file.thumb" alt="{{file.name}}" />\n                </a>\n                <a href="#" data-id="{{file.id}}" id="{{file.id}}" class="cpm-delete-file button" @click.prevent="deletefile(file.id)">{{text.delete_file}}</a>\n                <input type="hidden" name="cpm_attachment[]" value="{{file.id}}">\n            </div>\n        </div>\n        <div class=\'clearfix\'></div>\n    </div>\n\n    <span class="dashicons dashicons-paperclip"></span> {{text.to_attach}}, <a href=\'#\' id=\'cpm-upload-pickfiles-dc\' class="" > {{text.select_file}} </a> {{text.from_computer}}.\n\n</div>';
},{}],6:[function(require,module,exports){
module.exports = '<div class="docform" v-if="googleDocsForm" >\n\n    <div class="cpm-form-title">\n        {{text.link_google_doc}}\n\n        <a href="#" class="cpm-right"  @click="hideAllform()"> <span class=" dashicons  dashicons-no"></span> </a>\n    </div>\n\n    <form  autocomplete="off" @submit.prevent="sendGoogleDocForm" id="cpm-googledoc-form" >\n        <input type="hidden" name="action" value="{{formAction}}" />\n        <input type="hidden" name="project_id" value="{{current_project}}" />\n        <input type="hidden"   name="_wpnonce" value="{{wp_nonce}}" />\n        <input type="hidden" name="parent" value="{{currntfolder}}" />\n\n        <div class="cpm-col-10">\n            <label>{{text.title}}</label>\n            <input type="text" name="title" value="{{editeddoc.name}}" width="80%" />\n\n            <label>{{text.google_link}}</label>\n\n            <input type="text" name="doclink" value="{{editeddoc.doclink}}"  v-model="doclink"  width="80%" @blur="getDocIcon()" />\n        </div>\n        <div class="cpm-col-2 cpm-right docs-icon">\n\n            <img :src="currentIcon" height="128" v-align="center" />\n        </div>\n        <div class="clear"></div>\n        <div class="item message cpm-sm-col-12 ">\n             <label>{{text.note}}</label>\n            <input id="description" type="hidden" name="description" value="{{{editeddoc.name}}}">\n            <trix-editor input="description"></trix-editor>\n        </div>\n\n        <div class="cpm-privacy"> <label>  <input type="checkbox" name="privacy" value="yes" /> {{text.make_private}} </label> </div>\n\n        <input type="submit" name="submit" class="button-primary"  value="{{text.link_google_doc}}" />\n\n\n    </form>\n</div>';
},{}],7:[function(require,module,exports){
module.exports = '<a class="" v-bind:class="{\'cpm-colorbox-img\' : file.type !=\'file\'}"  title="{{file.name}}" href="{{file.url}}">\n    <img :src="file.thumb" alt="{{file.name}}" />\n</a>';
},{}],8:[function(require,module,exports){
module.exports = '<style>\n    .online-docx-image-content{\n        opacity: 0.1 !important;\n    }\n</style>\n\n<div class="online-docx-image-content" style="  background-image: url(\'{{checkDocIcon(file.doclink)}}\') !important  ;"></div>';
},{}],9:[function(require,module,exports){
module.exports = '<div class="modal-mask half-modal cpm-doc-modal" v-show="show" transition="modal">\n    <div class="modal-wrapper" @click="closeDocRead" >\n        <div class="modal-container"  style="width:{{modalwide}}">\n            <span class="close-vue-modal"><a class=""  @click="closeDocRead"><span class="dashicons dashicons-no"></span></a></span>\n\n\n            <div class="modal-body " @click.stop=""  v-bind:class="{ \'cpm-doc-edit-mod\': docEidted, \'cpm-doc-read-mod\': !docEidted, \'cpm-doc-rev-mod\' : revisionMode }" >\n                <div class="cpm-col-9 cpm-doc-content" v-bind:class="{\'cpm-col-12\' : viewDoc.type == \'attach\' ||  viewDoc.type ==\'regular_doc_file\'  }">\n\n                    <div v-if="!docEidted" v-bind:class="{ \'cpm-created-doc\': viewDoc.type == \'doc\' }">\n\n                        <div class="cpm-modal-conetnt">\n                            <div class="doc_contents"  >\n\n                                <h3>{{viewDoc.full_name}}\n                                    <span class="cpm-right" v-if="revisionMode">\n                                        <a class="show-orginal-doc button-primary"  @click="showOrgDoc">{{text.view_current_post}}</a>\n                                    </span>\n\n                                    <span class="cpm-right" v-if="viewDoc.permission && !revisionMode && viewDoc.type != \'attach\'">\n                                        <a class="button"  @click="docEidted=true"><span class="dashicons dashicons-edit"></span> Edit</a>\n                                    </span>\n                                    <span v-if="viewDoc.type == \'attach\' || viewDoc.type == \'regular_doc_file\' " class="cpm-right">\n                                        <a href="{{viewDoc.file_url}}" target="new" ><span class="dashicons dashicons-download"></span></a>\n                                    </span>\n                                    <div class="sub-title" v-if="viewDoc.type != \'regular_doc_file\'  && viewDoc.type != \'regular_doc_image\' ">\n                                        by <strong>{{viewDoc.created_name}}</strong> at <small>{{viewDoc.created_at}}</small>\n                                    </div>\n\n                                </h3>\n\n                                <div class="document-details">\n                                    <div v-if="viewDoc.type==\'google_doc\'" class="online_doc_link">\n                                        <div class="cpm-box">\n                                            <a href="{{viewDoc.doclink}}" target="_new">\n                                                <img :src="checkDocIcon(viewDoc.doclink)" height="128" v-align="center" /> <br/>\n                                                \n                                            </a>\n                                        </div>\n                                        <a href="{{viewDoc.doclink}}" target="_blank" class="button-primary"><span class="dashicons dashicons-external"></span> {{text.view_on_google}} </a>\n                                        <div class="cpm-title"> {{text.note}} : </div>\n                                    </div>\n\n                                    <div > {{{viewDoc.content}}} </div>\n                                </div>\n\n\n                                <div v-if="viewDoc.type == \'attach\' || viewDoc.type == \'regular_doc_file\' || viewDoc.type == \'regular_doc_image\' " class="cpm-doc-attch online_doc_link">\n                                    <div v-if="viewDoc.content_type == \'image\' " >\n                                        <img :src="viewDoc.file_url" />\n                                        <div v-if="viewDoc.type == \'regular_doc_file\' ||  viewDoc.type == \'regular_doc_image\' ">\n                                            {{{viewDoc.attach_text}}}\n                                        </div>\n                                        <div >\n                                            <a href="{{viewDoc.file_url}}" class="button-primary"> <span class="dashicons dashicons-download"></span>  {{text.download}} </a>\n                                        </div>\n                                    </div>\n\n                                    <div v-else class="file-download">\n                                        <div class="cpm-box">\n                                            <img :src="viewDoc.thumb"  />\n                                        </div>\n                                        <div v-if="viewDoc.type == \'regular_doc_file\' ||  viewDoc.type == \'regular_doc_image\' ">\n                                            {{{viewDoc.attach_text}}}\n                                        </div>\n                                        <div >\n                                            <a href="{{viewDoc.file_url}}" class="button-primary"> <span class="dashicons dashicons-download"></span>  {{text.download}} </a>\n                                        </div>\n                                    </div>\n\n\n\n                                </div>\n\n                            </div>\n\n                            <div class="doc_attach_comments">\n                                <h3 v-if="viewDoc.attachment.length != 0">{{text.attachment}}</h3>\n                                <div>\n                                    <ul>\n                                        <li v-for="attach in viewDoc.attachment" class="cpm-doc-attachment">\n                                            <img :src="attach.thumb" class="" />\n                                        </li>\n                                        <div class="clearfix"></div>\n                                    </ul>\n\n                                </div>\n                                <h3>{{text.comments}}</h3>\n                                <div class="comment-content">\n                                    <ul class="cpm-comment-wrap">\n                                        <li class="cpm-comment" v-for="comment in comments" >\n\n                                            <div class="cpm-avatar ">{{{comment.avatar}}}</div>\n                                            <div class="cpm-comment-container">\n                                                <div class="cpm-comment-meta">\n                                                    <span class="cpm-author">{{comment.comment_author}}</span>\n                                                    {{text.on}}\n                                                    <span class="cpm-date">{{comment.comment_date}}</span>\n\n                                                </div>\n                                                <div class="cpm-comment-content">\n                                                    {{{comment.comment_content}}}\n                                                </div>\n\n                                                <div v-if="comment.files.length">\n                                                    <ul class="cpm-attachments">\n                                                        <li v-for="cfile in comment.files">\n                                                        <prettyphoto :file="cfile" ></prettyphoto>\n\n                                                        </li>\n                                                    </ul>\n\n                                                </div>\n\n                                            </div>\n\n                                        </li>\n                                    </ul>\n\n                                </div>\n\n                                <div class=\'cpm-new-doc-comment-form\'>\n                                    <form @submit.prevent="createDocComment" id="new_comment_form">\n                                        <input type="hidden" name="action" value="cpm_pro_create_comment" />\n                                        <input type="hidden" name="project_id" value="{{current_project}}" />\n                                        <input type="hidden" name="parent_id" value="{{viewDoc.post_id}}" />\n                                        <input type="hidden" name="_wpnonce" value="{{wp_nonce}}" />\n\n                                        <div class="cpm-trix-editor">\n                                            <input id="coment-content" type="hidden" name="description" value="" />\n                                            <trix-editor input="coment-content"></trix-editor>\n                                        </div>\n                                        <fileuploader :files="" :text="text"></fileuploader>\n                                        <input type="submit" name="submit" value="{{text.add_comment}}" class="button-primary" />\n                                    </form>\n                                </div>\n                            </div>\n\n                            <div class="clearfix"></div>\n\n                        </div>\n                    </div>\n\n                    <div v-if="docEidted" >\n\n                        <form @submit.prevent="updateDoc" id="doc-update-form" >\n                            <input type="hidden" name="project_id" value="{{projectid}}" />\n                            <input type="hidden"   name="_wpnonce" value="{{wp_nonce}}" />\n                            <input type="hidden"   name="doc_id" value="{{viewDoc.post_id}}" />\n                            <input type="hidden" name="action" value="cpm_pro_doc_update" />\n\n                            <div class="cpm-modal-conetnt">\n                                <div class="doc_content">\n                                    <div class="top_part">\n                                    <input type="text" name="name" value="{{viewDoc.full_name}}" width="100%" />\n                                    <div v-if="viewDoc.type==\'google_doc\'">\n                                        <label>{{text.google_link}}</label>\n                                        <input type="text" name="doclink" value="{{viewDoc.doclink}}" width="100%" />\n                                        <div class="cpm-title"> {{text.note}} : </div>\n                                    </div>\n                                    </div>\n\n                                    <input id="doc-content" type="hidden" name="description" value="{{{viewDoc.content}}}" />\n                                    <div class="editor">\n                                    <trix-editor input="doc-content"></trix-editor>\n                                    </div>\n                                    <div class="bottom-part">\n                                    <div v-if="viewDoc.type==\'doc\'">\n                                        <fileuploader :files="viewDoc.attachment" :text="text" ></fileuploader>\n                                    </div>\n                                    <div class="cpm-privacy"> <label>  <input type="checkbox" name="private"  value="yes" v-bind:checked="{viewDoc.private == \'yes\'}" /> {{text.make_private}}. </label> </div>\n                                    <input type="submit" name="submit" class="button-primary" value="{{text.update_doc}}" />\n                                    <input type="button" name="cancel" class="button-secondary" value="{{text.cancel_edit}}" @click="docEidted=false" />\n                                    </div>\n                                </div>\n                            </div>\n                        </form>\n                    </div>\n\n                    <div class="clearfix" ></div>\n\n                </div>\n\n                <div class="cpm-col-3 cpm-revision" v-if="viewDoc.type != \'attach\'">\n\n                    <div v-if="viewDoc.type != \'attach\' || viewDoc.type !=\'regular_doc_file\' ">\n                        <h3>{{text.revisions}}</h3>\n                        <ul>\n                            <li v-for="rev in docRevisions"><a href="#"  @click="showDocRev(rev)"># {{rev.created_at}}</a></li>\n                        </ul>\n                        <div v-if="docRevisions.length == 0 ">{{text.no_revision}}</div>\n                    </div>\n                </div>\n\n                <div class="clearfix" ></div>\n            </div>\n\n        </div>\n    </div>\n\n</div>\n';
},{}],10:[function(require,module,exports){
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
},{"./../html/files/blanktemplate.html":1,"./../html/files/createfolder.html":2,"./../html/files/dataloading.html":3,"./../html/files/docform.html":4,"./../html/files/fileuploader.html":5,"./../html/files/googledocform.html":6,"./../html/files/imageview.html":7,"./../html/files/onlinedocs.html":8,"./../html/files/readdocmodal.html":9}]},{},[10]);
