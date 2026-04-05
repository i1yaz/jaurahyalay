@extends('admin.master')
@section('title')
    <title>Active Tournament</title>
@endsection
@push('css')
    <style>
        .event {
            display: block;
            min-height: 260px;
            border-bottom: 1px solid #eee;
            padding: 5px 0;
            clear: both;
        }

        .event img.poster {
            float: left;
            margin-right: 10px;
            max-width: 400px;
            max-height: 400px;
            width: auto;
            height: auto;
            border: none;
            padding: 0;
        }
    </style>
@endpush
@section('breadcrumb')
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark">Active tournament</h1>
                </div><!-- /.col -->
                @if (Auth::user()->super_admin)
                    <div class="col-sm-6">
                        <a class='float-right btn btn-danger' href="{{ route('admin.refresh') }}">Refresh</a>
                    </div><!-- /.col -->
                @endif
            </div><!-- /.row -->
        </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->
@endsection

@section('content')
    <div class="col-md-12">
        <div class="card card-secondary">
            <div class="card-header">
                <h5 class="card-title"></h5>

                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <!-- /.card-header -->
            <div class="card-body  table-responsive p-0">

                @include('admin.include.messages')
                <!-- /.box-header -->
                <div class="box-body">
                    <table id="example1" class="table table-head-fixed text-nowrap table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>Poster</th>
                                <th>Name</th>
                                <th>Start Date #</th>
                                <th>Days</th>
                                <th>Pigeons</th>
                                <th>Start Time</th>
                                <th>Lofts</th>
                                <th>Options</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($tournaments as $tournament)
                                @php $thisTournament = $tournamentModerator->where('tournament_id',$tournament->id)->first();  @endphp
                                @if ($thisTournament != null || Auth::user()->super_admin || Auth::user()->club_id == $tournament->club_id)
                                    <tr>
                                        <td>
                                            <div class="event">
                                                <img
                                                    @if($tournament->poster)
                                                        data-src="{{asset('uploads/'.$tournament->poster)}}"
                                                    @else
                                                        data-src="{{asset('website/img/200x250.png')}}"
                                                    @endif
                                                    class="img-responsive poster lozad"
                                                    alt="">
                                            </div>
                                        </td>
                                        <td><strong>{{ $tournament->name }}</strong>
                                            <br>
                                            <br>
                                            @if ($tournament->status)
                                                <strong>Status:</strong>
                                                <span class="badge badge-success">Active</span>
                                            @else
                                                <strong>Status:</strong>
                                                <span class="badge badge-danger">Inactive</span>
                                            @endif
                                            <br>
                                            <br>
                                            @if ($tournament->show)
                                                <strong>Show on home screen:</strong>
                                                <span class="badge badge-success">Yes</span>
                                            @else
                                                <strong>Show on home screen:</strong>
                                                <span class="badge badge-danger">No</span>
                                            @endif
                                        </td>
                                        <td>
                                            @php
                                                $date = \Carbon\Carbon::parse($tournament->start_date);
                                                echo $date->settings(['toStringFormat' => ' j F, Y']);
                                            @endphp
                                        </td>
                                        <td>{{ $tournament->days }}</td>
                                        <td>{{ $tournament->pigeons }}</td>
                                        <td>{{ $tournament->start_time }}</td>
                                        <td>{{ $tournament->players->count() }}</td>
                                        <td><a href="{{ route('result.edit', $tournament->id) }}"
                                                class="btn btn-info">Result</a></td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- /.row -->
            </div>
            <div class="col-sm-12 col-md7">
                {{ $tournaments->links() }}
            </div>
            <!-- ./card-body -->
            <div class="card-footer">

            </div>
            <!-- /.card-footer -->
        </div>
        <!-- /.card -->
    </div>
@endsection
@push('js')
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/lozad/dist/lozad.min.js"></script>
    <script>
        const observer = lozad();
        observer.observe();
    </script>
@endpush
