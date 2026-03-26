@extends('admin.master')
@section('title')
    <title>Edit tournament</title>
@endsection
@push('css')
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/select2/css/select2.min.css') }}">
@endpush
@section('breadcrumb')
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark">Edit tournament</h1>
                </div><!-- /.col -->
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
      <div class="card-body">

        <form role="form" action="{{ route('tournament.update',$tournament->id) }}" method="post"  enctype="multipart/form-data">
            {{ csrf_field() }}
            {{ method_field('PUT') }}
            <div class="box-body">
                  <div class="offset-lg-3 col-lg-6">
                      @include('admin.include.messages')

                      <div class="form-group">
                        <label>Poster</label>
                        <input class="form-control" type="file" name="poster">
                      </div>

                      <div class="form-group">
                        <label>Club Name</label>
                        <select class="form-control select2 select2-hidden-accessible" name="club" data-placeholder="Select club" style="width: 100%;">
                          @foreach ($clubs as $club)
                            <option @if($tournament->club_id === $club->id) selected="selected" @endif value="{{$club->id}}" >{{$club->name}}</option>
                          @endforeach
                        </select>
                      </div>

                      <div class="form-group">
                          <label for="name">Tournament Name</label>
                          <input type="text" class="form-control" id="name" name="name" placeholder="tournament Name" value="@if (old('name')){{ old('name') }}@else{{ $tournament->name }}@endif" required>
                      </div>

                      <div class="form-group">
                        <label for="pigeons">No of Pigeons</label>
                        <input type="number" class="form-control" id="pigeons" name="pigeons" placeholder="pigeons" value="@if (old('pigeons')){{ old('pigeons') }}@else{{ $tournament->pigeons }}@endif" required>
                    </div>

                    <div class="form-group">
                      <label for="supporter">Helper pigeons</label>
                      <input type="number" class="form-control" id="supporter" name="supporter" placeholder="supporter" value="@if (old('supporter')){{ old('supporter') }}@else{{ $tournament->supporter }}@endif" required>
                    </div>
                    <div class="form-group">
                      <label>Type</label>
                      <select class="form-control select2 select2-hidden-accessible" name="type"   style="width: 100%;">
                          <option value="OPEN" @if($tournament->type === 'OPEN') selected="selected" @endif>Open</option>
                          <option value="FIXED" @if($tournament->type === 'FIXED') selected="selected" @endif>Fixed</option>
                      </select>
                    </div>
                    <div class="form-group">
                      <label>Players</label>
                      <select class="form-control select2 select2-hidden-accessible" name="players[]" multiple="multiple" data-placeholder="Select players" style="width: 100%;">
                        @foreach ($players as $player)
                          <option @if($tournament->players->contains($player)) selected="selected" @endif value="{{$player->id}}" >{{$player->name}}</option>
                        @endforeach
                      </select>
                    </div>

                    <div class="form-check">
                      <input type="checkbox"name="status" class="form-check-input" id="status" @if($tournament->status==1) checked @endif>
                      <label class="form-check-label"  for="status">Show in Results </label>
                  </div>
                  <div class="form-check">
                      <input type="checkbox"name="show" class="form-check-input" id="show" @if($tournament->show==1) checked @endif>
                      <label class="form-check-label"  for="show">Show on Front Page</label>
                  </div>
  
                  <div class="form-check">
                    <input type="checkbox"name="public_hide" class="form-check-input" id="public_hide" @if($tournament->public_hide==1) checked @endif>
                    <label class="form-check-label"  for="public_hide">Hide From Public</label>
                  </div>

                  <div class="form-check">
                    <input type="checkbox"name="allow_double_stamp" class="form-check-input" id="allow_double_stamp" @if($tournament->allow_double_stamp==1) checked @endif>
                    <label class="form-check-label"  for="allow_double_stamp">Allow Double Stamp?</label>
                  </div>

                      <div class="form-group days">
                        <label for="days">Days</label>
                        <input type="text" class="form-control " id="days" name="days" placeholder="days" value="@if (old('days')){{ old('days') }}@else{{ $tournament->days }}@endif" required>
                    </div>

                    <div class="form-group date">
                        <label for="date">Start Date</label>
                        <input type="date" class="form-control" id="date" name="date[]" value="{{ $tournament->start_date }}" required>
                    </div>

                    @foreach ($days as $day)
                    @php
                    if ($loop->index == 0) continue;
                    @endphp
                    <div class="form-group days-{{$loop->index+1}} flying-days">
                        <label for="date-{{$loop->index+1}}">Day {{$loop->index+1}}</label>
                        <input type="date" class="form-control" id="date-{{$loop->index+1}}" name="date[]" value="{{ $day->date }}" required>
                    </div>
                    @endforeach

                    <div class="form-group">
                        <label for="time">Start Time</label>
                        <input type="time" class="form-control" id="time" name="time" value="{{ $tournament->start_time }}" required>
                    </div>

                    <div class="form-group total-prize">
                      <label for="prize">Total Prizes</label>
                      <input type="text" class="form-control" id="prize" value="{{$prizes->count()}}" required>
                    </div>

                    @foreach ($prizes as $prize)
                    <div class="form-group prize-{{$loop->index+1}} prize">
                      <label for="prize-{{$loop->index+1}}"> Prize {{$loop->index+1}}</label>
                      <input type="text" class="form-control" id="prize-{{$loop->index+1}}" name="prize[]" value="{{$prize->name}}">
                    </div>
                    @endforeach
                      <div class="form-group">
                          <label>Tournament admin</label>
                          <select class="form-control select2 select2-hidden-accessible" name="tournament_admins[]" multiple="multiple"  style="width: 100%;">
                              @foreach ($admins as $admin)
                                  <option  @if(in_array($admin->id,$tournamentAdmins)) selected="selected" @endif value="{{$admin->id}}" >{{$admin->name}}</option>
                              @endforeach
                          </select>
                      </div>
                    <div class="form-group">
                      <div class="form-group">
                        <a href='{{ route('tournament.index') }}' class="btn btn-warning">Back</a>
                        <button type="submit" class="btn btn-primary float-right">Update</button>
                      </div>
                  </div>
              </div>
            </div>
        </form>

        <!-- /.row -->
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
    <script>
      $('#date').click(function (e) {
        if(!$('#days').val() || $('#days').val()<1) {
          e.preventDefault();
          $("#days").focus();
          $('.days').addClass('has-error');
        }else{
          $('.days').removeClass('has-error')
        }
      });
      $('#days').on('change keyup',function (e) {
        $('.flying-days').remove();
        let days = $('#days').val();
        for (let index = 1; index < days; index++) {
          $('.date').after(
              '<div class="form-group days-'+(days-index+1)+' flying-days">\n' +
                  '<label for="date-'+(days-index+1)+'">Day '+(days-index+1)+'</label>\n' +
                  '<input type="date" class="form-control" id="date-'+(days-index+1)+'" name="date[]" value="{{ date("Y-m-d")  }}" required>\n' +
              '</div>'
          );
        }
      });
    </script>
    <script>
      $('#prize').on('change keyup',function (e) {
        $('.prize').remove();
        let total = $('#prize').val();
        for (let index = 0; index < total; index++) {
          $('.total-prize').after(
              '<div class="form-group prize-'+(total-index)+' prize">\n' +
                  '<label for="prize-'+(total-index+1)+'"> Prize '+(total-index)+'</label>\n' +
                  '<input type="text" class="form-control" id="prize-'+(total-index)+'" name="prize['+(total-index)+']" value="">\n' +
              '</div>'
          );
        }
      });
    </script>
    <!-- select2 -->
    <script src="{{ asset('adminlte/plugins/select2/js/select2.min.js') }}"></script>
    <script>
      $(document).ready(function () {
        $('.select2').select2({
      });
      });
    </script>
@endpush
