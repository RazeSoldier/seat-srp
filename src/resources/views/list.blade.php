@extends('web::layouts.grids.12')

@section('title', trans('srp::srp.list'))
@section('page_header', trans('srp::srp.list'))

@section('full')
    <div class="box box-primary box-solid">
        <div class="box-header">
            <h3 class="box-title">{{__('srp::srp.srp-request')}}</h3>
        </div>
        <div class="box-body">
            <div class="nav-tabs-custom">
                <ul class="nav nav-tabs">
                    <li class="active"><a href="#tab_1" data-toggle="tab">{{__('srp::srp.pending-request')}}</a></li>
                    <li><a href="#tab_2" data-toggle="tab">{{__('srp::srp.completed-request')}}</a></li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane active" id="tab_1">
                        <table id="srps" class="table table-bordered">
                            <thead>
                            <tr>
                                <th>{{ trans('srp::srp.characterName') }}</th>
                                <th>{{ trans('srp::srp.corpName') }}</th>
                                <th>{{ trans('srp::srp.shipType') }}</th>
                                <th>{{ trans('srp::srp.costs') }}</th>
                                <th>{{ trans('srp::srp.killTime') }}</th>
                                <th>{{ trans('srp::srp.action') }}</th>
                                <th>{{ trans('srp::srp.request-create-time') }}</th>
                                <th>{{ trans('srp::srp.changedby') }}</th>
                                <th>{{ trans('srp::srp.set-status') }}</th>
                                <th>{{ trans('srp::srp.approvedby') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($killmails as $kill)
                                @if(($kill->approved === 0) || ($kill->approved === 1))
                                    <tr>
                                        <td>
                                            <a href="https://zkillboard.com/kill/{{ $kill->kill_id }}/"
                                               target="_blank">{{ $kill->character_name }}</a>
                                            @if(!is_null($kill->ping()))
                                                <button class="btn btn-xs btn-link" data-toggle="modal"
                                                        data-target="#srp-ping" data-kill-id="{{ $kill->kill_id }}">
                                                    <i class="fa fa-comment"></i>
                                                </button>
                                            @endif
                                        </td>
                                        <td><span rel='id-to-name'>{{ $kill->corp_name }}</span></td>
                                        <td>{{ $kill->ship_type }}</td>
                                        <td>
                                            <button type="button" class="btn btn-xs btn-link" data-toggle="modal"
                                                    data-target="#insurances" data-kill-id="{{ $kill->kill_id }}">
                                                {{ number_format($kill->cost, 2) }} ISK
                                            </button>
                                        </td>
                                        <td><span>{{ $kill->kill_time }}</span></td>
                                        @if ($kill->approved === 0)
                                            <td id="id-{{ $kill->kill_id }}"><span
                                                        class="label label-warning">{{__('srp::srp.pending')}}</span>
                                            </td>
                                        @elseif ($kill->approved === -1)
                                            <td id="id-{{ $kill->kill_id }}"><span
                                                        class="label label-danger">{{__('srp::srp.rejected')}}</span>
                                            </td>
                                        @elseif ($kill->approved === 1)
                                            <td id="id-{{ $kill->kill_id }}"><span
                                                        class="label label-success">{{__('srp::srp.approved')}}</span>
                                            </td>
                                        @elseif ($kill->approved === 2)
                                            <td id="id-{{ $kill->kill_id }}"><span
                                                        class="label label-primary">{{__('srp::srp.paid-out')}}</span>
                                            </td>
                                        @endif
                                        <td data-order="{{ strtotime($kill->created_at) }}">
                                            <span data-toggle="tooltip" data-placement="top"
                                                  title="{{ $kill->created_at }}">{{ human_diff($kill->created_at) }}</span>
                                        </td>
                                        <td data-order="{{ strtotime($kill->updated_at) }}">
                                            <span data-toggle="tooltip" data-placement="top"
                                                  title="{{ $kill->updated_at }}">{{ human_diff($kill->updated_at) }}</span>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-xs btn-warning srp-status"
                                                    id="srp-status"
                                                    name="{{ $kill->kill_id }}/{{__('srp::srp.pending', [], 'en')}}">{{__('srp::srp.pending')}}</button>
                                            <button type="button" class="btn btn-xs btn-danger srp-status"
                                                    id="srp-status"
                                                    name="{{ $kill->kill_id }}/{{__('srp::srp.rejected', [], 'en')}}">{{__('srp::srp.rejected')}}</button>
                                            <button type="button" class="btn btn-xs btn-success srp-status"
                                                    id="srp-status"
                                                    name="{{ $kill->kill_id }}/{{__('srp::srp.approved', [], 'en')}}">{{__('srp::srp.approved')}}</button>
                                            <button type="button" class="btn btn-xs btn-primary srp-status"
                                                    id="srp-status"
                                                    name="{{ $kill->kill_id }}/{{__('srp::srp.paid-out', [], 'en')}}">{{__('srp::srp.paid-out')}}</button>
                                        </td>
                                        <td id="approver-{{ $kill->kill_id }}">{{ $kill->approver }}</td>
                                    </tr>
                                @endif
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="tab-pane" id="tab_2">
                        <table id="srps-arch" class="table table-bordered">
                            <thead>
                            <tr>
                                <th>{{ trans('srp::srp.id') }}</th>
                                <th>{{ trans('srp::srp.characterName') }}</th>
                                <th>{{ trans('srp::srp.shipType') }}</th>
                                <th>{{ trans('srp::srp.cost') }}</th>
                                <th>{{ trans('srp::srp.action') }}</th>
                                <th>{{ trans('srp::srp.changedby') }}</th>
                                <th>{{ trans('srp::srp.approvedby') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($killmails as $kill)
                                @if(($kill->approved === -1) || ($kill->approved === 2))
                                    <tr>
                                        <td>
                                            <a href="https://zkillboard.com/kill/{{ $kill->kill_id }}/"
                                               target="_blank">{{ $kill->kill_id }}</a>
                                            @if(!is_null($kill->ping()))
                                                <button class="btn btn-xs btn-link" data-toggle="modal"
                                                        data-target="#srp-ping" data-kill-id="{{ $kill->kill_id }}">
                                                    <i class="fa fa-comment"></i>
                                                </button>
                                            @endif
                                        </td>
                                        <td><span rel='id-to-name'>{{ $kill->character_name }}</span></td>
                                        <td>{{ $kill->ship_type }}</td>
                                        <td>
                                            <button type="button" class="btn btn-xs btn-link" data-toggle="modal"
                                                    data-target="#insurances" data-kill-id="{{ $kill->kill_id }}">
                                                {{ number_format($kill->cost, 2) }} ISK
                                            </button>
                                        </td>
                                        @if ($kill->approved === 0)
                                            <td id="id-{{ $kill->kill_id }}"><span
                                                        class="label label-warning">{{__('srp::srp.pending')}}</span></td>
                                        @elseif ($kill->approved === -1)
                                            <td id="id-{{ $kill->kill_id }}"><span
                                                        class="label label-danger">{{__('srp::srp.rejected')}}</span></td>
                                        @elseif ($kill->approved === 1)
                                            <td id="id-{{ $kill->kill_id }}"><span
                                                        class="label label-success">{{__('srp::srp.approved')}}</span></td>
                                        @elseif ($kill->approved === 2)
                                            <td id="id-{{ $kill->kill_id }}"><span
                                                        class="label label-primary">{{__('srp::srp.paid-out')}}</span></td>
                                        @endif
                                        <td data-order="{{ strtotime($kill->created_at) }}">
                                            <span data-toggle="tooltip" data-placement="top"
                                                  title="{{ $kill->created_at }}">{{ human_diff($kill->created_at) }}</span>
                                        </td>
                                        <td id="approver-{{ $kill->kill_id }}">{{ $kill->approver }}</td>
                                    </tr>
                                @endif
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @include('srp::includes.insurances-modal')
    @include('srp::includes.ping-modal')
@stop

@push('head')
    <link rel="stylesheet" type="text/css" href="{{ asset('web/css/denngarr-srp-hook.css') }}"/>
@endpush

@push('javascript')
    @include('web::includes.javascript.id-to-name')
    <script type="application/javascript">

        $(function () {
            $('#srps').DataTable();
            $('#srps-arch').DataTable();

            $('#srp-ping').on('show.bs.modal', function (e) {
                var link = '{{ route('srp.ping', 0) }}';

                $(this).find('.overlay').show();
                $(this).find('.modal-body>p').text('');

                $.ajax({
                    url: link.replace('/0', '/' + $(e.relatedTarget).attr('data-kill-id')),
                    dataType: 'json',
                    method: 'GET'
                }).done(function (response) {
                    $('#srp-ping').find('.modal-body>p').text(response.note).removeClass('text-danger');
                }).fail(function (jqXHR, status) {
                    $('#srp-ping').find('.modal-body>p').text(status).addClass('text-danger');

                    if (jqXHR.statusCode() !== 500)
                        $('#srp-ping').find('.modal-body>p').text(jqXHR.responseJSON.msg);
                });

                $(this).find('.overlay').hide();
            });

            $('#insurances').on('show.bs.modal', function (e) {
                var link = '{{ route('srp.insurances', 0) }}';
                var table = $('#insurances').find('table');

                if (!$.fn.DataTable.isDataTable(table)) {
                    table.DataTable({
                        "ajax": {
                            url: link.replace('/0', '/' + $(e.relatedTarget).attr('data-kill-id')),
                            dataSrc: ''
                        },
                        "searching": false,
                        "ordering": true,
                        "info": false,
                        "paging": false,
                        "processing": true,
                        "order": [[0, "asc"]],
                        "columnDefs": [
                            {
                                "render": function (data, type, row) {
                                    return row.name;
                                },
                                "targets": 0
                            },
                            {
                                "className": "text-right",
                                "render": function (data, type, row) {
                                    return parseFloat(row.cost).toLocaleString(undefined, {
                                        "minimumFractionDigits": 2,
                                        "maximumFractionDigits": 2
                                    });
                                },
                                "targets": 1
                            },
                            {
                                "className": "text-right",
                                "render": function (data, type, row) {
                                    return parseFloat(row.payout).toLocaleString(undefined, {
                                        "minimumFractionDigits": 2,
                                        "maximumFractionDigits": 2
                                    });
                                },
                                "targets": 2
                            },
                            {
                                "className": "text-right",
                                "render": function (data, type, row) {
                                    return parseFloat(row.refunded).toLocaleString(undefined, {
                                        "minimumFractionDigits": 2,
                                        "maximumFractionDigits": 2
                                    });
                                },
                                "targets": 3
                            },
                            {
                                "className": "text-right",
                                "render": function (data, type, row) {
                                    return parseFloat(row.remaining).toLocaleString(undefined, {
                                        "minimumFractionDigits": 2,
                                        "maximumFractionDigits": 2
                                    });
                                },
                                "targets": 4
                            }
                        ]
                    });
                }
            })
                .on('hidden.bs.modal', function (e) {
                    var table = $('#insurances').find('table').DataTable();
                    table.destroy();
                });

            $('#srps tbody').on('click', 'button', function (btn) {
                $.ajax({
                    headers: function () {
                    },
                    url: "{{ route('srpadmin.list') }}/" + btn.target.name,
                    dataType: 'json',
                    timeout: 5000
                }).done(function (data) {
                    if (data.name === "Approved") {
                        $("#id-" + data.value).html('<span class="label label-success">{{__('srp::srp.approved')}}</span>');
                    } else if (data.name === "Rejected") {
                        $("#id-" + data.value).html('<span class="label label-danger">{{__('srp::srp.pending')}}</span>');
                    } else if (data.name === "Paid Out") {
                        $("#id-" + data.value).html('<span class="label label-primary">{{__('srp::srp.paid-out')}}</span>');
                    } else if (data.name === "Pending") {
                        $("#id-" + data.value).html('<span class="label label-warning">{{__('srp::srp.pending')}}</span>');
                    }
                    $("#approver-" + data.value).html(data.approver);
                });
            });
            ids_to_names();

        });
    </script>
@endpush
