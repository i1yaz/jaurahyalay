@extends('admin.master')
@section('title')
    <title>Update Result</title>
@endsection
@push('css')
    <!-- x-editable bootstrap4 css -->
    <link href="{{ asset('plugins/bootstrap4-editable/css/bootstrap-editable.css') }}" rel="stylesheet" />
@endpush
<style>
    .profileimg {
        width: 60px;
        height: 60px;
        padding-right: 3px;
        float: left;
    }
</style>
@section('breadcrumb')
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-1">
                <div class="col-md-12">

                    <div class="alert alert-success alert-dismissible">
                        <h3> {{ $tournament->name }}</h3>
                        <i class="icon fas fa-clock"></i><small>Start : {{ $tournament->start_time }}</small>
                    </div>
                </div><!-- /.col -->
                <div class="col-md-6">
                    <ul style="list-style-type: none;padding: 0;margin: 0;">
                        <a target="_blank" href="https://wa.me/?text={{ url("result/default/{$tournament->id}") }}"
                            class="btn btn-success"><span class="fab fa-whatsapp"></span></a>
                    </ul>
                </div>
                <div class="col-sm-6">
                    <form action="{{ route('admin.result.update') }}" method="POST" class="float-right">
                        @csrf
                        @method('PATCH')

                        <!-- Add your values here -->
                        <input type="hidden" name="name" value="start">
                        <input type="hidden" name="value" value="{{ $updateDate }}">
                        <input type="hidden" name="pk" value="{{ $tournament->id }}_{{ $updateDate }}">
                        <input type="hidden" name="tournament_id" value="{{ $tournament->id }}">
                        <input type="hidden" name="club_id" value="{{ $tournament->club_id }}">
                        @php
                            $date = \Carbon\Carbon::parse($updateDate);
                            $dateFormat = $date->settings(['toStringFormat' => ' j F, Y']);
                        @endphp

                        <button type="submit" class="btn btn-danger">Update Result of Date {{ $dateFormat }}</button>


                    </form>
                </div>
            </div><!-- /.row -->
        </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->
@endsection

@section('content')
    <div class="col-md-12">
        <div class="card card-secondary card-tabs">
            <div class="card-header p-0 pt-1">
                <ul class="nav nav-tabs" id="ul-tournament-date" role="tablist">
                    @foreach ($tournament->flyingDays as $flyingDay)
                        <li class="nav-item">
                            <a class="nav-link li-tournament-date
              @if (isset($updateDate) && $updateDate == $flyingDay->date) active @endif>"
                                id="li-tournament-{{ $flyingDay->date }}" data-toggle="pill"
                                href="{{ route('result.edit.date', ['result' => $tournament->id, 'date' => $flyingDay->date]) }}"
                                role="tab" aria-controls="custom-tabs-one-home" aria-selected="true">
                                @php
                                    $date = \Carbon\Carbon::parse($flyingDay->date);
                                    echo $date->settings(['toStringFormat' => ' j F, Y']);
                                @endphp
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
            <div class="card-body table-responsive p-0" style="overflow: inherit !important;">

                @include('admin.include.messages')
                <!-- /.box-header -->
                <div class="box-body">
                    <table class="table table-head-fixed text-nowrap table-striped table-bordered table-hover"
                        id="results">
                        <thead>
                            <tr>
                                <th>Sr</th>
                                <th>Pic</th>
                                <th>Name</th>
                                <th>Flying Time</th>
                                @for ($i = 0; $i < $tournament->pigeons; $i++)
                                    <th>Pigeon {{ $i + 1 }}</th>
                                @endfor
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($tournamentResult as $data)
                                @php
                                    $player = $tournament->players->where('id', $data->first()->player_id)->first();
                                    if (!isset($player)) {
                                        $player = \App\Models\Admin\Player::find($data->first()->player_id);
                                    }
                                    $playerResult = $tournamentResult->where('player_id', $player->id);
                                    foreach ($playerResult as $key => $value) {
                                        $tournamentResult->forget($key);
                                    }
                                @endphp
                                <tr>
                                    <td>{{ $loop->index + 1 }}</td>
                                    <td>
                                        <img @if ($player->poster) data-src="{{ asset('website/profiles/' . $player->poster) }}"
                                            @else
                                                @if (config('settings.profile_pic_type') === 'circle')
                                                    data-src="{{ asset('website/profiles/profile.png') }}"
                                                @else
                                                    data-src="{{ asset('website/profiles/profile-square.png') }}" @endif
                                            @endif
                                        alt="{{ $player->name }}"
                                        class="profileimg @if (config('settings.profile_pic_type') === 'circle') rounded-circle @endif lozad">
                                    </td>
                                    <td><b>{{ $player->name }}</b><br><small>{{ $player->city }}<small></td>
                                    <td>
                                        <span class="start editable-click" data-emptytext="Empty"
                                            data-pk="{{ $tournament->id }}_{{ $updateDate }}_{{ $player->id }}_{{ $tournament->club_id }}">
                                            @php
                                                if (isset($data->first()->start_time)) {
                                                    echo $data->first()->start_time;
                                                }
                                            @endphp
                                        </span>
                                    </td>

                                    @for ($i = 0; $i < $tournament->pigeons; $i++)
                                        @php
                                            $player_result = $data
                                                ->where('player_id', $player->id)
                                                ->where('date', $updateDate)
                                                ->where('pigeon_number', $i + 1)
                                                ->first();
                                        @endphp
                                        <td @if($tournament->allow_double_stamp && isset($player_result->is_double_stamp) && $player_result->is_double_stamp) style="background-color: #ffc107;" @endif>
                                            @if($tournament->allow_double_stamp)
                                            <button class="btn btn-xs double-stamp @if(isset($player_result->is_double_stamp) && $player_result->is_double_stamp) btn-warning @else btn-default @endif" data-pk="{{ $tournament->id }}_{{ $updateDate }}_{{ $player->id }}_{{ $i + 1 }}_{{ $tournament->club_id }}"><i class="fa fa-stamp"></i></button>
                                            @endif
                                            <span class="pigeon editable-click"
                                                data-pk="{{ $tournament->id }}_{{ $updateDate }}_{{ $player->id }}_{{ $i + 1 }}_{{ $tournament->club_id }}"
                                                data-emptytext="Empty" data-title="Enter time HH:ii:ss">
                                                @php
                                                    if (isset($player_result->pigeon_time)) {
                                                        echo $player_result->pigeon_time;
                                                    }
                                                @endphp
                                            </span>
                                        </td>
                                    @endfor
                                </tr>
                            @endforeach
                        </tbody>

                    </table>
                </div>

            </div>
            <div class="card-footer">

            </div>
            <!-- /.card -->
        </div>
        <!-- /.card -->
    </div>
@endsection
@push('js')
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/lozad/dist/lozad.min.js"></script>
    <script>
        const observer = lozad();
        observer.observe();
        $('.li-tournament-date').click(function(event) {
            window.location.href = event.target.href;
        });
    </script>
    <!-- x-editable bootstrap4 css -->
    <script src="{{ asset('plugins/bootstrap4-editable/js/bootstrap-editable.min.js') }}"></script>
    {{-- <script src="{{ asset('adminlte/plugins/popper/popper.min.js') }}"></script> --}}
    <script>
        $.ajaxSetup({
            headers: {
                'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content')
            }
        });
        $(document).ready(function(e) {
            /*
             * Update Pigeon Time
             */
            $('.pigeon').editable({
                type: 'POST',
                pk: $(this).data('pk'),
                url: '{!! route('result.time') !!}',
                name: 'pigeon',
                type: 'text',
                validate: function(value) {
                    const regex = RegExp(/^([01]\d|2[0-3])?:?([0-5]\d)?:?([0-5]\d)$/);
                    if (!regex.test(value)) return 'Please insert date in correct format';
                },
                ajaxOptions: {
                    dataType: 'json'
                },
                display: function(value, response) {
                    if (response != null) {
                        let e = $(this.parentElement.parentElement);
                        e.css('background-color', '#00ccff');
                        setTimeout(function() {
                            e.css('background-color', 'white');
                        }, 5000);
                    }
                    $(this).html(response);
                }
            });
            /*
             * Update Start Time
             */
            $('.start').editable({
                type: 'POST',
                pk: $(this).data('pk'),
                url: '{!! route('result.time') !!}',
                name: 'start',
                type: 'text',
                validate: function(value) {
                    const regex = RegExp(/^([01]\d|2[0-3])?:?([0-5]\d)?:?([0-5]\d)$/);
                    if (!regex.test(value)) return 'Please insert date in correct format';
                },
                ajaxOptions: {
                    dataType: 'json'
                },
                display: function(value, response) {
                    $(this).html(response);
                }
            });

            $('.double-stamp').click(function () {
                var btn = $(this);
                var pk = btn.data('pk');
                $.ajax({
                    url: '{{ route('result.double_stamp') }}',
                    type: 'POST',
                    data: {pk: pk},
                    success: function (response) {
                        if (response == 1 || response == true) {
                            btn.removeClass('btn-default').addClass('btn-warning');
                            btn.closest('td').css('background-color', '#ffc107');
                        } else {
                            btn.removeClass('btn-warning').addClass('btn-default');
                            btn.closest('td').css('background-color', 'transparent');
                        }
                    }
                });
            });
        });
    </script>
@endpush
