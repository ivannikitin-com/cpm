<?php cpm_get_header( __( 'Files', 'cpm' ), $project_id ); ?>
<div class="cpm-data-load-before" >
    <div class="loadmoreanimation">
        <div class="load-spinner">
            <div class="rect1"></div>
            <div class="rect2"></div>
            <div class="rect3"></div>
            <div class="rect4"></div>
            <div class="rect5"></div>
        </div>
    </div>
</div>


<div class="cpm-pro-file-container" id="myapp"  v-cloak v-if="fullLoad">
    <?php if ( cpm_user_can_access( $project_id, 'upload_file_doc' ) ) { ?>

        <div class="cpm-uplaod-btn-list cpm-right">
            <a href="JavaScript:void(0)"  class="button button-primary" @click="showModal = true">
                <span class="dashicons dashicons-plus-alt"></span> {{text.create_folder}}
            </a>
            <a href="JavaScript:void(0)"  class="button button-primary" @click="fileUploadShow">
                <span class="dashicons dashicons-plus-alt"></span> {{text.upload_file}}
            </a>

            <a href="JavaScript:void(0)" class="button button-primary" @click="createNewDoc">
                <span class="dashicons dashicons-plus-alt"></span>{{text.create_doc}}
            </a>
            <a href="JavaScript:void(0)"  class="button button-primary" @click="createGoogleDoc">
                <span class="dashicons dashicons-plus-alt"></span> {{text.link_google_doc}}
            </a>
        </div>
    <?php } ?>
    <!-- app -->
    <div  class="container">
        <div class="clearfix"></div>
        <div class="cpm-previous-back" >

            <ul >
                <li> <a href="#" @click="showfolder(0)"><span class="dashicons dashicons-admin-home"></span></a></li>
                <li v-for="fl in folderlink"  v-if="currntfolder != 0"> <span class="dashicons dashicons-arrow-right-alt2"></span>
                    <a href="#" @click="showfolder(fl.id)" v-if="currntfolder != fl.id">  {{fl.dir_name}}  </a>
                    <span v-else>{{fl.dir_name}}</span>
                </li>
                <li class="cpm-right"  v-if="currntfolder != 0" >
                    <a href="JavaScript:void(0)"   class=" button" @click='showfolder(backtofolder)'>
                        <span class="dashicons dashicons-arrow-left-alt"></span> {{text.back_previus}}
                    </a>
                </li>
                <div class="clearfix"></div>
            </ul>

        </div>

        <div v-show="uploadFormShow" transition="formshow" class="form-uploader">
            <div class="cpm-form-title">
                {{text.file_upload}}
                <a href="#" class="cpm-right" @click="uploadFormShow = false"><span class=" dashicons  dashicons-no"></span></a>
            </div>
            <form @submit.prevent="uplaodfiles" id="newuploadform" >
                <input type="hidden" name="project_id" value="{{projectid}}" />
                <input type="hidden" name="parent" value="{{currntfolder}}" />
                <input type="hidden"   name="_wpnonce" value="{{wp_nonce}}" />
                <input type="hidden" name="action" value="cpm_pro_file_new" />


                <div id='cpm-upload-container-nd'>
                    <div class='cpm-upload-filelist'>
                        <div class='clearfix'></div>
                    </div>
                    <div class='clearfix'></div>
                </div>
                <a href='#' id='cpm-upload-pickfiles-nd'  class="button" >{{text.attach_file}}</a>

                <div class="cpm-privacy"> <label>  <input type="checkbox" name="privacy" value="yes" /> {{text.make_file_private}} </label> </div>
                <input type="submit" name="submit" class="button-primary" value="{{text.submit}}" />
            </form>
            <div class="clearfix"></div>
        </div>

        <doccreate :text="text" :doc-form-show="docFormShow" :currntfolder="currntfolder"  :current_project="current_project" :wp_nonce="wp_nonce" :form-action="formAction" ></doccreate>
        <googledoccreate :text="text" :google-docs-form="googleDocsForm" :current-icon="currentIcon" :currntfolder="currntfolder"  :current_project="current_project" :wp_nonce="wp_nonce" :form-action="formAction" ></googledoccreate>




        <ul class="cpm-folders-list">

            <li  v-for="folder in folderlist | orderBy 'name' " class="folder"  >
                <div class="ff-content">
                    <div class="image-content" v-bind:class="{editing: folder == editedFolder}">
                        <img :src="folderLink" @click="showfolder( folder.id )"/>

                        <div class="view" @dblclick="updatefolder(folder)">{{folder.name}}</div>
                        <div class="edit" v-if="folder.permission">
                            <input type="text"
                                   v-model="folder.name"
                                   v-edit-focus="folder == editedFolder"
                                   @keyup.enter="doneEdit(folder)" @keyup.esc="cancelEdit(folder)" />
                            <a href="#" class="save button secondary dashicons-before dashicons-yes" @click.prevent="doneEdit(folder)"></a>
                            <a href="#" class="cancel button secondary dashicons-before dashicons-no-alt" @click.prevent="cancelEdit(folder)"></a>
                        </div>

                    </div>

                    <div class="footer-section">
                        <a href="#" v-if="folder.permission" @click.prevent="updatePrivacy(folder)" ><span class="dashicons" v-bind:class="{ 'dashicons-lock': folder.private=='yes', 'dashicons-unlock': folder.private=='no'}"></span></a>
                        <a href="#" v-if="folder.permission" @click.prevent="removeFolder(folder)"><span class="dashicons dashicons-trash"></span></a>
                    </div>
                </div>
            </li>

            <li  v-for="file in filelist" class="file" >

                <div class="ff-content">
                    <div v-if="file.type=='attach'">
                        <div class="image-content">
                            <a  @click.prevent="readDoc(file)" title="{{file.name}}" href="#">
                                <img :src="file.thumb" alt="{{file.name}}" />
                            </a>
                            <div class="item-title">{{file.name}}</div>
                        </div>

                        <div class="footer-section">
                            <a href="{{file.file_url}}"><span class="dashicons dashicons-download"></span></a>
                            <a href="#" v-if="file.permission" @click.prevent="updateFilePrivacy(file)" ><span class="dashicons" v-bind:class="{ 'dashicons-lock': file.private=='yes', 'dashicons-unlock': file.private=='no'}"></span></a>
                            <a href="#" v-if="file.permission" @click.prevent="delfile(file)"><span class="dashicons dashicons-trash"></span></a>
                        </div>
                    </div>
                    <div  v-if="file.type=='doc' || file.type=='google_doc' " class="cpm-doc-view" >
                        <div class="doc-content"  @click.prevent="readDoc(file)"  >
                            <div v-if="file.type=='google_doc'">
                                <onlinedocs :file="file" :current-icon="currentIcon"  ></onlinedocs>
                            </div>
                            <div class="item-title"> {{file.name}}</div>
                            <small>by <strong>{{file.created_name}}</strong></small>
                            <hr/>

                            <div class="file-content"> {{{file.content}}}

                            </div>

                        </div>

                        <div class="footer-section">

                            <a href="#"  @click.prevent="readDoc(file)"><span class="dashicons dashicons-media-document"></span></a>
                            <a href="#" v-if="file.permission" @click.prevent="updateFilePrivacy(file)" ><span class="dashicons" v-bind:class="{ 'dashicons-lock': file.private=='yes', 'dashicons-unlock': file.private=='no'}"></span></a>
                            <a href="#" v-if="file.permission" @click.prevent="delfile(file)"><span class="dashicons dashicons-trash"></span></a>
                        </div>
                    </div>

                    <div  v-if="file.type=='regular_doc_image' || file.type=='regular_doc_file' " class="cpm-rdoc-view" >
                        <div class="image-content">

                            <a @click.prevent="readDoc(file)" title="{{file.name}}" href="#">
                                <img :src="file.thumb" alt="{{file.name}}" />
                            </a>

                            <div class="item-title">{{ file.name }}</div>
                            <span class="text">{{{ file.attach_text }}}</span>
                        </div>

                        <div class="footer-section">
                            <a href="{{file.file_url}}"><span class="dashicons dashicons-download"></span></a>
                            <a href="{{file.topic_url}}"><span class="dashicons dashicons-admin-links"></span></a>
                            <a href="#" class="cpm-comments-count"><span class="cpm-btn cpm-btn-blue cpm-comment-count">{{file.comment_count}}</span></a>
                        </div>

                    </div>

                </div>
            </li>

            <div class="clearfix"></div>
        </ul>

        <div class="cpm-center" v-if="showMoreBtn">
            <a href="#" class="cpm-btn cpm-btn-blue" @click.prevent="loadmorefile()">{{text.load_more_file}}</a>
        </div>

        <!-- use the modal component, pass in the prop -->
        <modal  :text="text" :folder-link="folderLink" :show.sync="showModal" :folder-name=""  :parent='currntfolder' :baseurl="baseurl"  :folderlist="folderlist" :privacy='' :modalwide="modalwide">
            <h3 slot="header"> {{text.create_folder}} </h3>
            <div slot="folder-name"></div>
        </modal>
        <readdocmodal :text="text" :current-icon="currentIcon" :show.sync="readDocModal" :view-doc="viewDoc" :doc-eidted="docEidted" :comments="comments" :current_project="current_project" :wp_nonce="wp_nonce" :private="viewDoc.private" :doc-revisions="docRevisions" :revision-mode="revisionMode"></readdocmodal>
    </div>
    <blanktemplate v-if="emptyList"></blanktemplate>

    <dataloading :data-loading="dataLoading"></dataloading>



</div>
