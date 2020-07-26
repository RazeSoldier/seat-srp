@extends('web::layouts.grids.6-6')

@section('title', '导出数据')
@section('page_header', '导出数据')

@push('head')
    <!-- Vue -->
    <script src="https://cdn.jsdelivr.net/npm/vue/dist/vue.js"></script>
    <!-- element-ui -->
    <link rel="stylesheet" href="https://unpkg.com/element-ui/lib/theme-chalk/index.css">
    <script src="https://unpkg.com/element-ui/lib/index.js"></script>
@endpush

@section('left')
    <div class="box-body" id="vue">
        <export-form mark-paid-done="{{session('markPaidDone', false)}}"></export-form>
    </div>
@endsection

@section('right')
    <div id="history">
        <action-history></action-history>
    </div>
@endsection

@php
$formTemplate = <<<TEXT
<el-form ref="form" label-width="120px">
    <el-alert
        title="成功标记为“已补损”"
        type="success" v-if="markPaidDone">
    </el-alert>
    <el-form-item label="补损请求区间" required>
        <el-date-picker type="daterange" start-placeholder="开始日期" end-placeholder="结束日期" v-model="form.dateRange">
        </el-date-picker>
    </el-form-item>
    <el-form-item>
        <el-button type="primary" @click="submitForm">导出为Execl</el-button>
        <el-button type="success" @click="dialogVisible = true">标记为已补损</el-button>
    </el-form-item>
    <el-dialog
        title="确认"
        :visible.sync="dialogVisible"
        center>
        <span>确认将区间内的所有“已审核”的请求标记为“已补损”？</span>
        <span slot="footer" class="dialog-footer">
            <el-button @click="dialogVisible = false">取消</el-button>
            <el-button type="primary" @click="markAsPaid">确定</el-button>
        </span>
    </el-dialog>
</el-form>
TEXT;
$historyTemplate = <<<TEXT
<el-card class="box-card">
    <div slot="header" class="clearfix">
        <span>操作历史</span>
    </div>
    <el-table
        :data="actionHistory">
        <el-table-column
            prop="time"
            label="时间">
        </el-table-column>
         <el-table-column
            prop="operator"
            label="操作者">
        </el-table-column>
        <el-table-column
            prop="detail"
            label="行为">
        </el-table-column>
    </el-table>
</el-card>
TEXT;
@endphp

@push('javascript')
    <script>
        Vue.component('export-form', {
            props: ['markPaidDone'],
            template: `{!! $formTemplate !!}`,
            data() {
                return {
                    form: {
                        dateRange: [],
                    },
                    dialogVisible: false,
                }
            },
            methods: {
                submitForm() {
                    if (!this.checkDate()) {
                        return;
                    }
                    let dateRage = this.handleDateRange(this.form.dateRange);
                    let url = '{{route('srp.export-execl')}}' + '?startDate=' + dateRage[0] + '&endDate=' + dateRage[1];
                    fetch(url).then(res => res.json()).then(json => {
                        if (json.status === 'error') {
                            this.$notify({
                                title: '存在不符合联盟规定的船型',
                                message: json.error,
                                type: 'warning',
                                duration: 0
                            });
                            return;
                        }
                        if (json.status === 'ok') {
                            window.open('{{route('srp.export-execl-download')}}' + '/' + json.url, '_blank');
                        }
                    });
                },
                markAsPaid() {
                    if (!this.checkDate()) {
                        return;
                    }
                    let dateRange = this.handleDateRange(this.form.dateRange);
                    let url = '{{route('srp.mark-paid')}}' + '?startDate=' + dateRange[0] + '&endDate=' + dateRange[1];
                    window.open(url, '_self');
                },
                checkDate() {
                    if (this.form.dateRange.length === 0) {
                        this.$message({
                            message: '请选择日期',
                            type: 'warning'
                        });
                        return false;
                    }
                    return true;
                },
                handleDateRange(dateRange) {
                    return [
                        dateRange[0].getFullYear() + '-' + (dateRange[0].getMonth() + 1) + '-' + dateRange[0].getDate(),
                        dateRange[1].getFullYear() + '-' + (dateRange[1].getMonth() + 1) + '-' + dateRange[1].getDate(),
                    ]
                },
            }
        });
        Vue.component('action-history', {
            template: `{!! $historyTemplate !!}`,
            data() {
                return {
                    actionHistory: []
                }
            },
            mounted() {
                fetch('{{route('srp.action-history')}}').then(res => res.json()).then(json => {
                    this.actionHistory = json.data;
                })
            },
        })
        new Vue({el: '#vue'});
        new Vue({el: '#history'});
    </script>
@endpush