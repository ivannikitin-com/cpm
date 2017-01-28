<div id="cpm-report" v-cloak>

    <h1 class="cpm-no-print report-page-title">
        <?php _e( 'Project Report', 'cpm' ); ?>
        <a href="#" v-if="!dashboadmode" class="page-title-action" @click.prevent="switcreporthmode"><?php _e( 'Back to Report Dashboard', 'cpm' ) ?></a>
        <a href="<?php echo cpm_report_advancesearch_url() ?>" v-if="dashboadmode" class="cpm-right cpm-advance-search" ><?php _e( 'Advanced Report', 'cpm' ) ?></a>
    </h1>
    <div class="cpm-report-input animated" v-if="dashboadmode && !dataloading" v-animation>

        <?php
        $projects   = cpm()->project->get_projects( 1000, array('title', 'ASC') );
        unset( $projects['total_projects'] );
        $co_workers = cpm_get_co_worker_dropdown();
        ?>

        <div class="postbox">

            <ul>
                <li class="" v-on:click="showreport('overdue')">
                    <img src="<?php echo CPM_URL ?>/assets/images/overdue_task.svg" height="64" /> <br/>
                    <h3> <?php _e( 'Overdue Tasks', 'cpm' ) ?> </h3>
                    <p> <?php _e( 'Generate a report based on <strong>tasks</strong> which are <strong>pending</strong> beyond due dates.', 'cpm' ) ?> </p>
                </li>
                <li class="" v-on:click="showreport('completetask')">
                    <img src="<?php echo CPM_URL ?>/assets/images/completed_task.svg" height="64" /><br/>
                    <h3><?php _e( 'Complete Task', 'cpm' ) ?></h3>
                    <p><?php _e( 'Generate a report from <strong>tasks</strong> which were <strong>completed</strong>.', 'cpm' ) ?> </p>
                </li>
                <li class="" v-on:click="showreport('useractivity')">
                    <img src="<?php echo CPM_URL ?>/assets/images/user_activity.svg" height="64" /> <br/>
                    <h3><?php _e( 'User Activities', 'cpm' ) ?></h3>
                    <p><?php _e( 'Create a report based on an employee or <strong>all employee</strong> activity on <strong>tasks</strong>.', 'cpm' ) ?> </p>
                </li>
                <li class=""  v-on:click="showreport('taskbyproject')">
                    <img src="<?php echo CPM_URL ?>/assets/images/taskby_project.svg" height="64" /> <br/>
                    <h3><?php _e( 'Project Task', 'cpm' ) ?></h3>
                    <p><?php _e( 'Find out <strong>all tasks</strong> from your <strong>Project</strong>.', 'cpm' ) ?> </p>

                </li>
                <li class=""  v-on:click="showreport('taskbymilestone')">
                    <img src="<?php echo CPM_URL ?>/assets/images/taskby_milestone.svg" height="64" /> <br/>
                    <h3> <?php _e( 'Task by Milestone', 'cpm' ) ?> </h3>
                    <p> <?php _e( 'Browse   <strong>tasks</strong> reports according to <strong>Milestones</strong> (CSV exportable).', 'cpm' ) ?></p>

                </li>
                <li class="" v-on:click="showreport('unassignedtask')">
                    <img src="<?php echo CPM_URL ?>/assets/images/unassign_task.svg" height="64" /> <br/>
                    <h3> Unassigned Task  </h3>
                    <p><?php _e( 'Find out <strong>all tasks</strong> whichwere not  <strong>assigned</strong> to any employee.', 'cpm' ) ?> </p>

                </li>
                <div class="clearfix"></div>
            </ul>
        </div>
    </div>

    <div class="cpm-report-page animated" v-if="reportmode" v-animation  >
        <div class="cpm-report-body">
            <div class="cpm-report-filter">
                <div class="report-title">
                    <img :src="<?php echo CPM_URL ?>/assets/images/{{reporticon}}" height="20" class="cpm-report-icon" /> {{reporttitle}}
                </div>
                <div class=" cpm-no-print cpm-report-btn-pc">
                    <a class="cpm-right dashicons dashicons-no-alt cpm-close" href="#" @click.prevent="switcreporthmode"></a>
                    <a href="#" class="cpm-right  cpm-close "> <img src="<?php echo CPM_URL ?>/assets/images/print.svg" height="16" onclick="window.print()" /></a>
                </div>
                <div class=" cpm-no-print cpm-report-btn-group" >
                    <a href="#" class=" cpm-right button-primary dashicons-before dashicons-media-spreadsheet" @click.prevent="exporttocsv"><?php _e( 'Export to CSV', 'cpm' ) ?></a>
                    <a href="#" class=" cpm-right button-primary dashicons-before dashicons-search" @click.prevent="showmodal"><?php _e( 'Filter Report', 'cpm' ) ?></a>
                </div>

                <div class="clearfix"></div>
            </div>

            <div  v-if="datalist" id="" >
                <div class="cpl-data-head">
                    <div class="cpm-col-3 cpm-border-right"><b><?php _e( 'Project', 'cpm' ) ?></b> <br/>{{selectedproject}}</div>
                    <div class="cpm-col-3 cpm-border-right"><b><?php echo _e( 'Coworker', 'cpm' ) ?></b><br/>{{selectedcoworder}}</div>
                    <div class="cpm-col-3 cpm-border-right"><b><?php _e( 'Total Result', 'cpm' ) ?></b><br/>{{countresult}}</div>
                    <div class="cpm-col-3">{{{extrahead}}}</div>
                    <div class="clearfix"></div>
                </div>

                <div class="cpm-report-details">
                    <div v-for="project in datalist " class="cpm-report-project">
                        <h2>{{{project.project_name}}}</h2>
                        <hr/>
                        <div v-for="list in project.list">
                            <h4 class="cpm-project-list"> <img src="<?php echo CPM_URL ?>/assets/images/projects.svg" height="16" class="cpm-report-icon" />
                                {{{list.list_name}}}
                            </h4>
                            <table border="0" width="100%" align="center" class="wp-list-table widefat fixed striped posts">
                                <thead>
                                    <tr >
                                        <th width="32%"><?php _e( 'Task', 'cpm' ) ?></th>
                                        <th width="15%"><?php _e( 'Assigned to', 'cpm' ) ?></th>
                                        <th width="12%"><?php _e( 'Assigned Date', 'cpm' ) ?></th>
                                        <th width="12%" v-if="duedate"><?php _e( 'Due Date', 'cpm' ) ?></th>
                                        <th width="12%" v-if="completedate"><?php _e( 'Complete Date' ) ?> </th>
                                        <th width="10%" v-if="showTime" ><?php _e( 'Time' ) ?> </th>

                                        <th width="15%">Status</th
                                    </tr>
                                </thead>
                                <tr v-for="task in list.task">
                                    <td>{{{ task.task_name }}} </td>
                                    <td>{{{task.assignto}}} </td>
                                    <td>{{task.start_date}} </td>
                                    <td v-if="duedate">{{task.due_date}} </td>
                                    <td v-if="completedate">{{task.complete_date}} </td>
                                    <td v-if="showTime"> <span v-if="task.task_time != false">{{task.task_time.hour}}:{{task.task_time.minute}}:{{task.task_time.second}}</span> </td>
                                    <td v-bind:class="{ 'incomplete_task': task.complete_status == 'Incomplete', 'complete_task': task.complete_status == 'Complete'  }"> {{task.complete_status}}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="!datalist && !dataloading" >
            <h2><?php _e( 'No Data Found, Please search again!', 'cpm' ) ?></h2>
        </div>

        <div v-if="dataloading">
            <h2>Loading .... </h2>
        </div>


    </div>



    <!-- use the modal component, pass in the prop -->
    <overduemodal :show.sync="showModal" :modalwide="modalwide" :formaction="formaction" :datalist="datalist" :countresult="countresult" :wpnonce="wpnonce">
        <h3 slot="header"><?php _e( 'Report for', 'cpm' ) ?> {{reporttitle}} </h3>
        <span slot="projectselect"><?php echo cpm_report_project_form( $projects, '-1' ) ?> </span>
        <span slot="coworkerselect"><?php cpm_report_co_worker_dropdown( $co_workers ); ?></span>
        <div slot="task_status"></div>
        <span slot="daterange"></span>
    </overduemodal>
    <!-- Project Select only Modal -->
    <projectonlymodal :show.sync="projectonlyModal" :modalwide="modalwide" :formaction="formaction" :datalist="datalist" :countresult="countresult" :wpnonce="wpnonce">
        <h3 slot="header"><?php _e( 'Report for', 'cpm' ) ?> {{reporttitle}} </h3>
        <span slot="projectselect"><?php echo cpm_report_project_form( $projects, '-1' ) ?></span>
        <span slot="coworkerselect"></span>
        <span slot="daterange"></span>
        <div slot="task_status">
            <label> <input type="radio" value="-1" name="task_status" checked="checked" />  <?php _e( 'All Task', 'cpm' ) ?> </label> <br/>
            <label>  <input type="radio" value="1" name="task_status"  /> <?php _e( 'All Complete Task', 'cpm' ) ?> </label> <br/>

            <label> <input type="radio" value="0" name="task_status"  />  <?php _e( 'All Incomplete Task', 'cpm' ) ?> </label>
        </div>
        <div slot="extra_data"></div>
    </projectonlymodal>

    <!-- User and date range modal modal  -->
    <usermodal :show.sync="usershowModal" :modalwide="modalwide" :formaction="formaction" :datalist="datalist" :countresult="countresult" :daterange="daterange" :wpnonce="wpnonce">
        <h3 slot="header"> <?php _e( 'Report for', 'cpm' ) ?> {{reporttitle}} </h3>
        <span slot="projectselect"></span>
        <span slot="coworkerselect"><?php cpm_report_co_worker_dropdown( $co_workers ); ?></span>
        <div slot="extra_data"></div>

    </usermodal>

    <milestonemodal :show.sync="milestoneModal" :modalwide="modalwide" :formaction="formaction" :datalist="datalist" :countresult="countresult" :wpnonce="wpnonce" :milestonelist="milestonelist">
        <h3 slot="header"><?php _e( 'Report for', 'cpm' ) ?> {{reporttitle}} </h3>
        <div slot="extra_data"></div>

    </milestonemodal>


    <!-- template for the modal component -->
    <script type="x/template" id="modal-template">
        <div class="modal-mask" v-show="show" transition="modal" >
            <div class="modal-wrapper">
                <div class="modal-container" style="width:{{modalwide}}">
                    <div class="modal-header">
                        <span class="cpm-right close-vue-modal"> <a class=""  @click="show = false">X</a> </span>
                        <slot name="header"></slot>
                    </div>
                    <form @submit.prevent="filterreport('report-run-from')" id='report-run-from' method='post'>
                        <input type="hidden"   name="_wpnonce" value="{{wpnonce}}" />
                        <input type="hidden" v-model="action" name="action" value="{{formaction}}" />
                        <div class="modal-body">
                            <slot name="projectselect"></slot>
                            <slot name="coworkerselect"></slot>
                            <slot name="daterange"></slot>
                            <slot name="extra_data"></slot>
                            <slot name="task_status"></slot>
                        </div>
                        <div class="modal-footer">
                        <slot name='submit'><input type="submit" name="submit" class="button button-primary cpm-doc-btn" value="<?php _e( 'Run Report', 'cpm' ) ?>"    /></slot>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </script>

    <!-- template for the modal component -->
    <script type="x/template" id="modal-template-range">
        <div class="modal-mask" v-show="show" transition="modal" >
            <div class="modal-wrapper">
                <div class="modal-container" style="width:{{modalwide}}">
                    <div class="modal-header">
                        <span class="cpm-right close-vue-modal"> <a class=""  @click="show = false">X</a> </span>
                        <slot name="header"></slot>
                    </div>
                    <form @submit.prevent="filterreport('report-run-from2')" id='report-run-from2' method='post'>
                        <input type="hidden"   name="_wpnonce" value="{{wpnonce}}" />
                        <input type="hidden" v-model="action" name="action" value="{{formaction}}" />
                        <div class="modal-body">
                            <slot name="projectselect"></slot>
                            <slot name="coworkerselect"></slot>
                            <slot name="daterange">

                                <input type="text" v-datepicker="start_date" name="start_date" value="{{daterange.start}}" />
                                To
                                <input type="text" v-datepicker="end_date"  name="end_date" value="{{daterange.end}}" />
                            </slot>
                            <slot name="extra_data"></slot>
                        </div>
                        <div class="modal-footer">
                            <slot name='submit'>
                                <input type="submit" name="submit" class="button button-primary cpm-doc-btn" value="<?php _e( 'Run Report', 'cpm' ) ?>"    />
                            </slot>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </script>

    <!-- template for the modal component, Select task status with project  -->
    <script type="x/template" id="modal-template-status">
        <div class="modal-mask" v-show="show" transition="modal" >
            <div class="modal-wrapper">
                <div class="modal-container" style="width:{{modalwide}}">
                    <div class="modal-header">
                        <span class="cpm-right close-vue-modal"> <a class=""  @click="show = false">X</a> </span>
                        <slot name="header"></slot>

                    </div>
                    <form @submit.prevent="filterreport('report-dr-from')" id='report-dr-from' method='post'>
                        <input type="hidden"   name="_wpnonce" value="{{wpnonce}}" />
                        <input type="hidden" v-model="action" name="action" value="{{formaction}}" />
                        <div class="modal-body">
                            <slot name="projectselect"></slot>
                            <slot name="coworkerselect"></slot>
                            <slot name="daterange"></slot>
                            <slot name="extra_data"></slot>
                            <slot name="task_status"></slot>
                        </div>
                        <div class="modal-footer">
                            <slot name='submit'>
                                <input type="submit" name="submit" class="button button-primary cpm-doc-btn" value="<?php _e( 'Run Report', 'cpm' ) ?>"    />
                            </slot>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </script>

    <!-- template for the modal component, Select task status with project  -->
    <script type="x/template" id="projectmilestoneModal">
        <div class="modal-mask" v-show="show" transition="modal" >
            <div class="modal-wrapper">
                <div class="modal-container" style="width:{{modalwide}}">
                    <div class="modal-header">
                        <span class="cpm-right close-vue-modal"> <a class=""  @click="show = false">X</a> </span>
                        <slot name="header"></slot>

                        <form @submit.prevent="filterreport('report-ml-from')" id='report-ml-from' method='post'>
                            <input type="hidden"   name="_wpnonce" value="{{wpnonce}}" />
                            <input type="hidden" v-model="action" name="action" value="{{formaction}}" />
                            <div class="modal-body">

                                <?php // echo cpm_report_project_form( $projects, '-1' , "required  v-on='change:filtermilestone'" ) ?>
                                <select class="cpm-field" name="project"  required  v-select="selected" >
                                    <option <?php selected( '' ); ?> value="-1"><?php _e( 'Selec a project', 'cpm' ); ?></option>
                                    <?php
                                    foreach ( $projects as $project ) {
                                        ?>
                                        <option <?php selected( $project->ID ); ?> value="<?php echo $project->ID; ?>"><?php echo $project->post_title; ?></option>
                                        <?php
                                    }
                                    ?>
                                </select>


                                <slot name="milestone">
                                    <select name="milsestone" required>
                                        <option v-for=" milestone in milestonelist" v-bind:value="milestone.val">
                                            {{ milestone.text }}
                                        </option>
                                    </select>
                                </slot>

                            </div>
                            <div class="modal-footer">
                                <slot name='submit'>
                                    <input type="submit" name="submit" class="button button-primary cpm-doc-btn" value="<?php _e( 'Run Report', 'cpm' ) ?>"    />
                                </slot>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
    </script>


</div>
