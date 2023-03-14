<template>
    <div v-if="showIframe">
        <a href="#" class="back-button" v-on:click="hideIframe()">
            <i class="fa fa-list"></i> Back to list
        </a>
        <iframe class="relay-iq-iframe" :src="iframeUrl" frameborder="0"></iframe>
    </div>

    <div v-else
         class="template-component ld-over"
         style="transition: all .3s; transition-timing-function: ease-in;"
         v-bind:class="{ running: isLoading }"
    >
        <div style="font-size:41px;color:#999" class="ld ld-ring ld-spin"></div>

        <p class="template-message">Select an item below to subscribe to a metric alert</p>

        <div class="text-center create-template-button-container">
            <button
                    v-if="user_role != 'winnower'"
                    v-on:click="createTemplate"
                    class="create-template-button"
                    id="set-alert-button">
                New
            </button>
        </div>

        <div class="table-wrap">
            <table class="table table-hover templates-list">
                <thead>
                <tr>
                    <th>Worksheet</th>
                    <th>Metric</th>
                    <th></th>
                </tr>
                </thead>

                <tbody>
                <tr v-for="template in templates">
                    <td v-on:click="editSubscription(template.id)">{{ template.alertids.worksheets[0].sheet }}</td>
                    <td v-on:click="editSubscription(template.id)">{{ template.alertids.worksheets[0].targets[0].target }}</td>
                    <td class="actions-column">
                         <span class="circle" title="Recipients">
                            {{ template.schedule.recipientCount }}
                         </span>

                        <span class="edit-button"
                              v-on:click="editTemplate(template.id)"
                              v-if="user_role != 'winnower' && user_id == template.createdby"
                              href="#">
                            <i aria-hidden="true" class="fa fa-edit"></i>
                        </span>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script>
    import {globalsMixin} from "../mixins/globals";
    import {tableau_mixin} from "../mixins/tableau";

    export default {
        mixins: [globalsMixin, tableau_mixin],

        data: () => ({
            dashboard: '',
            templates: [],
            isLoading: false,
            dialogPayload: {},
            showIframe: false,
            iframeUrl: ''
        }),

        props: ['user_id', 'user_role'],

        mounted() {
            this.$store.commit('setDashboardUrl', document.referrer);

            tableau.extensions.initializeAsync().then(() => {
                this.isLoading = true;

                let worksheets = tableau.extensions.dashboardContent.dashboard.worksheets;
                let filterValues = [];
                let worksheetsFiltersProcessed = {};

                function allFiltersAreProcessed() {
                    for (const worksheet in worksheetsFiltersProcessed) {
                        for (var i = 0; i < worksheetsFiltersProcessed[worksheet].length; i++) {
                            if (worksheetsFiltersProcessed[worksheet][i]) return false;
                        }
                    }

                    return true;
                }

                let filterPromise = new Promise( (resolve, reject) => {
                    // worksheets
                    worksheets.forEach( function (worksheet, index, array) {
                        // worksheet filters
                        worksheet.getFiltersAsync().then( function(filters) {
                            // TODO: Still don't like this approach, execution of the script can finish prematurely.
                            // (If neither of worksheets promises aren't resolved before all current worksheet
                            // filters promises).
                            // This should be double checked.
                            worksheetsFiltersProcessed[index] = filters;

                            // filters can be empty
                            if (filters.length == 0) {
                                if (allFiltersAreProcessed()) resolve();
                            }

                            else {
                                // iterate filters
                                filters.forEach(function (filterPromise, f_index, f_array) {
                                    // todo: write comment
                                    if (typeof filterPromise.getDomainAsync === 'function') {
                                        filterPromise.getDomainAsync().then(function (filter) {
                                            filter.sheet = worksheet.name;
                                            filter.field = filterPromise.fieldName;
                                            filterValues.push(filter);

                                            worksheetsFiltersProcessed[index][f_index] = false;
                                            if (allFiltersAreProcessed()) resolve();
                                        });
                                    } else {
                                        worksheetsFiltersProcessed[index][f_index] = false;
                                        if (allFiltersAreProcessed()) resolve();
                                    }
                                });
                            }
                        });
                    });
                });

                // Waiting for full loading of filters from tableau extension
                filterPromise.then(() => {
                    this.dialogPayload.filters = filterValues;

                    this.loadTemplates();
                });
            });
        },

        methods: {
            generateTemplateUrl(template_id) {
                let referrer = this.parseTableauUrl(this.$store.state.dashboard_url);

                let host = referrer.host;
                let site = referrer.site != 'undefined' ? referrer.site : 'default';
                let dashboard = referrer.sheet;
                let workbook = referrer.workbook;

                let url_params = "?host=" + host +
                    "&site=" + site +
                    "&workbook=" + workbook +
                    "&sheet=" + dashboard +
                    "&dashboardid=" + template_id +
                    "&embed=y&source=templates";

                return this.legacy_app_url + '/views/configureAlert' + url_params;
            },

            loadTemplates: function () {
                let referrer = this.parseTableauUrl(this.$store.state.dashboard_url);

                axios.post('api/templates', {
                    user_id: this.user_id,
                    dashboard: referrer.sheet,
                    workbook: referrer.workbook,
                    host: referrer.host,
                }).then((response) => {
                    this.templates = response.data;
                    this.isLoading = false;
                }).catch((e) => {
                    console.error(e);
                    this.isLoading = false;
                })
            },

            createTemplate: function () {
                let template_id = this.generateTemplateId();

                this.iframeUrl = this.generateTemplateUrl(template_id);

                this.saveFilters(template_id, this.dialogPayload);
            },

            editTemplate: function (template_id) {
                this.iframeUrl = this.generateTemplateUrl(template_id);

                this.saveFilters(template_id, this.dialogPayload);
            },

            editSubscription: function (template_id) {
                let url_params = "?embed=true&template_id=" + template_id;

                this.iframeUrl = this.legacy_app_url + '/views/manage' + url_params;

                this.saveFilters(template_id, this.dialogPayload);
            },

            saveFilters: function (template_id, extension_payload) {
                //Check if filters already saved
                axios.get(this.legacy_app_url + '/api/show-extension-filters/' + template_id)
                    .then((res) => {
                        if (res.data == '') {
                            axios
                                .post(this.legacy_app_url + '/api/save-extension-filters', {
                                    template_id: template_id,
                                    filters: extension_payload
                                })
                                .then((res) => {
                                    this.showIframe = true;
                                })
                                .catch(e => {
                                    this.errors.push(e);
                                });
                        } else {
                            this.showIframe = true;
                        }
                    }).catch(e => {
                    this.errors.push(e);
                });
            },

            hideIframe: function () {
                this.showIframe = false;
                this.loadTemplates();
            }
        }
    }
</script>

<style type="text/css">
    .back-button {
        font-size: 13px;
        color: #6c757d;
        position: absolute;
        top: 15px;
        left: 5px;
    }

    .template-component {
        width: 50%;
        margin: auto;
    }

    .relay-iq-iframe {
        width: 105%;
        height: 620px;
        margin-top: -15px;
    }

    .template-message {
        text-align: center;
    }

    .create-template-button-container {
        margin-bottom: 16px;
        margin-top: 12px;
    }

    .create-template-button {
        width: 80px;

        display: inline-block;
        line-height: 24px;
        padding: 0px 24px;
        background-color: rgb(45, 204, 151);
        color: white;
        text-align: center;

        font-family: "bentonsanslight", sans-serif;
        font-size: 14px;
        border: 0;
        outline: 0;

        border-radius: 1px;
        -o-border-radius: 2px;
        -moz-border-radius: 2px;
        -webkit-border-radius: 2px;
        -khtml-border-radius: 2px;
        transition: background-color 150ms ease-in;
        -o-transition: background-color 150ms ease-in;
        -moz-transition: background-color 150ms ease-in;
        -webkit-transition: background-color 150ms ease-in;
        -khtml-transition: background-color 150ms ease-in;
    }

    .create-template-button:hover, .create-template-button:focus {
        outline: none;
        background-color: rgb(0, 177, 128);
    }

    .table-wrap {
        overflow: auto;
        max-height: 412px;
        scrollbar-width: thin;

        border-bottom: 1px solid #e8e8e8;
    }

    .table {
        margin-bottom: 0px;
    }

    .table-wrap::-webkit-scrollbar {
        width: 9px;
    }

    .table-wrap::-webkit-scrollbar-track-piece {
        background: #f0f0f0;
    }

    .table-wrap::-webkit-scrollbar-thumb {
        background: #cdcdcd;
    }

    ​
    .table > thead > tr > th,
    .table > tbody > tr > th,
    .table > tfoot > tr > th,
    .table > thead > tr > td,
    .table > tbody > tr > td,
    .table > tfoot > tr > td {
        padding: 4px;
    }

    .templates-list thead tr {
        font-size: 12px;
    }

    .templates-list tbody tr {
        font-size: 12px;
        cursor: pointer;
    }

    .actions-column {
        text-align: center;
    }

    .circle {
        float: left;
        display: inline-block;
        border-radius: 2px;
        height: 19px;
        width: 19px;
        text-align: center;
        background-color: #A5a5a5;
        font-size: 8px;
        line-height: 19px;
        color: white;
    }

    .edit-button {
        margin-left: 4px;
        color: #666666;
    }

    .edit-button i {
        font-size: 20px;
    }
</style>
