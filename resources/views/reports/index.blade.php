@extends('adminlte::page')

@section('title', 'Reports')

@section('content_header')
    <h1>Reports</h1>
@stop

@section('content')
    @include('partials.flash')
    <ul>
        @can('report.attendance')
            <li><a href="{{ route('reports.attendance') }}">Attendance Report</a></li>
        @endcan
        @can('report.attendance_export')
            <li><a href="{{ route('reports.attendance.export') }}">Attendance Bulk Export</a></li>
        @endcan
        @can('report.attendance_tsv')
            <li><a href="{{ route('reports.attendanceTsv') }}">Attendance TSV</a></li>
        @endcan
        @can('report.clients_by_house')
            <li><a href="{{ route('reports.clientsByHouse') }}">Client List by House</a></li>
        @endcan
        @can('report.clients_by_counselor')
            <li><a href="{{ route('reports.clientsByCounselor') }}">Client List by Counselor</a></li>
        @endcan
        @can('report.clients_by_peer')
            <li><a href="{{ route('reports.clientsByPeer') }}">Client List by Peer</a></li>
        @endcan
        @can('report.clients_by_group')
            <li><a href="{{ route('reports.clientsByGroup') }}">Client List by Group</a></li>
        @endcan
        @can('report.clients_by_peer_group')
            <li><a href="{{ route('reports.clientsByPeerGroup') }}">Client List by Peer Group</a></li>
        @endcan
        @can('report.house')
            <li><a href="{{ route('reports.house') }}">House Report</a></li>
        @endcan
        @can('report.client_list')
            <li><a href="{{ route('reports.clientList') }}">Client List</a></li>
        @endcan
        @can('report.total_client_list')
            <li><a href="{{ route('reports.totalClientList') }}">Total Client List</a></li>
        @endcan
        @can('report.intakes')
            <li><a href="{{ route('reports.intakes') }}">Intake Report</a></li>
        @endcan
        @can('report.reactivations')
            <li><a href="{{ route('reports.reactivations') }}">Reactivation Report</a></li>
        @endcan
        @can('report.discharges')
            <li><a href="{{ route('reports.discharges') }}">Discharge Report</a></li>
        @endcan
        @can('report.hospitalizations')
            <li><a href="{{ route('reports.hospitalizations') }}">Hospitalization Report</a></li>
        @endcan
        @can('report.transitions')
            <li><a href="{{ route('reports.transitions') }}">Transitions Report</a></li>
        @endcan
        @can('report.medicaid_by_date')
            <li><a href="{{ route('reports.medicaidByDate') }}">Medicaid List by Date</a></li>
        @endcan
        @can('report.group_attendance_summary')
            <li><a href="{{ route('reports.groupAttendanceSummary') }}">Group Attendance Summary</a></li>
        @endcan
        @can('report.peer_group_attendance_summary')
            <li><a href="{{ route('reports.peerGroupAttendanceSummary') }}">Peer Group Attendance Summary</a></li>
        @endcan
    </ul>
@stop
